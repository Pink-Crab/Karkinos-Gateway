<?php
/**
 * WP_Filesystem-backed File_Manager. Production implementation.
 *
 * Boots WP_Filesystem on demand and delegates create/exists/read/write
 * through it. The one exception is append() — WP_Filesystem has no
 * atomic-append API, so this method falls back to native file_put_contents
 * with FILE_APPEND | LOCK_EX. That matches the previous behaviour of
 * Webhook_Logger before the interface was extracted.
 *
 * @package Karkinos\Gateway\Filesystem
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Filesystem;

use WP_Filesystem_Base;

class WP_File_Manager implements File_Manager {

	/**
	 * Cached WP_Filesystem instance. Booted lazily on first use so this
	 * class can be instantiated by DI without forcing wp-admin/includes/file.php.
	 */
	private ?WP_Filesystem_Base $fs = null;

	public function is_dir( string $path ): bool {
		return is_dir( $path );
	}

	public function mkdir( string $path, int $mode = 0700 ): bool {
		if ( is_dir( $path ) ) {
			return true;
		}

		$fs = $this->fs();
		if ( ! $fs instanceof WP_Filesystem_Base ) {
			return false;
		}

		return (bool) $fs->mkdir( $path, $mode );
	}

	public function file_exists( string $path ): bool {
		$fs = $this->fs();
		if ( $fs instanceof WP_Filesystem_Base ) {
			return (bool) $fs->exists( $path );
		}
		return file_exists( $path );
	}

	public function put_contents( string $path, string $contents ): bool {
		$fs = $this->fs();
		if ( ! $fs instanceof WP_Filesystem_Base ) {
			return false;
		}
		return (bool) $fs->put_contents( $path, $contents );
	}

	public function append( string $path, string $contents ): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- atomic FILE_APPEND | LOCK_EX append; WP_Filesystem has no append API.
		$bytes = file_put_contents( $path, $contents, FILE_APPEND | LOCK_EX );
		return false !== $bytes;
	}

	public function get_contents( string $path ): string|false {
		$fs = $this->fs();
		if ( ! $fs instanceof WP_Filesystem_Base ) {
			return false;
		}
		return $fs->get_contents( $path );
	}

	/**
	 * Boot WP_Filesystem on first use and cache the instance. Returns null
	 * if the boot failed — callers handle that as an I/O failure.
	 *
	 * @return WP_Filesystem_Base|null
	 */
	private function fs(): ?WP_Filesystem_Base {
		if ( $this->fs instanceof WP_Filesystem_Base ) {
			return $this->fs;
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
			$this->fs = $wp_filesystem;
			return $this->fs;
		}
		return null;
	}
}
