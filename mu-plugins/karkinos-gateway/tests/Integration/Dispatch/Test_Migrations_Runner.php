<?php
/**
 * Integration test confirming the kg_dispatch_jobs table is created by the
 * Migrations_Runner Hookable on init.
 *
 * The runner is registered via config/registration.php and fires on init,
 * which has already run by the time tests execute — so the table should
 * exist with the schema declared by Create_Dispatch_Jobs_Table.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Dispatch;

use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_UnitTestCase;

/**
 * @group integration
 * @group dispatch
 * @group migrations
 */
class Test_Migrations_Runner extends WP_UnitTestCase {

	private App_Config $config;

	public function set_up(): void {
		parent::set_up();
		$this->config = App::make( App_Config::class );
	}

	/** @testdox The dispatch_jobs table exists after App boot */
	public function test_table_exists(): void {
		global $wpdb;

		$table = $this->config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- SHOW TABLES, test-only.
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$this->assertSame( $table, $found, "Expected table {$table} to exist after migrations run." );
	}

	/** @testdox The table has the expected columns */
	public function test_table_has_expected_columns(): void {
		global $wpdb;

		$table = $this->config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- DESCRIBE, test-only.
		$rows  = $wpdb->get_results( "DESCRIBE {$table}", ARRAY_A );
		$names = array_map( static fn( $r ) => $r['Field'], (array) $rows );

		$expected = array(
			'id',
			'priority',
			'status',
			'source',
			'event',
			'delivery_id',
			'target_url',
			'payload',
			'created_at',
			'dispatched_at',
			'response_status',
			'response_body',
			'error',
		);

		foreach ( $expected as $column ) {
			$this->assertContains( $column, $names, "Column '$column' missing from table." );
		}
	}

	/** @testdox The composite claim index exists on (status, priority, created_at) */
	public function test_claim_index_exists(): void {
		global $wpdb;

		$table = $this->config->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- SHOW INDEX, test-only.
		$rows = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );

		$claim_columns = array();
		foreach ( (array) $rows as $row ) {
			if ( 'claim_idx' === $row['Key_name'] ) {
				$claim_columns[ (int) $row['Seq_in_index'] ] = $row['Column_name'];
			}
		}
		ksort( $claim_columns );

		$this->assertSame(
			array( 'status', 'priority', 'created_at' ),
			array_values( $claim_columns ),
			'claim_idx should be a composite on (status, priority, created_at).'
		);
	}
}
