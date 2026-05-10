<?php

declare( strict_types=1 );

/**
 * Interface for anything that can be rendered in a settings form.
 *
 * Implemented by both Field (data fields) and Layout helpers
 * (Row, Grid, Section, etc.).
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting;

interface Renderable {

	/**
	 * Get the unique key/identifier.
	 *
	 * @return string
	 */
	public function get_key(): string;

	/**
	 * Get the type identifier.
	 *
	 * @return string
	 */
	public function get_type(): string;
}
