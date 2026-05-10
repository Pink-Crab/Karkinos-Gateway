<?php

declare( strict_types=1 );

/**
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Field
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Field;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox_Group;

class Test_Checkbox_Group extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Checkbox_Group::class;
	}

	/** @testdox Checkbox_Group uses the Options trait. */
	public function test_can_set_options(): void {
		$field = Checkbox_Group::new( 'k' );
		$field->set_option( 'a', 'A' );
		$this->assertTrue( $field->has_option( 'a' ) );
	}

	/** @testdox Checkbox_Group::get_value() always returns an array. */
	public function test_get_value_always_array(): void {
		$field = Checkbox_Group::new( 'k' )->set_value( array( 'a', 'b' ) );
		$this->assertSame( array( 'a', 'b' ), $field->get_value() );
	}

	/** @testdox Checkbox_Group::get_value() wraps a scalar value in an array. */
	public function test_get_value_wraps_scalar(): void {
		$field = Checkbox_Group::new( 'k' )->set_value( 'single' );
		$this->assertSame( array( 'single' ), $field->get_value() );
	}

	/**
	 * Override the shared test that checks set_value/get_value round-trips a scalar,
	 * since Checkbox_Group always returns an array.
	 *
	 * @testdox [Shared::Field] set_value/get_value round-trips a value (overridden for Checkbox_Group).
	 */
	public function test_value(): void {
		$field = Checkbox_Group::new( 'k' )->set_value( array( 'a', 'b' ) );
		$this->assertSame( array( 'a', 'b' ), $field->get_value() );
	}
}
