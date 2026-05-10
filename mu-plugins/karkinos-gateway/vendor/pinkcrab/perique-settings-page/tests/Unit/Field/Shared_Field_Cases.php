<?php

declare( strict_types=1 );

/**
 * Shared test cases for all concrete Field subclasses.
 *
 * Test classes that use this trait must implement the
 * abstract get_class_under_test() method returning the FQCN
 * of the field class being tested.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Field;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

trait Shared_Field_Cases {

	/**
	 * @return class-string<Field>
	 */
	abstract public function get_class_under_test(): string;

	/** @testdox [Shared::Field] new() returns an instance of the field class. */
	public function test_new_returns_instance(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'test_key' );
		$this->assertInstanceOf( $class, $field );
	}

	/** @testdox [Shared::Field] make() returns an instance of the field class. */
	public function test_make_returns_instance(): void {
		$class = $this->get_class_under_test();
		$field = $class::make( 'test_key' );
		$this->assertInstanceOf( $class, $field );
	}

	/** @testdox [Shared::Field] new() stores the key. */
	public function test_new_stores_key(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'my_key' );
		$this->assertSame( 'my_key', $field->get_key() );
	}

	/** @testdox [Shared::Field] The TYPE constant is exposed via get_type(). */
	public function test_type_constant_used(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'k' );
		$this->assertSame( $class::TYPE, $field->get_type() );
	}

	/** @testdox [Shared::Field] Field is an instance of Renderable. */
	public function test_is_renderable(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'k' );
		$this->assertInstanceOf( Renderable::class, $field );
	}

	/** @testdox [Shared::Field] Field is an instance of Field. */
	public function test_is_field(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'k' );
		$this->assertInstanceOf( Field::class, $field );
	}

	/** @testdox [Shared::Field] set_label/get_label round-trips a value. */
	public function test_label(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'k' )->set_label( 'My Label' );
		$this->assertSame( 'My Label', $field->get_label() );
	}

	/** @testdox [Shared::Field] set_description/get_description round-trips a value. */
	public function test_description(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'k' )->set_description( 'Help text' );
		$this->assertSame( 'Help text', $field->get_description() );
	}

	/** @testdox [Shared::Field] set_value/get_value round-trips a value. */
	public function test_value(): void {
		$class = $this->get_class_under_test();
		$field = $class::new( 'k' )->set_value( 'value' );
		$this->assertSame( 'value', $field->get_value() );
	}

	/** @testdox [Shared::Field] clone_as() returns a new instance with the new key. */
	public function test_clone_as(): void {
		$class    = $this->get_class_under_test();
		$original = $class::new( 'original' );
		$clone    = $original->clone_as( 'clone' );
		$this->assertNotSame( $original, $clone );
		$this->assertSame( 'original', $original->get_key() );
		$this->assertSame( 'clone', $clone->get_key() );
	}
}
