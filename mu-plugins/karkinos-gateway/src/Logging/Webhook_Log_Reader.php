<?php
/**
 * Read side of the JSONL webhook log (the viewer's data source).
 *
 * Webhook_Logger writes one file per day under path('webhook_logs'), with the
 * date->filename map held in the option additional('webhook_log_files_option')
 * — the filenames carry a random suffix so they can't be guessed externally.
 * This reader only ever opens files named in that map, so a caller can never
 * coax it into reading an arbitrary path. All I/O goes through File_Manager so
 * tests can swap in an in-memory fake.
 *
 * @package Karkinos\Gateway\Logging
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Logging;

use Karkinos\Gateway\Filesystem\File_Manager;
use PinkCrab\Perique\Application\App_Config;

class Webhook_Log_Reader {

	/** Default cap on records returned for one day. */
	private const DEFAULT_LIMIT = 500;

	/**
	 * Constructor.
	 *
	 * @param App_Config   $app_config Source of the log dir path + option key.
	 * @param File_Manager $files      Filesystem boundary.
	 */
	public function __construct(
		private App_Config $app_config,
		private File_Manager $files
	) {}

	/**
	 * Dates that have a log file, newest first.
	 *
	 * Only dates present in the option map whose file actually exists on disk
	 * are returned, so the viewer never offers a day it can't open.
	 *
	 * @return list<string> Dates as YYYY-MM-DD.
	 */
	public function days(): array {
		$dates = array();
		foreach ( $this->file_map() as $date => $filename ) {
			if ( $this->is_valid_date( $date ) && $this->files->file_exists( $this->path_for( $filename ) ) ) {
				$dates[] = $date;
			}
		}

		rsort( $dates );
		return $dates;
	}

	/**
	 * The most recent day with a log, or null if there are none.
	 *
	 * @return string|null YYYY-MM-DD, or null.
	 */
	public function latest_day(): ?string {
		return $this->days()[0] ?? null;
	}

	/**
	 * Parsed records for a single day, newest first.
	 *
	 * Returns an empty array for an unknown/invalid date or an unreadable file.
	 * Malformed lines are skipped rather than aborting the whole read.
	 *
	 * @param string $date  YYYY-MM-DD. Must be a key in the file map.
	 * @param int    $limit Max records to return.
	 *
	 * @return list<array<string, mixed>> Records, most recent first.
	 */
	public function read( string $date, int $limit = self::DEFAULT_LIMIT ): array {
		if ( ! $this->is_valid_date( $date ) ) {
			return array();
		}

		$map = $this->file_map();
		if ( ! isset( $map[ $date ] ) || ! is_string( $map[ $date ] ) ) {
			return array();
		}

		$contents = $this->files->get_contents( $this->path_for( $map[ $date ] ) );
		if ( false === $contents ) {
			return array();
		}

		$records = array();
		foreach ( array_filter( explode( "\n", $contents ) ) as $line ) {
			$decoded = json_decode( $line, true );
			if ( is_array( $decoded ) ) {
				$records[] = $decoded;
			}
		}

		// Newest first, then cap.
		return array_slice( array_reverse( $records ), 0, max( 1, $limit ) );
	}

	/**
	 * The date->filename map from the option, always an array.
	 *
	 * @return array<string, string>
	 */
	private function file_map(): array {
		$map = get_option( (string) $this->app_config->additional( 'webhook_log_files_option' ), array() );
		if ( ! is_array( $map ) ) {
			return array();
		}

		$clean = array();
		foreach ( $map as $date => $filename ) {
			if ( is_string( $date ) && is_string( $filename ) && '' !== $filename ) {
				$clean[ $date ] = $filename;
			}
		}
		return $clean;
	}

	/**
	 * Resolve a stored filename to its absolute path inside the log dir.
	 *
	 * basename() strips any path component so a tampered option value can't
	 * escape the log directory.
	 *
	 * @param string $filename Bare filename from the map.
	 *
	 * @return string Absolute path.
	 */
	private function path_for( string $filename ): string {
		$dir = $this->app_config->path( 'webhook_logs' );
		return ( is_string( $dir ) ? $dir : '' ) . '/' . basename( $filename );
	}

	/**
	 * Strict YYYY-MM-DD check.
	 *
	 * @param string $date Candidate date.
	 *
	 * @return bool
	 */
	private function is_valid_date( string $date ): bool {
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date );
	}
}
