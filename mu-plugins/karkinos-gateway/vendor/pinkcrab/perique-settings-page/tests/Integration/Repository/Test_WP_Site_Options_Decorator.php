<?php

declare( strict_types=1 );

/**
 * Integration tests for WP_Site_Options_Decorator.
 *
 * On a non-multisite install, get_site_option/update_site_option/delete_site_option
 * fall back to the regular options table, so these tests work in both contexts.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Repository
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Repository;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Individual_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Site_Options_Decorator;

class Test_WP_Site_Options_Decorator extends WP_UnitTestCase {

	protected function build(): WP_Site_Options_Decorator {
		return new WP_Site_Options_Decorator( new WP_Options_Settings_Repository() );
	}

	/** @testdox set() stores a value via update_site_option. */
	public function test_set(): void {
		$repo = $this->build();
		$repo->set( 'site_test_set', 'value' );
		$this->assertSame( 'value', \get_site_option( 'site_test_set' ) );
	}

	/** @testdox get() retrieves a stored value via get_site_option. */
	public function test_get(): void {
		\update_site_option( 'site_test_get', 'value' );
		$this->assertSame( 'value', $this->build()->get( 'site_test_get' ) );
	}

	/** @testdox get() returns false when the key doesn't exist. */
	public function test_get_missing(): void {
		$this->assertFalse( $this->build()->get( 'site_missing_xyz' ) );
	}

	/** @testdox delete() removes the value via delete_site_option. */
	public function test_delete(): void {
		\update_site_option( 'site_test_delete', 'gone' );
		$this->build()->delete( 'site_test_delete' );
		$this->assertFalse( \get_site_option( 'site_test_delete' ) );
	}

	/** @testdox has() returns true for an existing value. */
	public function test_has_existing(): void {
		\update_site_option( 'site_test_has', 'value' );
		$this->assertTrue( $this->build()->has( 'site_test_has' ) );
	}

	/** @testdox has() returns false for a missing value. */
	public function test_has_missing(): void {
		$this->assertFalse( $this->build()->has( 'site_missing_xyz' ) );
	}

	/** @testdox allow_grouped() delegates to the inner repository. */
	public function test_allow_grouped_delegates(): void {
		$decorator = new WP_Site_Options_Decorator( new WP_Options_Settings_Repository() );
		$this->assertTrue( $decorator->allow_grouped() );

		$decorator = new WP_Site_Options_Decorator( new WP_Options_Individual_Repository() );
		$this->assertFalse( $decorator->allow_grouped() );
	}
}
