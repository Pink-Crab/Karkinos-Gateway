<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use Karkinos\Gateway\Logging\Webhook_Logger;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group webhook
 */
class Test_Webhook_Routes extends WP_UnitTestCase {

	private const ROUTE = '/karkinos-gateway/v1/webhooks/github';

	public function tear_down(): void {
		delete_option( Webhook_Logger::OPTION_LOG_FILES );

		$dir = WP_CONTENT_DIR . '/karkinos-gateway-logs';
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

	/** @testdox An issues event with a valid signature returns 202 Accepted */
	public function test_issues_event_with_valid_signature_returns_202(): void {
		$body     = wp_json_encode(
			array(
				'action'     => 'opened',
				'issue'      => array( 'number' => 42 ),
				'repository' => array( 'full_name' => 'org/repo' ),
			)
		);
		$response = $this->dispatch( 'issues', $body, $this->sign( $body ) );

		$this->assertSame( 202, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'issues', $data['event'] );
		$this->assertSame( 'opened', $data['action'] );
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
		$body         = wp_json_encode( array( 'zen' => 'x' ) );
		$wrong_sig    = 'sha256=' . hash_hmac( 'sha256', $body, 'not-the-real-secret' );

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
		$map = get_option( Webhook_Logger::OPTION_LOG_FILES, array() );
		if ( ! is_array( $map ) || empty( $map ) ) {
			return array();
		}

		$filename = (string) reset( $map );
		$path     = WP_CONTENT_DIR . '/karkinos-gateway-logs/' . $filename;

		if ( ! is_file( $path ) ) {
			return array();
		}

		$contents = (string) file_get_contents( $path );
		$lines    = array_values( array_filter( explode( "\n", $contents ) ) );

		return $lines;
	}
}
