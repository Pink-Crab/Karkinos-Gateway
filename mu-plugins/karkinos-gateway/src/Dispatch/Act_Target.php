<?php
/**
 * Resolves the Actions-tool endpoint the act dispatcher talks to.
 *
 * The Actions tool (nektos/act runner) lives on the tools box behind a
 * Cloudflare Tunnel, so unlike Karkinos it has a STABLE public hostname and a
 * real CA-issued certificate. That means:
 *
 *   - the URL is a plain wp-config constant, not derived from a rotating IP
 *   - TLS verification is ordinary (no cert pinning, no hostname override)
 *   - the only credential is HTTP basic auth, enforced by nginx on the box
 *
 *   KARKINOS_ACT_URL   https://tools.pinkcrab.co.uk/actions/api.php
 *   KARKINOS_ACT_USER  basic-auth user
 *   KARKINOS_ACT_PASS  basic-auth password
 *
 * All three must be present; a partially configured target reports itself as
 * unconfigured so the worker leaves act jobs queued rather than firing
 * unauthenticated requests at the tunnel.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

class Act_Target {

	/** Query arg that enqueues a PR run on the Actions tool. */
	public const ACTION_RUN_PR = 'run_pr';

	/**
	 * Full URL for a given Actions-tool action.
	 *
	 * @param string $action Value for the tool's `a` query arg.
	 *
	 * @return string URL, or '' when the target is not fully configured.
	 */
	public function url( string $action = self::ACTION_RUN_PR ): string {
		$base = $this->constant( 'KARKINOS_ACT_URL' );
		if ( '' === $base || ! $this->is_configured() ) {
			return '';
		}

		return add_query_arg( 'a', $action, $base );
	}

	/**
	 * Is every credential needed to call the tool present?
	 *
	 * @return bool True only when URL, user and password are all non-empty.
	 */
	public function is_configured(): bool {
		return '' !== $this->constant( 'KARKINOS_ACT_URL' )
			&& '' !== $this->constant( 'KARKINOS_ACT_USER' )
			&& '' !== $this->constant( 'KARKINOS_ACT_PASS' );
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
			$this->constant( 'KARKINOS_ACT_USER' ) . ':' . $this->constant( 'KARKINOS_ACT_PASS' )
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
