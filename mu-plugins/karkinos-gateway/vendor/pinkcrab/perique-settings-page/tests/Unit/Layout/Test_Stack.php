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
use PinkCrab\Perique_Settings_Page\Setting\Layout\Stack;

class Test_Stack extends WP_UnitTestCase {

	/** @testdox get_type returns "layout_stack". */
	public function test_get_type(): void {
		$this->assertSame( 'layout_stack', Stack::of()->get_type() );
	}

	/** @testdox Default gap is 0. */
	public function test_default_gap(): void {
		$this->assertSame( '0', Stack::of()->get_gap() );
	}
}
