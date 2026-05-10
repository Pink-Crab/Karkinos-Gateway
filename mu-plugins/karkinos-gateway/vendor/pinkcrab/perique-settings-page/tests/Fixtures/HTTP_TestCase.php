<?php

declare( strict_types=1 );

/**
 * Base test case for REST API integration tests.
 *
 * Sets up a Spy_REST_Server, exposes register_routes() to fire
 * rest_api_init, and provides dispatch_request() for sending mock
 * REST requests through the server.
 *
 * Pattern from PinkCrab Perique-Route's HTTP_TestCase.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Application\App;
use Spy_REST_Server;
use WP_REST_Request;
use WP_UnitTestCase;

abstract class HTTP_TestCase extends WP_UnitTestCase {

	/**
	 * The spy REST server installed for the duration of each test.
	 *
	 * @var Spy_REST_Server
	 */
	protected Spy_REST_Server $server;

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new Spy_REST_Server();
		$this->server   = $wp_rest_server;
	}

	public function tearDown(): void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;

		// Reset Perique App singleton state via reflection so each test gets a clean slate.
		if ( class_exists( App::class ) ) {
			$app = new App( __DIR__ );
			Objects::set_property( $app, 'app_config', null );
			Objects::set_property( $app, 'container', null );
			Objects::set_property( $app, 'module_manager', null );
			Objects::set_property( $app, 'loader', null );
			Objects::set_property( $app, 'booted', false );
		}
	}

	/**
	 * Trigger the rest_api_init hook so registered routes are added to the spy server.
	 */
	protected function register_routes(): void {
		do_action( 'rest_api_init', $this->server );
	}

	/**
	 * Build and dispatch a REST request through the spy server.
	 *
	 * @param string        $method
	 * @param string        $route
	 * @param array<mixed>  $args
	 * @param callable|null $config Optional callback to mutate the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function dispatch_request( string $method, string $route, array $args = array(), ?callable $config = null ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $args as $key => $value ) {
			$request->set_param( $key, $value );
		}
		if ( null !== $config ) {
			$request = $config( $request );
		}
		return $this->server->dispatch( $request );
	}
}
