<?php

declare(strict_types=1);

/**
 * Abstract settings object.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Abstract_Layout;

abstract class Abstract_Settings {

	/**
	 * The settings
	 *
	 * @var Setting_Collection
	 */
	protected Setting_Collection $settings;

	/**
	 * The settings repository.
	 *
	 * @var Setting_Repository
	 */
	protected $settings_repository;

	public function __construct( Setting_Repository $settings_repository ) {
		$this->settings_repository = $settings_repository;
		$this->settings            = $this->fields( new Setting_Collection() );
	}

	/**
	 * Populates the settings group with all fields.
	 *
	 * @param \PinkCrab\Perique_Settings_Page\Setting\Setting_Collection $settings
	 * @return \PinkCrab\Perique_Settings_Page\Setting\Setting_Collection
	 */
	abstract protected function fields( Setting_Collection $settings ): Setting_Collection;

	/**
	 * Denotes of the settings is grouped
	 *
	 * @return bool
	 */
	abstract protected function is_grouped(): bool;

	/**
	 * Denotes the group key (can be used a prefix for key, or the key all settings are saved under)
	 *
	 * @return string
	 */
	abstract public function group_key(): string;

	/**
	 * Prefixes the key for the
	 *
	 * @param string $key
	 * @return string
	 */
	public function prefix_key( string $key ): string {
		return "{$this->group_key()}_{$key}";
	}

	/**
	 * Checks if a field exists (including nested in layouts).
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has( string $key ): bool {
		return array_key_exists( $key, $this->get_all_fields() );
	}

	/**
	 * Finds a field or field group from the collection (including nested in layouts).
	 *
	 * @param string $key
	 * @return Field|Field_Group|null
	 */
	public function find( string $key ): Field|Field_Group|null {
		$fields = $this->get_all_fields();
		return $fields[ $key ] ?? null;
	}

	/**
	 * Gets a value from the settings, if not set will return default.
	 *
	 * @param string $key
	 * @param mixed $fallback
	 * @return mixed
	 */
	public function get( string $key, $fallback = null ) {
		$field = $this->find( $key );
		return $field ? $field->get_value() : $fallback;
	}

	/**
	 * Sets a setting based on its key and value.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return bool
	 */
	public function set( string $key, $data ): bool {
		if ( ! $this->has( $key ) ) {
			return false;
		}

		if ( $this->is_grouped() && $this->settings_repository->allow_grouped() ) {
			return $this->set_grouped( $key, $data );
		}

		return $this->set_single( $key, $data );
	}

	/**
	 * Sets a value as a grouped data set.
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @return bool
	 */
	protected function set_grouped( string $key, $data ): bool {
		$field = $this->find( $key );
		if ( null !== $field ) {
			$field->set_value( $data );
		}
		return $this->store_grouped();
	}

	/**
	 * Sets a value as a single data set.
	 *
	 * @param string $key
	 * @param mixed  $data
	 * @return bool
	 */
	protected function set_single( string $key, $data ): bool {
		$updated = $this->settings_repository->set( $this->prefix_key( $key ), $data );
		if ( $updated ) {
			$field = $this->find( $key );
			if ( null !== $field ) {
				$field->set_value( $data );
			}
		}
		return $updated;
	}

	/**
	 * Deletes a setting if it exists, returns null if it doesn't.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete( string $key ): ?bool {
		if ( ! $this->has( $key ) ) {
			return null;
		}

		if ( $this->is_grouped() && $this->settings_repository->allow_grouped() ) {
			$this->delete_grouped( $key );
		} else {
			$this->delete_single( $key );
		}

		return true;
	}

	/**
	 * Deletes a value as a single data set.
	 *
	 * @param string $key
	 * @return void
	 */
	protected function delete_single( string $key ): void {
		$removed = $this->settings_repository->delete( $this->prefix_key( $key ) );
		if ( $removed ) {
			$field = $this->find( $key );
			if ( null !== $field ) {
				$field->set_value( null );
			}
		}
	}

	/**
	 * Deletes a value as a grouped data set.
	 *
	 * @param string $key
	 * @return void
	 */
	protected function delete_grouped( string $key ): void {
		$field = $this->find( $key );
		if ( null !== $field ) {
			$field->set_value( null );
		}
		$this->store_grouped();
	}

	/**
	 * Hydrates field values from the persistence layer.
	 *
	 * Called by Settings_Page::set_settings() after DI injection,
	 * and on every export() to ensure values are fresh.
	 *
	 * @return void
	 */
	public function refresh_settings(): void {
		$all_fields = $this->get_all_fields();

		if ( $this->is_grouped() && $this->settings_repository->allow_grouped() ) {
			$settings = $this->get_grouped_values();
			foreach ( $all_fields as $key => $field ) {
				$field->set_value( array_key_exists( $key, $settings ) ? $settings[ $key ] : null );
			}
		} else {
			foreach ( $all_fields as $key => $field ) {
				$field->set_value( $this->settings_repository->get( $this->prefix_key( $key ) ) );
			}
		}
	}

	/**
	 * Gets the grouped values.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_grouped_values(): array {
		$values = $this->settings_repository->get( $this->group_key() );
		return is_array( $values ) ? $values : array();
	}

	/**
	 * Returns the settings collection as an array.
	 *
	 * @return array<int|string, mixed>
	 */
	public function export(): array {
		// Update values from repository.
		$this->refresh_settings();
		return $this->settings->to_array();
	}

	/**
	 * Returns all the settings keys from collection.
	 *
	 * @return array<string>
	 */
	public function get_keys(): array {
		return array_keys( $this->get_all_fields() );
	}

	/**
	 * Returns all Field and Field_Group instances, including those nested in layouts.
	 *
	 * @return array<string, Field|Field_Group>
	 */
	public function get_all_fields(): array {
		/** @var array<Renderable> $items */
		$items = $this->settings->to_array();
		return self::extract_fields( $items );
	}

	/**
	 * Recursively extracts Field and Field_Group instances from a mixed array.
	 *
	 * @param array<Renderable> $items
	 * @return array<string, Field|Field_Group>
	 */
	protected static function extract_fields( array $items ): array {
		$fields = array();
		foreach ( $items as $item ) {
			if ( $item instanceof Field_Group ) {
				$fields[ $item->get_key() ] = $item;
			} elseif ( $item instanceof Field ) {
				$fields[ $item->get_key() ] = $item;
			} elseif ( $item instanceof Abstract_Layout ) {
				foreach ( self::extract_fields( $item->get_children() ) as $key => $field ) {
					$fields[ $key ] = $field;
				}
			}
		}
		return $fields;
	}

	/**
	 * Updates the options as a single, grouped value.
	 *
	 * @return bool
	 */
	protected function store_grouped(): bool {
		$settings = array_map(
			function ( $field ) {
				return $field->get_value();
			},
			$this->get_all_fields()
		);
		return $this->settings_repository->set( $this->group_key(), $settings );
	}
}
