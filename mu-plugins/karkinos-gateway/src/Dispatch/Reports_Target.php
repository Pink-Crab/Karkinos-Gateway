<?php
/**
 * Resolves the URL-reports tool endpoint the reports dispatcher talks to.
 *
 * The URL-reports tool profiles a CSV of sites through site-eyes and writes a
 * markdown report per site back to the FTP it read the CSV from. It lives on
 * the same tools box as the Actions tool, behind the same Cloudflare Tunnel
 * and the same nginx basic auth, so this mirrors Act_Target exactly:
 *
 *   - the URL is a plain wp-config constant, not derived from a rotating IP
 *   - TLS verification is ordinary (no cert pinning, no hostname override)
 *   - the only credential is HTTP basic auth, enforced by nginx on the box
 *
 *   KARKINOS_REPORTS_URL   https://tools.pinkcrab.co.uk/url-reports/api.php
 *   KARKINOS_REPORTS_USER  basic-auth user
 *   KARKINOS_REPORTS_PASS  basic-auth password
 *
 * The credentials are the same pair as KARKINOS_ACT_*, because nginx applies
 * basic auth at server level across the whole site. They are kept as separate
 * constants rather than shared so that either tool can be pointed elsewhere,
 * or have its access revoked, without disturbing the other.
 *
 * All three must be present; a partially configured target reports itself as
 * unconfigured so the worker leaves reports jobs queued rather than firing
 * unauthenticated requests at the tunnel.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

class Reports_Target {

	/** Query arg that enqueues a CSV run on the URL-reports tool. */
	public const ACTION_RUN = 'run';

	/**
	 * Full URL for a given URL-reports-tool action.
	 *
	 * @param string $action Value for the tool's `a` query arg.
	 *
	 * @return string URL, or '' when the target is not fully configured.
	 */
	public function url( string $action = self::ACTION_RUN ): string {
		$base = $this->constant( 'KARKINOS_REPORTS_URL' );
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
		return '' !== $this->constant( 'KARKINOS_REPORTS_URL' )
			&& '' !== $this->constant( 'KARKINOS_REPORTS_USER' )
			&& '' !== $this->constant( 'KARKINOS_REPORTS_PASS' );
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
			$this->constant( 'KARKINOS_REPORTS_USER' ) . ':' . $this->constant( 'KARKINOS_REPORTS_PASS' )
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
