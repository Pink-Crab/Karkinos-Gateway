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
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;

class Test_Repeater extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Repeater::class;
	}

	/** @testdox get_fields() returns null before any fields are added. */
	public function test_get_fields_null_by_default(): void {
		$this->assertNull( Repeater::new( 'k' )->get_fields() );
	}

	/** @testdox add_field() initialises a Setting_Collection. */
	public function test_add_field_initialises_collection(): void {
		$repeater = Repeater::new( 'k' )->add_field( Text::new( 'child' ) );
		$this->assertInstanceOf( Setting_Collection::class, $repeater->get_fields() );
	}

	/** @testdox add_field() pushes the field into the collection. */
	public function test_add_field_pushes_field(): void {
		$repeater = Repeater::new( 'k' )
			->add_field( Text::new( 'a' ) )
			->add_field( Text::new( 'b' ) );
		$this->assertCount( 2, $repeater->get_fields()->to_array() );
	}

	/** @testdox add_field() throws when adding a Repeater. */
	public function test_add_field_throws_when_adding_repeater(): void {
		$this->expectException( \Exception::class );
		Repeater::new( 'parent' )->add_field( Repeater::new( 'nested' ) );
	}

	/** @testdox set_add_to_group_label / get_add_to_group_label round-trip. */
	public function test_add_to_group_label(): void {
		$repeater = Repeater::new( 'k' )->set_add_to_group_label( 'Add Row' );
		$this->assertSame( 'Add Row', $repeater->get_add_to_group_label() );
	}

	/** @testdox Default add_to_group_label is "Add". */
	public function test_default_add_to_group_label(): void {
		$this->assertSame( 'Add', Repeater::new( 'k' )->get_add_to_group_label() );
	}

	/** @testdox set_layout accepts "row". */
	public function test_set_layout_row(): void {
		$repeater = Repeater::new( 'k' )->set_layout( 'row' );
		$this->assertSame( 'row', $repeater->get_layout() );
	}

	/** @testdox set_layout accepts "columns". */
	public function test_set_layout_columns(): void {
		$repeater = Repeater::new( 'k' )->set_layout( 'columns' );
		$this->assertSame( 'columns', $repeater->get_layout() );
	}

	/** @testdox set_layout ignores invalid values. */
	public function test_set_layout_ignores_invalid(): void {
		$repeater = Repeater::new( 'k' )->set_layout( 'bogus' );
		$this->assertNotSame( 'bogus', $repeater->get_layout() );
	}

	/** @testdox set_group_class / get_group_class round-trip. */
	public function test_group_class(): void {
		$repeater = Repeater::new( 'k' )->set_group_class( 'my-group' );
		$this->assertSame( 'my-group', $repeater->get_group_class() );
	}

	/** @testdox Default group_class is repeater-group. */
	public function test_default_group_class(): void {
		$this->assertSame( 'repeater-group', Repeater::new( 'k' )->get_group_class() );
	}
}
