<?php

declare(strict_types=1);

/**
 * Unit tests for the Field base class.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;

class Test_Field extends WP_UnitTestCase {

	/** @testdox It should be possible to create a field using the static make() constructor. */
	public function test_make_constructor(): void {
		$field = Text::make( 'my_field' );
		$this->assertInstanceOf( Text::class, $field );
		$this->assertEquals( 'my_field', $field->get_key() );
		$this->assertEquals( 'text', $field->get_type() );
	}

	/** @testdox It should be possible to create a field using the static new() constructor. */
	public function test_new_constructor(): void {
		$field = Text::new( 'my_field' );
		$this->assertInstanceOf( Text::class, $field );
		$this->assertEquals( 'my_field', $field->get_key() );
	}

	/** @testdox It should be possible to set and get the field ID. */
	public function test_id(): void {
		$field = Text::make( 'test' );
		$this->assertNull( $field->get_id() );

		$result = $field->set_id( 'my-id' );
		$this->assertSame( $field, $result );
		$this->assertEquals( 'my-id', $field->get_id() );
	}

	/** @testdox It should be possible to add and get CSS classes. */
	public function test_classes(): void {
		$field = Text::make( 'test' );
		$this->assertEmpty( $field->get_classes() );

		$field->add_class( 'foo' );
		$field->add_class( 'bar' );
		$this->assertEquals( array( 'foo', 'bar' ), $field->get_classes() );
	}

	/** @testdox It should be possible to set and get before content. */
	public function test_before(): void {
		$field = Text::make( 'test' );
		$this->assertNull( $field->get_before() );

		$result = $field->set_before( '<div class="wrap">' );
		$this->assertSame( $field, $result );
		$this->assertEquals( '<div class="wrap">', $field->get_before() );
	}

	/** @testdox It should be possible to set and get after content. */
	public function test_after(): void {
		$field = Text::make( 'test' );
		$this->assertNull( $field->get_after() );

		$result = $field->set_after( '</div>' );
		$this->assertSame( $field, $result );
		$this->assertEquals( '</div>', $field->get_after() );
	}

	/** @testdox It should be possible to set and get a config callback. */
	public function test_config_callback(): void {
		$field = Text::make( 'test' );
		$this->assertNull( $field->get_config() );

		$callback = function ( $element ) {
			return $element;
		};

		$result = $field->set_config( $callback );
		$this->assertSame( $field, $result );
		$this->assertSame( $callback, $field->get_config() );
	}

	/** @testdox It should be possible to set and get the label. */
	public function test_label(): void {
		$field = Text::make( 'test' );
		$this->assertEquals( '', $field->get_label() );

		$field->set_label( 'My Label' );
		$this->assertEquals( 'My Label', $field->get_label() );
	}

	/** @testdox It should be possible to set and get the value. */
	public function test_value(): void {
		$field = Text::make( 'test' );
		$this->assertEquals( '', $field->get_value() );

		$field->set_value( 'hello' );
		$this->assertEquals( 'hello', $field->get_value() );
	}

	/** @testdox It should be possible to set and get the description. */
	public function test_description(): void {
		$field = Text::make( 'test' );
		$this->assertEquals( '', $field->get_description() );

		$field->set_description( 'Help text here' );
		$this->assertEquals( 'Help text here', $field->get_description() );
	}

	/** @testdox It should be possible to set and check the required state. */
	public function test_required(): void {
		$field = Text::make( 'test' );
		$this->assertFalse( $field->is_required() );

		$field->set_required();
		$this->assertTrue( $field->is_required() );

		$field->set_required( false );
		$this->assertFalse( $field->is_required() );
	}

	/** @testdox It should be possible to set and check the read-only state. */
	public function test_read_only(): void {
		$field = Text::make( 'test' );
		$this->assertFalse( $field->is_read_only() );

		$field->set_read_only();
		$this->assertTrue( $field->is_read_only() );
	}

	/** @testdox It should be possible to set and get custom attributes. */
	public function test_attributes(): void {
		$field = Text::make( 'test' );
		$this->assertEmpty( $field->get_attributes() );

		$field->set_attribute( 'data-foo', 'bar' );
		$field->set_attribute( 'title', 'My Title' );
		$this->assertEquals(
			array( 'data-foo' => 'bar', 'title' => 'My Title' ),
			$field->get_attributes()
		);
	}

	/** @testdox It should be possible to set and get the icon. */
	public function test_icon(): void {
		$field = Text::make( 'test' );
		$this->assertNull( $field->get_icon() );

		$field->set_icon( 'dashicons-admin-settings' );
		$this->assertEquals( 'dashicons-admin-settings', $field->get_icon() );
	}

	/** @testdox It should be possible to set a sanitise callback and sanitise a value. */
	public function test_sanitize(): void {
		$field = Text::make( 'test' );
		$field->set_sanitize( 'strtoupper' );
		$this->assertEquals( 'HELLO', $field->sanitize( 'hello' ) );
	}

	/** @testdox It should be possible to validate a value using a callable. */
	public function test_validate_with_callable(): void {
		$field = Text::make( 'test' );
		$field->set_validate( 'is_numeric' );
		$this->assertTrue( $field->validate( '123' ) );
		$this->assertFalse( $field->validate( 'abc' ) );
	}

	/** @testdox It should be possible to validate a value using Respect\Validation. */
	public function test_validate_with_respect_validator(): void {
		$field = Text::make( 'test' );
		$field->set_validate( \Respect\Validation\Validator::stringType()->length( 1, 5 ) );
		$this->assertTrue( $field->validate( 'hi' ) );
		$this->assertFalse( $field->validate( 'too long string' ) );
	}

	/** @testdox A field with no validate callback should always pass validation. */
	public function test_validate_default(): void {
		$field = new Field( 'test', 'text' );
		$this->assertTrue( $field->validate( 'anything' ) );
	}

	/** @testdox A field with no sanitise callback should return the value unchanged. */
	public function test_sanitize_default(): void {
		$field = new Field( 'test', 'text' );
		$this->assertEquals( 'unchanged', $field->sanitize( 'unchanged' ) );
	}

	/** @testdox It should be possible to clone a field with a new key. */
	public function test_clone_as(): void {
		$field = Text::make( 'original' )
			->set_label( 'My Label' )
			->set_id( 'my-id' );

		$clone = $field->clone_as( 'cloned' );
		$this->assertEquals( 'cloned', $clone->get_key() );
		$this->assertEquals( 'My Label', $clone->get_label() );
		$this->assertEquals( 'my-id', $clone->get_id() );
		$this->assertNotSame( $field, $clone );
	}

	/** @testdox Fluent methods should return the same instance for chaining. */
	public function test_fluent_chaining(): void {
		$field = Text::make( 'test' )
			->set_label( 'Label' )
			->set_description( 'Desc' )
			->set_id( 'my-id' )
			->add_class( 'foo' )
			->set_before( '<div>' )
			->set_after( '</div>' )
			->set_required()
			->set_read_only()
			->set_icon( 'dashicons-admin-generic' )
			->set_attribute( 'title', 'test' );

		$this->assertInstanceOf( Text::class, $field );
		$this->assertEquals( 'Label', $field->get_label() );
		$this->assertEquals( 'Desc', $field->get_description() );
		$this->assertEquals( 'my-id', $field->get_id() );
		$this->assertEquals( array( 'foo' ), $field->get_classes() );
		$this->assertEquals( '<div>', $field->get_before() );
		$this->assertEquals( '</div>', $field->get_after() );
		$this->assertTrue( $field->is_required() );
		$this->assertTrue( $field->is_read_only() );
	}

	/** @testdox The config callback should be stored in the callbacks array. */
	public function test_config_in_callbacks_array(): void {
		$field    = Text::make( 'test' );
		$callback = function ( $el ) { return $el; };
		$field->set_config( $callback );

		// Verify it's accessible via get_config.
		$this->assertSame( $callback, $field->get_config() );
	}

	/** @testdox set_flag adds a flag to the flags array. */
	public function test_set_flag(): void {
		$field = Text::make( 'test' );
		$this->assertSame( $field, $field->set_flag( 'my-flag' ) );
		$this->assertContains( 'my-flag', $field->get_flags() );
	}

	/** @testdox get_label_position returns "before" by default. */
	public function test_get_label_position_default(): void {
		$this->assertSame( 'before', Text::make( 'test' )->get_label_position() );
	}

	/** @testdox set_label_position stores the position. */
	public function test_set_label_position(): void {
		$field = Text::make( 'test' );
		$this->assertSame( $field, $field->set_label_position( 'after' ) );
		$this->assertSame( 'after', $field->get_label_position() );
	}

	/** @testdox label_after sets the position to "after". */
	public function test_label_after(): void {
		$field = Text::make( 'test' );
		$this->assertSame( $field, $field->label_after() );
		$this->assertSame( 'after', $field->get_label_position() );
	}

	/** @testdox label_before sets the position to "before". */
	public function test_label_before(): void {
		$field = Text::make( 'test' )->label_after();
		$this->assertSame( $field, $field->label_before() );
		$this->assertSame( 'before', $field->get_label_position() );
	}
}
