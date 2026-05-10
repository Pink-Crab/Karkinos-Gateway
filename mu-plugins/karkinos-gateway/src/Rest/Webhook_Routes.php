<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\Logging\Webhook_Logger;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use WP_REST_Request;
use WP_REST_Response;

class Webhook_Routes extends Route_Controller {

	protected ?string $namespace = 'karkinos-gateway/v1';

	public function __construct( private Webhook_Logger $logger ) {}

	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/webhooks/github', array( $this, 'handle_github' ) ),
		);
	}

	public function handle_github( WP_REST_Request $request ): WP_REST_Response {
		$raw_body         = $request->get_body();
		$signature_header = (string) $request->get_header( 'x-hub-signature-256' );
		$event            = (string) $request->get_header( 'x-github-event' );
		$delivery         = (string) $request->get_header( 'x-github-delivery' );

		$signature_valid = $this->verify_signature( $raw_body, $signature_header );

		$payload = json_decode( $raw_body, true );
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		$action = isset( $payload['action'] ) && is_string( $payload['action'] )
			? $payload['action']
			: null;

		$repo = isset( $payload['repository']['full_name'] ) && is_string( $payload['repository']['full_name'] )
			? $payload['repository']['full_name']
			: null;

		$this->logger->log(
			array(
				'ts'              => gmdate( 'c' ),
				'delivery'        => $delivery,
				'event'           => $event !== '' ? $event : null,
				'action'          => $action,
				'repo'            => $repo,
				'signature_valid' => $signature_valid,
				'payload'         => $payload,
			)
		);

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
				'action'   => $action,
				'delivery' => $delivery,
			),
			202
		);
	}

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
