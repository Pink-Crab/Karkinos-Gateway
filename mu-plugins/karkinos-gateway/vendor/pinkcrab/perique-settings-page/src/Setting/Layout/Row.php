<?php

declare( strict_types=1 );

/**
 * Row layout - renders fields horizontally.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

class Row extends Abstract_Layout {

	/**
	 * Relative column sizes.
	 *
	 * @var int[]
	 */
	protected array $sizes = array();

	/**
	 * Vertical alignment.
	 *
	 * @var string
	 */
	protected string $align = 'start';

	/** @inheritDoc */
	public function get_type(): string {
		return 'layout_row';
	}

	/**
	 * Set relative column sizes.
	 *
	 * @param int ...$sizes e.g. sizes(1, 2) = 1fr 2fr
	 * @return static
	 */
	public function sizes( int ...$sizes ): static {
		$this->sizes = $sizes;
		return $this;
	}

	/**
	 * Get the column sizes.
	 *
	 * @return int[]
	 */
	public function get_sizes(): array {
		return $this->sizes;
	}

	/**
	 * Set vertical alignment.
	 *
	 * @param string $align 'start', 'center', 'end', 'stretch'
	 * @return static
	 */
	public function align( string $align ): static {
		$this->align = $align;
		return $this;
	}

	/**
	 * Get the alignment.
	 *
	 * @return string
	 */
	public function get_align(): string {
		return $this->align;
	}

	/**
	 * Build the grid-template-columns CSS value.
	 *
	 * @return string
	 */
	public function get_grid_template(): string {
		if ( empty( $this->sizes ) ) {
			return implode( ' ', array_fill( 0, count( $this->children ), '1fr' ) );
		}
		return implode( ' ', array_map( fn( int $s ) => "{$s}fr", $this->sizes ) );
	}
}
