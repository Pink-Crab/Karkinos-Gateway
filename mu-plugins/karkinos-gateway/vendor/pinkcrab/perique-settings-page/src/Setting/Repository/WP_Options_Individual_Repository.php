<?php

declare( strict_types=1 );

/**
 * WP Options repository that stores each field as its own option.
 *
 * Each field is stored as an individual row in the wp_options table,
 * prefixed with an optional namespace to avoid key collisions.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Repository;

use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;

class WP_Options_Individual_Repository implements Setting_Repository {

	/**
	 * Key prefix applied to all option names.
	 *
	 * @var string
	 */
	protected string $prefix;

	/**
	 * @param string $prefix Optional prefix for all option keys.
	 */
	public function __construct( string $prefix = '' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Builds the full option name from a key.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function prefixed( string $key ): string {
		return $this->prefix . $key;
	}

	/** @inheritDoc */
	public function set( string $key, $data ): bool {
		return \update_option( $this->prefixed( $key ), $data, true );
	}

	/** @inheritDoc */
	public function get( string $key ) {
		return \get_option( $this->prefixed( $key ) );
	}

	/** @inheritDoc */
	public function delete( string $key ): bool {
		return \delete_option( $this->prefixed( $key ) );
	}

	/** @inheritDoc */
	public function has( string $key ): bool {
		$option = \get_option( $this->prefixed( $key ), $this );
		return $option !== $this;
	}

	/**
	 * Individual storage does not support grouped data.
	 *
	 * @return bool
	 */
	public function allow_grouped(): bool {
		return false;
	}
}
