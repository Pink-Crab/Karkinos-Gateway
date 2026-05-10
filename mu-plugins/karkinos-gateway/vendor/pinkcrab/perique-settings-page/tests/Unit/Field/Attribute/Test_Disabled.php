<?php

declare( strict_types=1 );

/**
 * Unit tests for the Disabled attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Disabled;

class Test_Disabled extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'text') extends Field {
			use Disabled;
		};
	}

	/** @testdox set_disabled() adds the disabled flag. */
	public function test_set_disabled_default_true(): void {
		$field = $this->get_field();
		$field->set_disabled();
		$this->assertTrue( $field->is_disabled() );
	}

	/** @testdox is_disabled() returns false by default. */
	public function test_is_disabled_false_by_default(): void {
		$this->assertFalse( $this->get_field()->is_disabled() );
	}

	/** @testdox set_disabled(false) removes the flag. */
	public function test_set_disabled_false_removes_flag(): void {
		$field = $this->get_field();
		$field->set_disabled( true );
		$this->assertTrue( $field->is_disabled() );
		$field->set_disabled( false );
		$this->assertFalse( $field->is_disabled() );
	}

	/** @testdox Calling set_disabled multiple times only adds the flag once. */
	public function test_set_disabled_idempotent_when_already_set(): void {
		$field = $this->get_field();
		$field->set_disabled();
		$field->set_disabled();
		$this->assertTrue( $field->is_disabled() );
	}

	/** @testdox set_disabled() returns the field for chaining. */
	public function test_set_disabled_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_disabled() );
	}
}
