<?php

declare( strict_types=1 );

/**
 * Unit tests for the Field_Group class.
 *
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Test_Field_Group extends WP_UnitTestCase {

	protected function get_group(): Field_Group {
		return Field_Group::of(
			'address',
			Text::new( 'line_1' ),
			Text::new( 'city' )
		);
	}

	/** @testdox Field_Group is a Renderable. */
	public function test_is_renderable(): void {
		$this->assertInstanceOf( Renderable::class, $this->get_group() );
	}

	/** @testdox of() stores the key. */
	public function test_get_key(): void {
		$this->assertSame( 'address', $this->get_group()->get_key() );
	}

	/** @testdox get_type returns "field_group". */
	public function test_get_type(): void {
		$this->assertSame( 'field_group', $this->get_group()->get_type() );
	}

	/** @testdox get_fields returns the child fields. */
	public function test_get_fields(): void {
		$this->assertCount( 2, $this->get_group()->get_fields() );
	}

	/** @testdox set_label / get_label round-trip. */
	public function test_label(): void {
		$group = $this->get_group()->set_label( 'Address' );
		$this->assertSame( 'Address', $group->get_label() );
	}

	/** @testdox Default label is empty string. */
	public function test_default_label(): void {
		$this->assertSame( '', $this->get_group()->get_label() );
	}

	/** @testdox set_description / get_description round-trip. */
	public function test_description(): void {
		$group = $this->get_group()->set_description( 'Where you live' );
		$this->assertSame( 'Where you live', $group->get_description() );
	}

	/** @testdox Default description is empty string. */
	public function test_default_description(): void {
		$this->assertSame( '', $this->get_group()->get_description() );
	}

	/** @testdox set_value stores an array and hydrates child fields. */
	public function test_set_value_hydrates_children(): void {
		$group  = $this->get_group()->set_value( array( 'line_1' => '1 Foo St', 'city' => 'Bar' ) );
		$fields = $group->get_fields();
		$this->assertSame( '1 Foo St', $fields[0]->get_value() );
		$this->assertSame( 'Bar', $fields[1]->get_value() );
	}

	/** @testdox set_value with a non-array stores an empty array. */
	public function test_set_value_with_non_array(): void {
		$group = $this->get_group()->set_value( 'not-an-array' );
		$this->assertSame( array(), $group->get_value() );
	}

	/** @testdox get() retrieves a child value by key. */
	public function test_get_child_value(): void {
		$group = $this->get_group()->set_value( array( 'line_1' => '1 Foo St' ) );
		$this->assertSame( '1 Foo St', $group->get( 'line_1' ) );
	}

	/** @testdox get() returns the fallback when key is missing. */
	public function test_get_child_value_fallback(): void {
		$this->assertSame( 'fallback', $this->get_group()->get( 'missing', 'fallback' ) );
	}

	/** @testdox sanitize() runs every child sanitiser. */
	public function test_sanitize(): void {
		$group  = $this->get_group();
		$result = $group->sanitize( array( 'line_1' => '<p>1 Foo St</p>', 'city' => '<b>Bar</b>' ) );
		$this->assertSame( '1 Foo St', $result['line_1'] );
		$this->assertSame( 'Bar', $result['city'] );
	}

	/** @testdox sanitize() handles missing child values as empty strings. */
	public function test_sanitize_missing_value(): void {
		$result = $this->get_group()->sanitize( array() );
		$this->assertArrayHasKey( 'line_1', $result );
		$this->assertArrayHasKey( 'city', $result );
	}

	/** @testdox validate() returns no errors when all children pass. */
	public function test_validate_passes(): void {
		$errors = $this->get_group()->validate( array( 'line_1' => 'a', 'city' => 'b' ) );
		$this->assertEmpty( $errors );
	}

	/** @testdox validate() returns errors keyed by child key when a child fails. */
	public function test_validate_returns_errors(): void {
		$group = Field_Group::of(
			'g',
			Text::new( 'must_be_x' )->set_validate( fn( $v ): bool => 'x' === $v )
		);
		$errors = $group->validate( array( 'must_be_x' => 'y' ) );
		$this->assertArrayHasKey( 'must_be_x', $errors );
	}
}
