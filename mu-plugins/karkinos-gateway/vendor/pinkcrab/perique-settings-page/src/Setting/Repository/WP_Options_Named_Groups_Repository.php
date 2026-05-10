<?php

declare( strict_types=1 );

/**
 * WP Options repository that splits fields across named groups.
 *
 * Each group is stored as a single serialised array in the wp_options
 * table. The group mapping is defined at the repository level — fields
 * are unaware of which group they belong to.
 *
 * Keys not assigned to any group are placed in a '_default' group.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Repository;

use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;

class WP_Options_Named_Groups_Repository implements Setting_Repository {

	/**
	 * Prefix applied to all option names.
	 *
	 * @var string
	 */
	protected string $prefix;

	/**
	 * Group mapping: group_name => [field_key, ...]
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $groups;

	/**
	 * In-memory cache of loaded group data.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $cache = array();

	/**
	 * @param string                            $prefix Prefix for all option names.
	 * @param array<string, array<int, string>> $groups Group mapping (group_name => [field_keys]).
	 */
	public function __construct( string $prefix, array $groups ) {
		$this->prefix = $prefix;
		$this->groups = $groups;
	}

	/**
	 * Finds which group a key belongs to.
	 *
	 * @param string $key
	 * @return string The group name, or '_default' if not mapped.
	 */
	protected function find_group( string $key ): string {
		foreach ( $this->groups as $group_name => $keys ) {
			if ( \in_array( $key, $keys, true ) ) {
				return $group_name;
			}
		}
		return '_default';
	}

	/**
	 * Builds the wp_options key for a group.
	 *
	 * @param string $group
	 * @return string
	 */
	protected function option_name( string $group ): string {
		return $this->prefix . '_' . $group;
	}

	/**
	 * Loads a group's data from the database (or cache).
	 *
	 * @param string $group
	 * @return array<string, mixed>
	 */
	protected function load_group( string $group ): array {
		if ( ! \array_key_exists( $group, $this->cache ) ) {
			$data = \get_option( $this->option_name( $group ), array() );
			$this->cache[ $group ] = \is_array( $data ) ? $data : array();
		}
		return $this->cache[ $group ];
	}

	/**
	 * Saves a group's data back to the database and updates cache.
	 *
	 * @param string               $group
	 * @param array<string, mixed> $data
	 * @return bool
	 */
	protected function save_group( string $group, array $data ): bool {
		$this->cache[ $group ] = $data;
		return \update_option( $this->option_name( $group ), $data, true );
	}

	/** @inheritDoc */
	public function set( string $key, $data ): bool {
		$group         = $this->find_group( $key );
		$group_data    = $this->load_group( $group );
		$group_data[ $key ] = $data;
		return $this->save_group( $group, $group_data );
	}

	/** @inheritDoc */
	public function get( string $key ) {
		$group      = $this->find_group( $key );
		$group_data = $this->load_group( $group );
		return $group_data[ $key ] ?? null;
	}

	/** @inheritDoc */
	public function delete( string $key ): bool {
		$group      = $this->find_group( $key );
		$group_data = $this->load_group( $group );

		if ( ! \array_key_exists( $key, $group_data ) ) {
			return false;
		}

		unset( $group_data[ $key ] );
		return $this->save_group( $group, $group_data );
	}

	/** @inheritDoc */
	public function has( string $key ): bool {
		$group      = $this->find_group( $key );
		$group_data = $this->load_group( $group );
		return \array_key_exists( $key, $group_data );
	}

	/**
	 * Named groups handle their own grouping internally.
	 * Abstract_Settings should treat this as individual mode.
	 *
	 * @return bool
	 */
	public function allow_grouped(): bool {
		return false;
	}
}
