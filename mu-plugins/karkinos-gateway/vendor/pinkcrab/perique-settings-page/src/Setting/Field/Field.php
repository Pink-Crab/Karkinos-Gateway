<?php

declare( strict_types=1 );

/**
 * Base field model.
 *
 * All settings field types extend this class. Provides the core
 * properties and fluent API for defining a field's key, label,
 * value, description, validation and sanitisation callbacks.
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

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use Respect\Validation\Validator;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Field implements Renderable {

	/**
	 * The field's unique key (used for storage and form name).
	 *
	 * @var string
	 */
	protected string $key;

	/**
	 * The field type identifier.
	 *
	 * @var string
	 */
	protected string $type;

	/**
	 * The field label.
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * The current field value.
	 *
	 * @var mixed
	 */
	protected $value = null;

	/**
	 * Help text / description displayed below the field.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * Whether the field is read-only.
	 *
	 * @var bool
	 */
	protected bool $read_only = false;

	/**
	 * Whether the field is required.
	 *
	 * @var bool
	 */
	protected bool $required = false;

	/**
	 * The field's icon (URL or dashicon class).
	 *
	 * @var string|null
	 */
	protected ?string $icon = null;

	/**
	 * The field's HTML ID attribute.
	 *
	 * @var string|null
	 */
	protected ?string $id = null;

	/**
	 * CSS classes for the field element.
	 *
	 * @var array<int, string>
	 */
	protected array $classes = array();

	/**
	 * Label position relative to the input.
	 *
	 * Options: 'before', 'after'
	 *
	 * @var string
	 */
	protected string $label_position = 'before';

	/**
	 * Field width within the form grid.
	 *
	 * Options: 'full', 'half', 'third', 'two-third'
	 *
	 * @var string
	 */
	protected string $width = 'full';

	/**
	 * Content to render before the field.
	 *
	 * @var string|null
	 */
	protected ?string $before = null;

	/**
	 * Content to render after the field.
	 *
	 * @var string|null
	 */
	protected ?string $after = null;

	/**
	 * Custom HTML attributes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $attributes = array();

	/**
	 * Arbitrary flags.
	 *
	 * @var array<int, string>
	 */
	protected array $flags = array();

	/**
	 * General-purpose callbacks bag.
	 *
	 * Used by traits and subclasses for additional callbacks
	 * (e.g. option_label). Sanitise and validate have dedicated
	 * properties but are also accessible here for backwards compat.
	 *
	 * @var array<string, callable|null>
	 */
	protected array $callbacks = array(
		'sanitize' => null,
		'validate' => null,
		'config'   => null,
	);

	public function __construct( string $key, string $type ) {
		$this->key  = $key;
		$this->type = $type;
	}

	/**
	 * Static constructor.
	 *
	 * @param string $key
	 * @return static
	 */
	public static function make( string $key ): static {
		/** @phpstan-ignore-next-line -- TYPE is defined on subclasses */
		return new static( $key, defined( 'static::TYPE' ) ? static::TYPE : 'text' );
	}

	/**
	 * Creates a clone of the existing field with a new key.
	 *
	 * @param string $key
	 * @return static
	 */
	public function clone_as( string $key ): static {
		$clone      = clone $this;
		$clone->key = $key;
		return $clone;
	}

	/**
	 * Get the field's key.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return $this->key;
	}

	/**
	 * Get the field type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Get the field label.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Set the field label.
	 *
	 * @param string $label
	 * @return static
	 */
	public function set_label( string $label ): static {
		$this->label = $label;
		return $this;
	}

	/**
	 * Get the current field value.
	 *
	 * @return mixed
	 */
	public function get_value() {
		return $this->value ?? '';
	}

	/**
	 * Set the field value.
	 *
	 * @param mixed $value
	 * @return static
	 */
	public function set_value( $value ): static {
		$this->value = $value;
		return $this;
	}

	/**
	 * Get the description / help text.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Set the description / help text.
	 *
	 * @param string $description
	 * @return static
	 */
	public function set_description( string $description ): static {
		$this->description = $description;
		return $this;
	}

	/**
	 * Get all custom attributes.
	 *
	 * @return array<string, mixed>
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Set a single attribute.
	 *
	 * @param string $attribute
	 * @param mixed  $value
	 * @return static
	 */
	public function set_attribute( string $attribute, $value ): static {
		$this->attributes[ $attribute ] = $value;
		return $this;
	}

	/**
	 * Get all flags.
	 *
	 * @return array<int, string>
	 */
	public function get_flags(): array {
		return $this->flags;
	}

	/**
	 * Add a flag.
	 *
	 * @param string $flag
	 * @return static
	 */
	public function set_flag( string $flag ): static {
		$this->flags[] = $flag;
		return $this;
	}

	/**
	 * Set read-only state.
	 *
	 * @param bool $read_only
	 * @return static
	 */
	public function set_read_only( bool $read_only = true ): static {
		$this->read_only = $read_only;
		return $this;
	}

	/**
	 * Check if the field is read-only.
	 *
	 * @return bool
	 */
	public function is_read_only(): bool {
		return $this->read_only;
	}

	/**
	 * Set required state.
	 *
	 * @param bool $required
	 * @return static
	 */
	public function set_required( bool $required = true ): static {
		$this->required = $required;
		return $this;
	}

	/**
	 * Check if the field is required.
	 *
	 * @return bool
	 */
	public function is_required(): bool {
		return $this->required;
	}

	/**
	 * Get the field icon.
	 *
	 * @return string|null
	 */
	public function get_icon(): ?string {
		return $this->icon;
	}

	/**
	 * Set the field icon.
	 *
	 * @param string $icon
	 * @return static
	 */
	public function set_icon( string $icon ): static {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Get the label position.
	 *
	 * @return string
	 */
	public function get_label_position(): string {
		return $this->label_position;
	}

	/**
	 * Set the label position.
	 *
	 * @param string $position 'before' or 'after'
	 * @return static
	 */
	public function set_label_position( string $position ): static {
		$this->label_position = $position;
		return $this;
	}

	/**
	 * Shorthand: set label after input.
	 *
	 * @return static
	 */
	public function label_after(): static {
		$this->label_position = 'after';
		return $this;
	}

	/**
	 * Shorthand: set label before input (default).
	 *
	 * @return static
	 */
	public function label_before(): static {
		$this->label_position = 'before';
		return $this;
	}

	/**
	 * Get the field's HTML ID.
	 *
	 * @return string|null
	 */
	public function get_id(): ?string {
		return $this->id;
	}

	/**
	 * Set the field's HTML ID.
	 *
	 * @param string $id
	 * @return static
	 */
	public function set_id( string $id ): static {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get the CSS classes.
	 *
	 * @return array<int, string>
	 */
	public function get_classes(): array {
		return $this->classes;
	}

	/**
	 * Add a CSS class.
	 *
	 * @param string $class_name
	 * @return static
	 */
	public function add_class( string $class_name ): static {
		$this->classes[] = $class_name;
		return $this;
	}

	/**
	 * Get the before content.
	 *
	 * @return string|null
	 */
	public function get_before(): ?string {
		return $this->before;
	}

	/**
	 * Set content to render before the field.
	 *
	 * @param string $before
	 * @return static
	 */
	public function set_before( string $before ): static {
		$this->before = $before;
		return $this;
	}

	/**
	 * Get the after content.
	 *
	 * @return string|null
	 */
	public function get_after(): ?string {
		return $this->after;
	}

	/**
	 * Set content to render after the field.
	 *
	 * @param string $after
	 * @return static
	 */
	public function set_after( string $after ): static {
		$this->after = $after;
		return $this;
	}

	/**
	 * Get the config callback.
	 *
	 * @return callable|null
	 */
	public function get_config(): ?callable {
		return $this->callbacks['config'] ?? null;
	}

	/**
	 * Set the config callback.
	 *
	 * Called at render time with the Form Component element. Acts as a
	 * final filter for any customisation that can't be expressed
	 * through the Field API.
	 *
	 * @param callable $config
	 * @return static
	 */
	public function set_config( callable $config ): static {
		$this->callbacks['config'] = $config;
		return $this;
	}

	/**
	 * Set the sanitisation callback.
	 *
	 * @param callable $func
	 * @return static
	 */
	public function set_sanitize( callable $func ): static {
		$this->callbacks['sanitize'] = $func;
		return $this;
	}

	/**
	 * Set the validation callback or Respect\Validation Validator.
	 *
	 * @param callable|Validator $func
	 * @return static
	 */
	public function set_validate( callable|Validator $func ): static {
		$this->callbacks['validate'] = $func;
		return $this;
	}

	/**
	 * Sanitise a value using this field's sanitisation callback.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function sanitize( $data ) {
		$func = $this->callbacks['sanitize'] ?? null;
		if ( null !== $func ) {
			return $func( $data );
		}
		return $data;
	}

	/**
	 * Validate a value using this field's validation callback.
	 *
	 * Returns true if valid, false if not.
	 *
	 * @param mixed $data
	 * @return bool
	 */
	public function validate( $data ): bool {
		$func = $this->callbacks['validate'] ?? null;

		if ( null === $func ) {
			return true;
		}

		if ( $func instanceof Validator ) {
			return $func->validate( $data );
		}

		return (bool) $func( $data );
	}
}
