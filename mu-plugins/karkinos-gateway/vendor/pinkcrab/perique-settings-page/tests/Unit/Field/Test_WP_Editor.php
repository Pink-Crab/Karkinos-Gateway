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
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;

class Test_WP_Editor extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return WP_Editor::class;
	}

	/** @testdox get_options() returns an empty array by default. */
	public function test_default_options(): void {
		$this->assertSame( array(), WP_Editor::new( 'k' )->get_options() );
	}

	/** @testdox set_options() stores the options array. */
	public function test_set_options(): void {
		$opts  = array( 'media_buttons' => false, 'textarea_rows' => 5 );
		$field = WP_Editor::new( 'k' )->set_options( $opts );
		$this->assertSame( $opts, $field->get_options() );
	}

	/** @testdox set_options() returns the field for chaining. */
	public function test_set_options_chains(): void {
		$field = WP_Editor::new( 'k' );
		$this->assertSame( $field, $field->set_options( array() ) );
	}
}
