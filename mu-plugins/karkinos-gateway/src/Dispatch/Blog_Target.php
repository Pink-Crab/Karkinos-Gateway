<?php
/**
 * Resolves the blog endpoint the stub-release sync writes to.
 *
 * The blog is an ordinary public WordPress site with a stable hostname and a
 * real certificate, so like the Actions tool there is nothing to pin — the
 * URL is a plain wp-config constant and the credential is a WP application
 * password sent as HTTP basic auth.
 *
 *   KARKINOS_BLOG_URL        https://glynnquelch.co.uk
 *   KARKINOS_BLOG_USER       WP username the application password belongs to
 *   KARKINOS_BLOG_PASS       WP application password
 *   KARKINOS_BLOG_POST_ID    ID of the post holding the stubs section
 *   KARKINOS_BLOG_REST_BASE  optional REST base of the post's type (default
 *                            'posts'; a custom post type uses its own base,
 *                            e.g. 'software')
 *
 * All four must be present; a partially configured target reports itself as
 * unconfigured so the worker leaves blog jobs queued rather than firing
 * unauthenticated requests at the site.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

class Blog_Target {

	/**
	 * REST endpoint for the post holding the stubs section.
	 *
	 * @return string URL, or '' when the target is not fully configured.
	 */
	public function post_endpoint(): string {
		if ( ! $this->is_configured() ) {
			return '';
		}

		return trailingslashit( $this->constant( 'KARKINOS_BLOG_URL' ) )
			. 'wp-json/wp/v2/' . $this->rest_base() . '/' . $this->post_id();
	}

	/**
	 * REST base of the target post's type. A custom post type registers its
	 * own base (show_in_rest / rest_base), so this is configurable.
	 *
	 * @return string Base from KARKINOS_BLOG_REST_BASE, or 'posts'.
	 */
	public function rest_base(): string {
		$base = trim( $this->constant( 'KARKINOS_BLOG_REST_BASE' ), '/' );
		return '' !== $base ? $base : 'posts';
	}

	/**
	 * Is every credential needed to update the post present?
	 *
	 * @return bool True only when URL, user, password and post ID are all set.
	 */
	public function is_configured(): bool {
		return '' !== $this->constant( 'KARKINOS_BLOG_URL' )
			&& '' !== $this->constant( 'KARKINOS_BLOG_USER' )
			&& '' !== $this->constant( 'KARKINOS_BLOG_PASS' )
			&& 0 < $this->post_id();
	}

	/**
	 * The target post ID from wp-config.
	 *
	 * @return int Post ID, or 0 when undefined/not numeric.
	 */
	public function post_id(): int {
		if ( ! defined( 'KARKINOS_BLOG_POST_ID' ) ) {
			return 0;
		}
		$id = constant( 'KARKINOS_BLOG_POST_ID' );
		return is_numeric( $id ) ? (int) $id : 0;
	}

	/**
	 * Value for the Authorization header.
	 *
	 * @return string `Basic <base64>`, or '' when not configured.
	 */
	public function auth_header(): string {
		if ( ! $this->is_configured() ) {
			return '';
		}

		// base64 is the wire format RFC 7617 mandates here, not obfuscation.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return 'Basic ' . base64_encode(
			$this->constant( 'KARKINOS_BLOG_USER' ) . ':' . $this->constant( 'KARKINOS_BLOG_PASS' )
		);
	}

	/**
	 * Read a wp-config constant as a trimmed string.
	 *
	 * @param string $name Constant name.
	 *
	 * @return string Trimmed value, or '' when undefined or not a string.
	 */
	private function constant( string $name ): string {
		if ( ! defined( $name ) ) {
			return '';
		}
		$value = constant( $name );
		return is_string( $value ) ? trim( $value ) : '';
	}
}
