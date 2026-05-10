<?php

declare( strict_types=1 );

/**
 * User Picker field — async search via REST API.
 *
 * Interactive search-as-you-type component for selecting
 * WordPress users, backed by a REST endpoint.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Multiple;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Disabled;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Placeholder;

class User_Picker extends Field {

	/**
	 * The type of field.
	 */
	public const TYPE = 'user_picker';

	use Multiple, Data, Disabled, Placeholder;

	/**
	 * The user role to filter by.
	 *
	 * @var string
	 */
	protected string $role = '';

	/**
	 * Static constructor for field.
	 *
	 * @param string $key
	 * @return static
	 */
	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );
	}

	/**
	 * Sets the user role to filter by.
	 *
	 * @param string $role
	 * @return static
	 */
	public function set_role( string $role ): static {
		$this->role = $role;
		return $this;
	}

	/**
	 * Gets the user role filter.
	 *
	 * @return string
	 */
	public function get_role(): string {
		return $this->role;
	}

	/**
	 * Returns the defined label callback or fallback to display name.
	 *
	 * @return callable(\WP_User): string
	 */
	public function get_option_label(): callable {
		return $this->callbacks['option_label'] ?? function ( \WP_User $user ): string {
			return $user->display_name;
		};
	}

	/**
	 * Returns the defined value callback or fallback to user ID.
	 *
	 * @return callable(\WP_User): string
	 */
	public function get_option_value(): callable {
		return $this->callbacks['option_value'] ?? function ( \WP_User $user ): string {
			return (string) $user->ID;
		};
	}

	/**
	 * Sets the option label callback.
	 *
	 * @param callable $callback
	 * @return static
	 */
	public function set_option_label( callable $callback ): static {
		$this->callbacks['option_label'] = $callback;
		return $this;
	}

	/**
	 * Sets the option value callback.
	 *
	 * @param callable $callback
	 * @return static
	 */
	public function set_option_value( callable $callback ): static {
		$this->callbacks['option_value'] = $callback;
		return $this;
	}
}
