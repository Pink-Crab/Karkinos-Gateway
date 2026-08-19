<?php
/**
 * Drains the dispatch queue to whichever downstream a job is addressed to.
 *
 * Each job carries a `kind` selecting its protocol:
 *
 *   karkinos — capacity probe, HMAC-signed envelope, pinned self-signed cert
 *   act      — Actions tool on tools.pinkcrab.co.uk, basic auth, ordinary TLS
 *   blog     — rebuild the blog post's stubs section (Blog_Sync), basic auth
 *
 * Only kinds whose target is currently configured are offered to the queue, so
 * an unconfigured (or busy) target leaves its own jobs queued instead of
 * blocking everything behind them.
 *
 * For karkinos jobs the loop is exactly: ask Karkinos "are you busy?" → if
 * free, take the next undispatched job, POST it, stamp dispatched_at. Karkinos
 * holds its own lock
 * while processing, so its "busy" answer is what serialises delivery — the
 * gateway keeps no local lock. A job is only ever marked dispatched on a
 * terminal outcome (2xx success or 4xx permanent reject); transient outcomes
 * (busy/5xx/transport error) leave it untouched to be retried next tick.
 *
 * Driven on demand by POST /dispatch/tick (an external cron on the home
 * server) and once inline when a webhook enqueues. The app never self-
 * schedules.
 *
 * Outbound TLS is pinned to the home server's self-signed cert by identity,
 * not hostname (the IP rotates) — see Karkinos_TLS_Pinning.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

class Dispatch_Worker {

	/** Max jobs to attempt in one run, so a tick can't run unbounded. */
	private const MAX_PER_RUN = 20;

	/** Timeout (seconds) for the capacity probe. */
	private const PROBE_TIMEOUT = 5;

	/** Timeout (seconds) for a dispatch POST. Kept well under PHP max_execution_time. */
	private const POST_TIMEOUT = 10;

	/**
	 * Constructor.
	 *
	 * @param Dispatch_Queue $queue       Two-state queue (dispatched_at NULL/set).
	 * @param Forward_Target $target      Resolves the Karkinos dispatch + capacity URLs.
	 * @param Act_Target     $act_target  Resolves the Actions-tool URL + basic auth.
	 * @param Blog_Target    $blog_target Resolves the blog post endpoint + basic auth.
	 * @param Blog_Sync      $blog_sync   Rebuilds the blog stubs section for blog jobs.
	 */
	public function __construct(
		private Dispatch_Queue $queue,
		private Forward_Target $target,
		private Act_Target $act_target,
		private Blog_Target $blog_target,
		private Blog_Sync $blog_sync
	) {}

	/**
	 * Run the drain loop.
	 *
	 * @return array{sent:int, rejected:int, stopped:string}
	 *         Counts plus why the loop stopped: 'busy' (Karkinos full),
	 *         'empty' (nothing left), 'cap' (hit MAX_PER_RUN),
	 *         'misconfigured' (missing secret/CA/target), 'transient'
	 *         (a send failed and will be retried).
	 */
	public function run(): array {
		$sent     = 0;
		$rejected = 0;

		$secret = $this->secret();
		$ca     = $this->ca_path();

		// Only offer the queue kinds we can actually deliver right now. An
		// unconfigured target simply leaves its jobs queued instead of
		// blocking the head of the queue for every other target.
		$kinds = array();
		if ( null !== $secret && null !== $ca && '' !== $this->target->url() && '' !== $this->target->capacity_url() ) {
			$kinds[] = Dispatch_Job::KIND_KARKINOS;
		}
		if ( $this->act_target->is_configured() ) {
			$kinds[] = Dispatch_Job::KIND_ACT;
		}
		if ( $this->blog_target->is_configured() ) {
			$kinds[] = Dispatch_Job::KIND_BLOG;
		}

		if ( array() === $kinds ) {
			return array(
				'sent'     => 0,
				'rejected' => 0,
				'stopped'  => 'misconfigured',
			);
		}

		$saw_busy = false;

		for ( $i = 0; $i < self::MAX_PER_RUN; $i++ ) {
			$job = $this->queue->next( $kinds );
			if ( null === $job ) {
				return $this->summary( $sent, $rejected, $saw_busy ? 'busy' : 'empty' );
			}

			if ( Dispatch_Job::KIND_ACT === $job->kind ) {
				$response = $this->post_act( $job );
			} elseif ( Dispatch_Job::KIND_BLOG === $job->kind ) {
				// Not a forward: the job is a signal to rebuild the blog's
				// stubs section from GitHub. The sync returns the final blog
				// response (wp_remote shape), so the terminal/transient
				// handling below applies unchanged.
				$response = $this->blog_sync->run();
			} else {
				if ( ! $this->karkinos_is_free( (string) $secret, (string) $ca ) ) {
					// Karkinos is busy. Stop offering it work this tick, but
					// keep draining any other kind still in the queue.
					$saw_busy = true;
					$kinds    = array_values( array_diff( $kinds, array( Dispatch_Job::KIND_KARKINOS ) ) );
					if ( array() === $kinds ) {
						return $this->summary( $sent, $rejected, 'busy' );
					}
					continue;
				}

				$response = $this->post( $job, (string) $secret, (string) $ca );
			}

			if ( is_wp_error( $response ) ) {
				// Transport failure — leave the job for the next tick.
				return $this->summary( $sent, $rejected, 'transient' );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );

			if ( $code >= 200 && $code < 300 ) {
				$this->queue->mark_dispatched( $job->id, $code, $body );
				++$sent;
				continue;
			}

			if ( $code >= 400 && $code < 500 && 429 !== $code ) {
				// Permanent rejection (malformed/unwanted). Stamp it so it is
				// not retried forever; the 4xx is recorded for ops.
				$this->queue->mark_dispatched( $job->id, $code, $body, 'rejected' );
				++$rejected;
				continue;
			}

			// 429 (busy) / 5xx / anything else → transient. Retry next tick.
			return $this->summary( $sent, $rejected, 'transient' );
		}

		return $this->summary( $sent, $rejected, 'cap' );
	}

	/**
	 * Probe the capacity/lock endpoint. Free only on 200 + {"available":true}.
	 *
	 * @param string $secret Shared bearer secret.
	 * @param string $ca     Path to the pinned cert.
	 *
	 * @return bool True if Karkinos reports it is free to accept a job.
	 */
	private function karkinos_is_free( string $secret, string $ca ): bool {
		$response = wp_remote_get(
			$this->target->capacity_url(),
			array(
				'timeout'                     => self::PROBE_TIMEOUT,
				'sslverify'                   => true,
				'sslcertificates'             => $ca,
				Karkinos_TLS_Pinning::PIN_ARG => true,
				'headers'                     => array(
					'Authorization' => 'Bearer ' . $secret,
					'Accept'        => 'application/json',
					'User-Agent'    => 'karkinos-gateway',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $decoded ) && true === ( $decoded['available'] ?? null );
	}

	/**
	 * POST one job's envelope to the Karkinos dispatch endpoint.
	 *
	 * The signature is computed over the exact bytes sent, so the payload is
	 * passed verbatim as a string body (an array would be form-encoded and
	 * break the HMAC).
	 *
	 * @param Dispatch_Job $job    Job to forward.
	 * @param string       $secret Shared HMAC/bearer secret.
	 * @param string       $ca     Path to the pinned cert.
	 *
	 * @return array<string, mixed>|\WP_Error The wp_remote_post result.
	 */
	private function post( Dispatch_Job $job, string $secret, string $ca ): array|\WP_Error {
		$signature = 'sha256=' . hash_hmac( 'sha256', $job->payload, $secret );

		return wp_remote_post(
			$this->target->url(),
			array(
				'timeout'                     => self::POST_TIMEOUT,
				'sslverify'                   => true,
				'sslcertificates'             => $ca,
				Karkinos_TLS_Pinning::PIN_ARG => true,
				'body'                        => $job->payload,
				'headers'                     => array(
					'Content-Type'         => 'application/json',
					'X-Karkinos-Event'     => $job->event,
					'X-Karkinos-Delivery'  => $job->delivery_id,
					'X-Karkinos-Signature' => $signature,
					'User-Agent'           => 'karkinos-gateway',
				),
			)
		);
	}

	/**
	 * POST one act job to the Actions tool.
	 *
	 * Deliberately unlike post(): the tool sits behind a Cloudflare Tunnel with
	 * a real certificate, so TLS verification is ordinary (no pinning) and the
	 * only credential is HTTP basic auth. The stored payload is already the
	 * exact JSON body the tool expects ({"url": "<PR html_url>"}).
	 *
	 * The job's own target_url is used, not a freshly resolved one, so a job
	 * always goes where it was addressed when enqueued.
	 *
	 * @param Dispatch_Job $job Job to forward.
	 *
	 * @return array<string, mixed>|\WP_Error The wp_remote_post result.
	 */
	private function post_act( Dispatch_Job $job ): array|\WP_Error {
		$url = '' !== $job->target_url ? $job->target_url : $this->act_target->url();

		if ( '' === $url ) {
			return new \WP_Error( 'karkinos_gateway_no_act_target', 'No Actions tool URL resolved.' );
		}

		return wp_remote_post(
			$url,
			array(
				'timeout'   => self::POST_TIMEOUT,
				'sslverify' => true,
				'body'      => $job->payload,
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => $this->act_target->auth_header(),
					'Accept'        => 'application/json',
					'User-Agent'    => 'karkinos-gateway',
				),
			)
		);
	}

	/**
	 * Build the run summary.
	 *
	 * @param int    $sent     Jobs forwarded OK.
	 * @param int    $rejected Jobs permanently rejected (4xx).
	 * @param string $stopped  Stop reason.
	 *
	 * @return array{sent:int, rejected:int, stopped:string}
	 */
	private function summary( int $sent, int $rejected, string $stopped ): array {
		return array(
			'sent'     => $sent,
			'rejected' => $rejected,
			'stopped'  => $stopped,
		);
	}

	/**
	 * Shared secret from wp-config, or null when unset/empty.
	 *
	 * @return string|null
	 */
	private function secret(): ?string {
		if ( ! defined( 'KARKINOS_DISPATCH_SECRET' ) ) {
			return null;
		}
		$secret = constant( 'KARKINOS_DISPATCH_SECRET' );
		return is_string( $secret ) && '' !== $secret ? $secret : null;
	}

	/**
	 * Pinned-cert path from wp-config, or null when unset/unreadable.
	 *
	 * @return string|null
	 */
	private function ca_path(): ?string {
		if ( ! defined( 'KARKINOS_DISPATCH_CA' ) ) {
			return null;
		}
		$path = constant( 'KARKINOS_DISPATCH_CA' );
		return is_string( $path ) && is_readable( $path ) ? $path : null;
	}
}
