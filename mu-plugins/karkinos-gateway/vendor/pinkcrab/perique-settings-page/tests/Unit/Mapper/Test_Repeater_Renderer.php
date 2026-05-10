<?php

declare( strict_types=1 );

/**
 * Unit tests for Repeater_Renderer.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Mapper
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Mapper;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Mapper\Element_Mapper;
use PinkCrab\Perique_Settings_Page\Mapper\Repeater_Renderer;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;

class Test_Repeater_Renderer extends WP_UnitTestCase {

	protected function build( Repeater $repeater ): Repeater_Renderer {
		return new Repeater_Renderer( $repeater, new Element_Mapper() );
	}

	/** @testdox render() returns an empty string when the repeater has no fields. */
	public function test_render_with_no_fields(): void {
		$repeater = Repeater::new( 'k' );
		$renderer = $this->build( $repeater );
		$this->assertSame( '', $renderer->render() );
	}

	/** @testdox render() with a single text child returns wrapper HTML and a template. */
	public function test_render_with_text_child(): void {
		$repeater = Repeater::new( 'k' )->add_field( Text::new( 'name' ) );
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'pc-repeater', $html );
		$this->assertStringContainsString( 'data-repeater="k"', $html );
		$this->assertStringContainsString( '<template', $html );
		$this->assertStringContainsString( 'sortorder', $html );
	}

	/** @testdox render() with stored values renders rows for each group. */
	public function test_render_with_values(): void {
		$repeater = Repeater::new( 'k' )
			->add_field( Text::new( 'name' ) )
			->set_value(
				new Repeater_Value(
					array(
						'name' => array( 'Alice', 'Bob' ),
					)
				)
			);
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'Alice', $html );
		$this->assertStringContainsString( 'Bob', $html );
	}

	/** @testdox render renders a number child with min/max/step. */
	public function test_render_number_child(): void {
		$repeater = Repeater::new( 'k' )->add_field(
			Number::new( 'count' )->set_min( 1 )->set_max( 10 )->set_step( 1 )
		);
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'type="number"', $html );
		$this->assertStringContainsString( 'min="1"', $html );
		$this->assertStringContainsString( 'max="10"', $html );
		$this->assertStringContainsString( 'step="1"', $html );
	}

	/** @testdox render renders a select child with options. */
	public function test_render_select_child(): void {
		$repeater = Repeater::new( 'k' )->add_field(
			Select::new( 'choice' )
				->set_option( 'a', 'A' )
				->set_option( 'b', 'B' )
		);
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'value="a"', $html );
		$this->assertStringContainsString( 'value="b"', $html );
	}

	/** @testdox render renders a checkbox child. */
	public function test_render_checkbox_child(): void {
		$repeater = Repeater::new( 'k' )->add_field(
			Checkbox::new( 'agree' )->set_checked_value( 'yes' )
		);
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'type="checkbox"', $html );
		$this->assertStringContainsString( 'value="yes"', $html );
	}

	/** @testdox render falls back to a text input for unknown field types. */
	public function test_render_unknown_type_fallback(): void {
		// Anonymous Field subclass with a custom type that isn't handled.
		$field = new class('mystery_field', 'mystery_type') extends \PinkCrab\Perique_Settings_Page\Setting\Field\Field {};

		$repeater = Repeater::new( 'k' )->add_field( $field );
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'mystery_field', $html );
	}

	/** @testdox render with checkbox value renders it as checked. */
	public function test_render_checkbox_with_checked_value(): void {
		$repeater = Repeater::new( 'k' )
			->add_field( Checkbox::new( 'agree' ) )
			->set_value(
				new Repeater_Value(
					array( 'agree' => array( 'yes' ) )
				)
			);
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'checked', $html );
	}

	/** @testdox render with select value marks the matching option as selected. */
	public function test_render_select_with_value(): void {
		$repeater = Repeater::new( 'k' )
			->add_field(
				Select::new( 'choice' )
					->set_option( 'a', 'Apple' )
					->set_option( 'b', 'Banana' )
			)
			->set_value(
				new Repeater_Value(
					array( 'choice' => array( 'b' ) )
				)
			);
		$html     = $this->build( $repeater )->render();
		$this->assertStringContainsString( 'selected', $html );
	}
}
