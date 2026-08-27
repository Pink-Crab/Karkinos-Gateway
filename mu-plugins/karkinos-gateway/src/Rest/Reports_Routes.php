<?php
/**
 * Proxy endpoint that relays a task envelope to the URL-reports tool.
 *
 * Single POST at `karkinos-gateway/v1/reports/run`. The caller sends:
 *
 *     { "task": "csv", "meta": { "file": "whatever.csv" } }
 *
 * and the tool receives the same envelope with who asked added:
 *
 *     { "task": …, "meta": { … }, "caller": { ip, user, id, agent, at } }
 *
 * Authenticate, add `caller`, forward, relay the answer. No queue, no retry,
 * no validation — the tool owns the task list, the shape of each task's meta
 * and every decision about what is acceptable, so a new task is a change at
 * the tool only. `task` and `meta` are passed through exactly as sent.
 *
 * `caller` is stated here, not by the caller: the incoming body's own `caller`
 * key is dropped and the caller's headers are never forwarded.
 *
 * If the tool cannot be reached the caller gets a 500 and owns the retry.
 *
 * Auth is a WP user with `edit_posts` — an application password, as
 * Ingest_Routes does it. Give each caller its own user so access can be
 * revoked individually and runs are attributable.
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\Dispatch\Reports_Target;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use WP_REST_Request;
use WP_REST_Response;

class Reports_Routes extends Route_Controller {

	/** @var ?string Shared REST namespace. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	/** Timeout (seconds) for the forward. The tool only enqueues and answers. */
	private const FORWARD_TIMEOUT = 10;

	/**
	 * Constructor.
	 *
	 * @param Reports_Target $target Resolves the URL-reports-tool URL + basic auth.
	 */
	public function __construct( private Reports_Target $target ) {}

	/**
	 * Declare the reports routes this controller owns.
	 *
	 * No arguments are declared: the envelope is relayed as sent, so declaring
	 * it here would only create a second, stale copy of the tool's contract.
	 *
	 * @param Route_Factory $factory Pre-configured with the namespace.
	 *
	 * @return array<int, mixed> Route definitions to register.
	 */
	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/reports/run', array( $this, 'run' ) )
				->authentication( array( $this, 'check_auth' ) ),
		);
	}

	/**
	 * Authentication callback — a WP application password with `edit_posts`.
	 *
	 * @param WP_REST_Request $request Unused; signature required by authentication().
	 *
	 * @return bool True if the current user holds the `edit_posts` capability.
	 */
	public function check_auth( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Relay the envelope to the tool and return its answer.
	 *
	 * @param WP_REST_Request $request Inbound request.
	 *
	 * @return WP_REST_Response The tool's status and body, or 500 if unreachable.
	 */
	public function run( WP_REST_Request $request ): WP_REST_Response {
		$target = $this->target->url();
		if ( '' === $target ) {
			return new WP_REST_Response( array( 'error' => 'no_reports_target' ), 500 );
		}

		$incoming = json_decode( $request->get_body(), true );
		$incoming = is_array( $incoming ) ? $incoming : array();

		$body = (string) wp_json_encode(
			array(
				'task'   => $incoming['task'] ?? null,
				'meta'   => (object) ( is_array( $incoming['meta'] ?? null ) ? $incoming['meta'] : array() ),
				'caller' => $this->caller( $request ),
			),
			JSON_UNESCAPED_SLASHES
		);

		$response = wp_remote_post(
			$target,
			array(
				'timeout'   => self::FORWARD_TIMEOUT,
				'sslverify' => true,
				'body'      => $body,
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => $this->target->auth_header(),
					'Accept'        => 'application/json',
					'User-Agent'    => 'karkinos-gateway',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				array(
					'error'  => 'reports_tool_unreachable',
					'detail' => $response->get_error_message(),
				),
				500
			);
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return new WP_REST_Response(
			is_array( $decoded ) ? $decoded : array( 'error' => 'reports_tool_bad_response' ),
			$code >= 100 ? $code : 500
		);
	}

	/**
	 * Who asked for this.
	 *
	 * @param WP_REST_Request $request Inbound request.
	 *
	 * @return array<string, mixed> Caller block for the envelope.
	 */
	private function caller( WP_REST_Request $request ): array {
		$user = wp_get_current_user();

		return array(
			'ip'    => $this->caller_ip(),
			'user'  => (string) $user->user_login,
			'id'    => (int) $user->ID,
			'agent' => mb_substr( (string) $request->get_header( 'user-agent' ), 0, 200 ),
			'at'    => gmdate( 'c' ),
		);
	}

	/**
	 * Best available client IP.
	 *
	 * ops sits behind Cloudflare, so `CF-Connecting-IP` is the real client and
	 * REMOTE_ADDR is an edge node. Cloudflare sets that header itself and
	 * strips any the client sends, unlike X-Forwarded-For which anyone may set.
	 *
	 * @return string IP, or '' when none could be read.
	 */
	private function caller_ip(): string {
		$cf = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) )
			: '';
		if ( '' !== $cf && false !== filter_var( $cf, FILTER_VALIDATE_IP ) ) {
			return $cf;
		}

		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		return false !== filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '';
	}
}
