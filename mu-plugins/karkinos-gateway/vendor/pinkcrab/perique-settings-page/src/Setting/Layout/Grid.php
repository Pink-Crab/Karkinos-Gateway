<?php

declare( strict_types=1 );

/**
 * Grid layout - renders fields in a multi-column grid.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

class Grid extends Abstract_Layout {

	/**
	 * Number of columns.
	 *
	 * @var int
	 */
	protected int $column_count = 2;

	/** @inheritDoc */
	public function get_type(): string {
		return 'layout_grid';
	}

	/**
	 * Set the number of columns.
	 *
	 * @param int $cols
	 * @return static
	 */
	public function columns( int $cols ): static {
		$this->column_count = $cols;
		return $this;
	}

	/**
	 * Get the number of columns.
	 *
	 * @return int
	 */
	public function get_columns(): int {
		return $this->column_count;
	}
}
