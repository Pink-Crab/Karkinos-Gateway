<?php

declare( strict_types=1 );

/**
 * Unit tests for the Buffer helper class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Util
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Util;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Util\Buffer;

class Test_Buffer extends WP_UnitTestCase {

	/** @testdox A Buffer captures echoed output and returns it as a string. */
	public function test_captures_echo(): void {
		$buffer = new Buffer(
			function (): void {
				echo 'hello world';
			}
		);
		$this->assertSame( 'hello world', $buffer() );
	}

	/** @testdox A Buffer captures multiple echo calls. */
	public function test_captures_multiple_echoes(): void {
		$buffer = new Buffer(
			function (): void {
				echo 'one';
				echo ' ';
				echo 'two';
			}
		);
		$this->assertSame( 'one two', $buffer() );
	}

	/** @testdox A Buffer returns an empty string when the callback echoes nothing. */
	public function test_returns_empty_string_when_no_output(): void {
		$buffer = new Buffer(
			function (): void {
				// No output.
			}
		);
		$this->assertSame( '', $buffer() );
	}

	/** @testdox A Buffer captures printf output. */
	public function test_captures_printf(): void {
		$buffer = new Buffer(
			function (): void {
				printf( '<p>%s</p>', 'value' );
			}
		);
		$this->assertSame( '<p>value</p>', $buffer() );
	}

	/** @testdox A Buffer is invokable via the __invoke magic method. */
	public function test_is_invokable(): void {
		$buffer = new Buffer(
			function (): void {
				echo 'invoked';
			}
		);
		$this->assertIsCallable( $buffer );
		$this->assertSame( 'invoked', $buffer() );
	}
}
