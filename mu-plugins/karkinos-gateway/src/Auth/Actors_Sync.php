<?php
/**
 * Orchestrates a roster refresh: GitHub org members -> local cache.
 *
 * A plain service (NOT Hookable) — the gateway never self-schedules. It is
 * invoked on demand by the `/actors/sync` REST route, which an external cron
 * on the home server calls on its own cadence.
 *
 * Fail-safe: if the GitHub call errors (token missing, API down, rate-limited)
 * the existing roster is left untouched. A transient GitHub outage must never
 * empty the allow-list and silently stop every forward.
 *
 * @package Karkinos\Gateway\Auth
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Auth;

class Actors_Sync {

	/** Org used when KARKINOS_GH_ORG is not defined. */
	public const DEFAULT_ORG = 'Pink-Crab';

	/**
	 * Constructor.
	 *
	 * @param Org_Members_Client $client GitHub membership fetcher.
	 * @param Authorised_Actors  $actors Local roster cache.
	 */
	public function __construct(
		private Org_Members_Client $client,
		private Authorised_Actors $actors
	) {}

	/**
	 * Run a sync.
	 *
	 * @return array{org: string, count: int, synced_at: ?string, error?: string}
	 *         Summary for the REST response. On error, `error` is set and the
	 *         count/synced_at reflect the *unchanged* existing roster.
	 */
	public function run(): array {
		$org    = $this->org();
		$logins = $this->client->fetch_member_logins( $org );

		if ( is_wp_error( $logins ) ) {
			return array(
				'org'       => $org,
				'count'     => $this->actors->count(),
				'synced_at' => $this->actors->synced_at(),
				'error'     => $logins->get_error_message(),
			);
		}

		$this->actors->replace( $logins, $org );

		return array(
			'org'       => $org,
			'count'     => $this->actors->count(),
			'synced_at' => $this->actors->synced_at(),
		);
	}

	/**
	 * Resolve the org to sync from the wp-config constant, defaulting to
	 * Pink-Crab.
	 *
	 * @return string
	 */
	private function org(): string {
		if ( defined( 'KARKINOS_GH_ORG' ) ) {
			$org = constant( 'KARKINOS_GH_ORG' );
			if ( is_string( $org ) && '' !== trim( $org ) ) {
				return trim( $org );
			}
		}
		return self::DEFAULT_ORG;
	}
}
