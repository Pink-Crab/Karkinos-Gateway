<?php
/**
 * Integration tests for the dispatch worker.
 *
 * Stubs the capacity probe + dispatch POST via pre_http_request, and captures
 * the outbound POST args to assert the signature and pinned-TLS options.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Dispatch;

use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Dispatch\Dispatch_Worker;
use Karkinos\Gateway\Dispatch\Karkinos_TLS_Pinning;
use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_Error;
use WP_UnitTestCase;

/**
 * @group integration
 * @group dispatch
 */
class Test_Dispatch_Worker extends WP_UnitTestCase {

	private Dispatch_Worker $worker;
	private Dispatch_Queue $queue;

	/** @var array<string, mixed>|null Captured args of the dispatch POST. */
	private ?array $post_args = null;

	public function set_up(): void {
		parent::set_up();
		$this->worker    = App::make( Dispatch_Worker::class );
		$this->queue     = App::make( Dispatch_Queue::class );
		$this->post_args = null;
		$this->truncate_table();
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		$this->truncate_table();
		parent::tear_down();
	}

	/** @testdox when Karkinos is busy nothing is dispatched and the job stays pending */
	public function test_busy_dispatches_nothing(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{"a":1}', 'event' => 'issues', 'delivery_id' => 'd1' ) );
		$this->stub_http( false, null );

		$summary = $this->worker->run();

		$this->assertSame( 0, $summary['sent'] );
		$this->assertSame( 'busy', $summary['stopped'] );
		$this->assertFalse( $this->queue->find( $id )->is_dispatched() );
	}

	/** @testdox a free server + 2xx marks the job dispatched and signs the exact payload */
	public function test_success_marks_dispatched_and_signs_payload(): void {
		$payload = '{"hello":"world"}';
		$id      = $this->queue->enqueue( array( 'payload' => $payload, 'event' => 'issues', 'delivery_id' => 'abc-123' ) );
		$this->stub_http( true, array( 'code' => 200, 'body' => '{"ok":true}' ) );

		$summary = $this->worker->run();

		$this->assertSame( 1, $summary['sent'] );

		$job = $this->queue->find( $id );
		$this->assertTrue( $job->is_dispatched() );
		$this->assertSame( 200, $job->response_status );

		// Signature is over the exact stored bytes.
		$expected = 'sha256=' . hash_hmac( 'sha256', $payload, KARKINOS_DISPATCH_SECRET );
		$this->assertSame( $expected, $this->post_args['headers']['X-Karkinos-Signature'] );
		$this->assertSame( $payload, $this->post_args['body'] );
		$this->assertSame( 'issues', $this->post_args['headers']['X-Karkinos-Event'] );
		$this->assertSame( 'abc-123', $this->post_args['headers']['X-Karkinos-Delivery'] );

		// Pinned-TLS options are present.
		$this->assertTrue( $this->post_args['sslverify'] );
		$this->assertSame( KARKINOS_DISPATCH_CA, $this->post_args['sslcertificates'] );
		$this->assertTrue( $this->post_args[ Karkinos_TLS_Pinning::PIN_ARG ] );
	}

	/** @testdox a 4xx is a permanent reject: dispatched, recorded, not retried */
	public function test_4xx_is_permanent_reject(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}', 'event' => 'issues', 'delivery_id' => 'd4' ) );
		$this->stub_http( true, array( 'code' => 400, 'body' => '{"error":"bad"}' ) );

		$summary = $this->worker->run();

		$this->assertSame( 1, $summary['rejected'] );
		$job = $this->queue->find( $id );
		$this->assertTrue( $job->is_dispatched() );
		$this->assertSame( 400, $job->response_status );
		$this->assertSame( 'rejected', $job->error );
	}

	/** @testdox a 5xx is transient: the job is left pending for a later tick */
	public function test_5xx_is_transient(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}', 'event' => 'issues', 'delivery_id' => 'd5' ) );
		$this->stub_http( true, array( 'code' => 503, 'body' => '' ) );

		$summary = $this->worker->run();

		$this->assertSame( 'transient', $summary['stopped'] );
		$this->assertFalse( $this->queue->find( $id )->is_dispatched() );
	}

	/** @testdox a transport error is transient: the job is left pending */
	public function test_transport_error_is_transient(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}', 'event' => 'issues', 'delivery_id' => 'd6' ) );
		$this->stub_http( true, 'error' );

		$summary = $this->worker->run();

		$this->assertSame( 'transient', $summary['stopped'] );
		$this->assertFalse( $this->queue->find( $id )->is_dispatched() );
	}

	/**
	 * Stub the capacity probe and the dispatch POST.
	 *
	 * @param bool                                $available    Capacity probe answer.
	 * @param array{code:int,body:string}|string|null $post_result Dispatch result: a
	 *        {code,body} array, the string 'error' for a transport WP_Error, or null
	 *        when no POST is expected.
	 *
	 * @return void
	 */
	private function stub_http( bool $available, array|string|null $post_result ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $available, $post_result ) {
				if ( str_contains( (string) $url, '/dispatch/capacity' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => (string) wp_json_encode( array( 'available' => $available ) ),
						'headers'  => array(),
					);
				}

				// Dispatch POST.
				$this->post_args = $args;

				if ( 'error' === $post_result ) {
					return new WP_Error( 'http_request_failed', 'boom' );
				}

				return array(
					'response' => array( 'code' => $post_result['code'] ),
					'body'     => $post_result['body'],
					'headers'  => array(),
				);
			},
			10,
			3
		);
	}

	private function truncate_table(): void {
		global $wpdb;
		$table = App::make( App_Config::class )->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Truncate of a test-owned table.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
