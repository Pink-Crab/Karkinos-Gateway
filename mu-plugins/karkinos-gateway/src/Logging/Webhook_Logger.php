<?php
/**
 * JSONL log writer for inbound webhook deliveries.
 *
 * Directory path + option key are resolved via App_Config — physical names
 * live in config/settings.php under `path[webhook_logs]` and
 * `additional[webhook_log_files_option]`. All disk I/O goes through the
 * injected File_Manager so tests can swap in an in-memory fake.
 *
 * Files are per-day but rolled once they pass MAX_FILE_BYTES, so a busy day
 * spreads across several files instead of one unwieldy blob (the reader joins
 * them). The option map holds `date => list<filename>` (newest last); the
 * legacy `date => filename` string form is still read. Filenames carry a
 * random hex suffix so the URL can't be guessed from outside. The directory is
 * created mode 0700 on first write with an empty `index.php` blocker.
 *
 * @package Karkinos\Gateway\Logging
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Logging;

use Karkinos\Gateway\Filesystem\File_Manager;
use PinkCrab\Perique\Application\App_Config;

class Webhook_Logger {

	/** Raw bytes for the random filename suffix (12 hex chars). */
	private const FILE_SUFFIX_BYTES = 6;

	/** Permissions applied to the log directory on first creation. */
	private const DIR_MODE = 0700;

	/** Roll to a fresh file once the current day-file reaches this size (4 MiB). */
	private const MAX_FILE_BYTES = 4194304;

	/**
	 * Constructor.
	 *
	 * @param App_Config   $app_config Source of truth for log dir path + option key.
	 * @param File_Manager $files      Filesystem boundary — production binding is WP_File_Manager.
	 */
	public function __construct(
		private App_Config $app_config,
		private File_Manager $files
	) {}

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

		$this->files->append( $path, $line . "\n" );
	}

	/**
	 * Resolve the log directory path from App_Config.
	 *
	 * @return string Absolute filesystem path.
	 */
	private function log_dir(): string {
		$path = $this->app_config->path( 'webhook_logs' );
		return is_string( $path ) ? $path : '';
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

		if ( $this->files->is_dir( $dir ) ) {
			return $dir;
		}

		if ( ! $this->files->mkdir( $dir, self::DIR_MODE ) ) {
			return $dir;
		}

		$index = $dir . '/index.php';
		if ( ! $this->files->file_exists( $index ) ) {
			$this->files->put_contents( $index, "<?php\n// Silence is golden.\n" );
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

		$dir   = $this->ensure_log_dir();
		$files = $this->normalise_list( $map[ $date ] ?? array() );

		// Start a new file for the day when there isn't one yet, or the current
		// one has grown past the cap — so no single file gets unwieldy.
		$roll = empty( $files ) || $this->files->size( $dir . '/' . end( $files ) ) >= self::MAX_FILE_BYTES;

		if ( $roll ) {
			$files[]      = sprintf( '%s-%s.jsonl', $date, bin2hex( random_bytes( self::FILE_SUFFIX_BYTES ) ) );
			$map[ $date ] = $files;
			update_option( $option, $map, false );
		}

		return $dir . '/' . end( $files );
	}

	/**
	 * Coerce a map entry into a list of filenames, newest last. Accepts the
	 * current list form and the legacy single-string form (pre-rotation data).
	 *
	 * @param mixed $value Map entry for a date.
	 *
	 * @return list<string>
	 */
	private function normalise_list( $value ): array {
		if ( is_string( $value ) && '' !== $value ) {
			return array( $value );
		}
		if ( is_array( $value ) ) {
			return array_values( array_filter( $value, static fn( $f ): bool => is_string( $f ) && '' !== $f ) );
		}
		return array();
	}
}
