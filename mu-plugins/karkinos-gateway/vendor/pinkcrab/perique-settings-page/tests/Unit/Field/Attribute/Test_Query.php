<?php

declare( strict_types=1 );

/**
 * Unit tests for the Query attribute trait.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Query;

class Test_Query extends WP_UnitTestCase {

	protected function get_field(): Field {
		return new class('key', 'post_picker') extends Field {
			use Query;

			public function get_option_label(): callable {
				return $this->callbacks['option_label'] ?? fn( $x ): string => (string) $x;
			}

			public function get_option_value(): callable {
				return $this->callbacks['option_value'] ?? fn( $x ): string => (string) $x;
			}
		};
	}

	/** @testdox set_query_args() stores the args array. */
	public function test_set_query_args(): void {
		$field = $this->get_field();
		$field->set_query_args( array( 'post_type' => 'page', 'numberposts' => 5 ) );
		$this->assertSame( array( 'post_type' => 'page', 'numberposts' => 5 ), $field->get_query_args() );
	}

	/** @testdox get_query_args() returns an empty array by default. */
	public function test_get_query_args_default(): void {
		$this->assertSame( array(), $this->get_field()->get_query_args() );
	}

	/** @testdox set_query_args() returns the field for chaining. */
	public function test_set_query_args_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_query_args( array() ) );
	}

	/** @testdox set_option_label() stores a callable in callbacks. */
	public function test_set_option_label(): void {
		$field    = $this->get_field();
		$callback = fn( $x ): string => 'label';
		$field->set_option_label( $callback );
		$this->assertSame( $callback, $field->get_option_label() );
	}

	/** @testdox set_option_label() returns the field for chaining. */
	public function test_set_option_label_chains(): void {
		$field = $this->get_field();
		$this->assertSame( $field, $field->set_option_label( fn( $x ): string => '' ) );
	}
}
