<?php

declare( strict_types=1 );

/**
 * Simple output buffer helper.
 *
 * Captures output from a callable that echoes/prints directly
 * and returns it as a string.
 *
 * Usage: $html = (new Buffer(function() { echo 'hello'; }))();
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Util;

class Buffer {

	/**
	 * The callable to buffer.
	 *
	 * @var callable
	 */
	protected $callback;

	/**
	 * @param callable $callback
	 */
	public function __construct( callable $callback ) {
		$this->callback = $callback;
	}

	/**
	 * Execute the callback and return captured output.
	 *
	 * @return string
	 */
	public function __invoke(): string {
		\ob_start();
		( $this->callback )();
		$output = \ob_get_clean();
		return false !== $output ? $output : '';
	}
}
