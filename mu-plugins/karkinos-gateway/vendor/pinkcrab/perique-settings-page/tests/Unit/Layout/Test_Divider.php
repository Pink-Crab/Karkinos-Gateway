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
use PinkCrab\Perique_Settings_Page\Setting\Layout\Divider;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Test_Divider extends WP_UnitTestCase {

	/** @testdox make() returns a Divider instance. */
	public function test_make(): void {
		$this->assertInstanceOf( Divider::class, Divider::make() );
	}

	/** @testdox Divider implements Renderable. */
	public function test_is_renderable(): void {
		$this->assertInstanceOf( Renderable::class, Divider::make() );
	}

	/** @testdox get_type returns "layout_divider". */
	public function test_get_type(): void {
		$this->assertSame( 'layout_divider', Divider::make()->get_type() );
	}

	/** @testdox get_key returns a string starting with "divider_". */
	public function test_get_key_format(): void {
		$this->assertStringStartsWith( 'divider_', Divider::make()->get_key() );
	}

	/** @testdox Each Divider has a unique key. */
	public function test_unique_keys(): void {
		$a = Divider::make();
		$b = Divider::make();
		$this->assertNotSame( $a->get_key(), $b->get_key() );
	}
}
