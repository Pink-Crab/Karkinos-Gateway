<?php
/**
 * Read side of the JSONL webhook log (the viewer's data source).
 *
 * Discovers log files by scanning the log directory directly (glob) and reads
 * them with native file functions — NOT WP_Filesystem and NOT the option map.
 * This is deliberate: the writer appends with native file_put_contents, and on
 * some hosts (e.g. Plesk) WP_Filesystem won't initialise in the admin page
 * context, which would make every read return false and the viewer look empty
 * even though the files exist. Scanning the directory also means the viewer
 * shows whatever is actually on disk regardless of the option map's state.
 *
 * Only files inside the log directory matching the YYYY-MM-DD-*.jsonl pattern
 * are ever opened, so there's no path-traversal surface.
 *
 * @package Karkinos\Gateway\Logging
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Logging;

use PinkCrab\Perique\Application\App_Config;

class Webhook_Log_Reader {

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Source of the log directory path.
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Dates that have at least one log file, newest first.
	 *
	 * @return list<string> Dates as YYYY-MM-DD.
	 */
	public function days(): array {
		$dates = array();
		foreach ( $this->files_for( null ) as $path ) {
			if ( 1 === preg_match( '/^(\d{4}-\d{2}-\d{2})-/', basename( $path ), $matches ) ) {
				$dates[ $matches[1] ] = true;
			}
		}

		$list = array_keys( $dates );
		rsort( $list );
		return $list;
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
	 * @param string $date YYYY-MM-DD.
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
	 * Only the lines on the requested page are JSON-decoded — a day file can be
	 * large (one line per delivery, full payload), so decoding the whole thing
	 * per page view would exhaust memory.
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
	 * Raw non-empty log lines for a day, newest first (not decoded). Joins all
	 * of the day's files (oldest first by mtime), then reverses.
	 *
	 * @param string $date YYYY-MM-DD.
	 *
	 * @return list<string>
	 */
	private function lines( string $date ): array {
		if ( ! $this->is_valid_date( $date ) ) {
			return array();
		}

		$lines = array();
		foreach ( $this->files_for( $date ) as $path ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local log file we own; WP_Filesystem is unreliable in this context.
			$contents = file_get_contents( $path );
			if ( false === $contents ) {
				continue;
			}
			foreach ( explode( "\n", $contents ) as $line ) {
				if ( '' !== $line ) {
					$lines[] = $line;
				}
			}
		}

		return array_reverse( $lines );
	}

	/**
	 * Absolute paths of log files (optionally just one date's), oldest first
	 * by modification time with the filename as a stable tiebreaker.
	 *
	 * @param string|null $date YYYY-MM-DD to scope to, or null for all days.
	 *
	 * @return list<string>
	 */
	private function files_for( ?string $date ): array {
		$dir = $this->app_config->path( 'webhook_logs' );
		$dir = is_string( $dir ) ? $dir : '';
		if ( '' === $dir ) {
			return array();
		}

		$prefix = ( null !== $date && $this->is_valid_date( $date ) ) ? $date : '*';
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions -- directory scan of our own log dir.
		$found = glob( $dir . '/' . $prefix . '-*.jsonl' );
		if ( ! is_array( $found ) ) {
			return array();
		}

		usort(
			$found,
			static fn( string $a, string $b ): int =>
				array( filemtime( $a ), basename( $a ) ) <=> array( filemtime( $b ), basename( $b ) )
		);

		return $found;
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
