<?php
/**
 * Integration tests for the webhook log reader (viewer data source).
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Logging;

use Karkinos\Gateway\Logging\Webhook_Log_Reader;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_UnitTestCase;

/**
 * @group integration
 * @group logging
 */
class Test_Webhook_Log_Reader extends WP_UnitTestCase {

	private Webhook_Log_Reader $reader;
	private App_Config $config;

	public function set_up(): void {
		parent::set_up();
		$this->reader = App::make( Webhook_Log_Reader::class );
		$this->config = App::make( App_Config::class );
		delete_option( $this->config->additional( 'webhook_log_files_option' ) );
		$this->ensure_dir();
	}

	public function tear_down(): void {
		delete_option( $this->config->additional( 'webhook_log_files_option' ) );
		$dir = (string) $this->config->path( 'webhook_logs' );
		if ( is_dir( $dir ) ) {
			foreach ( (array) glob( $dir . '/*' ) as $file ) {
				if ( is_string( $file ) && is_file( $file ) ) {
					unlink( $file );
				}
			}
			rmdir( $dir );
		}
		parent::tear_down();
	}

	/** @testdox days lists dates with a file, newest first */
	public function test_days_newest_first(): void {
		$this->write_day( '2026-06-01', '2026-06-01-aaaa.jsonl', array( array( 'event' => 'issues' ) ) );
		$this->write_day( '2026-06-03', '2026-06-03-bbbb.jsonl', array( array( 'event' => 'ping' ) ) );
		$this->write_day( '2026-06-02', '2026-06-02-cccc.jsonl', array( array( 'event' => 'push' ) ) );

		$this->assertSame( array( '2026-06-03', '2026-06-02', '2026-06-01' ), $this->reader->days() );
		$this->assertSame( '2026-06-03', $this->reader->latest_day() );
	}

	/** @testdox a day in the option map but with no file on disk is omitted */
	public function test_days_omits_missing_file(): void {
		$this->write_day( '2026-06-01', '2026-06-01-aaaa.jsonl', array( array( 'event' => 'issues' ) ) );
		// Map entry with no corresponding file.
		$map                 = get_option( $this->config->additional( 'webhook_log_files_option' ), array() );
		$map['2026-05-30']   = '2026-05-30-ghost.jsonl';
		update_option( $this->config->additional( 'webhook_log_files_option' ), $map );

		$this->assertSame( array( '2026-06-01' ), $this->reader->days() );
	}

	/** @testdox read returns the day's records newest first */
	public function test_read_newest_first(): void {
		$this->write_day(
			'2026-06-04',
			'2026-06-04-dddd.jsonl',
			array(
				array( 'ts' => 'first', 'event' => 'issues' ),
				array( 'ts' => 'second', 'event' => 'push' ),
				array( 'ts' => 'third', 'event' => 'ping' ),
			)
		);

		$records = $this->reader->read( '2026-06-04' );

		$this->assertCount( 3, $records );
		$this->assertSame( 'third', $records[0]['ts'] );
		$this->assertSame( 'first', $records[2]['ts'] );
	}

	/** @testdox page slices newest-first and reports paging metadata */
	public function test_page_slices_and_reports_meta(): void {
		$rows = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$rows[] = array( 'ts' => (string) $i );
		}
		$this->write_day( '2026-06-04', '2026-06-04-eeee.jsonl', $rows );

		$page1 = $this->reader->page( '2026-06-04', 1, 2 );
		$this->assertSame( 5, $page1['total'] );
		$this->assertSame( 3, $page1['total_pages'] );
		$this->assertSame( 1, $page1['page'] );
		$this->assertCount( 2, $page1['records'] );
		$this->assertSame( '5', $page1['records'][0]['ts'] );
		$this->assertSame( '4', $page1['records'][1]['ts'] );

		$page3 = $this->reader->page( '2026-06-04', 3, 2 );
		$this->assertCount( 1, $page3['records'] );
		$this->assertSame( '1', $page3['records'][0]['ts'] );
	}

	/** @testdox page clamps an out-of-range page to the last page */
	public function test_page_clamps_out_of_range(): void {
		$this->write_day(
			'2026-06-04',
			'2026-06-04-gggg.jsonl',
			array( array( 'ts' => '1' ), array( 'ts' => '2' ), array( 'ts' => '3' ) )
		);

		$clamped = $this->reader->page( '2026-06-04', 99, 2 );
		$this->assertSame( 2, $clamped['page'] );
		$this->assertSame( 2, $clamped['total_pages'] );
	}

	/** @testdox page on an unknown day returns empty with sane metadata */
	public function test_page_empty_day(): void {
		$result = $this->reader->page( '2026-01-01', 1, 50 );
		$this->assertSame( array(), $result['records'] );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( 1, $result['total_pages'] );
	}

	/** @testdox read skips malformed JSONL lines */
	public function test_read_skips_malformed(): void {
		$dir  = (string) $this->config->path( 'webhook_logs' );
		$name = '2026-06-04-ffff.jsonl';
		file_put_contents(
			$dir . '/' . $name,
			wp_json_encode( array( 'ts' => 'ok' ) ) . "\n" . 'not-json{' . "\n" . wp_json_encode( array( 'ts' => 'ok2' ) ) . "\n"
		);
		$this->set_map( '2026-06-04', $name );

		$records = $this->reader->read( '2026-06-04' );
		$this->assertCount( 2, $records );
	}

	/** @testdox read returns empty for an unknown or invalid date */
	public function test_read_unknown_or_invalid(): void {
		$this->assertSame( array(), $this->reader->read( '2026-01-01' ) );
		$this->assertSame( array(), $this->reader->read( 'not-a-date' ) );
		$this->assertSame( array(), $this->reader->read( '../../etc/passwd' ) );
	}

	/**
	 * Write a day's log file and register it in the option map.
	 *
	 * @param string                       $date     YYYY-MM-DD.
	 * @param string                       $filename Bare filename.
	 * @param list<array<string, mixed>>   $records  Records, oldest first (as appended).
	 *
	 * @return void
	 */
	private function write_day( string $date, string $filename, array $records ): void {
		$dir   = (string) $this->config->path( 'webhook_logs' );
		$lines = '';
		foreach ( $records as $record ) {
			$lines .= wp_json_encode( $record ) . "\n";
		}
		file_put_contents( $dir . '/' . $filename, $lines );
		$this->set_map( $date, $filename );
	}

	/**
	 * Add/overwrite one date->filename entry in the option map.
	 *
	 * @param string $date     YYYY-MM-DD.
	 * @param string $filename Bare filename.
	 *
	 * @return void
	 */
	private function set_map( string $date, string $filename ): void {
		$option         = $this->config->additional( 'webhook_log_files_option' );
		$map            = get_option( $option, array() );
		$map            = is_array( $map ) ? $map : array();
		$map[ $date ]   = $filename;
		update_option( $option, $map );
	}

	private function ensure_dir(): void {
		$dir = (string) $this->config->path( 'webhook_logs' );
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0700, true );
		}
	}
}
