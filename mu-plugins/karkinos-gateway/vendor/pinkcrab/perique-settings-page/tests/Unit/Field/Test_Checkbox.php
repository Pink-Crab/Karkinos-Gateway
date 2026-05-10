<?php

declare( strict_types=1 );

/**
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Field
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Field;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;

class Test_Checkbox extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Checkbox::class;
	}

	/** @testdox Checkbox uses the Checked_Value trait, defaulting to "on". */
	public function test_default_checked_value(): void {
		$this->assertSame( 'on', Checkbox::new( 'k' )->get_checked_value() );
	}
}
