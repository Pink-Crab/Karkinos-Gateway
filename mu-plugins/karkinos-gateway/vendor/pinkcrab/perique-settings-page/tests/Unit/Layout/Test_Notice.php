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
use PinkCrab\Perique_Settings_Page\Setting\Layout\Notice;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Test_Notice extends WP_UnitTestCase {

	/** @testdox info() creates an info-level notice. */
	public function test_info(): void {
		$notice = Notice::info( 'Heads up' );
		$this->assertSame( 'info', $notice->get_level() );
		$this->assertSame( 'Heads up', $notice->get_message() );
	}

	/** @testdox warning() creates a warning-level notice. */
	public function test_warning(): void {
		$notice = Notice::warning( 'Careful' );
		$this->assertSame( 'warning', $notice->get_level() );
		$this->assertSame( 'Careful', $notice->get_message() );
	}

	/** @testdox error() creates an error-level notice. */
	public function test_error(): void {
		$notice = Notice::error( 'Bad' );
		$this->assertSame( 'error', $notice->get_level() );
		$this->assertSame( 'Bad', $notice->get_message() );
	}

	/** @testdox success() creates a success-level notice. */
	public function test_success(): void {
		$notice = Notice::success( 'OK' );
		$this->assertSame( 'success', $notice->get_level() );
		$this->assertSame( 'OK', $notice->get_message() );
	}

	/** @testdox Notice implements Renderable. */
	public function test_is_renderable(): void {
		$this->assertInstanceOf( Renderable::class, Notice::info( '' ) );
	}

	/** @testdox get_type returns "layout_notice". */
	public function test_get_type(): void {
		$this->assertSame( 'layout_notice', Notice::info( '' )->get_type() );
	}

	/** @testdox get_key starts with "notice_". */
	public function test_get_key_format(): void {
		$this->assertStringStartsWith( 'notice_', Notice::info( '' )->get_key() );
	}

	/** @testdox Each notice has a unique key. */
	public function test_unique_keys(): void {
		$a = Notice::info( 'a' );
		$b = Notice::info( 'b' );
		$this->assertNotSame( $a->get_key(), $b->get_key() );
	}
}
