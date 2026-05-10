<?php

declare( strict_types=1 );

/**
 * Section layout - visual grouping with title and description.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

class Section extends Abstract_Layout {

	/**
	 * Section title.
	 *
	 * @var string
	 */
	protected string $title = '';

	/**
	 * Section description.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Whether the section can be collapsed.
	 *
	 * @var bool
	 */
	protected bool $collapsible = false;

	/**
	 * Whether the section starts collapsed.
	 *
	 * @var bool
	 */
	protected bool $collapsed = false;

	/**
	 * Whether the section header is right-to-left.
	 *
	 * @var bool
	 */
	protected bool $rtl = false;

	/** @inheritDoc */
	public function get_type(): string {
		return 'layout_section';
	}

	/** @inheritDoc */
	public function get_key(): string {
		if ( '' !== $this->title ) {
			return 'section_' . \sanitize_title( $this->title );
		}
		return parent::get_key();
	}

	/**
	 * Set the section title.
	 *
	 * @param string $title
	 * @return static
	 */
	public function title( string $title ): static {
		$this->title = $title;
		return $this;
	}

	/**
	 * Get the section title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Set the section description.
	 *
	 * @param string $description
	 * @return static
	 */
	public function description( string $description ): static {
		$this->description = $description;
		return $this;
	}

	/**
	 * Get the section description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Set whether the section can be collapsed.
	 *
	 * @param bool $collapsible
	 * @return static
	 */
	public function collapsible( bool $collapsible = true ): static {
		$this->collapsible = $collapsible;
		return $this;
	}

	/**
	 * Check if the section is collapsible.
	 *
	 * @return bool
	 */
	public function is_collapsible(): bool {
		return $this->collapsible;
	}

	/**
	 * Set whether the section starts collapsed.
	 *
	 * @param bool $collapsed
	 * @return static
	 */
	public function collapsed( bool $collapsed = true ): static {
		$this->collapsed  = $collapsed;
		$this->collapsible = true;
		return $this;
	}

	/**
	 * Check if the section starts collapsed.
	 *
	 * @return bool
	 */
	public function is_collapsed(): bool {
		return $this->collapsed;
	}

	/**
	 * Set right-to-left header layout.
	 *
	 * @param bool $rtl
	 * @return static
	 */
	public function rtl( bool $rtl = true ): static {
		$this->rtl = $rtl;
		return $this;
	}

	/**
	 * Check if the section is RTL.
	 *
	 * @return bool
	 */
	public function is_rtl(): bool {
		return $this->rtl;
	}
}
