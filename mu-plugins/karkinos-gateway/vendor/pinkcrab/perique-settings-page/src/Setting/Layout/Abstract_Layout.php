<?php

declare( strict_types=1 );

/**
 * Base class for layout helpers.
 *
 * Layout helpers wrap fields and control their visual arrangement.
 * They implement Renderable so they can be pushed into a
 * Setting_Collection alongside fields.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

use PinkCrab\Perique_Settings_Page\Setting\Renderable;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;

abstract class Abstract_Layout implements Renderable {

	/**
	 * Child renderables (fields or nested layouts).
	 *
	 * @var Renderable[]
	 */
	protected array $children = array();

	/**
	 * CSS gap value.
	 *
	 * @var string
	 */
	protected string $gap = '16px';

	/**
	 * Static constructor.
	 *
	 * @param Renderable ...$children
	 * @return static
	 */
	public static function of( Renderable ...$children ): static {
		$instance           = new static();
		$instance->children = $children;
		return $instance;
	}

	/**
	 * Set the CSS gap between children.
	 *
	 * @param string $gap
	 * @return static
	 */
	public function gap( string $gap ): static {
		$this->gap = $gap;
		return $this;
	}

	/**
	 * Get the gap value.
	 *
	 * @return string
	 */
	public function get_gap(): string {
		return $this->gap;
	}

	/**
	 * Get the child renderables.
	 *
	 * @return Renderable[]
	 */
	public function get_children(): array {
		return $this->children;
	}

	/**
	 * Recursively get all Field instances from this layout and any nested layouts.
	 *
	 * @return Field[]
	 */
	public function get_all_fields(): array {
		$fields = array();
		foreach ( $this->children as $child ) {
			if ( $child instanceof Field ) {
				$fields[ $child->get_key() ] = $child;
			} elseif ( $child instanceof self ) {
				$fields = array_merge( $fields, $child->get_all_fields() );
			}
		}
		return $fields;
	}

	/**
	 * Auto-generate a key from child keys.
	 *
	 * @return string
	 */
	public function get_key(): string {
		$child_keys = array_map(
			function ( Renderable $child ): string {
				return $child->get_key();
			},
			$this->children
		);
		return $this->get_type() . '_' . implode( '_', array_slice( $child_keys, 0, 3 ) );
	}
}
