<?php
/**
 * In-memory File_Manager implementation for tests.
 *
 * Tracks the set of created directories and the contents of every written
 * file inside two associative arrays. No actual disk I/O — production
 * tests can assert on filesystem behaviour without touching the host.
 *
 * @package Karkinos\Gateway\Tests\Fakes
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Fakes;

use Karkinos\Gateway\Filesystem\File_Manager;

class Memory_File_Manager implements File_Manager {

	/** @var array<string, bool> Path → marker. Presence means "is a directory". */
	private array $dirs = array();

	/** @var array<string, string> Path → file contents. */
	private array $files = array();

	public function is_dir( string $path ): bool {
		return isset( $this->dirs[ $path ] );
	}

	public function mkdir( string $path, int $mode = 0700 ): bool {
		$this->dirs[ $path ] = true;
		return true;
	}

	public function file_exists( string $path ): bool {
		return array_key_exists( $path, $this->files );
	}

	public function put_contents( string $path, string $contents ): bool {
		$this->files[ $path ] = $contents;
		return true;
	}

	public function append( string $path, string $contents ): bool {
		$this->files[ $path ] = ( $this->files[ $path ] ?? '' ) . $contents;
		return true;
	}

	public function get_contents( string $path ): string|false {
		return $this->files[ $path ] ?? false;
	}

	/**
	 * Snapshot of every directory the SUT has created. Useful when asserting
	 * a directory was mkdir'd without caring about file contents.
	 *
	 * @return list<string>
	 */
	public function created_directories(): array {
		return array_keys( $this->dirs );
	}

	/**
	 * Snapshot of every file the SUT has written. Path → contents.
	 *
	 * @return array<string, string>
	 */
	public function written_files(): array {
		return $this->files;
	}
}
