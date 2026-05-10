<?php

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

	protected ?string $namespace = 'karkinos-gateway/v1';

	public function __construct( private Gateway_Settings $settings ) {}

	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/settings/local-server-ip', array( $this, 'update_local_server_ip' ) )
				->authentication( array( $this, 'check_auth' ) )
				->argument(
					String_Type::field( 'ip' )
						->required()
						->format( Argument::FORMAT_IP )
						->validation( static fn( $value ): bool => is_string( $value ) && false !== filter_var( $value, FILTER_VALIDATE_IP ) )
				),
		);
	}

	public function check_auth( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

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
