<?php
/**
 * Inbound webhook receiver for GitHub deliveries.
 *
 * Single POST endpoint at `karkinos-gateway/v1/webhooks/github`. Verifies
 * the HMAC SHA-256 signature against the secret defined in wp-config as
 * `KARKINOS_GH_WEBHOOK_SECRET`, logs every delivery (valid or not), then
 * either ACKs the ping or 202-accepts other events.
 *
 * Security posture:
 *   - Bodies above MAX_BODY_BYTES are rejected with 413 *before* any I/O,
 *     so an unauthenticated attacker can't fill the disk by POSTing huge
 *     payloads.
 *   - Invalid-signature deliveries are still logged (operator visibility)
 *     but only headers + body sha256 are persisted — the parsed payload
 *     is reserved for verified deliveries.
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\Auth\Authorised_Actors;
use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Dispatch\Dispatch_Worker;
use Karkinos\Gateway\Dispatch\Forward_Target;
use Karkinos\Gateway\Logging\Webhook_Logger;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use WP_REST_Request;
use WP_REST_Response;

class Webhook_Routes extends Route_Controller {

	/** @var ?string Shared REST namespace. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	/**
	 * Maximum accepted request body size, in bytes. GitHub itself caps
	 * webhook payloads at 25 MB; legitimate deliveries are typically
	 * < 100 KB. 5 MB leaves comfortable headroom for the largest real
	 * events while rejecting anything obviously hostile.
	 */
	private const MAX_BODY_BYTES = 5 * 1024 * 1024;

	/**
	 * Exact set of labels that trigger a Karkinos routine. Adding one of these
	 * to an issue OR pull request (action `labeled`) by an authorised actor is
	 * forwarded; a `[karkinos]`-prefixed label not in this list is not. Karkinos
	 * reads which routine to run from the label in the payload.
	 *
	 * @var list<string>
	 */
	private const KARKINOS_TRIGGER_LABELS = array(
		'[karkinos] Abort',
		'[karkinos] Builder',
		'[karkinos] Designer',
		'[karkinos] Guardian',
		'[karkinos] Pause',
		'[karkinos] Planner',
		'[karkinos] PlannerReview',
		'[karkinos] ProjectBriefing',
		'[karkinos] Reviewer',
		'[karkinos] ReviewFixer',
		'[karkinos] Triage',
	);

	/**
	 * Events acknowledged (202) but neither parsed, logged, nor forwarded —
	 * per-job / per-check CI chatter we never act on. `check_suite` is NOT here:
	 * a *completed* suite attached to a PR is a forward trigger, so check_suite
	 * is parsed and its non-trigger actions are dropped separately.
	 *
	 * @var list<string>
	 */
	private const UNLOGGED_EVENTS = array( 'workflow_job', 'check_run' );

	/**
	 * Constructor.
	 *
	 * @param Webhook_Logger    $logger Writes one JSONL line per delivery.
	 * @param Authorised_Actors $actors Roster the sender is gated against.
	 * @param Dispatch_Queue    $queue  Where an authorised delivery is enqueued.
	 * @param Forward_Target    $target Resolves the Karkinos dispatch URL.
	 * @param Dispatch_Worker   $worker Drains the queue (one inline attempt on enqueue).
	 */
	public function __construct(
		private Webhook_Logger $logger,
		private Authorised_Actors $actors,
		private Dispatch_Queue $queue,
		private Forward_Target $target,
		private Dispatch_Worker $worker
	) {}

	/**
	 * Declare the inbound-webhook routes this controller owns.
	 *
	 * @param Route_Factory $factory Pre-configured with the namespace.
	 *
	 * @return array<int, mixed> Route definitions to register.
	 */
	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/webhooks/github', array( $this, 'handle_github' ) ),
		);
	}

	/**
	 * Handle one delivery from GitHub.
	 *
	 * Flow: size cap → verify signature → parse → gate on sender → reply.
	 *   - Body > MAX_BODY_BYTES   → 413, nothing logged (denial-of-storage guard).
	 *   - Invalid signature       → 401, logged with body hash only (no payload).
	 *   - `ping` event            → 200 {ok:true, pong:true} (never forwarded).
	 *   - Authorised sender        → envelope enqueued + one inline dispatch, 202.
	 *   - Unauthorised / no sender → nothing enqueued, 202.
	 *
	 * The 202 is identical whether or not we forwarded, so the actor gate is
	 * not observable from GitHub's delivery UI. Every verified delivery is
	 * logged with the gate decision for operator visibility.
	 *
	 * @param WP_REST_Request $request Inbound request — raw body is read for HMAC verify.
	 *
	 * @return WP_REST_Response Always a JSON response; status reflects outcome.
	 */
	public function handle_github( WP_REST_Request $request ): WP_REST_Response {
		$raw_body         = $request->get_body();
		$signature_header = (string) $request->get_header( 'x-hub-signature-256' );
		$event            = (string) $request->get_header( 'x-github-event' );
		$delivery         = (string) $request->get_header( 'x-github-delivery' );

		if ( strlen( $raw_body ) > self::MAX_BODY_BYTES ) {
			return new WP_REST_Response(
				array( 'error' => 'request_too_large' ),
				413
			);
		}

		$signature_valid = $this->verify_signature( $raw_body, $signature_header );

		$record = array(
			'ts'              => gmdate( 'c' ),
			'delivery'        => $delivery,
			'event'           => '' !== $event ? $event : null,
			'signature_valid' => $signature_valid,
			'body_hash'       => 'sha256:' . hash( 'sha256', $raw_body ),
			'action'          => null,
			'repo'            => null,
			'actor'           => null,
			'authorised'      => null,
			'dispatched'      => false,
			'dispatch_reason' => null,
			'job_id'          => null,
		);

		// Unverified requests are logged for visibility but their bodies are
		// not stored — an attacker who can hit the endpoint cannot use it as a
		// journal.
		if ( ! $signature_valid ) {
			$this->logger->log( $record );
			return new WP_REST_Response(
				array( 'error' => 'invalid_signature' ),
				401
			);
		}

		// GitHub setup ping — acknowledge with pong. Not logged (handshake noise).
		if ( 'ping' === $event ) {
			return new WP_REST_Response(
				array(
					'ok'   => true,
					'pong' => true,
				),
				200
			);
		}

		// High-volume CI chatter we never act on — acknowledge but don't parse,
		// log, or forward.
		if ( in_array( $event, self::UNLOGGED_EVENTS, true ) ) {
			return new WP_REST_Response(
				array(
					'ok'       => true,
					'delivery' => $delivery,
				),
				202
			);
		}

		$payload = json_decode( $raw_body, true );
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		if ( isset( $payload['action'] ) && is_string( $payload['action'] ) ) {
			$record['action'] = $payload['action'];
		}
		if ( isset( $payload['repository']['full_name'] ) && is_string( $payload['repository']['full_name'] ) ) {
			$record['repo'] = $payload['repository']['full_name'];
		}
		$record['payload'] = $payload;

		// check_suite is only acted on when a suite COMPLETES for a PR. Its other
		// actions (requested / rerequested / completed-without-PR) are noise —
		// ack without logging.
		if ( 'check_suite' === $event && ! $this->is_ci_finished_trigger( $event, $payload ) ) {
			return new WP_REST_Response(
				array(
					'ok'       => true,
					'delivery' => $delivery,
				),
				202
			);
		}

		$actor           = $this->actor_login( $payload );
		$record['actor'] = '' !== $actor ? $actor : null;

		$this->gate_and_dispatch( $event, $delivery, $actor, $payload, $record );

		$this->logger->log( $record );

		return new WP_REST_Response(
			array(
				'ok'       => true,
				'delivery' => $delivery,
			),
			202
		);
	}

	/**
	 * Apply the actor gate and, if authorised, enqueue the envelope and make
	 * one inline dispatch attempt. Mutates $record with the gate decision.
	 *
	 * @param string               $event    X-GitHub-Event header.
	 * @param string               $delivery X-GitHub-Delivery header (dedupe key).
	 * @param string               $actor    Resolved sender login ('' if none).
	 * @param array<string, mixed> $payload  Parsed, verified GitHub payload.
	 * @param array<string, mixed> $record   Log record, updated by reference.
	 *
	 * @return void
	 */
	private function gate_and_dispatch( string $event, string $delivery, string $actor, array $payload, array &$record ): void {
		$is_label = $this->is_label_trigger( $event, $payload );
		$is_ci    = $this->is_ci_finished_trigger( $event, $payload );

		// Not something we forward — logged for visibility, nothing queued.
		if ( ! $is_label && ! $is_ci ) {
			$record['dispatch_reason'] = 'not_karkinos_trigger';
			return;
		}

		// Label triggers are gated on the human who applied the label. A
		// CI-finished delivery (check_suite completed for a PR) is a system
		// event from a bot, so the PR + completion condition is the gate, not
		// the roster.
		if ( $is_label ) {
			if ( '' === $actor ) {
				$record['authorised']      = false;
				$record['dispatch_reason'] = 'no_sender';
				return;
			}

			$authorised           = $this->actors->is_authorised( $actor );
			$record['authorised'] = $authorised;

			if ( ! $authorised ) {
				$record['dispatch_reason'] = 'unauthorised_actor';
				return;
			}
		}

		$target = $this->target->url();
		if ( '' === $target ) {
			$record['dispatch_reason'] = 'no_target';
			return;
		}

		$envelope = wp_json_encode(
			array(
				'event'       => '' !== $event ? $event : null,
				'action'      => $record['action'],
				'delivery'    => $delivery,
				'repo'        => $record['repo'],
				'sender'      => $actor,
				'received_at' => gmdate( 'c' ),
				'payload'     => $payload,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		if ( false === $envelope ) {
			$record['dispatch_reason'] = 'encode_failed';
			return;
		}

		$job_id = $this->queue->enqueue(
			array(
				'payload'     => $envelope,
				'source'      => 'github',
				'event'       => $event,
				'delivery_id' => $delivery,
				'target_url'  => $target,
			)
		);

		if ( 0 === $job_id ) {
			$record['dispatch_reason'] = 'enqueue_failed';
			return;
		}

		$record['dispatched']      = true;
		$record['dispatch_reason'] = 'enqueued';
		$record['job_id']          = $job_id;

		// One inline attempt so a healthy home server gets it immediately; if
		// Karkinos is busy or down the job simply waits for the external tick.
		$this->worker->run();
	}

	/**
	 * Resolve the acting login from a webhook payload.
	 *
	 * `sender.login` is GitHub's "who triggered this delivery" — the labeller
	 * on a `labeled` action, the commenter on `issue_comment.created`, etc.
	 *
	 * @param array<string, mixed> $payload Parsed payload.
	 *
	 * @return string Login as sent, or '' when absent.
	 */
	private function actor_login( array $payload ): string {
		$login = $payload['sender']['login'] ?? null;
		return is_string( $login ) ? $login : '';
	}

	/**
	 * Is this delivery a Karkinos routine label trigger?
	 *
	 * True only for an `issues` or `pull_request` event with action `labeled`
	 * where the label just added (`payload.label.name`) is one of
	 * KARKINOS_TRIGGER_LABELS. The match is case-insensitive but otherwise
	 * exact — a `[karkinos]` label that isn't in the list does not trigger.
	 *
	 * @param string               $event   X-GitHub-Event header.
	 * @param array<string, mixed> $payload Parsed, verified payload.
	 *
	 * @return bool
	 */
	private function is_label_trigger( string $event, array $payload ): bool {
		if ( 'issues' !== $event && 'pull_request' !== $event ) {
			return false;
		}

		if ( 'labeled' !== ( $payload['action'] ?? null ) ) {
			return false;
		}

		$label = $payload['label']['name'] ?? null;
		if ( ! is_string( $label ) ) {
			return false;
		}

		$needle = strtolower( $label );
		foreach ( self::KARKINOS_TRIGGER_LABELS as $allowed ) {
			if ( strtolower( $allowed ) === $needle ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is this delivery a "PR's checks finished" signal?
	 *
	 * True only for a `check_suite` event with action `completed` that is
	 * attached to at least one pull request (`check_suite.pull_requests`).
	 * This is a system event (bot sender), so it is NOT actor-gated — Karkinos
	 * matches the PR(s) to its own routines.
	 *
	 * @param string               $event   X-GitHub-Event header.
	 * @param array<string, mixed> $payload Parsed, verified payload.
	 *
	 * @return bool
	 */
	private function is_ci_finished_trigger( string $event, array $payload ): bool {
		if ( 'check_suite' !== $event ) {
			return false;
		}

		if ( 'completed' !== ( $payload['action'] ?? null ) ) {
			return false;
		}

		$prs = $payload['check_suite']['pull_requests'] ?? null;
		return is_array( $prs ) && array() !== $prs;
	}

	/**
	 * Constant-time HMAC SHA-256 verification of the request body against
	 * the secret defined in wp-config (`KARKINOS_GH_WEBHOOK_SECRET`).
	 *
	 * Returns false on any of: missing header, missing/empty constant,
	 * malformed value. Uses hash_equals so timing is independent of where
	 * the strings differ.
	 *
	 * @param string $raw_body Exact request bytes (not the decoded body).
	 * @param string $header   Value of the X-Hub-Signature-256 header.
	 *
	 * @return bool True if the signature matches, false otherwise.
	 */
	private function verify_signature( string $raw_body, string $header ): bool {
		if ( '' === $header ) {
			return false;
		}

		if ( ! defined( 'KARKINOS_GH_WEBHOOK_SECRET' ) ) {
			return false;
		}

		$secret = constant( 'KARKINOS_GH_WEBHOOK_SECRET' );
		if ( ! is_string( $secret ) || '' === $secret ) {
			return false;
		}

		$expected = 'sha256=' . hash_hmac( 'sha256', $raw_body, $secret );

		return hash_equals( $expected, $header );
	}
}
