<?php

declare( strict_types=1 );

/**
 * Unit tests for the Checked_Value attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Checked_Value;

class Test_Checked_Value extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'checkbox') extends Field {
			use Checked_Value;
		};
	}

	/** @testdox get_checked_value() defaults to "on". */
	public function test_default_checked_value(): void {
		$this->assertSame( 'on', $this->get_field()->get_checked_value() );
	}

	/** @testdox set_checked_value() updates the stored value. */
	public function test_set_checked_value(): void {
		$field = $this->get_field();
		$field->set_checked_value( 'yes' );
		$this->assertSame( 'yes', $field->get_checked_value() );
	}

	/** @testdox set_checked_value() returns the field for chaining. */
	public function test_set_checked_value_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_checked_value( '1' ) );
	}
}
