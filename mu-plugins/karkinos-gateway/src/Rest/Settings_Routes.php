<?php
/**
 * REST endpoint for updating the home-server IP setting.
 *
 * Single POST under `karkinos-gateway/v1/settings/local-server-ip`. Called
 * by the home server itself when its ISP-rotated IP changes. Auth is the
 * WP user system: caller must hold `manage_options` (typically via an
 * application password).
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\Settings\Gateway_Settings;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use PinkCrab\WP_Rest_Schema\Argument\Argument;
use PinkCrab\WP_Rest_Schema\Argument\String_Type;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Settings_Routes extends Route_Controller {

	/** @var ?string REST namespace shared with the other gateway routes. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	/**
	 * Constructor.
	 *
	 * @param Gateway_Settings $settings Persistence layer for the gateway settings.
	 */
	public function __construct( private Gateway_Settings $settings ) {}

	/**
	 * Declare the routes this controller owns.
	 *
	 * @param Route_Factory $factory Factory pre-configured with the namespace.
	 *
	 * @return array<int, mixed> Route definitions to register.
	 */
	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/settings/local-server-ip', array( $this, 'update_local_server_ip' ) )
				->authentication( array( $this, 'check_auth' ) )
				->argument(
					String_Type::field( 'ip' )
						->required()
						->format( Argument::FORMAT_IP )
						->validation( static fn( $value ): bool => false !== filter_var( $value, FILTER_VALIDATE_IP ) )
				),
		);
	}

	/**
	 * Authentication callback. Required by Route_Controller's authentication() —
	 * receives the request even though we don't inspect it (capability is
	 * already attached to the current_user via WP's app-password handling).
	 *
	 * @param WP_REST_Request $request Unused; kept for signature compatibility.
	 *
	 * @return bool True if the current user can edit options.
	 */
	public function check_auth( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Persist the new local server IP. Returns the saved IP plus a UTC
	 * timestamp so the caller can confirm what we stored.
	 *
	 * @param WP_REST_Request $request Validated by the schema above — `ip` is
	 *                                 guaranteed to be a non-empty IP string.
	 *
	 * @return WP_REST_Response|WP_Error 200 with {ip, updated_at} on success,
	 *                                   500 WP_Error on persistence failure.
	 */
	public function update_local_server_ip( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ip = (string) $request->get_param( 'ip' );

		$saved = $this->settings->set( Gateway_Settings::FIELD_LOCAL_SERVER_IP, $ip );

		if ( ! $saved ) {
			return new WP_Error(
				'karkinos_gateway_persist_failed',
				'Failed to persist local server IP.',
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'ip'         => $ip,
				'updated_at' => gmdate( 'c' ),
			),
			200
		);
	}
}
