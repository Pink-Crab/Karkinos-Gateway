<?php
/**
 * Fires the wp-db-migrations Migration_Manager on init.
 *
 * The canonical Perique_Migrations module hooks into Plugin_Life_Cycle's
 * activation event, which never fires for mu-plugins (mu-plugins are always
 * active — no register_activation_hook). We use the same underlying
 * Migration_Manager directly, gated by the migration log so the schema is
 * only upserted when something actually changed.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\DB_Migration\Factory;
use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Interfaces\Hookable;

class Migrations_Runner implements Hookable {

	/** Migration log option key — namespaces this plugin's migration log. */
	public const LOG_KEY = 'kg_dispatch_migrations';

	/**
	 * Constructor.
	 *
	 * @param Create_Dispatch_Jobs_Table $dispatch_jobs_migration Resolved via DI so its own App_Config dependency is injected.
	 */
	public function __construct( private Create_Dispatch_Jobs_Table $dispatch_jobs_migration ) {}

	/**
	 * Register hooks. Runs the migration manager on every `init` so a newly
	 * deployed schema gets applied without any activation step.
	 *
	 * @param Hook_Loader $loader Perique's hook collector.
	 *
	 * @return void
	 */
	public function register( Hook_Loader $loader ): void {
		$loader->action( 'init', array( $this, 'run' ) );
	}

	/**
	 * Build the manager, register our migrations, run create_tables().
	 *
	 * create_tables() consults the migration log (stored in an option keyed
	 * by LOG_KEY) and only invokes dbDelta for migrations whose schema hash
	 * has changed since the last run — cheap to call on every request.
	 *
	 * @return void
	 */
	public function run(): void {
		$manager = Factory::manager_with_db_delta( self::LOG_KEY );
		$manager->add_migration( $this->dispatch_jobs_migration );
		$manager->create_tables();
	}
}
