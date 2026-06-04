<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group dispatch
 */
class Test_Dispatch_Routes extends WP_UnitTestCase {

	private App_Config $config;
	private Dispatch_Queue $queue;
	private int $admin_id;

	public function set_up(): void {
		parent::set_up();
		$this->config   = App::make( App_Config::class );
		$this->queue    = App::make( Dispatch_Queue::class );
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->truncate_dispatch();
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		$this->truncate_dispatch();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/** @testdox POST /dispatch/tick is denied without manage_options */
	public function test_tick_requires_auth(): void {
		wp_set_current_user( 0 );
		$response = rest_do_request( new WP_REST_Request( 'POST', '/karkinos-gateway/v1/dispatch/tick' ) );
		$this->assertGreaterThanOrEqual( 400, $response->get_status() );
	}

	/** @testdox POST /dispatch/tick returns the worker summary */
	public function test_tick_returns_summary(): void {
		wp_set_current_user( $this->admin_id );
		$this->queue->enqueue( array( 'payload' => '{}', 'event' => 'issues', 'delivery_id' => 'd1' ) );

		// Karkinos busy → nothing sent, job left pending.
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 200 ),
				'body'     => '{"available":false}',
				'headers'  => array(),
			),
			10,
			3
		);

		$response = rest_do_request( new WP_REST_Request( 'POST', '/karkinos-gateway/v1/dispatch/tick' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 0, $data['sent'] );
		$this->assertSame( 'busy', $data['stopped'] );
		$this->assertSame( 1, $this->queue->pending_count() );
	}

	/** @testdox GET /dispatch reports the pending backlog */
	public function test_status_reports_backlog(): void {
		wp_set_current_user( $this->admin_id );
		$this->queue->enqueue( array( 'payload' => '{}' ) );
		$this->queue->enqueue( array( 'payload' => '{}' ) );

		$response = rest_do_request( new WP_REST_Request( 'GET', '/karkinos-gateway/v1/dispatch' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 2, $response->get_data()['pending'] );
	}

	private function truncate_dispatch(): void {
		global $wpdb;
		$table = $this->config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Truncate of a test-owned table.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
