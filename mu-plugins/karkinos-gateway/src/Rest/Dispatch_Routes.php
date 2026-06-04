<?php
/**
 * REST endpoints that drive and observe the dispatch queue.
 *
 * Both require `manage_options` (the WP application password the home server
 * already holds). The app never self-schedules: an external cron on the home
 * server POSTs /dispatch/tick on its own cadence to drain the queue, and
 * /dispatch reports backlog so ops can see if anything is piling up (Karkinos
 * down / busy).
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Dispatch\Dispatch_Worker;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use WP_REST_Request;
use WP_REST_Response;

class Dispatch_Routes extends Route_Controller {

	/** @var ?string Shared REST namespace. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	/**
	 * Constructor.
	 *
	 * @param Dispatch_Worker $worker Drains the queue to Karkinos.
	 * @param Dispatch_Queue  $queue  Backlog read for the status route.
	 */
	public function __construct(
		private Dispatch_Worker $worker,
		private Dispatch_Queue $queue
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
			$factory->post( '/dispatch/tick', array( $this, 'tick' ) )
				->authentication( array( $this, 'check_auth' ) ),

			$factory->get( '/dispatch', array( $this, 'status' ) )
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
	 * POST /dispatch/tick — drain the queue (called by the home-server cron).
	 *
	 * @param WP_REST_Request $request Unused.
	 *
	 * @return WP_REST_Response 200 with the worker run summary.
	 */
	public function tick( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( $this->worker->run(), 200 );
	}

	/**
	 * GET /dispatch — backlog visibility for ops.
	 *
	 * @param WP_REST_Request $request Unused.
	 *
	 * @return WP_REST_Response 200 with { pending }.
	 */
	public function status( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			array( 'pending' => $this->queue->pending_count() ),
			200
		);
	}
}
