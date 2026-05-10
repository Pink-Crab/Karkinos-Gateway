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
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;

class Test_Select extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Select::class;
	}

	/** @testdox Select uses the Multiple trait. */
	public function test_can_set_multiple(): void {
		$field = Select::new( 'k' );
		$field->set_multiple();
		$this->assertTrue( $field->is_multiple() );
	}

	/** @testdox Select uses the Options trait. */
	public function test_can_set_options(): void {
		$field = Select::new( 'k' );
		$field->set_option( 'a', 'A' );
		$this->assertTrue( $field->has_option( 'a' ) );
	}

	/** @testdox Default sanitiser runs sanitize_text_field on scalar values. */
	public function test_default_sanitiser(): void {
		$this->assertSame( 'cleaned', Select::new( 'k' )->sanitize( '<p>cleaned</p>' ) );
	}

	/** @testdox Default sanitiser maps over arrays so multi-select values survive. */
	public function test_default_sanitiser_handles_arrays(): void {
		$this->assertSame(
			array( 'a', 'b' ),
			Select::new( 'k' )->sanitize( array( '<p>a</p>', 'b' ) )
		);
	}
}
