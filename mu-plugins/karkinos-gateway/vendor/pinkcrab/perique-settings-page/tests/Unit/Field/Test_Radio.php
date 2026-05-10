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
use PinkCrab\Perique_Settings_Page\Setting\Field\Radio;

class Test_Radio extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Radio::class;
	}

	/** @testdox Radio uses the Options trait. */
	public function test_can_set_options(): void {
		$field = Radio::new( 'k' );
		$field->set_option( 'a', 'A' );
		$this->assertTrue( $field->has_option( 'a' ) );
	}
}
