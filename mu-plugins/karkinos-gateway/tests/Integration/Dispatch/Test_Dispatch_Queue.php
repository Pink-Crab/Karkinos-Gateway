<?php
/**
 * Integration tests for the dispatch queue.
 *
 * Exercises the FIFO + priority + single-flight semantics against a real
 * MySQL table (the WP-PHPUnit install creates the dispatch_jobs table via
 * Migrations_Runner on init).
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Dispatch;

use Karkinos\Gateway\Dispatch\Dispatch_Job;
use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_UnitTestCase;

/**
 * @group integration
 * @group dispatch
 */
class Test_Dispatch_Queue extends WP_UnitTestCase {

	private Dispatch_Queue $queue;

	public function set_up(): void {
		parent::set_up();
		$this->queue = App::make( Dispatch_Queue::class );
		$this->truncate_table();
	}

	public function tear_down(): void {
		$this->truncate_table();
		parent::tear_down();
	}

	/** @testdox enqueue inserts a pending row and returns its id */
	public function test_enqueue_returns_id_and_pending_state(): void {
		$id = $this->queue->enqueue(
			array(
				'payload' => '{"action":"opened"}',
				'source'  => 'github_webhook',
				'event'   => 'issues',
			)
		);

		$this->assertGreaterThan( 0, $id );

		$job = $this->queue->find( $id );
		$this->assertInstanceOf( Dispatch_Job::class, $job );
		$this->assertSame( Dispatch_Job::STATUS_PENDING, $job->status );
		$this->assertSame( 'github_webhook', $job->source );
		$this->assertSame( 'issues', $job->event );
		$this->assertSame( '{"action":"opened"}', $job->payload );
	}

	/** @testdox enqueue sanitises source/event slugs and url */
	public function test_enqueue_sanitises_caller_input(): void {
		$id = $this->queue->enqueue(
			array(
				'payload'    => '{}',
				'source'     => 'Bad@Source!',
				'event'      => 'Issues!Now',
				'target_url' => 'http://1.2.3.4/?x=<script>',
			)
		);

		$job = $this->queue->find( $id );
		// sanitize_key strips uppercase + non-alnum/-/_ characters (no replacement).
		$this->assertSame( 'badsource', $job->source );
		$this->assertSame( 'issuesnow', $job->event );
		$this->assertStringNotContainsString( '<script>', $job->target_url );
	}

	/** @testdox claim_next picks the only pending job and flips it to in_flight */
	public function test_claim_next_flips_status_to_in_flight(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}' ) );

		$claimed = $this->queue->claim_next();

		$this->assertNotNull( $claimed );
		$this->assertSame( $id, $claimed->id );
		$this->assertSame( Dispatch_Job::STATUS_IN_FLIGHT, $claimed->status );
	}

	/** @testdox claim_next returns null when the queue is empty */
	public function test_claim_next_returns_null_when_empty(): void {
		$this->assertNull( $this->queue->claim_next() );
	}

	/** @testdox claim_next returns null while another job is in_flight (single-flight lock) */
	public function test_claim_next_is_single_flight(): void {
		$first  = $this->queue->enqueue( array( 'payload' => '{"first":true}' ) );
		$second = $this->queue->enqueue( array( 'payload' => '{"second":true}' ) );

		$claimed_first = $this->queue->claim_next();
		$this->assertNotNull( $claimed_first );
		$this->assertSame( $first, $claimed_first->id );

		// Second pending job exists, but lock is held — claim must refuse.
		$claimed_second = $this->queue->claim_next();
		$this->assertNull( $claimed_second );

		// Job count sanity-check.
		$this->queue->mark_done( $first, 200, 'ok' );

		// Lock released — second can now be claimed.
		$claimed_after_release = $this->queue->claim_next();
		$this->assertNotNull( $claimed_after_release );
		$this->assertSame( $second, $claimed_after_release->id );
	}

	/** @testdox higher-priority jobs are claimed before default-priority FIFO */
	public function test_claim_next_respects_priority(): void {
		$low_old = $this->queue->enqueue( array( 'payload' => '{"n":1}' ) );
		usleep( 1100000 ); // 1.1s — guarantees a distinct created_at.
		$low_new  = $this->queue->enqueue( array( 'payload' => '{"n":2}' ) );
		$urgent   = $this->queue->enqueue(
			array(
				'payload'  => '{"n":3}',
				'priority' => Dispatch_Job::PRIORITY_BUMPED,
			)
		);

		// First out: the high-priority one even though it was inserted last.
		$first_out = $this->queue->claim_next();
		$this->assertNotNull( $first_out );
		$this->assertSame( $urgent, $first_out->id );

		// Release the lock so we can pull the next.
		$this->queue->mark_done( $urgent, 200, '' );

		// Next out: the oldest default-priority one.
		$second_out = $this->queue->claim_next();
		$this->assertNotNull( $second_out );
		$this->assertSame( $low_old, $second_out->id );

		$this->queue->mark_done( $low_old, 200, '' );

		$third_out = $this->queue->claim_next();
		$this->assertNotNull( $third_out );
		$this->assertSame( $low_new, $third_out->id );
	}

	/** @testdox mark_done stores the upstream status + body and releases the lock */
	public function test_mark_done_records_response(): void {
		$id      = $this->queue->enqueue( array( 'payload' => '{}' ) );
		$claimed = $this->queue->claim_next();
		$this->assertSame( $id, $claimed->id );

		$this->queue->mark_done( $id, 200, '{"ok":true}' );

		$reloaded = $this->queue->find( $id );
		$this->assertSame( Dispatch_Job::STATUS_DONE, $reloaded->status );
		$this->assertSame( 200, $reloaded->response_status );
		$this->assertSame( '{"ok":true}', $reloaded->response_body );
		$this->assertNotNull( $reloaded->dispatched_at );
		$this->assertSame( '', $reloaded->error );
	}

	/** @testdox mark_failed stores the error + any upstream response */
	public function test_mark_failed_records_error(): void {
		$id      = $this->queue->enqueue( array( 'payload' => '{}' ) );
		$claimed = $this->queue->claim_next();
		$this->assertSame( $id, $claimed->id );

		$this->queue->mark_failed( $id, 'connection refused', 0, '' );

		$reloaded = $this->queue->find( $id );
		$this->assertSame( Dispatch_Job::STATUS_FAILED, $reloaded->status );
		$this->assertSame( 'connection refused', $reloaded->error );
		$this->assertSame( 0, $reloaded->response_status );
	}

	/** @testdox bump raises priority of a pending job and it gets claimed first */
	public function test_bump_promotes_a_pending_job(): void {
		$first  = $this->queue->enqueue( array( 'payload' => '{"first":true}' ) );
		$second = $this->queue->enqueue( array( 'payload' => '{"second":true}' ) );

		// Bump the second one — it should now win.
		$bumped = $this->queue->bump( $second );
		$this->assertTrue( $bumped );

		$claimed = $this->queue->claim_next();
		$this->assertSame( $second, $claimed->id );
		$this->assertNotSame( $first, $claimed->id );
	}

	/** @testdox bump refuses to promote a job that isn't pending */
	public function test_bump_returns_false_for_in_flight_job(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}' ) );
		$this->queue->claim_next(); // moves it to in_flight

		$this->assertFalse( $this->queue->bump( $id ) );
	}

	/** @testdox find returns null for unknown ids */
	public function test_find_returns_null_for_missing_id(): void {
		$this->assertNull( $this->queue->find( 999999 ) );
	}

	private function truncate_table(): void {
		global $wpdb;
		$config = App::make( App_Config::class );
		$table  = $config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Truncate of a test-owned table.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
