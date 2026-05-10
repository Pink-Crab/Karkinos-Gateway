<?php

declare( strict_types=1 );

/**
 * Unit tests for the Hooks helper class.
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
use PinkCrab\Perique_Settings_Page\Util\Hooks;

class Test_Hooks extends WP_UnitTestCase {

	/** @testdox All static hook constants are prefixed with the module namespace. */
	public function test_constants_are_prefixed(): void {
		$prefix = 'pinkcrab/perique-settings/';

		$this->assertStringStartsWith( $prefix, Hooks::ELEMENT_LABEL_CLASS );
		$this->assertStringStartsWith( $prefix, Hooks::ELEMENT_INPUT_CLASS );
		$this->assertStringStartsWith( $prefix, Hooks::ELEMENT_WRAPPER_CLASS );
		$this->assertStringStartsWith( $prefix, Hooks::PAGE_GLOBAL_SCRIPT );
		$this->assertStringStartsWith( $prefix, Hooks::PAGE_GLOBAL_STYLE );
	}

	/** @testdox All hook constants are unique. */
	public function test_constants_are_unique(): void {
		$values = array(
			Hooks::ELEMENT_LABEL_CLASS,
			Hooks::ELEMENT_INPUT_CLASS,
			Hooks::ELEMENT_WRAPPER_CLASS,
			Hooks::PAGE_GLOBAL_SCRIPT,
			Hooks::PAGE_GLOBAL_STYLE,
		);
		$this->assertSame( count( $values ), count( array_unique( $values ) ) );
	}

	/** @testdox settings_page_header_action() returns a populated handle for a given key. */
	public function test_settings_page_header_action(): void {
		$this->assertSame(
			'pinkcrab/perique-settings/settings-page-my-page-header',
			Hooks::settings_page_header_action( 'my-page' )
		);
	}

	/** @testdox settings_page_footer_action() returns a populated handle for a given key. */
	public function test_settings_page_footer_action(): void {
		$this->assertSame(
			'pinkcrab/perique-settings/settings-page-my-page-footer',
			Hooks::settings_page_footer_action( 'my-page' )
		);
	}

	/** @testdox settings_page_view_path() returns a populated handle for a given key. */
	public function test_settings_page_view_path(): void {
		$this->assertSame(
			'pinkcrab/perique-settings/settings-page-my-page-view-path',
			Hooks::settings_page_view_path( 'my-page' )
		);
	}

	/** @testdox settings_page_view_data() returns a populated handle for a given key. */
	public function test_settings_page_view_data(): void {
		$this->assertSame(
			'pinkcrab/perique-settings/settings-page-my-page-view-data',
			Hooks::settings_page_view_data( 'my-page' )
		);
	}

	/** @testdox Dynamic hook helpers handle empty keys without error. */
	public function test_dynamic_hooks_with_empty_key(): void {
		$this->assertIsString( Hooks::settings_page_header_action( '' ) );
		$this->assertIsString( Hooks::settings_page_footer_action( '' ) );
		$this->assertIsString( Hooks::settings_page_view_path( '' ) );
		$this->assertIsString( Hooks::settings_page_view_data( '' ) );
	}
}
