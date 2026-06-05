<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use Karkinos\Gateway\Auth\Authorised_Actors;
use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group webhook
 */
class Test_Webhook_Routes extends WP_UnitTestCase {

	private const ROUTE = '/karkinos-gateway/v1/webhooks/github';

	private App_Config $config;
	private Authorised_Actors $actors;
	private Dispatch_Queue $queue;

	public function set_up(): void {
		parent::set_up();
		$this->config = App::make( App_Config::class );
		$this->actors = App::make( Authorised_Actors::class );
		$this->queue  = App::make( Dispatch_Queue::class );

		delete_option( $this->config->additional( 'authorised_actors_option' ) );
		$this->truncate_dispatch();

		// Inline dispatch fires on an authorised delivery — stub Karkinos as
		// "busy" so the worker enqueues but sends nothing (no real network).
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
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		delete_option( $this->config->additional( 'authorised_actors_option' ) );
		delete_option( $this->config->additional( 'webhook_log_files_option' ) );
		$this->truncate_dispatch();

		$dir = (string) $this->config->path( 'webhook_logs' );
		if ( is_dir( $dir ) ) {
			foreach ( (array) glob( $dir . '/*' ) as $file ) {
				if ( is_string( $file ) && is_file( $file ) ) {
					unlink( $file );
				}
			}
			rmdir( $dir );
		}

		parent::tear_down();
	}

