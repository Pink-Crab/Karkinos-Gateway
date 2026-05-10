<?php

declare( strict_types=1 );

/**
 * Unit tests for the Cast helper class.
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
use PinkCrab\Perique_Settings_Page\Util\Cast;

class Test_Cast extends WP_UnitTestCase {

	/** @testdox to_string() casts a string to itself. */
	public function test_to_string_with_string(): void {
		$this->assertSame( 'hello', Cast::to_string( 'hello' ) );
	}

	/** @testdox to_string() casts an integer to its string representation. */
	public function test_to_string_with_int(): void {
		$this->assertSame( '42', Cast::to_string( 42 ) );
	}

	/** @testdox to_string() casts a float to its string representation. */
	public function test_to_string_with_float(): void {
		$this->assertSame( '3.14', Cast::to_string( 3.14 ) );
	}

	/** @testdox to_string() casts a boolean true to "1". */
	public function test_to_string_with_true(): void {
		$this->assertSame( '1', Cast::to_string( true ) );
	}

	/** @testdox to_string() casts a boolean false to empty string. */
	public function test_to_string_with_false(): void {
		$this->assertSame( '', Cast::to_string( false ) );
	}

	/** @testdox to_string() returns null for an array by default. */
	public function test_to_string_with_array_returns_null(): void {
		$this->assertNull( Cast::to_string( array( 'a', 'b' ) ) );
	}

	/** @testdox to_string() returns null for null by default. */
	public function test_to_string_with_null(): void {
		$this->assertNull( Cast::to_string( null ) );
	}

	/** @testdox to_string() returns the fallback when the value cannot be cast. */
	public function test_to_string_with_array_returns_fallback(): void {
		$this->assertSame( 'fallback', Cast::to_string( array(), 'fallback' ) );
	}

	/** @testdox to_string() returns the fallback for null. */
	public function test_to_string_with_null_returns_fallback(): void {
		$this->assertSame( 'fallback', Cast::to_string( null, 'fallback' ) );
	}

	/** @testdox to_string() casts a Stringable object via __toString(). */
	public function test_to_string_with_stringable_object(): void {
		$obj = new class() implements \Stringable {
			public function __toString(): string {
				return 'stringable';
			}
		};
		$this->assertSame( 'stringable', Cast::to_string( $obj ) );
	}

	/** @testdox to_string() returns null for a non-Stringable object. */
	public function test_to_string_with_object_returns_null(): void {
		$obj = new \stdClass();
		$this->assertNull( Cast::to_string( $obj ) );
	}

	/** @testdox to_int() casts a numeric string to int. */
	public function test_to_int_with_numeric_string(): void {
		$this->assertSame( 42, Cast::to_int( '42' ) );
	}

	/** @testdox to_int() casts an int to itself. */
	public function test_to_int_with_int(): void {
		$this->assertSame( 99, Cast::to_int( 99 ) );
	}

	/** @testdox to_int() casts a float to int (truncates). */
	public function test_to_int_with_float(): void {
		$this->assertSame( 3, Cast::to_int( 3.99 ) );
	}

	/** @testdox to_int() returns 0 by default for a non-numeric value. */
	public function test_to_int_with_non_numeric_returns_default(): void {
		$this->assertSame( 0, Cast::to_int( 'abc' ) );
	}

	/** @testdox to_int() returns the fallback for a non-numeric value. */
	public function test_to_int_with_non_numeric_returns_fallback(): void {
		$this->assertSame( -1, Cast::to_int( 'abc', -1 ) );
	}

	/** @testdox to_int() returns the fallback for null. */
	public function test_to_int_with_null(): void {
		$this->assertSame( 7, Cast::to_int( null, 7 ) );
	}

	/** @testdox to_int() returns the fallback for an array. */
	public function test_to_int_with_array(): void {
		$this->assertSame( 5, Cast::to_int( array( 1, 2, 3 ), 5 ) );
	}

	/** @testdox esc_attr() escapes a scalar value. */
	public function test_esc_attr_with_string(): void {
		$this->assertSame( esc_attr( 'hello&world' ), Cast::esc_attr( 'hello&world' ) );
	}

	/** @testdox esc_attr() returns the fallback for non-castable values. */
	public function test_esc_attr_with_array_returns_fallback(): void {
		$this->assertSame( esc_attr( 'fallback' ), Cast::esc_attr( array(), 'fallback' ) );
	}

	/** @testdox esc_attr() returns empty string by default for non-castable values. */
	public function test_esc_attr_with_array_default_fallback(): void {
		$this->assertSame( '', Cast::esc_attr( array() ) );
	}

	/** @testdox esc_attr() escapes an integer. */
	public function test_esc_attr_with_int(): void {
		$this->assertSame( '42', Cast::esc_attr( 42 ) );
	}
}
