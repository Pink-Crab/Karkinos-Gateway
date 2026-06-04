<?php
/**
 * Integration tests for the GitHub org-members client.
 *
 * Outbound HTTP is stubbed via the pre_http_request filter — no real network.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Auth;

use Karkinos\Gateway\Auth\Org_Members_Client;
use WP_Error;
use WP_UnitTestCase;

/**
 * @group integration
 * @group auth
 */
class Test_Org_Members_Client extends WP_UnitTestCase {

	private Org_Members_Client $client;

	/** @var array<int, array<string, mixed>> Captured request args, one per call. */
	private array $captured = array();

	public function set_up(): void {
		parent::set_up();
		$this->client   = new Org_Members_Client();
		$this->captured = array();
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		parent::tear_down();
	}

	/** @testdox fetch_member_logins follows Link pagination and collects every login */
	public function test_fetches_and_paginates(): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) {
				$this->captured[] = $args;

				if ( str_contains( (string) $url, 'page=2' ) ) {
					return $this->ok( array( array( 'login' => 'second' ) ) );
				}

				return $this->ok(
					array( array( 'login' => 'first' ) ),
					'<https://api.github.com/orgs/Pink-Crab/members?per_page=100&page=2>; rel="next"'
				);
			},
			10,
			3
		);

		$logins = $this->client->fetch_member_logins( 'Pink-Crab' );

		$this->assertSame( array( 'first', 'second' ), $logins );
		$this->assertCount( 2, $this->captured );
	}

	/** @testdox the bearer token and user-agent are sent */
	public function test_sends_auth_headers(): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args ) {
				$this->captured[] = $args;
				return $this->ok( array() );
			},
			10,
			3
		);

		$this->client->fetch_member_logins( 'Pink-Crab' );

		$headers = $this->captured[0]['headers'];
		$this->assertSame( 'Bearer phpunit-gh-token', $headers['Authorization'] );
		$this->assertSame( 'karkinos-gateway', $headers['User-Agent'] );
	}

	/** @testdox a non-200 response yields a WP_Error */
	public function test_non_200_returns_wp_error(): void {
		add_filter(
			'pre_http_request',
			fn() => array(
				'response' => array( 'code' => 403 ),
				'body'     => '{"message":"Forbidden"}',
				'headers'  => array(),
			),
			10,
			3
		);

		$result = $this->client->fetch_member_logins( 'Pink-Crab' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'karkinos_gateway_gh_http_error', $result->get_error_code() );
	}

	/** @testdox a transport error is returned as-is */
	public function test_transport_error_returns_wp_error(): void {
		add_filter(
			'pre_http_request',
			fn() => new WP_Error( 'http_request_failed', 'boom' ),
			10,
			3
		);

		$result = $this->client->fetch_member_logins( 'Pink-Crab' );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Build a stubbed 200 response.
	 *
	 * @param array<int, array<string, string>> $members Member rows.
	 * @param string                            $link    Optional Link header value.
	 *
	 * @return array<string, mixed>
	 */
	private function ok( array $members, string $link = '' ): array {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => (string) wp_json_encode( $members ),
			'headers'  => '' !== $link ? array( 'link' => $link ) : array(),
		);
	}
}
