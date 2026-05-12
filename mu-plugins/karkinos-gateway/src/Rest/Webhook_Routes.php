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
	 * Constructor.
	 *
	 * @param Webhook_Logger $logger Writes one JSONL line per delivery.
	 */
	public function __construct( private Webhook_Logger $logger ) {}

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
	 * Flow: size cap → verify signature → log → reply.
	 *   - Body > MAX_BODY_BYTES → 413, nothing logged (denial-of-storage guard).
	 *   - Invalid signature      → 401, logged with body hash only (no payload).
	 *   - `ping` event           → 200 with {ok:true, pong:true}.
	 *   - Anything else          → 202 Accepted (real dispatch wired in next iteration).
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
		);

		// Only verified payloads are parsed and persisted in full. Unverified
		// requests are logged for visibility but their bodies are not stored
		// — an attacker who can hit the endpoint cannot use it as a journal.
		if ( $signature_valid ) {
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
		}

		$this->logger->log( $record );

		if ( ! $signature_valid ) {
			return new WP_REST_Response(
				array( 'error' => 'invalid_signature' ),
				401
			);
		}

		if ( 'ping' === $event ) {
			return new WP_REST_Response(
				array(
					'ok'   => true,
					'pong' => true,
				),
				200
			);
		}

		// Accepted. Event-specific dispatch will land in a follow-up iteration.
		return new WP_REST_Response(
			array(
				'ok'       => true,
				'event'    => $event,
				'action'   => $record['action'],
				'delivery' => $delivery,
			),
			202
		);
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
