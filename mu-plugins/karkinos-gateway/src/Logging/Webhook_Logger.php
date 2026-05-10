<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Logging;

use WP_Filesystem_Base;

class Webhook_Logger {

	/**
	 * Maps each date to the unguessable filename used for that day's JSONL log.
	 *
	 * Not autoloaded — the filenames are effectively secret (URL unguessability
	 * is the only thing keeping the logs off the public web). Autoloading would
	 * place them in alloptions on every request, leaking them into any debug
	 * dump / error page / option-aware tooling for zero benefit (only the
	 * webhook handler reads this).
	 */
	public const OPTION_LOG_FILES = 'karkinos_gateway_webhook_log_files';

	private const LOG_DIR_NAME      = 'karkinos-gateway-logs';
	private const FILE_SUFFIX_BYTES = 6;
	private const DIR_MODE          = 0700;

	/**
	 * @param array<string, mixed> $record
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

	private function log_dir(): string {
		return WP_CONTENT_DIR . '/' . self::LOG_DIR_NAME;
	}

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

	private function log_file_for_today(): string {
		$date = gmdate( 'Y-m-d' );
		$map  = get_option( self::OPTION_LOG_FILES, array() );

		if ( ! is_array( $map ) ) {
			$map = array();
		}

		if ( ! isset( $map[ $date ] ) || ! is_string( $map[ $date ] ) || '' === $map[ $date ] ) {
			$suffix       = bin2hex( random_bytes( self::FILE_SUFFIX_BYTES ) );
			$map[ $date ] = sprintf( '%s-%s.jsonl', $date, $suffix );
			update_option( self::OPTION_LOG_FILES, $map, false );
		}

		return $this->ensure_log_dir() . '/' . $map[ $date ];
	}
}
