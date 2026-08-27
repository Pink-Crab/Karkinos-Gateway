<?php
/**
 * Integration tests for the URL-reports proxy endpoint.
 *
 * The route forwards rather than acts, so these assert on what leaves: the
 * auth gate, the envelope shape, that task/meta pass through untouched, that
 * caller cannot be spoofed, and that the tool's answer is relayed.
 *
 * Outbound HTTP is stubbed at pre_http_request — nothing leaves the process.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group reports
 */
class Test_Reports_Routes extends WP_UnitTestCase {

	private const ROUTE = '/karkinos-gateway/v1/reports/run';

	private int $editor_id = 0;

	/** @var list<array<string, mixed>> Requests the stub intercepted. */
	private array $sent = array();

	/** @var array{code:int, body:string} What the stub answers with. */
	private array $reply = array(
		'code' => 202,
		'body' => '{"ok":true,"job_id":7}',
	);

	public function set_up(): void {
		parent::set_up();
		$this->editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		add_filter( 'pre_http_request', array( $this, 'stub_http' ), 10, 3 );
	}

	public function tear_down(): void {
		remove_filter( 'pre_http_request', array( $this, 'stub_http' ), 10 );
		wp_set_current_user( 0 );
		$this->sent = array();
		parent::tear_down();
	}

	/**
	 * pre_http_request stub — records the call, answers with $this->reply.
	 *
	 * @param mixed                $preempt Short-circuit value.
	 * @param array<string, mixed> $args    Request args.
	 * @param string               $url     Request URL.
	 *
	 * @return array<string, mixed> Faked wp_remote response.
	 */
	public function stub_http( $preempt, $args, $url ): array {
		$this->sent[] = array(
			'url'  => $url,
			'args' => $args,
		);

		return array(
			'headers'  => array(),
			'body'     => $this->reply['body'],
			'response' => array(
				'code'    => $this->reply['code'],
				'message' => '',
			),
		);
	}

	/** @testdox An unauthenticated POST is refused and forwards nothing */
	public function test_unauthenticated_is_refused(): void {
		$response = $this->post( array( 'task' => 'csv' ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( array(), $this->sent );
	}

	/** @testdox task and meta are forwarded exactly as sent */
	public function test_task_and_meta_pass_through_untouched(): void {
		wp_set_current_user( $this->editor_id );

		$this->post(
			array(
				'task' => 'csv',
				'meta' => array(
					'file'  => 'glynn-batch2-20260827-115615.csv',
					'extra' => array( 'kept' => true ),
				),
			)
		);

		$forwarded = $this->forwarded_body();

		$this->assertSame( 'csv', $forwarded['task'] );
		$this->assertSame(
			array(
				'file'  => 'glynn-batch2-20260827-115615.csv',
				'extra' => array( 'kept' => true ),
			),
			$forwarded['meta']
		);
	}

	/** @testdox An unknown task is forwarded, not judged here */
	public function test_unknown_task_is_still_forwarded(): void {
		wp_set_current_user( $this->editor_id );

		$this->post( array( 'task' => 'something-the-tool-owns' ) );

		$this->assertSame( 'something-the-tool-owns', $this->forwarded_body()['task'] );
	}

	/** @testdox caller is added and reflects the authenticated user */
	public function test_caller_is_added(): void {
		wp_set_current_user( $this->editor_id );

		$this->post( array( 'task' => 'csv' ) );

		$caller = $this->forwarded_body()['caller'];

		$this->assertSame( $this->editor_id, $caller['id'] );
		$this->assertSame( wp_get_current_user()->user_login, $caller['user'] );
		$this->assertArrayHasKey( 'ip', $caller );
		$this->assertNotEmpty( $caller['at'] );
	}

	/** @testdox A caller block in the request body is discarded, not trusted */
	public function test_caller_cannot_be_spoofed(): void {
		wp_set_current_user( $this->editor_id );

		$this->post(
			array(
				'task'   => 'csv',
				'caller' => array(
					'id'   => 999999,
					'user' => 'administrator',
					'ip'   => '203.0.113.7',
				),
			)
		);

		$caller = $this->forwarded_body()['caller'];

		$this->assertSame( $this->editor_id, $caller['id'] );
		$this->assertNotSame( 'administrator', $caller['user'] );
		$this->assertNotSame( '203.0.113.7', $caller['ip'] );
	}

	/** @testdox The tool's status and body are relayed to the caller */
	public function test_tool_response_is_relayed(): void {
		wp_set_current_user( $this->editor_id );
		$this->reply = array(
			'code' => 400,
			'body' => '{"error":"unknown_task"}',
		);

		$response = $this->post( array( 'task' => 'nope' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( array( 'error' => 'unknown_task' ), $response->get_data() );
	}

	/** @testdox An unreachable tool is a 500 so the caller owns the retry */
	public function test_unreachable_tool_is_a_500(): void {
		wp_set_current_user( $this->editor_id );

		remove_filter( 'pre_http_request', array( $this, 'stub_http' ), 10 );
		add_filter(
			'pre_http_request',
			static fn() => new \WP_Error( 'http_request_failed', 'Connection refused' ),
			10
		);

		$response = $this->post( array( 'task' => 'csv' ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'reports_tool_unreachable', $response->get_data()['error'] );
	}

	/**
	 * The decoded JSON body of the single forwarded request.
	 *
	 * @return array<string, mixed>
	 */
	private function forwarded_body(): array {
		$this->assertCount( 1, $this->sent, 'expected exactly one forwarded request' );

		return (array) json_decode( (string) $this->sent[0]['args']['body'], true );
	}

	/**
	 * POST a JSON body to the reports route.
	 *
	 * @param array<string, mixed> $body Request body.
	 *
	 * @return WP_REST_Response
	 */
	private function post( array $body ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $body ) );

		return rest_do_request( $request );
	}
}
