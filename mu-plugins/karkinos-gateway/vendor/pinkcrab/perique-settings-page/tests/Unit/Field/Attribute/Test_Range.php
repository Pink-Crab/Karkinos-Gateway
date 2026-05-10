<?php

declare( strict_types=1 );

/**
 * Unit tests for the Range attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Range;

class Test_Range extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'number') extends Field {
			use Range;
		};
	}

	/** @testdox set_min() stores the min value as a string. */
	public function test_set_min_int(): void {
		$field = $this->get_field();
		$field->set_min( 5 );
		$this->assertSame( '5', $field->get_min() );
	}

	/** @testdox set_min() accepts a string. */
	public function test_set_min_string(): void {
		$field = $this->get_field();
		$field->set_min( '0.5' );
		$this->assertSame( '0.5', $field->get_min() );
	}

	/** @testdox has_min()/get_min() defaults. */
	public function test_min_defaults(): void {
		$field = $this->get_field();
		$this->assertFalse( $field->has_min() );
		$this->assertNull( $field->get_min() );
	}

	/** @testdox has_min() returns true after set. */
	public function test_has_min(): void {
		$field = $this->get_field();
		$field->set_min( 1 );
		$this->assertTrue( $field->has_min() );
	}

	/** @testdox set_max() stores the max value. */
	public function test_set_max(): void {
		$field = $this->get_field();
		$field->set_max( 100 );
		$this->assertSame( '100', $field->get_max() );
	}

	/** @testdox has_max()/get_max() defaults. */
	public function test_max_defaults(): void {
		$field = $this->get_field();
		$this->assertFalse( $field->has_max() );
		$this->assertNull( $field->get_max() );
	}

	/** @testdox has_max() returns true after set. */
	public function test_has_max(): void {
		$field = $this->get_field();
		$field->set_max( 10 );
		$this->assertTrue( $field->has_max() );
	}

	/** @testdox set_step() stores the step value. */
	public function test_set_step(): void {
		$field = $this->get_field();
		$field->set_step( 0.1 );
		$this->assertSame( '0.1', $field->get_step() );
	}

	/** @testdox has_step()/get_step() defaults. */
	public function test_step_defaults(): void {
		$field = $this->get_field();
		$this->assertFalse( $field->has_step() );
		$this->assertNull( $field->get_step() );
	}

	/** @testdox has_step() returns true after set. */
	public function test_has_step(): void {
		$field = $this->get_field();
		$field->set_step( 1 );
		$this->assertTrue( $field->has_step() );
	}

	/** @testdox All Range setters return the field for chaining. */
	public function test_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_min( 1 ) );
		$this->assertSame( $field, $field->set_max( 10 ) );
		$this->assertSame( $field, $field->set_step( 1 ) );
	}
}
