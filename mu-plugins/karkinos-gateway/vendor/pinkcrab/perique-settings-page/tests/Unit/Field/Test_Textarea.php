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
use PinkCrab\Perique_Settings_Page\Setting\Field\Textarea;

class Test_Textarea extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Textarea::class;
	}

	/** @testdox rows and cols default to null. */
	public function test_default_rows_cols(): void {
		$field = Textarea::new( 'k' );
		$this->assertNull( $field->get_rows() );
		$this->assertNull( $field->get_cols() );
	}

	/** @testdox set_rows stores the row count. */
	public function test_set_rows(): void {
		$field = Textarea::new( 'k' )->set_rows( 5 );
		$this->assertSame( 5, $field->get_rows() );
	}

	/** @testdox set_cols stores the column count. */
	public function test_set_cols(): void {
		$field = Textarea::new( 'k' )->set_cols( 40 );
		$this->assertSame( 40, $field->get_cols() );
	}

	/** @testdox set_rows returns the field for chaining. */
	public function test_set_rows_chains(): void {
		$field = Textarea::new( 'k' );
		$this->assertSame( $field, $field->set_rows( 3 ) );
	}

	/** @testdox set_cols returns the field for chaining. */
	public function test_set_cols_chains(): void {
		$field = Textarea::new( 'k' );
		$this->assertSame( $field, $field->set_cols( 20 ) );
	}
}
