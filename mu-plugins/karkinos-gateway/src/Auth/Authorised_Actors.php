<?php
/**
 * Locally cached roster of GitHub actors allowed to trigger a forward.
 *
 * Backed by a single non-autoloaded option (key resolved via App_Config under
 * `additional[authorised_actors_option]`). The stored shape is:
 *
 *   array{ actors: list<string>, org: string, synced_at: ?string }
 *
 * Logins are normalised to lowercase on write and compared case-insensitively
 * on read — GitHub logins are case-insensitive. The roster is the allow-list
 * the webhook receiver gates `sender.login` against.
 *
 * @package Karkinos\Gateway\Auth
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Auth;

use PinkCrab\Perique\Application\App_Config;

class Authorised_Actors {

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Source of truth for the option key
	 *                               (additional('authorised_actors_option')).
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Is this GitHub login currently authorised?
	 *
	 * Case-insensitive. An empty login is never authorised.
	 *
	 * @param string $login GitHub login (e.g. payload sender.login).
	 *
	 * @return bool True if the login is in the cached roster.
	 */
	public function is_authorised( string $login ): bool {
		$login = strtolower( trim( $login ) );
		if ( '' === $login ) {
			return false;
		}

		return in_array( $login, $this->actors(), true );
	}

	/**
	 * Replace the entire roster and stamp the sync time.
	 *
	 * Logins are lowercased, trimmed, de-duplicated, and empties dropped before
	 * persisting. Stored not-autoloaded.
	 *
	 * @param list<string> $logins Fresh set of authorised logins.
	 * @param string       $org    Org the roster was pulled from.
	 *
	 * @return void
	 */
	public function replace( array $logins, string $org ): void {
		$clean = array();
		foreach ( $logins as $login ) {
			if ( ! is_string( $login ) ) {
				continue;
			}
			$login = strtolower( trim( $login ) );
			if ( '' !== $login && ! in_array( $login, $clean, true ) ) {
				$clean[] = $login;
			}
		}

		update_option(
			$this->option_name(),
			array(
				'actors'    => $clean,
				'org'       => $org,
				'synced_at' => gmdate( 'c' ),
			),
			false
		);
	}

	/**
	 * Full list of authorised logins (lowercased).
	 *
	 * @return list<string>
	 */
	public function all(): array {
		return $this->actors();
	}

	/**
	 * Number of authorised logins currently cached.
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->actors() );
	}

	/**
	 * Timestamp of the last successful sync, or null if never synced.
	 *
	 * @return string|null ISO-8601 UTC string, or null.
	 */
	public function synced_at(): ?string {
		$record = $this->record();
		$value  = $record['synced_at'] ?? null;
		return is_string( $value ) && '' !== $value ? $value : null;
	}

	/**
	 * Org the roster was last pulled from, or '' if never synced.
	 *
	 * @return string
	 */
	public function org(): string {
		$record = $this->record();
		return is_string( $record['org'] ?? null ) ? $record['org'] : '';
	}

	/**
	 * The cached login list, defensively normalised on read.
	 *
	 * @return list<string>
	 */
	private function actors(): array {
		$record = $this->record();
		$actors = $record['actors'] ?? array();
		if ( ! is_array( $actors ) ) {
			return array();
		}

		$out = array();
		foreach ( $actors as $login ) {
			if ( is_string( $login ) && '' !== $login ) {
				$out[] = $login;
			}
		}
		return $out;
	}

	/**
	 * Raw option record, always an array.
	 *
	 * @return array<string, mixed>
	 */
	private function record(): array {
		$record = get_option( $this->option_name(), array() );
		return is_array( $record ) ? $record : array();
	}

	/**
	 * Resolve the option key from configuration.
	 *
	 * @return string Option name for wp_options.
	 */
	private function option_name(): string {
		return (string) $this->app_config->additional( 'authorised_actors_option' );
	}
}
