<?php

declare( strict_types=1 );

/**
 * Unit tests for the Pattern attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Pattern;

class Test_Pattern extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'text') extends Field {
			use Pattern;
		};
	}

	/** @testdox set_pattern() stores the pattern as an attribute. */
	public function test_set_pattern(): void {
		$field = $this->get_field();
		$field->set_pattern( '[a-z]+' );
		$this->assertSame( '[a-z]+', $field->get_pattern() );
	}

	/** @testdox has_pattern() returns false when not set. */
	public function test_has_pattern_false_by_default(): void {
		$this->assertFalse( $this->get_field()->has_pattern() );
	}

	/** @testdox has_pattern() returns true after set. */
	public function test_has_pattern_true_after_set(): void {
		$field = $this->get_field();
		$field->set_pattern( '\d+' );
		$this->assertTrue( $field->has_pattern() );
	}

	/** @testdox get_pattern() returns null when not set. */
	public function test_get_pattern_returns_null_when_not_set(): void {
		$this->assertNull( $this->get_field()->get_pattern() );
	}

	/** @testdox set_pattern() returns the field for chaining. */
	public function test_set_pattern_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_pattern( 'x' ) );
	}
}
