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
		foreach ( $this->file_map() as $date => $value ) {
			if ( ! is_string( $date ) || ! $this->is_valid_date( $date ) ) {
				continue;
			}
			foreach ( $this->normalise_list( $value ) as $filename ) {
				if ( $this->files->file_exists( $this->path_for( $filename ) ) ) {
					$dates[] = $date;
					break;
				}
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
	 * All parsed records for a single day, newest first.
	 *
	 * Returns an empty array for an unknown/invalid date or an unreadable file.
	 * Malformed lines are skipped rather than aborting the whole read.
	 *
	 * @param string $date YYYY-MM-DD. Must be a key in the file map.
	 *
	 * @return list<array<string, mixed>> Records, most recent first.
	 */
	public function read( string $date ): array {
		$records = array();
		foreach ( $this->lines( $date ) as $line ) {
			$decoded = json_decode( $line, true );
			if ( is_array( $decoded ) ) {
				$records[] = $decoded;
			}
		}
		return $records;
	}

	/**
	 * One page of a day's records, newest first.
	 *
	 * Only the lines on the requested page are JSON-decoded — the daily file
	 * can be large (one line per delivery, full payload), so decoding the
	 * whole thing per page view would exhaust memory.
	 *
	 * @param string $date     YYYY-MM-DD.
	 * @param int    $page     1-based page number (clamped into range).
	 * @param int    $per_page Records per page (min 1).
	 *
	 * @return array{records: list<array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
	 */
	public function page( string $date, int $page, int $per_page ): array {
		$lines       = $this->lines( $date );
		$total       = count( $lines );
		$per_page    = max( 1, $per_page );
		$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;
		$page        = max( 1, min( $page, $total_pages ) );

		$records = array();
		foreach ( array_slice( $lines, ( $page - 1 ) * $per_page, $per_page ) as $line ) {
			$decoded = json_decode( $line, true );
			if ( is_array( $decoded ) ) {
				$records[] = $decoded;
			}
		}

		return array(
			'records'     => $records,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => $total_pages,
		);
	}

	/**
	 * Raw non-empty log lines for a day, newest first (not decoded).
	 *
	 * @param string $date YYYY-MM-DD. Must be a key in the file map.
	 *
	 * @return list<string>
	 */
	private function lines( string $date ): array {
		if ( ! $this->is_valid_date( $date ) ) {
			return array();
		}

		$map   = $this->file_map();
		$files = $this->normalise_list( $map[ $date ] ?? array() );

		$lines = array();
		foreach ( $files as $filename ) {
			$contents = $this->files->get_contents( $this->path_for( $filename ) );
			if ( false === $contents ) {
				continue;
			}
			foreach ( explode( "\n", $contents ) as $line ) {
				if ( '' !== $line ) {
					$lines[] = $line;
				}
			}
		}

		// Files + lines are stored oldest-first; reverse for newest-first
		// across the whole day's set.
		return array_reverse( $lines );
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

	/**
	 * The raw date->files map from the option, always an array. Values may be
	 * a list of filenames (current) or a single filename string (legacy) —
	 * callers run them through normalise_list().
	 *
	 * @return array<array-key, mixed>
	 */
	private function file_map(): array {
		$map = get_option( (string) $this->app_config->additional( 'webhook_log_files_option' ), array() );
		return is_array( $map ) ? $map : array();
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
