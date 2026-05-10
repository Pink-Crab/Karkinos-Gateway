<?php

declare( strict_types=1 );

/**
 * Stack layout - renders fields vertically.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

class Stack extends Abstract_Layout {

	protected string $gap = '0';

	/** @inheritDoc */
	public function get_type(): string {
		return 'layout_stack';
	}
}
