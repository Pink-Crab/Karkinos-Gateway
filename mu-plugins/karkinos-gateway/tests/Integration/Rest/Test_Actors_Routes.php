<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use Karkinos\Gateway\Auth\Authorised_Actors;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group auth
 */
class Test_Actors_Routes extends WP_UnitTestCase {

	private App_Config $config;
	private Authorised_Actors $actors;
	private int $admin_id;

	public function set_up(): void {
		parent::set_up();
		$this->config   = App::make( App_Config::class );
		$this->actors   = App::make( Authorised_Actors::class );
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		delete_option( $this->config->additional( 'authorised_actors_option' ) );
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( $this->config->additional( 'authorised_actors_option' ) );
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/** @testdox POST /actors/sync is denied without manage_options */
	public function test_sync_requires_auth(): void {
		wp_set_current_user( 0 );
		$response = rest_do_request( new WP_REST_Request( 'POST', '/karkinos-gateway/v1/actors/sync' ) );
		$this->assertGreaterThanOrEqual( 400, $response->get_status() );
	}

	/** @testdox POST /actors/sync refreshes the roster from GitHub */
	public function test_sync_refreshes_roster(): void {
		wp_set_current_user( $this->admin_id );
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => (string) wp_json_encode( array( array( 'login' => 'octocat' ) ) ),
				'headers'  => array(),
			),
			10,
			3
		);

		$response = rest_do_request( new WP_REST_Request( 'POST', '/karkinos-gateway/v1/actors/sync' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'Pink-Crab', $data['org'] );
		$this->assertSame( 1, $data['count'] );
		$this->assertTrue( $this->actors->is_authorised( 'octocat' ) );
	}

	/** @testdox GET /actors reports roster health */
	public function test_status_reports_roster(): void {
		wp_set_current_user( $this->admin_id );
		$this->actors->replace( array( 'octocat', 'hubot' ), 'Pink-Crab' );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/karkinos-gateway/v1/actors' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 2, $data['count'] );
		$this->assertSame( 'Pink-Crab', $data['org'] );
		$this->assertNotNull( $data['synced_at'] );
	}
}
