<?php

declare( strict_types=1 );

/**
 * Unit tests for Abstract_Layout via the Row concrete class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Layout
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Layout;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Row;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Stack;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Test_Abstract_Layout extends WP_UnitTestCase {

	/** @testdox of() stores child renderables. */
	public function test_of_stores_children(): void {
		$row = Row::of( Text::new( 'a' ), Text::new( 'b' ) );
		$this->assertCount( 2, $row->get_children() );
	}

	/** @testdox of() with no children returns an empty children list. */
	public function test_of_no_children(): void {
		$this->assertSame( array(), Row::of()->get_children() );
	}

	/** @testdox gap() returns static for chaining. */
	public function test_gap_chains(): void {
		$row = Row::of();
		$this->assertSame( $row, $row->gap( '10px' ) );
	}

	/** @testdox get_gap() returns the configured gap. */
	public function test_get_gap(): void {
		$row = Row::of()->gap( '32px' );
		$this->assertSame( '32px', $row->get_gap() );
	}

	/** @testdox Default gap is 16px. */
	public function test_default_gap(): void {
		$this->assertSame( '16px', Row::of()->get_gap() );
	}

	/** @testdox get_all_fields() returns direct child fields keyed by field key. */
	public function test_get_all_fields_direct(): void {
		$row    = Row::of( Text::new( 'a' ), Text::new( 'b' ) );
		$fields = $row->get_all_fields();
		$this->assertArrayHasKey( 'a', $fields );
		$this->assertArrayHasKey( 'b', $fields );
		$this->assertCount( 2, $fields );
	}

	/** @testdox get_all_fields() recursively flattens nested layouts. */
	public function test_get_all_fields_nested(): void {
		$row    = Row::of(
			Text::new( 'a' ),
			Stack::of( Text::new( 'b' ), Text::new( 'c' ) )
		);
		$fields = $row->get_all_fields();
		$this->assertCount( 3, $fields );
		$this->assertArrayHasKey( 'a', $fields );
		$this->assertArrayHasKey( 'b', $fields );
		$this->assertArrayHasKey( 'c', $fields );
	}

	/** @testdox get_key() auto-generates a key from child keys. */
	public function test_get_key_auto_generated(): void {
		$row = Row::of( Text::new( 'a' ), Text::new( 'b' ) );
		$this->assertStringContainsString( 'a', $row->get_key() );
		$this->assertStringContainsString( 'b', $row->get_key() );
	}

	/** @testdox Abstract_Layout implements Renderable. */
	public function test_implements_renderable(): void {
		$this->assertInstanceOf( Renderable::class, Row::of() );
	}
}
