<?php
/**
 * Resolves the Karkinos home-server URLs the dispatcher talks to.
 *
 * The home server sits on a rotating ISP IP that it keeps current via the
 * `local_server_ip` setting (POST /settings/local-server-ip). So the dispatch
 * and capacity URLs are *derived* from that setting over https plus a fixed
 * path — never hardcoded — unless a full-URL override constant is defined for
 * the day Karkinos moves behind a stable hostname.
 *
 *   dispatch  -> KARKINOS_DISPATCH_URL  | https://{local_server_ip}{dispatch_path}
 *   capacity  -> KARKINOS_CAPACITY_URL  | https://{local_server_ip}{capacity_path}
 *
 * Paths come from App_Config (`additional[dispatch_path|capacity_path]`).
 * Both resolvers return '' when no IP is set and no override exists — the
 * caller treats that as "no target" and skips work.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

use Karkinos\Gateway\Settings\Gateway_Settings;
use PinkCrab\Perique\Application\App_Config;

class Forward_Target {

	/**
	 * Constructor.
	 *
	 * @param App_Config       $app_config Source of truth for the URL paths.
	 * @param Gateway_Settings $settings   Holds the rotating home-server IP.
	 */
	public function __construct(
		private App_Config $app_config,
		private Gateway_Settings $settings
	) {}

	/**
	 * Full URL of the Karkinos dispatch (ingest) endpoint.
	 *
	 * @return string URL, or '' when unresolvable (no override + no IP).
	 */
	public function url(): string {
		return $this->resolve( 'KARKINOS_DISPATCH_URL', 'dispatch_path' );
	}

	/**
	 * Full URL of the Karkinos capacity/lock probe endpoint.
	 *
	 * @return string URL, or '' when unresolvable (no override + no IP).
	 */
	public function capacity_url(): string {
		return $this->resolve( 'KARKINOS_CAPACITY_URL', 'capacity_path' );
	}

	/**
	 * Resolve a target URL: override constant first, else derive from the
	 * stored IP over https + the configured path.
	 *
	 * @param string $override_constant Name of the full-URL override constant.
	 * @param string $path_alias        App_Config additional() alias for the path.
	 *
	 * @return string Resolved URL, or '' if neither source yields one.
	 */
	private function resolve( string $override_constant, string $path_alias ): string {
		$override = $this->override( $override_constant );
		if ( '' !== $override ) {
			return $override;
		}

		$ip = $this->local_server_ip();
		if ( '' === $ip ) {
			return '';
		}

		$path = (string) $this->app_config->additional( $path_alias );

		return 'https://' . $ip . $path;
	}

	/**
	 * Read a full-URL override constant, if defined and non-empty.
	 *
	 * @param string $constant Constant name.
	 *
	 * @return string Trimmed URL, or '' when undefined/empty.
	 */
	private function override( string $constant ): string {
		if ( ! defined( $constant ) ) {
			return '';
		}
		$value = constant( $constant );
		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Current home-server IP from settings, trimmed.
	 *
	 * @return string IP literal, or '' when unset.
	 */
	private function local_server_ip(): string {
		$ip = $this->settings->get( Gateway_Settings::FIELD_LOCAL_SERVER_IP );
		return is_string( $ip ) ? trim( $ip ) : '';
	}
}
