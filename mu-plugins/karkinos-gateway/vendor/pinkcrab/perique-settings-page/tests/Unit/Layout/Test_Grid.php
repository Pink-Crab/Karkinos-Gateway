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
use PinkCrab\Perique_Settings_Page\Setting\Layout\Grid;

class Test_Grid extends WP_UnitTestCase {

	/** @testdox get_type returns "layout_grid". */
	public function test_get_type(): void {
		$this->assertSame( 'layout_grid', Grid::of()->get_type() );
	}

	/** @testdox columns() sets the column count. */
	public function test_columns(): void {
		$grid = Grid::of()->columns( 4 );
		$this->assertSame( 4, $grid->get_columns() );
	}

	/** @testdox Default column count is 2. */
	public function test_default_columns(): void {
		$this->assertSame( 2, Grid::of()->get_columns() );
	}

	/** @testdox columns() returns static for chaining. */
	public function test_columns_chains(): void {
		$grid = Grid::of();
		$this->assertSame( $grid, $grid->columns( 3 ) );
	}
}
