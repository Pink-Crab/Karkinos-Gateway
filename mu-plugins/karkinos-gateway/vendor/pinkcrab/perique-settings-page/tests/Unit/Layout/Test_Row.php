<?php

declare( strict_types=1 );

/**
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

class Test_Row extends WP_UnitTestCase {

	/** @testdox get_type returns "layout_row". */
	public function test_get_type(): void {
		$this->assertSame( 'layout_row', Row::of()->get_type() );
	}

	/** @testdox sizes() stores the column sizes. */
	public function test_sizes(): void {
		$row = Row::of()->sizes( 1, 2, 1 );
		$this->assertSame( array( 1, 2, 1 ), $row->get_sizes() );
	}

	/** @testdox sizes() returns static for chaining. */
	public function test_sizes_chains(): void {
		$row = Row::of();
		$this->assertSame( $row, $row->sizes( 1 ) );
	}

	/** @testdox align() sets the vertical alignment. */
	public function test_align(): void {
		$row = Row::of()->align( 'center' );
		$this->assertSame( 'center', $row->get_align() );
	}

	/** @testdox Default alignment is "start". */
	public function test_default_align(): void {
		$this->assertSame( 'start', Row::of()->get_align() );
	}

	/** @testdox align() returns static for chaining. */
	public function test_align_chains(): void {
		$row = Row::of();
		$this->assertSame( $row, $row->align( 'end' ) );
	}

	/** @testdox get_grid_template() with no sizes returns "1fr" repeated for each child. */
	public function test_grid_template_default(): void {
		$row = Row::of( Text::new( 'a' ), Text::new( 'b' ) );
		$this->assertSame( '1fr 1fr', $row->get_grid_template() );
	}

	/** @testdox get_grid_template() uses configured sizes. */
	public function test_grid_template_with_sizes(): void {
		$row = Row::of( Text::new( 'a' ), Text::new( 'b' ) )->sizes( 1, 2 );
		$this->assertSame( '1fr 2fr', $row->get_grid_template() );
	}
}
