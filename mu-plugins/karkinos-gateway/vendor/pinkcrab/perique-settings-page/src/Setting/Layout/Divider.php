<?php

declare( strict_types=1 );

/**
 * Divider - horizontal rule between fields.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Divider implements Renderable {

	/**
	 * Counter for unique keys.
	 *
	 * @var int
	 */
	protected static int $counter = 0;

	/**
	 * Instance key.
	 *
	 * @var string
	 */
	protected string $key;

	public function __construct() {
		$this->key = 'divider_' . self::$counter++;
	}

	/**
	 * Static constructor.
	 *
	 * @return static
	 */
	public static function make(): static {
		return new static();
	}

	/** @inheritDoc */
	public function get_key(): string {
		return $this->key;
	}

	/** @inheritDoc */
	public function get_type(): string {
		return 'layout_divider';
	}
}
