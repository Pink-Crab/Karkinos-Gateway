<?php
/**
 * Migration: kg_dispatch_jobs custom table.
 *
 * Backs the dispatch queue (one-at-a-time forwarding of inbound webhook
 * deliveries to the home server). The full table name is held in
 * App_Config under db_tables('dispatch_jobs') so consumers (Dispatch_Queue)
 * never need to know the prefix.
 *
 * @package Karkinos\Gateway\Migration
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Migration;

use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Migration\Migration;
use PinkCrab\Table_Builder\Schema;

class Create_Dispatch_Jobs_Table extends Migration {

	/** Alias key for App_Config::db_tables() lookup. */
	public const TABLE_ALIAS = 'dispatch_jobs';

	/**
	 * Constructor.
	 *
	 * Property promotion runs before the body, so $this->app_config is
	 * populated by the time parent::__construct() invokes $this->table_name().
	 *
	 * @param App_Config $app_config Injected by the DI container.
	 */
	public function __construct( private App_Config $app_config ) {
		parent::__construct();
	}

	/**
	 * Full (prefixed) table name as configured in config/settings.php.
	 *
	 * @return string
	 */
	protected function table_name(): string {
		return $this->app_config->db_tables( self::TABLE_ALIAS );
	}

	/**
	 * Build the schema. Fluent column + index definitions for the dispatch
	 * queue. The composite (status, priority, created_at) index serves the
	 * hot path: "highest-priority oldest pending job".
	 *
	 * @param Schema $schema
	 *
	 * @return void
	 */
	public function schema( Schema $schema ): void {
		$schema->column( 'id' )
			->unsigned_big( 20 )
			->auto_increment();

		$schema->column( 'priority' )
			->int( 11 )
			->default( 0 );

		$schema->column( 'status' )
			->varchar( 20 )
			->default( 'pending' );

		$schema->column( 'source' )
			->varchar( 50 )
			->default( '' );

		$schema->column( 'event' )
			->varchar( 100 )
			->default( '' );

		$schema->column( 'delivery_id' )
			->varchar( 64 )
			->default( '' );

		$schema->column( 'target_url' )->text();
		$schema->column( 'payload' )->type( 'longtext' );

		$schema->column( 'created_at' )->datetime();
		$schema->column( 'dispatched_at' )->datetime()->nullable();

		$schema->column( 'response_status' )
			->int( 11 )
			->default( 0 );

		$schema->column( 'response_body' )->type( 'mediumtext' );
		$schema->column( 'error' )->text();

		$schema->index( 'id' )->primary();

		// Identical key_name across three index() calls = composite index.
		$schema->index( 'status', 'claim_idx' );
		$schema->index( 'priority', 'claim_idx' );
		$schema->index( 'created_at', 'claim_idx' );
	}
}
