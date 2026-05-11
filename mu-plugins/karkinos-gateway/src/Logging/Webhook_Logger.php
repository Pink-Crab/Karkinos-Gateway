<?php
/**
 * JSONL log writer for inbound webhook deliveries.
 *
 * Directory path + option key are resolved via App_Config — physical names
 * live in config/settings.php under `path[webhook_logs]` and
 * `additional[webhook_log_files_option]`.
 *
 * One file per day. Filenames carry a random hex suffix so the URL can't
 * be guessed from outside; the per-day suffix is persisted in the option
 * map so deliveries on the same day share a file. The directory is
 * created mode 0700 on first write with an empty `index.php` blocker.
 *
 * @package Karkinos\Gateway\Logging
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Logging;

use PinkCrab\Perique\Application\App_Config;
use WP_Filesystem_Base;

class Webhook_Logger {

	/** Raw bytes for the random filename suffix (12 hex chars). */
	private const FILE_SUFFIX_BYTES = 6;

	/** Permissions applied to the log directory on first creation. */
	private const DIR_MODE = 0700;

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Source of truth for log dir path + option key.
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Append a JSONL line describing a delivery.
	 *
	 * @param array<string, mixed> $record Free-form record. Common keys:
	 *                                     ts, delivery, event, action, repo,
	 *                                     signature_valid, payload.
	 *
	 * @return void
	 */
	public function log( array $record ): void {
		$path = $this->log_file_for_today();
		$line = wp_json_encode( $record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $line ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- atomic FILE_APPEND | LOCK_EX append; WP_Filesystem has no append API.
		file_put_contents( $path, $line . "\n", FILE_APPEND | LOCK_EX );
	}

	/**
	 * Resolve the log directory path from App_Config.
	 *
	 * @return string Absolute filesystem path.
	 */
	private function log_dir(): string {
		return (string) $this->app_config->path( 'webhook_logs' );
	}

	/**
	 * Resolve the option name (where the date→filename map lives) from App_Config.
	 *
	 * @return string Option key for wp_options.
	 */
	private function option_name(): string {
		return (string) $this->app_config->additional( 'webhook_log_files_option' );
	}

	/**
	 * Ensure the log directory exists with restrictive perms and a blank
	 * index.php blocker. Idempotent — short-circuits after first creation.
	 *
	 * @return string The directory path.
	 */
	private function ensure_log_dir(): string {
		$dir = $this->log_dir();

		if ( is_dir( $dir ) ) {
			return $dir;
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			return $dir;
		}

		$wp_filesystem->mkdir( $dir, self::DIR_MODE );

		$index = $dir . '/index.php';
		if ( ! $wp_filesystem->exists( $index ) ) {
			$wp_filesystem->put_contents( $index, "<?php\n// Silence is golden.\n" );
		}

		return $dir;
	}

	/**
	 * Resolve today's full log-file path. Picks (and persists) a random
	 * suffix the first time it's called for a given date so subsequent
	 * deliveries on the same day append to the same file.
	 *
	 * @return string Absolute path to today's JSONL log file.
	 */
	private function log_file_for_today(): string {
		$date   = gmdate( 'Y-m-d' );
		$option = $this->option_name();
		$map    = get_option( $option, array() );

		if ( ! is_array( $map ) ) {
			$map = array();
		}

		if ( ! isset( $map[ $date ] ) || ! is_string( $map[ $date ] ) || '' === $map[ $date ] ) {
			$suffix       = bin2hex( random_bytes( self::FILE_SUFFIX_BYTES ) );
			$map[ $date ] = sprintf( '%s-%s.jsonl', $date, $suffix );
			update_option( $option, $map, false );
		}

		return $this->ensure_log_dir() . '/' . $map[ $date ];
	}
}
