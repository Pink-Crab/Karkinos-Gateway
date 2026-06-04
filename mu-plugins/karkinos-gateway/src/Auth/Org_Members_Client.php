<?php
/**
 * Minimal GitHub REST client for org membership.
 *
 * Deliberately a thin `wp_remote_get` wrapper rather than a full SDK — the
 * only call needed is "list the members of an org", and going through WP's
 * HTTP API keeps the project dependency-light and lets the outbound-HTTP
 * house rules (timeout, is_wp_error, user-agent) apply uniformly.
 *
 * Authentication uses the PAT in the `KARKINOS_GH_API_TOKEN` constant
 * (needs the `read:org` scope). Pagination follows the `Link: rel="next"`
 * header until exhausted.
 *
 * @package Karkinos\Gateway\Auth
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Auth;

use WP_Error;

class Org_Members_Client {

	/** GitHub REST API base. */
	private const API_BASE = 'https://api.github.com';

	/** Per-page cap GitHub allows for list endpoints. */
	private const PER_PAGE = 100;

	/** Hard stop on pages followed, so a runaway Link chain can't loop forever. */
	private const MAX_PAGES = 50;

	/** Request timeout in seconds. */
	private const TIMEOUT = 10;

	/**
	 * Fetch every member login for an org.
	 *
	 * @param string $org Org slug (e.g. 'Pink-Crab').
	 *
	 * @return list<string>|WP_Error Lowercased-as-returned login list, or a
	 *                               WP_Error on missing token / transport
	 *                               failure / non-200 response.
	 */
	public function fetch_member_logins( string $org ): array|WP_Error {
		$token = $this->token();
		if ( null === $token ) {
			return new WP_Error(
				'karkinos_gateway_missing_gh_token',
				'KARKINOS_GH_API_TOKEN is not defined; cannot sync the org roster.'
			);
		}

		$org = trim( $org );
		if ( '' === $org ) {
			return new WP_Error( 'karkinos_gateway_missing_org', 'No GitHub org configured.' );
		}

		$url    = sprintf( '%s/orgs/%s/members?per_page=%d', self::API_BASE, rawurlencode( $org ), self::PER_PAGE );
		$logins = array();
		$pages  = 0;

		while ( '' !== $url && $pages < self::MAX_PAGES ) {
			++$pages;

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'Authorization'        => 'Bearer ' . $token,
						'Accept'               => 'application/vnd.github+json',
						'X-GitHub-Api-Version' => '2022-11-28',
						'User-Agent'           => 'karkinos-gateway',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				return new WP_Error(
					'karkinos_gateway_gh_http_error',
					sprintf( 'GitHub returned HTTP %d while listing members of %s.', $code, $org ),
					array( 'status' => $code )
				);
			}

			$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $decoded ) ) {
				return new WP_Error(
					'karkinos_gateway_gh_bad_payload',
					'GitHub members response was not a JSON array.'
				);
			}

			foreach ( $decoded as $member ) {
				if ( is_array( $member ) && isset( $member['login'] ) && is_string( $member['login'] ) ) {
					$logins[] = $member['login'];
				}
			}

			$url = $this->next_page_url( wp_remote_retrieve_header( $response, 'link' ) );
		}

		return array_values( array_unique( $logins ) );
	}

	/**
	 * Resolve the PAT from the wp-config constant.
	 *
	 * @return string|null Non-empty token string, or null if absent/empty.
	 */
	private function token(): ?string {
		if ( ! defined( 'KARKINOS_GH_API_TOKEN' ) ) {
			return null;
		}
		$token = constant( 'KARKINOS_GH_API_TOKEN' );
		return is_string( $token ) && '' !== $token ? $token : null;
	}

	/**
	 * Extract the `rel="next"` URL from a GitHub `Link` header, if present.
	 *
	 * @param string|array<int, string> $link_header Raw Link header value(s).
	 *
	 * @return string Next-page URL, or '' when there is no next page.
	 */
	private function next_page_url( string|array $link_header ): string {
		$link = is_array( $link_header ) ? implode( ', ', $link_header ) : $link_header;
		if ( '' === $link ) {
			return '';
		}

		// Each segment looks like: <https://api.github.com/...&page=2>; rel="next"
		foreach ( explode( ',', $link ) as $segment ) {
			if ( ! str_contains( $segment, 'rel="next"' ) ) {
				continue;
			}
			if ( preg_match( '/<([^>]+)>/', $segment, $matches ) ) {
				return trim( $matches[1] );
			}
		}

		return '';
	}
}
