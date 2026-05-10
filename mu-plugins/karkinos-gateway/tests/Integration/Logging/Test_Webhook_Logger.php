<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Logging;

use Karkinos\Gateway\Logging\Webhook_Logger;
use PinkCrab\Perique\Application\App;
use WP_UnitTestCase;

/**
 * @group integration
 * @group logging
 * @group webhook
 */
class Test_Webhook_Logger extends WP_UnitTestCase {

	private string $log_dir;

	public function set_up(): void {
		parent::set_up();
		$this->log_dir = WP_CONTENT_DIR . '/karkinos-gateway-logs';
	}

	public function tear_down(): void {
		delete_option( Webhook_Logger::OPTION_LOG_FILES );

		if ( is_dir( $this->log_dir ) ) {
			foreach ( (array) glob( $this->log_dir . '/*' ) as $file ) {
				if ( is_string( $file ) && is_file( $file ) ) {
					unlink( $file );
				}
			}
			rmdir( $this->log_dir );
		}

		parent::tear_down();
	}

	/** @testdox A log call creates the log directory if it does not exist */
	public function test_log_creates_directory(): void {
		$this->assertDirectoryDoesNotExist( $this->log_dir );

		$this->logger()->log( array( 'hello' => 'world' ) );

		$this->assertDirectoryExists( $this->log_dir );
	}

	/** @testdox A log call drops an empty index.php blocker into the directory */
	public function test_log_creates_index_php_blocker(): void {
		$this->logger()->log( array( 'a' => 1 ) );

		$index = $this->log_dir . '/index.php';
		$this->assertFileExists( $index );
		$this->assertStringContainsString( 'Silence is golden', (string) file_get_contents( $index ) );
	}

	/** @testdox A log call appends one JSONL line per call to today's file */
	public function test_log_appends_one_line_per_call(): void {
		$logger = $this->logger();
		$logger->log( array( 'n' => 1 ) );
		$logger->log( array( 'n' => 2 ) );
		$logger->log( array( 'n' => 3 ) );

		$path  = $this->todays_log_path();
		$lines = array_values( array_filter( explode( "\n", (string) file_get_contents( $path ) ) ) );

		$this->assertCount( 3, $lines );
		$this->assertSame( 1, json_decode( $lines[0], true )['n'] );
		$this->assertSame( 2, json_decode( $lines[1], true )['n'] );
		$this->assertSame( 3, json_decode( $lines[2], true )['n'] );
	}

	/** @testdox The first write for a date persists the filename map in the option */
	public function test_filename_persisted_in_option(): void {
		$this->logger()->log( array( 'a' => 1 ) );

		$map = get_option( Webhook_Logger::OPTION_LOG_FILES );
		$this->assertIsArray( $map );

		$date = gmdate( 'Y-m-d' );
		$this->assertArrayHasKey( $date, $map );
		$this->assertMatchesRegularExpression(
			'/^' . preg_quote( $date, '/' ) . '-[a-f0-9]{12}\.jsonl$/',
			$map[ $date ]
		);
	}

	/** @testdox The filename map option is NOT autoloaded */
	public function test_option_is_not_autoloaded(): void {
		global $wpdb;

		$this->logger()->log( array( 'a' => 1 ) );

		$autoload = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s",
				Webhook_Logger::OPTION_LOG_FILES
			)
		);

		$this->assertContains(
			$autoload,
			array( 'no', 'off' ),
			'Expected autoload=no for the webhook log files option. Got: ' . var_export( $autoload, true )
		);
	}

	/** @testdox Subsequent writes the same day reuse the same filename (no churn) */
	public function test_same_day_reuses_filename(): void {
		$logger = $this->logger();
		$logger->log( array( 'a' => 1 ) );

		$map_after_first    = get_option( Webhook_Logger::OPTION_LOG_FILES );
		$filename_first     = $map_after_first[ gmdate( 'Y-m-d' ) ];

		$logger->log( array( 'b' => 2 ) );

		$map_after_second   = get_option( Webhook_Logger::OPTION_LOG_FILES );
		$filename_second    = $map_after_second[ gmdate( 'Y-m-d' ) ];

		$this->assertSame( $filename_first, $filename_second );
	}

	private function logger(): Webhook_Logger {
		return App::make( Webhook_Logger::class );
	}

	private function todays_log_path(): string {
		$map = get_option( Webhook_Logger::OPTION_LOG_FILES );
		return $this->log_dir . '/' . $map[ gmdate( 'Y-m-d' ) ];
	}
}
