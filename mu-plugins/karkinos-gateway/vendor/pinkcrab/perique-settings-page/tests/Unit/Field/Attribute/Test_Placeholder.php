<?php

declare( strict_types=1 );

/**
 * Unit tests for the Placeholder attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Placeholder;

class Test_Placeholder extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'text') extends Field {
			use Placeholder;
		};
	}

	/** @testdox set_placeholder() stores the placeholder as an attribute. */
	public function test_set_placeholder(): void {
		$field = $this->get_field();
		$field->set_placeholder( 'Enter text' );
		$this->assertSame( 'Enter text', $field->get_placeholder() );
	}

	/** @testdox has_placeholder() returns false when not set. */
	public function test_has_placeholder_false_by_default(): void {
		$this->assertFalse( $this->get_field()->has_placeholder() );
	}

	/** @testdox has_placeholder() returns true after set. */
	public function test_has_placeholder_true_after_set(): void {
		$field = $this->get_field();
		$field->set_placeholder( 'Hi' );
		$this->assertTrue( $field->has_placeholder() );
	}

	/** @testdox get_placeholder() returns null when not set. */
	public function test_get_placeholder_returns_null_when_not_set(): void {
		$this->assertNull( $this->get_field()->get_placeholder() );
	}

	/** @testdox set_placeholder() returns the field for chaining. */
	public function test_set_placeholder_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_placeholder( 'Hi' ) );
	}
}
