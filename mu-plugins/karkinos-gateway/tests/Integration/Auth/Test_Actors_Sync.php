<?php
/**
 * Integration tests for the roster sync orchestrator.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Auth;

use Karkinos\Gateway\Auth\Actors_Sync;
use Karkinos\Gateway\Auth\Authorised_Actors;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_Error;
use WP_UnitTestCase;

/**
 * @group integration
 * @group auth
 */
class Test_Actors_Sync extends WP_UnitTestCase {

	private Actors_Sync $sync;
	private Authorised_Actors $actors;

	public function set_up(): void {
		parent::set_up();
		$this->sync   = App::make( Actors_Sync::class );
		$this->actors = App::make( Authorised_Actors::class );
		delete_option( App::make( App_Config::class )->additional( 'authorised_actors_option' ) );
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( App::make( App_Config::class )->additional( 'authorised_actors_option' ) );
		parent::tear_down();
	}

	/** @testdox a successful sync replaces the roster */
	public function test_success_replaces_roster(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => (string) wp_json_encode(
					array( array( 'login' => 'octocat' ), array( 'login' => 'hubot' ) )
				),
				'headers'  => array(),
			),
			10,
			3
		);

		$result = $this->sync->run();

		$this->assertSame( 'Pink-Crab', $result['org'] );
		$this->assertSame( 2, $result['count'] );
		$this->assertArrayNotHasKey( 'error', $result );
		$this->assertTrue( $this->actors->is_authorised( 'octocat' ) );
		$this->assertTrue( $this->actors->is_authorised( 'hubot' ) );
	}

	/** @testdox a failed sync keeps the existing roster (fail-safe) */
	public function test_failure_keeps_existing_roster(): void {
		$this->actors->replace( array( 'existing_member' ), 'Pink-Crab' );

		add_filter(
			'pre_http_request',
			fn() => new WP_Error( 'http_request_failed', 'github down' ),
			10,
			3
		);

		$result = $this->sync->run();

		$this->assertArrayHasKey( 'error', $result );
		$this->assertSame( 1, $result['count'] );
		// The old roster must survive a GitHub outage — never emptied.
		$this->assertTrue( $this->actors->is_authorised( 'existing_member' ) );
	}
}
