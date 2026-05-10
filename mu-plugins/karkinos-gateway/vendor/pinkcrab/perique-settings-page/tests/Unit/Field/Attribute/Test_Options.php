<?php

declare( strict_types=1 );

/**
 * Unit tests for the Options attribute trait.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Attribute
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Field\Attribute;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Options;

class Test_Options extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'select') extends Field {
			use Options;
		};
	}

	/** @testdox set_option() stores a single option in the default group. */
	public function test_set_single_option(): void {
		$field = $this->get_field();
		$field->set_option( 'a', 'Apple' );
		$this->assertTrue( $field->has_option( 'a' ) );
		$this->assertSame( array( 'a' => 'Apple' ), $field->get_options() );
	}

	/** @testdox set_option() can group options by group name. */
	public function test_set_option_with_group(): void {
		$field = $this->get_field();
		$field->set_option( 'a', 'Apple', 'Fruit' );
		$field->set_option( 'd', 'Dog', 'Animal' );
		$options = $field->get_options();
		$this->assertArrayHasKey( 'Fruit', $options );
		$this->assertArrayHasKey( 'Animal', $options );
		$this->assertSame( 'Apple', $options['Fruit']['a'] );
		$this->assertSame( 'Dog', $options['Animal']['d'] );
	}

	/** @testdox has_option() returns false when option not set. */
	public function test_has_option_false_when_missing(): void {
		$this->assertFalse( $this->get_field()->has_option( 'missing' ) );
	}

	/** @testdox has_option() can check within a specific group. */
	public function test_has_option_in_group(): void {
		$field = $this->get_field();
		$field->set_option( 'x', 'X', 'group' );
		$this->assertTrue( $field->has_option( 'x', 'group' ) );
		$this->assertFalse( $field->has_option( 'x', 'other' ) );
	}

	/** @testdox set_option() returns the field for chaining. */
	public function test_set_option_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_option( 'a', 'A' ) );
	}

	/** @testdox get_options() returns multiple options in the default group. */
	public function test_get_options_with_multiple_default(): void {
		$field = $this->get_field();
		$field->set_option( 'a', 'A' );
		$field->set_option( 'b', 'B' );
		$this->assertSame( array( 'a' => 'A', 'b' => 'B' ), $field->get_options() );
	}
}
