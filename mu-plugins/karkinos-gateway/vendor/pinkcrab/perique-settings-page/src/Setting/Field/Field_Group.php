<?php

declare( strict_types=1 );

/**
 * Field Group - groups multiple fields under a single key.
 *
 * Renders child fields with name="group[child_key]" and stores
 * the result as a single array value.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Field_Group implements Renderable {

	/**
	 * The group key (used for storage and form name prefix).
	 *
	 * @var string
	 */
	protected string $key;

	/**
	 * The group label.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * The group description.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Child fields.
	 *
	 * @var Field[]
	 */
	protected array $fields = array();

	/**
	 * The stored value (array of child key => value).
	 *
	 * @var array<string, mixed>
	 */
	protected array $value = array();

	protected function __construct( string $key ) {
		$this->key = $key;
	}

	/**
	 * Static constructor.
	 *
	 * @param string $key
	 * @param Field  ...$fields
	 * @return static
	 */
	public static function of( string $key, Field ...$fields ): static {
		$instance         = new static( $key );
		$instance->fields = $fields;
		return $instance;
	}

	/** @inheritDoc */
	public function get_key(): string {
		return $this->key;
	}

	/** @inheritDoc */
	public function get_type(): string {
		return 'field_group';
	}

	/**
	 * Set the group label.
	 *
	 * @param string $label
	 * @return static
	 */
	public function set_label( string $label ): static {
		$this->label = $label;
		return $this;
	}

	/**
	 * Get the group label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Set the group description.
	 *
	 * @param string $description
	 * @return static
	 */
	public function set_description( string $description ): static {
		$this->description = $description;
		return $this;
	}

	/**
	 * Get the group description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Get the child fields.
	 *
	 * @return Field[]
	 */
	public function get_fields(): array {
		return $this->fields;
	}

	/**
	 * Get the stored group value.
	 *
	 * @return array<string, mixed>
	 */
	public function get_value(): array {
		return $this->value;
	}

	/**
	 * Set the stored group value (array of child key => value).
	 *
	 * Also hydrates child fields with their values.
	 *
	 * @param array<string, mixed>|mixed $value
	 * @return static
	 */
	public function set_value( $value ): static {
		$this->value = is_array( $value ) ? $value : array();

		// Hydrate child fields.
		foreach ( $this->fields as $field ) {
			$child_key = $field->get_key();
			if ( array_key_exists( $child_key, $this->value ) ) {
				$field->set_value( $this->value[ $child_key ] );
			}
		}

		return $this;
	}

	/**
	 * Get a child field's value.
	 *
	 * @param string $child_key
	 * @param mixed  $fallback
	 * @return mixed
	 */
	public function get( string $child_key, $fallback = null ) {
		return $this->value[ $child_key ] ?? $fallback;
	}

	/**
	 * Sanitise all child values using their sanitise callbacks.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public function sanitize( array $data ): array {
		$sanitised = array();
		foreach ( $this->fields as $field ) {
			$child_key = $field->get_key();
			$raw       = $data[ $child_key ] ?? '';
			$sanitised[ $child_key ] = $field->sanitize( $raw );
		}
		return $sanitised;
	}

	/**
	 * Validate all child values.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, string> Errors keyed by child key.
	 */
	public function validate( array $data ): array {
		$errors = array();
		foreach ( $this->fields as $field ) {
			$child_key = $field->get_key();
			$raw       = $data[ $child_key ] ?? '';
			if ( ! $field->validate( $raw ) ) {
				$label = '' !== $field->get_label() ? $field->get_label() : $child_key;
				$errors[ $child_key ] = sprintf(
					/* translators: %s: field label */
					__( 'Validation failed for %s.', 'perique-settings-page' ),
					$label
				);
			}
		}
		return $errors;
	}
}
