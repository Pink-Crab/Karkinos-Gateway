<?php

declare( strict_types=1 );

/**
 * Unit tests for the File_Helper class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Util
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Util;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Util\File_Helper;

class Test_File_Helper extends WP_UnitTestCase {

	/** @testdox assets_path() returns the absolute path to the assets directory. */
	public function test_assets_path_returns_absolute_path(): void {
		$path = File_Helper::assets_path();
		$this->assertIsString( $path );
		$this->assertStringEndsWith( '/assets', $path );
	}

	/** @testdox assets_url() returns a URL string. */
	public function test_assets_url_returns_string(): void {
		$url = File_Helper::assets_url();
		$this->assertIsString( $url );
	}

	/** @testdox get_file_url() converts an absolute filesystem path to a URL by replacing ABSPATH with site_url. */
	public function test_get_file_url_converts_path_to_url(): void {
		$path = ABSPATH . 'wp-content/plugins/example/file.php';
		$url  = File_Helper::get_file_url( $path );
		$this->assertIsString( $url );
		$this->assertStringContainsString( 'wp-content/plugins/example/file.php', $url );
	}

	/** @testdox get_file_url() returns a URL for a path inside ABSPATH. */
	public function test_get_file_url_with_abspath_subpath(): void {
		$url = File_Helper::get_file_url( ABSPATH . 'sub/path' );
		$this->assertStringContainsString( 'sub/path', $url );
	}
}
