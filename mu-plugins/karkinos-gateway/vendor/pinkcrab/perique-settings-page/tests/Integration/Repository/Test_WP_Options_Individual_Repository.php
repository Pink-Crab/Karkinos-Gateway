<?php

declare( strict_types=1 );

/**
 * Integration tests for WP_Options_Individual_Repository.
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
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Individual_Repository;

class Test_WP_Options_Individual_Repository extends WP_UnitTestCase {

	/** @testdox set() stores a value with the prefix applied. */
	public function test_set_with_prefix(): void {
		$repo = new WP_Options_Individual_Repository( 'myapp_' );
		$repo->set( 'name', 'Glynn' );
		$this->assertSame( 'Glynn', \get_option( 'myapp_name' ) );
	}

	/** @testdox set() stores a value without a prefix when none is given. */
	public function test_set_no_prefix(): void {
		$repo = new WP_Options_Individual_Repository();
		$repo->set( 'lonely_option', 'solo' );
		$this->assertSame( 'solo', \get_option( 'lonely_option' ) );
	}

	/** @testdox get() retrieves a stored prefixed value. */
	public function test_get_with_prefix(): void {
		\update_option( 'myapp_get', 'value' );
		$repo = new WP_Options_Individual_Repository( 'myapp_' );
		$this->assertSame( 'value', $repo->get( 'get' ) );
	}

	/** @testdox get() returns false when the option doesn't exist. */
	public function test_get_missing_returns_false(): void {
		$repo = new WP_Options_Individual_Repository( 'myapp_' );
		$this->assertFalse( $repo->get( 'no_such_thing' ) );
	}

	/** @testdox delete() removes the prefixed option. */
	public function test_delete(): void {
		\update_option( 'myapp_delete_me', 'gone' );
		$repo = new WP_Options_Individual_Repository( 'myapp_' );
		$this->assertTrue( $repo->delete( 'delete_me' ) );
		$this->assertFalse( \get_option( 'myapp_delete_me' ) );
	}

	/** @testdox has() returns true for an existing prefixed option. */
	public function test_has_existing(): void {
		\update_option( 'myapp_has_me', 'yes' );
		$repo = new WP_Options_Individual_Repository( 'myapp_' );
		$this->assertTrue( $repo->has( 'has_me' ) );
	}

	/** @testdox has() returns false for a missing option. */
	public function test_has_missing(): void {
		$repo = new WP_Options_Individual_Repository( 'myapp_' );
		$this->assertFalse( $repo->has( 'never_set' ) );
	}

	/** @testdox allow_grouped() returns false. */
	public function test_allow_grouped(): void {
		$repo = new WP_Options_Individual_Repository();
		$this->assertFalse( $repo->allow_grouped() );
	}
}
