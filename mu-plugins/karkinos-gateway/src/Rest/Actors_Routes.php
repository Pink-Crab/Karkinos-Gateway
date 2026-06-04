<?php
/**
 * REST endpoints for the authorised-actors roster.
 *
 * Both require `manage_options` (the WP application password the home server
 * already holds). An external cron on the home server POSTs /actors/sync on
 * its own cadence to keep the roster fresh; /actors is a read-only health
 * check. Slugs/keys are resolved through the injected services — no hardcoded
 * strings here.
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\Auth\Actors_Sync;
use Karkinos\Gateway\Auth\Authorised_Actors;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use WP_REST_Request;
use WP_REST_Response;

class Actors_Routes extends Route_Controller {

	/** @var ?string Shared REST namespace. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	/**
	 * Constructor.
	 *
	 * @param Actors_Sync       $sync   Runs a GitHub roster refresh.
	 * @param Authorised_Actors $actors Cached roster, for the status read.
	 */
	public function __construct(
		private Actors_Sync $sync,
		private Authorised_Actors $actors
	) {}

	/**
	 * Declare the routes this controller owns.
	 *
	 * @param Route_Factory $factory Pre-configured with the namespace.
	 *
	 * @return array<int, mixed> Route definitions to register.
	 */
	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/actors/sync', array( $this, 'sync' ) )
				->authentication( array( $this, 'check_auth' ) ),

			$factory->get( '/actors', array( $this, 'status' ) )
				->authentication( array( $this, 'check_auth' ) ),
		);
	}

	/**
	 * Authentication callback shared by both routes.
	 *
	 * @param WP_REST_Request $request Unused; signature required by authentication().
	 *
	 * @return bool True if the current user can manage options.
	 */
	public function check_auth( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * POST /actors/sync — refresh the roster from GitHub.
	 *
	 * @param WP_REST_Request $request Unused.
	 *
	 * @return WP_REST_Response 200 with { org, count, synced_at, error? }.
	 */
	public function sync( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( $this->sync->run(), 200 );
	}

	/**
	 * GET /actors — roster health (org, count, last sync time).
	 *
	 * @param WP_REST_Request $request Unused.
	 *
	 * @return WP_REST_Response 200 with { org, count, synced_at }.
	 */
	public function status( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'org'       => $this->actors->org(),
				'count'     => $this->actors->count(),
				'synced_at' => $this->actors->synced_at(),
			),
			200
		);
	}
}
