<?php

declare( strict_types=1 );

/**
 * Unit tests for the Multiple attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Multiple;

class Test_Multiple extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'select') extends Field {
			use Multiple;
		};
	}

	/** @testdox set_multiple() defaults to true and adds the flag. */
	public function test_set_multiple_default(): void {
		$field = $this->get_field();
		$field->set_multiple();
		$this->assertTrue( $field->is_multiple() );
	}

	/** @testdox is_multiple() returns false by default. */
	public function test_is_multiple_false_by_default(): void {
		$this->assertFalse( $this->get_field()->is_multiple() );
	}

	/** @testdox set_multiple(false) removes the flag if previously set. */
	public function test_set_multiple_false_removes_flag(): void {
		$field = $this->get_field();
		$field->set_multiple( true );
		$this->assertTrue( $field->is_multiple() );
		$field->set_multiple( false );
		$this->assertFalse( $field->is_multiple() );
	}

	/** @testdox Calling set_multiple multiple times only adds the flag once. */
	public function test_set_multiple_idempotent_when_already_set(): void {
		$field = $this->get_field();
		$field->set_multiple();
		$field->set_multiple();
		$this->assertTrue( $field->is_multiple() );
	}

	/** @testdox set_multiple() returns the field for chaining. */
	public function test_set_multiple_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_multiple() );
	}
}
