<?php

declare( strict_types=1 );

/**
 * Unit tests for the Data attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;

class Test_Data extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'text') extends Field {
			use Data;
		};
	}

	/** @testdox set_data() stores a data attribute. */
	public function test_set_data(): void {
		$field = $this->get_field();
		$field->set_data( 'foo', 'bar' );
		$this->assertSame( 'bar', $field->get_data( 'foo' ) );
	}

	/** @testdox has_data() returns false when key not set. */
	public function test_has_data_false_by_default(): void {
		$this->assertFalse( $this->get_field()->has_data( 'missing' ) );
	}

	/** @testdox has_data() returns true after set. */
	public function test_has_data_true_after_set(): void {
		$field = $this->get_field();
		$field->set_data( 'role', 'admin' );
		$this->assertTrue( $field->has_data( 'role' ) );
	}

	/** @testdox get_data() returns null when key not set. */
	public function test_get_data_returns_null_when_not_set(): void {
		$this->assertNull( $this->get_field()->get_data( 'missing' ) );
	}

	/** @testdox set_data() returns the field for chaining. */
	public function test_set_data_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_data( 'a', 'b' ) );
	}

	/** @testdox Multiple data keys can be stored. */
	public function test_multiple_data_keys(): void {
		$field = $this->get_field();
		$field->set_data( 'one', '1' );
		$field->set_data( 'two', '2' );
		$this->assertSame( '1', $field->get_data( 'one' ) );
		$this->assertSame( '2', $field->get_data( 'two' ) );
	}
}