	/** @testdox A ping event with a valid signature returns 200 with pong:true */
	public function test_ping_event_with_valid_signature_returns_200(): void {
		$body     = wp_json_encode( array( 'zen' => 'Mind your words, they are important.' ) );
		$response = $this->dispatch( 'ping', $body, $this->sign( $body ) );

		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['ok'] );
		$this->assertTrue( $data['pong'] );
	}

	/** @testdox An authorised sender's event returns 202 and enqueues one dispatch job */
	public function test_authorised_sender_enqueues_job(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body     = $this->event_body( 'octocat' );
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertTrue( $response->get_data()['ok'] );
		$this->assertSame( 1, $this->queue->pending_count() );
	}

	/** @testdox An unauthorised sender returns 202 but enqueues nothing */
	public function test_unauthorised_sender_enqueues_nothing(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body     = $this->event_body( 'randouser' );
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 0, $this->queue->pending_count() );
	}

	/** @testdox An event with no sender returns 202 and enqueues nothing */
	public function test_no_sender_enqueues_nothing(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body = wp_json_encode(
			array(
				'action'     => 'opened',
				'repository' => array( 'full_name' => 'Pink-Crab/repo' ),
			)
		);
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 0, $this->queue->pending_count() );
	}

	/** @testdox An authorised sender adding a non-Karkinos label is not queued */
	public function test_non_karkinos_label_not_queued(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body     = $this->event_body( 'octocat', 'bug' );
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 0, $this->queue->pending_count() );

		$record = json_decode( $this->read_log_lines()[0], true );
		$this->assertTrue( $record['authorised'] );
		$this->assertFalse( $record['dispatched'] );
		$this->assertSame( 'not_karkinos_trigger', $record['dispatch_reason'] );
	}

	/** @testdox A non-labeled event from an authorised sender is not queued */
	public function test_non_labeled_event_not_queued(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body = wp_json_encode(
			array(
				'action'     => 'opened',
				'issue'      => array( 'number' => 7 ),
				'repository' => array( 'full_name' => 'Pink-Crab/repo' ),
				'sender'     => array( 'login' => 'octocat' ),
			)
		);
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 0, $this->queue->pending_count() );

		$record = json_decode( $this->read_log_lines()[0], true );
		$this->assertSame( 'not_karkinos_trigger', $record['dispatch_reason'] );
	}

	/** @testdox A [karkinos]-prefixed label not in the trigger set is not queued */
	public function test_unknown_karkinos_label_not_queued(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body     = $this->event_body( 'octocat', '[karkinos] Bogus' );
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 0, $this->queue->pending_count() );

		$record = json_decode( $this->read_log_lines()[0], true );
		$this->assertSame( 'not_karkinos_trigger', $record['dispatch_reason'] );
	}

	/** @testdox The gate decision is recorded in the log */
	public function test_gate_decision_is_logged(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );

		$body = $this->event_body( 'randouser' );
		$this->dispatch( 'issues', $body, $this->sign( $body ) );

		$record = json_decode( $this->read_log_lines()[0], true );
		$this->assertSame( 'randouser', $record['actor'] );
		$this->assertFalse( $record['authorised'] );
		$this->assertFalse( $record['dispatched'] );
		$this->assertSame( 'unauthorised_actor', $record['dispatch_reason'] );
	}

	/** @testdox A request with no signature header returns 401 */
	public function test_missing_signature_returns_401(): void {
		$response = $this->dispatch( 'ping', '{}', null );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_signature', $response->get_data()['error'] );
	}

	/** @testdox A request with a wrong signature returns 401 */
	public function test_invalid_signature_returns_401(): void {
		$body     = wp_json_encode( array( 'zen' => 'whatever' ) );
		$response = $this->dispatch( 'ping', $body, 'sha256=deadbeef' );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'invalid_signature', $response->get_data()['error'] );
	}

	/** @testdox A request signed with a different secret returns 401 */
	public function test_signature_from_wrong_secret_returns_401(): void {
		$body      = wp_json_encode( array( 'zen' => 'x' ) );
		$wrong_sig = 'sha256=' . hash_hmac( 'sha256', $body, 'not-the-real-secret' );

		$response = $this->dispatch( 'ping', $body, $wrong_sig );

		$this->assertSame( 401, $response->get_status() );
	}

	/** @testdox Every delivery is logged regardless of signature validity */
	public function test_invalid_signature_delivery_is_still_logged(): void {
		$body = wp_json_encode( array( 'zen' => 'log me anyway' ) );

		$this->dispatch( 'ping', $body, 'sha256=bogus' );

		$lines = $this->read_log_lines();
		$this->assertNotEmpty( $lines, 'Expected the invalid delivery to be logged.' );

		$record = json_decode( $lines[0], true );
		$this->assertFalse( $record['signature_valid'] );
		$this->assertSame( 'ping', $record['event'] );
	}

	/** @testdox Invalid-signature log records the body sha256 but NOT the parsed payload */
	public function test_invalid_signature_log_omits_payload(): void {
		$body = wp_json_encode( array( 'secret' => 'do-not-store-this' ) );

		$this->dispatch( 'ping', $body, 'sha256=bogus' );

		$record = json_decode( $this->read_log_lines()[0], true );

		$this->assertArrayNotHasKey( 'payload', $record, 'Unverified payloads must not be persisted.' );
		$this->assertArrayHasKey( 'body_hash', $record );
		$this->assertSame( 'sha256:' . hash( 'sha256', $body ), $record['body_hash'] );
	}

	/** @testdox Valid-signature log records the parsed payload in full */
	public function test_valid_signature_log_includes_payload(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );
		$body = $this->event_body( 'octocat' );

		$this->dispatch( 'issues', $body, $this->sign( $body ) );

		$record = json_decode( $this->read_log_lines()[0], true );

		$this->assertTrue( $record['signature_valid'] );
		$this->assertSame( 'octocat', $record['payload']['sender']['login'] );
	}

	/** @testdox A valid ping is acknowledged but not written to the log */
	public function test_valid_ping_is_not_logged(): void {
		$body = wp_json_encode( array( 'zen' => 'handshake' ) );

		$response = $this->dispatch( 'ping', $body, $this->sign( $body ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEmpty( $this->read_log_lines(), 'A verified ping must not be logged.' );
	}

	/** @testdox Noisy CI events (check_run/check_suite/workflow_job) are acked but not logged */
	public function test_ignored_ci_events_not_logged(): void {
		foreach ( array( 'check_run', 'check_suite', 'workflow_job' ) as $event ) {
			$body     = wp_json_encode( array( 'action' => 'completed' ) );
			$response = $this->dispatch( $event, $body, $this->sign( $body ) );
			$this->assertSame( 202, $response->get_status() );
		}

		$this->assertEmpty( $this->read_log_lines(), 'CI chatter must not be logged.' );
	}

	/** @testdox workflow_run is still logged (kept) but never dispatched */
	public function test_workflow_run_is_logged_not_dispatched(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );
		$body = wp_json_encode(
			array(
				'action' => 'completed',
				'sender' => array( 'login' => 'octocat' ),
			)
		);

		$response = $this->dispatch( 'workflow_run', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( 0, $this->queue->pending_count() );

		$record = json_decode( $this->read_log_lines()[0], true );
		$this->assertSame( 'workflow_run', $record['event'] );
		$this->assertSame( 'not_karkinos_trigger', $record['dispatch_reason'] );
	}

	/** @testdox A request body larger than the cap is rejected with 413 and never logged */
	public function test_oversized_body_returns_413_and_skips_logging(): void {
		// Valid JSON above the 5 MiB MAX_BODY_BYTES cap. Must parse cleanly
		// — WP's REST stack rejects malformed JSON with 400 before the route
		// handler runs, which would mask the 413 we're trying to assert.
		$big_body = (string) wp_json_encode(
			array( 'data' => str_repeat( 'a', 6 * 1024 * 1024 ) )
		);

		$response = $this->dispatch( 'ping', $big_body, $this->sign( $big_body ) );

		$this->assertSame( 413, $response->get_status() );
		$this->assertSame( 'request_too_large', $response->get_data()['error'] );
		$this->assertEmpty(
			$this->read_log_lines(),
			'Oversized requests must short-circuit before any disk I/O.'
		);
	}

	/**
	 * Build an issues/labeled event body with a given sender and label.
	 * Defaults to a Karkinos routine label (a forward trigger).
	 *
	 * @param string $sender_login Login to set as sender.
	 * @param string $label        Label name added.
	 *
	 * @return string JSON body.
	 */
	private function event_body( string $sender_login, string $label = '[karkinos] Reviewer' ): string {
		return (string) wp_json_encode(
			array(
				'action'     => 'labeled',
				'issue'      => array( 'number' => 42 ),
				'label'      => array( 'name' => $label ),
				'repository' => array( 'full_name' => 'Pink-Crab/repo' ),
				'sender'     => array( 'login' => $sender_login ),
			)
		);
	}

	private function sign( string $body ): string {
		return 'sha256=' . hash_hmac( 'sha256', $body, KARKINOS_GH_WEBHOOK_SECRET );
	}

	private function dispatch( string $event, string $body, ?string $signature ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'X-GitHub-Event', $event );
		$request->set_header( 'X-GitHub-Delivery', 'test-' . md5( $body . $event ) );
		if ( null !== $signature ) {
			$request->set_header( 'X-Hub-Signature-256', $signature );
		}
		$request->set_body( $body );

		return rest_do_request( $request );
	}

	/**
	 * Read the JSONL log file written during the test (resolved through
	 * the same option the production code wrote it to).
	 *
	 * @return string[]
	 */
	private function read_log_lines(): array {
		$map = get_option( $this->config->additional( 'webhook_log_files_option' ), array() );
		if ( ! is_array( $map ) || empty( $map ) ) {
			return array();
		}

		$first = reset( $map );
		$files = is_array( $first ) ? $first : array( (string) $first );

		$lines = array();
		foreach ( $files as $filename ) {
			$path = $this->config->path( 'webhook_logs' ) . '/' . $filename;
			if ( is_file( $path ) ) {
				$contents = (string) file_get_contents( $path );
				foreach ( array_values( array_filter( explode( "\n", $contents ) ) ) as $line ) {
					$lines[] = $line;
				}
			}
		}

		return $lines;
	}

	private function truncate_dispatch(): void {
		global $wpdb;
		$table = $this->config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Truncate of a test-owned table.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
