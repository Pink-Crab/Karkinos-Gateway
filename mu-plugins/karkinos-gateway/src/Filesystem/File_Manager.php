<?php
/**
 * Filesystem boundary for the plugin.
 *
 * Every disk operation goes through this interface so production code can
 * use WP_Filesystem and tests can swap in an in-memory fake — no real I/O,
 * no permissions surprises in CI.
 *
 * @package Karkinos\Gateway\Filesystem
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Filesystem;

interface File_Manager {

	/**
	 * Does $path exist and resolve to a directory?
	 *
	 * @param string $path Absolute filesystem path.
	 */
	public function is_dir( string $path ): bool;

	/**
	 * Create $path as a directory with $mode permissions. Implementations
	 * may treat this as idempotent (no-op if it already exists). Parents
	 * are NOT created automatically — caller passes a path whose parent
	 * already exists.
	 *
	 * @param string $path Absolute filesystem path.
	 * @param int    $mode Octal mode, e.g. 0700. Implementations may ignore
	 *                     if the underlying system can't honour it.
	 *
	 * @return bool True on success or when already present.
	 */
	public function mkdir( string $path, int $mode = 0700 ): bool;

	/**
	 * Does a file exist at $path?
	 *
	 * @param string $path Absolute filesystem path.
	 */
	public function file_exists( string $path ): bool;

	/**
	 * Write $contents to $path, replacing any existing file.
	 *
	 * @param string $path     Absolute filesystem path.
	 * @param string $contents Raw bytes.
	 *
	 * @return bool True on success.
	 */
	public function put_contents( string $path, string $contents ): bool;

	/**
	 * Append $contents to $path, creating the file if missing. Must be
	 * atomic with respect to concurrent writers in production
	 * implementations (e.g. LOCK_EX on the real filesystem).
	 *
	 * @param string $path     Absolute filesystem path.
	 * @param string $contents Raw bytes to append.
	 *
	 * @return bool True on success.
	 */
	public function append( string $path, string $contents ): bool;

	/**
	 * Read the full contents of $path.
	 *
	 * @param string $path Absolute filesystem path.
	 *
	 * @return string|false File contents, or false on failure (missing/unreadable).
	 */
	public function get_contents( string $path ): string|false;
}
