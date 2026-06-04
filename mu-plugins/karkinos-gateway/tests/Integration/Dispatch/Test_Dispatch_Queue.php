<?php
/**
 * Integration tests for the dispatch queue.
 *
 * Exercises the two-state model (dispatched_at NULL vs set) and the SSRF
 * guard against a real MySQL table (the WP-PHPUnit install creates the
 * dispatch_jobs table via Migrations_Runner on init).
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

	/** @testdox enqueue inserts an undispatched row and returns its id */
	public function test_enqueue_returns_id_and_undispatched_state(): void {
		$id = $this->queue->enqueue(
			array(
				'payload' => '{"action":"opened"}',
				'source'  => 'github',
				'event'   => 'issues',
			)
		);

		$this->assertGreaterThan( 0, $id );

		$job = $this->queue->find( $id );
		$this->assertInstanceOf( Dispatch_Job::class, $job );
		$this->assertFalse( $job->is_dispatched() );
		$this->assertNull( $job->dispatched_at );
		$this->assertSame( 'github', $job->source );
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

	/** @testdox next returns the oldest undispatched job */
	public function test_next_returns_oldest_undispatched(): void {
		$first = $this->queue->enqueue( array( 'payload' => '{"n":1}' ) );
		usleep( 1100000 ); // 1.1s — guarantees a distinct created_at.
		$this->queue->enqueue( array( 'payload' => '{"n":2}' ) );

		$next = $this->queue->next();
		$this->assertNotNull( $next );
		$this->assertSame( $first, $next->id );
	}

	/** @testdox next returns null when the queue is empty */
	public function test_next_returns_null_when_empty(): void {
		$this->assertNull( $this->queue->next() );
	}

	/** @testdox a dispatched job is skipped; next moves on to the following one */
	public function test_next_skips_dispatched_jobs(): void {
		$first  = $this->queue->enqueue( array( 'payload' => '{"n":1}' ) );
		usleep( 1100000 );
		$second = $this->queue->enqueue( array( 'payload' => '{"n":2}' ) );

		$this->assertSame( $first, $this->queue->next()->id );

		$this->queue->mark_dispatched( $first, 200, '{"ok":true}' );

		$next = $this->queue->next();
		$this->assertNotNull( $next );
		$this->assertSame( $second, $next->id );
	}

	/** @testdox mark_dispatched stamps dispatched_at and records the response */
	public function test_mark_dispatched_records_response(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}' ) );

		$this->queue->mark_dispatched( $id, 200, '{"ok":true}' );

		$reloaded = $this->queue->find( $id );
		$this->assertTrue( $reloaded->is_dispatched() );
		$this->assertNotNull( $reloaded->dispatched_at );
		$this->assertSame( 200, $reloaded->response_status );
		$this->assertSame( '{"ok":true}', $reloaded->response_body );
		$this->assertSame( '', $reloaded->error );
	}

	/** @testdox mark_dispatched records an error note for a permanent reject */
	public function test_mark_dispatched_records_reject_error(): void {
		$id = $this->queue->enqueue( array( 'payload' => '{}' ) );

		$this->queue->mark_dispatched( $id, 400, '{"error":"bad"}', 'rejected' );

		$reloaded = $this->queue->find( $id );
		$this->assertTrue( $reloaded->is_dispatched() );
		$this->assertSame( 400, $reloaded->response_status );
		$this->assertSame( 'rejected', $reloaded->error );
	}

	/** @testdox pending_count reflects only undispatched jobs */
	public function test_pending_count(): void {
		$this->assertSame( 0, $this->queue->pending_count() );

		$a = $this->queue->enqueue( array( 'payload' => '{}' ) );
		$this->queue->enqueue( array( 'payload' => '{}' ) );
		$this->assertSame( 2, $this->queue->pending_count() );

		$this->queue->mark_dispatched( $a, 200, '' );
		$this->assertSame( 1, $this->queue->pending_count() );
	}

	/** @testdox find returns null for unknown ids */
	public function test_find_returns_null_for_missing_id(): void {
		$this->assertNull( $this->queue->find( 999999 ) );
	}

	/** @testdox enqueue rejects a target_url that points at localhost */
	public function test_enqueue_rejects_localhost(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'http://localhost/x' ) )
		);
	}

	/** @testdox enqueue rejects a target_url that points at the loopback IP */
	public function test_enqueue_rejects_loopback_ip(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'http://127.0.0.1/x' ) )
		);
	}

	/** @testdox enqueue rejects RFC 1918 private IPs (10/8) */
	public function test_enqueue_rejects_private_10_range(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'http://10.0.0.1/x' ) )
		);
	}

	/** @testdox enqueue rejects RFC 1918 private IPs (192.168/16) */
	public function test_enqueue_rejects_private_192_range(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'http://192.168.1.1/x' ) )
		);
	}

	/** @testdox enqueue rejects the AWS metadata service IP (link-local) */
	public function test_enqueue_rejects_aws_metadata_ip(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue(
				array( 'payload' => '{}', 'target_url' => 'http://169.254.169.254/latest/meta-data/' )
			)
		);
	}

	/** @testdox enqueue rejects schemes other than http/https */
	public function test_enqueue_rejects_non_http_scheme(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'file:///etc/passwd' ) )
		);
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'gopher://x/' ) )
		);
	}

	/** @testdox enqueue rejects .local hostnames (mDNS) */
	public function test_enqueue_rejects_dotlocal_hostname(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue(
				array( 'payload' => '{}', 'target_url' => 'http://my-server.local/webhook' )
			)
		);
	}

	/** @testdox enqueue rejects IPv6 loopback */
	public function test_enqueue_rejects_ipv6_loopback(): void {
		$this->assertSame(
			0,
			$this->queue->enqueue( array( 'payload' => '{}', 'target_url' => 'http://[::1]/x' ) )
		);
	}

	/** @testdox enqueue allows a public https URL */
	public function test_enqueue_allows_public_https_url(): void {
		$id = $this->queue->enqueue(
			array( 'payload' => '{}', 'target_url' => 'https://example.com/webhook' )
		);
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( 'https://example.com/webhook', $this->queue->find( $id )->target_url );
	}

	private function truncate_table(): void {
		global $wpdb;
		$config = App::make( App_Config::class );
		$table  = $config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Truncate of a test-owned table.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
