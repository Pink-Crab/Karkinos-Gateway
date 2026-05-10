<?php

declare( strict_types=1 );

/**
 * Unit tests for the Autocomplete attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Autocomplete;

class Test_Autocomplete extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'text') extends Field {
			use Autocomplete;
		};
	}

	/** @testdox set_autocomplete() stores the autocomplete value. */
	public function test_set_autocomplete(): void {
		$field = $this->get_field();
		$field->set_autocomplete( 'email' );
		$this->assertSame( 'email', $field->get_autocomplete() );
	}

	/** @testdox has_autocomplete() returns false by default. */
	public function test_has_autocomplete_false_by_default(): void {
		$this->assertFalse( $this->get_field()->has_autocomplete() );
	}

	/** @testdox has_autocomplete() returns true after set. */
	public function test_has_autocomplete_true_after_set(): void {
		$field = $this->get_field();
		$field->set_autocomplete( 'off' );
		$this->assertTrue( $field->has_autocomplete() );
	}

	/** @testdox get_autocomplete() returns null when not set. */
	public function test_get_autocomplete_returns_null_when_not_set(): void {
		$this->assertNull( $this->get_field()->get_autocomplete() );
	}

	/** @testdox set_autocomplete() returns the field for chaining. */
	public function test_set_autocomplete_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_autocomplete( 'name' ) );
	}
}
