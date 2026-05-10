<?php

declare( strict_types=1 );

/**
 * Multisite decorator for any Setting_Repository.
 *
 * Wraps an existing repository and redirects all storage to the
 * WordPress site options table (wp_sitemeta) using get_site_option(),
 * update_site_option(), and delete_site_option().
 *
 * On non-multisite installs these functions fall back to the standard
 * wp_options table, so this decorator is safe to use in all contexts.
 *
 * The allow_grouped() capability is delegated to the inner repository.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Repository;

use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;

class WP_Site_Options_Decorator implements Setting_Repository {

	/**
	 * The inner repository (used only for allow_grouped()).
	 *
	 * @var Setting_Repository
	 */
	protected Setting_Repository $inner;

	/**
	 * @param Setting_Repository $inner The repository to decorate.
	 */
	public function __construct( Setting_Repository $inner ) {
		$this->inner = $inner;
	}

	/** @inheritDoc */
	public function set( string $key, $data ): bool {
		return \update_site_option( $key, $data );
	}

	/** @inheritDoc */
	public function get( string $key ) {
		return \get_site_option( $key );
	}

	/** @inheritDoc */
	public function delete( string $key ): bool {
		return \delete_site_option( $key );
	}

	/** @inheritDoc */
	public function has( string $key ): bool {
		$option = \get_site_option( $key, $this );
		return $option !== $this;
	}

	/**
	 * Delegates to the inner repository's capability.
	 *
	 * @return bool
	 */
	public function allow_grouped(): bool {
		return $this->inner->allow_grouped();
	}
}
