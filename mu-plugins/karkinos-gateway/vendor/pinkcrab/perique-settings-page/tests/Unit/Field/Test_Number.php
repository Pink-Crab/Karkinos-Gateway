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
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;

class Test_Number extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Number::class;
	}

	/** @testdox decimal_places defaults to 0. */
	public function test_default_decimal_places(): void {
		$this->assertSame( 0, Number::new( 'k' )->get_decimal_places() );
	}

	/** @testdox set_decimal_places stores and returns the value. */
	public function test_set_decimal_places(): void {
		$field = Number::new( 'k' )->set_decimal_places( 4 );
		$this->assertSame( 4, $field->get_decimal_places() );
	}

	/** @testdox set_decimal_places returns static for chaining. */
	public function test_set_decimal_places_chains(): void {
		$field = Number::new( 'k' );
		$this->assertSame( $field, $field->set_decimal_places( 2 ) );
	}

	/** @testdox Default sanitiser casts to int when decimal_places <= 1. */
	public function test_sanitiser_int_when_no_decimals(): void {
		$field = Number::new( 'k' );
		$this->assertSame( 42, $field->sanitize( '42.99' ) );
	}

	/** @testdox Default sanitiser rounds to decimal_places when set. */
	public function test_sanitiser_rounds_floats(): void {
		$field = Number::new( 'k' )->set_decimal_places( 2 );
		$this->assertSame( 3.14, $field->sanitize( '3.14159' ) );
	}
}
