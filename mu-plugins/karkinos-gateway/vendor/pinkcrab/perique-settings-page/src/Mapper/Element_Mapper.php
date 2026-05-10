<?php

declare( strict_types=1 );

/**
 * Maps Settings Page Field objects to Form Components Element objects.
 *
 * Takes the developer-facing Field definitions and converts them to
 * Form Components elements for rendering. The developer never touches
 * Form Components directly - this mapper is the bridge.
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

namespace PinkCrab\Perique_Settings_Page\Mapper;

use PinkCrab\Form_Components\Element\Element;
use PinkCrab\Form_Components\Element\Custom_Field;
use PinkCrab\Form_Components\Element\Field\Select as FC_Select;
use PinkCrab\Form_Components\Element\Field\Input\Text as FC_Text;
use PinkCrab\Form_Components\Element\Field\Input\Color as FC_Color;
use PinkCrab\Form_Components\Element\Field\Input\Number as FC_Number;
use PinkCrab\Form_Components\Element\Field\Input\Checkbox as FC_Checkbox;
use PinkCrab\Form_Components\Element\Field\Input\Email as FC_Email;
use PinkCrab\Form_Components\Element\Field\Input\Tel as FC_Tel;
use PinkCrab\Form_Components\Element\Field\Input\Url as FC_Url;
use PinkCrab\Form_Components\Element\Field\Input\Password as FC_Password;
use PinkCrab\Form_Components\Element\Field\Input\Hidden as FC_Hidden;
use PinkCrab\Form_Components\Element\Field\Textarea as FC_Textarea;
use PinkCrab\Form_Components\Element\Field\Group\Radio_Group as FC_Radio_Group;
use PinkCrab\Form_Components\Element\Field\Group\Checkbox_Group as FC_Checkbox_Group;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Radio;
use PinkCrab\Perique_Settings_Page\Setting\Field\Colour;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;
use PinkCrab\Perique_Settings_Page\Setting\Field\Media_Library;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\User_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox_Group;
use PinkCrab\Perique_Settings_Page\Setting\Field\Email;
use PinkCrab\Perique_Settings_Page\Setting\Field\Phone;
use PinkCrab\Perique_Settings_Page\Setting\Field\Url;
use PinkCrab\Perique_Settings_Page\Setting\Field\Password;
use PinkCrab\Perique_Settings_Page\Setting\Field\Textarea;
use PinkCrab\Perique_Settings_Page\Setting\Field\Hidden;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Abstract_Layout;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Row;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Grid;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Stack;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Divider;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Notice;
use PinkCrab\Perique_Settings_Page\Util\Buffer;
use PinkCrab\Perique_Settings_Page\Util\Cast;

class Element_Mapper {

	/**
	 * View service for rendering child components.
	 *
	 * @var \PinkCrab\Perique\Services\View\View|null
	 */
	protected $view = null;

	/**
	 * Field-level errors keyed by field key.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $field_errors = array();

	/**
	 * Set the View service (needed for rendering layout children).
	 *
	 * @param \PinkCrab\Perique\Services\View\View $view
	 * @return static
	 */
	public function set_view( \PinkCrab\Perique\Services\View\View $view ): static {
		$this->view = $view;
		return $this;
	}

	/**
	 * Set field-level errors to be applied during element mapping.
	 *
	 * @param array<string, array<int, string>> $errors
	 * @return static
	 */
	public function set_field_errors( array $errors ): static {
		$this->field_errors = $errors;
		return $this;
	}

	/**
	 * Maps a Renderable (Field or Layout) to a Form Components Element.
	 *
	 * @param Renderable $renderable
	 * @return Element
	 */
	public function to_element( Renderable $renderable ): Element {
		// Layout containers render their own HTML with child fields.
		if ( $renderable instanceof Abstract_Layout ) {
			return $this->render_layout( $renderable );
		}

		if ( $renderable instanceof Divider ) {
			return Custom_Field::make( $renderable->get_key() )
				->content( '<hr class="pc-form__divider">' )
				->disable_kses();
		}

		if ( $renderable instanceof Notice ) {
			return Custom_Field::make( $renderable->get_key() )
				->content(
					sprintf(
						'<div class="pc-form__notice pc-form__notice--%s"><p>%s</p></div>',
						esc_attr( $renderable->get_level() ),
						esc_html( $renderable->get_message() )
					)
				);
		}

		// Field groups - render children with prefixed names.
		if ( $renderable instanceof Field_Group ) {
			return $this->map_field_group( $renderable );
		}

		// Regular fields.
		if ( $renderable instanceof Field ) {
			$element = $this->create_element( $renderable );
			$element = $this->apply_shared_attributes( $renderable, $element );
			return $element;
		}

		// Unknown renderable.
		return Custom_Field::make( $renderable->get_key() )
			->content( '<!-- Unknown renderable type -->' );
	}

	/**
	 * Creates the appropriate Form Component element for the field type.
	 *
	 * @param Field $field
	 * @return Element
	 */
	protected function create_element( Field $field ): Element {
		return match ( \get_class( $field ) ) {
			Text::class           => $this->map_text( $field ),
			Email::class          => $this->map_email( $field ),
			Phone::class          => $this->map_phone( $field ),
			Url::class            => $this->map_url( $field ),
			Password::class       => $this->map_password( $field ),
			Textarea::class       => $this->map_textarea( $field ),
			Hidden::class         => $this->map_hidden( $field ),
			Number::class         => $this->map_number( $field ),
			Checkbox::class       => $this->map_checkbox( $field ),
			Select::class         => $this->map_select( $field ),
			Radio::class          => $this->map_radio( $field ),
			Colour::class         => $this->map_colour( $field ),
			Checkbox_Group::class => $this->map_checkbox_group( $field ),
			WP_Editor::class      => $this->map_wp_editor( $field ),
			Media_Library::class  => $this->map_media_library( $field ),
			Post_Picker::class    => $this->map_post_picker( $field ),
			User_Picker::class    => $this->map_user_picker( $field ),
			Repeater::class       => $this->map_repeater( $field ),
			default               => $this->map_fallback( $field ),
		};
	}

	/**
	 * Maps a Text field.
	 *
	 * @param Text $field
	 * @return FC_Text
	 */
	protected function map_text( Text $field ): FC_Text {
		$element = FC_Text::make( $field->get_key() );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		if ( null !== $field->get_pattern() && '' !== $field->get_pattern() ) {
			$element->pattern( $field->get_pattern() );
		}

		return $element;
	}

	/**
	 * Maps an Email field.
	 *
	 * @param Email $field
	 * @return FC_Email
	 */
	protected function map_email( Email $field ): FC_Email {
		$element = FC_Email::make( $field->get_key() )
			->autocomplete( 'email' );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		if ( null !== $field->get_pattern() && '' !== $field->get_pattern() ) {
			$element->pattern( $field->get_pattern() );
		}

		return $element;
	}

	/**
	 * Maps a Phone field.
	 *
	 * @param Phone $field
	 * @return FC_Tel
	 */
	protected function map_phone( Phone $field ): FC_Tel {
		$element = FC_Tel::make( $field->get_key() )
			->autocomplete( 'tel' );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		if ( null !== $field->get_pattern() && '' !== $field->get_pattern() ) {
			$element->pattern( $field->get_pattern() );
		}

		return $element;
	}

	/**
	 * Maps a Url field.
	 *
	 * @param Url $field
	 * @return FC_Url
	 */
	protected function map_url( Url $field ): FC_Url {
		$element = FC_Url::make( $field->get_key() );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		if ( null !== $field->get_pattern() && '' !== $field->get_pattern() ) {
			$element->pattern( $field->get_pattern() );
		}

		return $element;
	}

	/**
	 * Maps a Password field.
	 *
	 * @param Password $field
	 * @return FC_Password
	 */
	protected function map_password( Password $field ): FC_Password {
		$element = FC_Password::make( $field->get_key() )
			->autocomplete( 'off' );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		return $element;
	}

	/**
	 * Maps a Textarea field.
	 *
	 * @param Textarea $field
	 * @return FC_Textarea
	 */
	protected function map_textarea( Textarea $field ): FC_Textarea {
		$element = FC_Textarea::make( $field->get_key() );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		if ( null !== $field->get_rows() ) {
			$element->rows( $field->get_rows() );
		}

		if ( null !== $field->get_cols() ) {
			$element->cols( $field->get_cols() );
		}

		return $element;
	}

	/**
	 * Maps a Hidden field.
	 *
	 * @param Hidden $field
	 * @return FC_Hidden
	 */
	protected function map_hidden( Hidden $field ): FC_Hidden {
		return FC_Hidden::make( $field->get_key() );
	}

	/**
	 * Maps a Number field.
	 *
	 * @param Number $field
	 * @return FC_Number
	 */
	protected function map_number( Number $field ): FC_Number {
		$element = FC_Number::make( $field->get_key() );

		if ( null !== $field->get_placeholder() && '' !== $field->get_placeholder() ) {
			$element->placeholder( $field->get_placeholder() );
		}

		// Map min/max/step from the Range attribute trait.
		if ( null !== $field->get_min() ) {
			$element->min( $field->get_min() );
		}
		if ( null !== $field->get_max() ) {
			$element->max( $field->get_max() );
		}
		if ( null !== $field->get_step() ) {
			$element->step( $field->get_step() );
		}

		return $element;
	}

	/**
	 * Maps a Checkbox field.
	 *
	 * @param Checkbox $field
	 * @return FC_Checkbox
	 */
	protected function map_checkbox( Checkbox $field ): FC_Checkbox {
		$element = FC_Checkbox::make( $field->get_key() );

		$element->value( $field->get_checked_value() );

		// Mark as checked if field has a value.
		if ( '' !== $field->get_value() ) {
			$element->checked( true );
		}

		return $element;
	}

	/**
	 * Maps a Select field.
	 *
	 * @param Select $field
	 * @return FC_Select
	 */
	protected function map_select( Select $field ): FC_Select {
		$element = FC_Select::make( $field->get_key() );
		$element->options( $field->get_options() );

		if ( $field->is_multiple() ) {
			$element->multiple( true );
		}

		return $element;
	}

	/**
	 * Maps a Radio field.
	 *
	 * @param Radio $field
	 * @return FC_Radio_Group
	 */
	protected function map_radio( Radio $field ): FC_Radio_Group {
		$element = FC_Radio_Group::make( $field->get_key() );
		$element->options( $field->get_options() );

		$selected = Cast::to_string( $field->get_value() );
		if ( null !== $selected && '' !== $selected ) {
			$element->selected( $selected );
		}

		return $element;
	}

	/**
	 * Maps a Colour field.
	 *
	 * @param Colour $field
	 * @return FC_Color
	 */
	protected function map_colour( Colour $field ): FC_Color {
		$element = FC_Color::make( $field->get_key() );

		if ( null !== $field->get_autocomplete() && '' !== $field->get_autocomplete() ) {
			$element->autocomplete( $field->get_autocomplete() );
		}

		return $element;
	}

	/**
	 * Maps a Checkbox_Group field.
	 *
	 * @param Checkbox_Group $field
	 * @return FC_Checkbox_Group
	 */
	protected function map_checkbox_group( Checkbox_Group $field ): FC_Checkbox_Group {
		$element = FC_Checkbox_Group::make( $field->get_key() );
		$element->options( $field->get_options() );

		$value = $field->get_value();
		if ( is_array( $value ) ) {
			$element->selected( array_filter( array_map( fn( $v ): ?string => Cast::to_string( $v ), $value ), fn( $v ): bool => null !== $v ) );
		}

		return $element;
	}

	/**
	 * Maps a WP_Editor field to a Custom_Field.
	 *
	 * @param WP_Editor $field
	 * @return Custom_Field
	 */
	protected function map_wp_editor( WP_Editor $field ): Custom_Field {
		$key     = $field->get_key();
		$value   = Cast::to_string( $field->get_value() ) ?? '';
		/** @var array{wpautop?: bool, media_buttons?: bool, default_editor?: string, drag_drop_upload?: bool, textarea_name?: string, textarea_rows?: int, tabindex?: int|string, tabfocus_elements?: string, editor_css?: string, editor_class?: string, teeny?: bool, dfw?: bool, tinymce?: bool|array<mixed>, quicktags?: bool|array<mixed>} $options */
		$options = $field->get_options();

		$editor_html = ( new Buffer(
			function () use ( $value, $key, $options ): void {
				\wp_editor( $value, $key, $options );
			}
		) )();

		return Custom_Field::make( $key )
			->content( $editor_html )
			->disable_kses()
			->add_wrapper_class( 'pc-form__element--wp_editor' );
	}

	/**
	 * Maps a Media_Library field to a Custom_Field.
	 *
	 * @param Media_Library $field
	 * @return Custom_Field
	 */
	protected function map_media_library( Media_Library $field ): Custom_Field {
		$key       = esc_attr( $field->get_key() );
		$raw_value = $field->get_value();
		$int_value = is_numeric( $raw_value ) ? \absint( $raw_value ) : 0;
		$value     = Cast::to_string( $raw_value, '' );

		// Attempt to get the media data if a value is set.
		$media = $int_value > 0 ? \wp_get_attachment_image_src( $int_value ) : null;
		$title = $media ? esc_html( (string) \get_the_title( $int_value ) ) : '';
		$src   = $media ? esc_url( $media[0] ) : '';

		$inner = <<<HTML
<div id="media_upload_{$key}" class="pc-settings-media-upload">
	<div class="pc-settings-media-upload__dropzone pc-settings-media-select" data-key="{$key}">
		<div class="pc-settings-media-upload__placeholder">
			<svg class="pc-settings-media-upload__icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
			<p class="pc-settings-media-upload__text">Click to select media</p>
		</div>
		<div class="pc-settings-media-upload__preview">
			<img src="{$src}" alt="{$title}" data-media-library-preview="{$key}">
			<figcaption id="{$key}_title">{$title}</figcaption>
		</div>
	</div>
	<input type="hidden" name="{$key}" id="{$key}" data-media-library-file-name="{$key}" value="{$value}">
	<button type="button" class="button pc-settings-media-clear" data-key="{$key}" title="Remove">&times;</button>
</div>
HTML;

		return Custom_Field::make( $key )
			->content( $inner )
			->disable_kses()
			->add_wrapper_class( 'pc-form__element--media_library' );
	}

	/**
	 * Maps a Post_Picker field to a Custom_Field with async search UI.
	 *
	 * @param Post_Picker $field
	 * @return Custom_Field
	 */
	protected function map_post_picker( Post_Picker $field ): Custom_Field {
		$key       = esc_attr( $field->get_key() );
		$multiple  = $field->is_multiple() ? 'true' : 'false';
		$post_type = esc_attr( $field->get_post_type() );
		$value     = $field->get_value();
		$ids       = $this->normalize_picker_ids( $value );
		$json_ids  = esc_attr( (string) \wp_json_encode( $ids ) );
		$ph        = esc_attr( $field->get_placeholder() ?? __( 'Search posts\u2026', 'perique-settings-page' ) );

		$hidden_inputs = $this->render_picker_hidden_inputs( $key, $ids, $field->is_multiple() );

		$inner = <<<HTML
<div class="pc-picker"
     data-picker-key="{$key}"
     data-picker-type="post"
     data-picker-multiple="{$multiple}"
     data-picker-post-type="{$post_type}"
     data-picker-value="{$json_ids}">
    <div class="pc-picker__selected"></div>
    <div class="pc-picker__search-wrap">
        <input type="text" class="pc-picker__search" placeholder="{$ph}" autocomplete="off">
    </div>
    {$hidden_inputs}
</div>
HTML;

		return Custom_Field::make( $key )
			->content( $inner )
			->disable_kses();
	}

	/**
	 * Maps a User_Picker field to a Custom_Field with async search UI.
	 *
	 * @param User_Picker $field
	 * @return Custom_Field
	 */
	protected function map_user_picker( User_Picker $field ): Custom_Field {
		$key      = esc_attr( $field->get_key() );
		$multiple = $field->is_multiple() ? 'true' : 'false';
		$role     = esc_attr( $field->get_role() );
		$value    = $field->get_value();
		$ids      = $this->normalize_picker_ids( $value );
		$json_ids = esc_attr( (string) \wp_json_encode( $ids ) );
		$ph       = esc_attr( $field->get_placeholder() ?? __( 'Search users\u2026', 'perique-settings-page' ) );

		$hidden_inputs = $this->render_picker_hidden_inputs( $key, $ids, $field->is_multiple() );

		$inner = <<<HTML
<div class="pc-picker"
     data-picker-key="{$key}"
     data-picker-type="user"
     data-picker-multiple="{$multiple}"
     data-picker-role="{$role}"
     data-picker-value="{$json_ids}">
    <div class="pc-picker__selected"></div>
    <div class="pc-picker__search-wrap">
        <input type="text" class="pc-picker__search" placeholder="{$ph}" autocomplete="off">
    </div>
    {$hidden_inputs}
</div>
HTML;

		return Custom_Field::make( $key )
			->content( $inner )
			->disable_kses();
	}

	/**
	 * Normalises a picker value to an array of integer IDs.
	 *
	 * @param mixed $value
	 * @return array<int, int>
	 */
	protected function normalize_picker_ids( $value ): array {
		if ( empty( $value ) ) {
			return array();
		}

		$values = is_array( $value ) ? $value : array( $value );
		return array_values( array_filter( array_map( fn( $v ): int => Cast::to_int( $v ), $values ) ) );
	}

	/**
	 * Renders hidden input(s) for a picker field.
	 *
	 * @param string     $key
	 * @param array<int> $ids
	 * @param bool       $multiple
	 * @return string
	 */
	protected function render_picker_hidden_inputs( string $key, array $ids, bool $multiple ): string {
		if ( empty( $ids ) ) {
			$name = $multiple ? $key . '[]' : $key;
			return sprintf( '<input type="hidden" name="%s" value="" data-picker-input="%s">', esc_attr( $name ), esc_attr( $key ) );
		}

		if ( ! $multiple ) {
			return sprintf(
				'<input type="hidden" name="%s" value="%s" data-picker-input="%s">',
				esc_attr( $key ),
				esc_attr( (string) $ids[0] ),
				esc_attr( $key )
			);
		}

		$html = '';
		foreach ( $ids as $id ) {
			$html .= sprintf(
				'<input type="hidden" name="%s[]" value="%s" data-picker-input="%s">',
				esc_attr( $key ),
				esc_attr( (string) $id ),
				esc_attr( $key )
			);
		}
		return $html;
	}

	/**
	 * Maps a Repeater field to a Custom_Field.
	 *
	 * @param Repeater $field
	 * @return Custom_Field
	 */
	protected function map_repeater( Repeater $field ): Custom_Field {
		$renderer = new Repeater_Renderer( $field, $this );

		return Custom_Field::make( $field->get_key() )
			->content( $renderer->render() )
			->disable_kses()
			->add_wrapper_class( 'pc-form__element--repeater' );
	}

	/**
	 * Fallback for unknown field types.
	 *
	 * @param Field $field
	 * @return Custom_Field
	 */
	protected function map_fallback( Field $field ): Custom_Field {
		return Custom_Field::make( $field->get_key() )
			->content(
				sprintf(
					'<!-- Unknown field type "%s" for key "%s" -->',
					esc_attr( $field->get_type() ),
					esc_attr( $field->get_key() )
				)
			);
	}

	/**
	 * Applies shared attributes from the Settings Field to the Form Component element.
	 *
	 * @param Field   $field
	 * @param Element $element
	 * @return Element
	 */
	protected function apply_shared_attributes( Field $field, Element $element ): Element {

		// Set ID - use custom ID if set, otherwise default to field key for label association.
		if ( method_exists( $element, 'id' ) ) {
			$element->id( $field->get_id() ?? $field->get_key() );
		}

		// Set CSS classes.
		foreach ( $field->get_classes() as $class_name ) {
			if ( method_exists( $element, 'add_class' ) ) {
				$element->add_class( $class_name );
			}
		}

		// Set label if the element supports it.
		if ( '' !== $field->get_label() && method_exists( $element, 'label' ) ) {
			$element->label( $field->get_label() );
		}

		// Set value / existing value for non-checkbox/radio types.
		if ( method_exists( $element, 'set_existing' )
			&& ! is_a( $field, Checkbox::class )
			&& ! is_a( $field, Checkbox_Group::class )
			&& ! is_a( $field, Radio::class )
		) {
			$element->set_existing( $field->get_value() );
		}

		// Set disabled state.
		if ( method_exists( $field, 'is_disabled' ) && $field->is_disabled() && method_exists( $element, 'disabled' ) ) {
			$element->disabled( true );
		}

		// Set read-only state.
		if ( $field->is_read_only() && method_exists( $element, 'readonly' ) ) {
			$element->readonly( true );
		}

		// Set required state.
		if ( $field->is_required() && method_exists( $element, 'required' ) ) {
			$element->required( true );
		}

		// Ensure a placeholder attribute is always present on text-like
		// inputs so CSS :placeholder-shown works for floating label
		// detection in themes like Material. If the field doesn't have
		// an explicit placeholder, set a single space.
		if ( method_exists( $element, 'placeholder' ) && method_exists( $field, 'has_placeholder' ) ) {
			if ( ! $field->has_placeholder() ) {
				$element->placeholder( ' ' );
			}
		}

		// Set label position class on wrapper.
		if ( 'after' === $field->get_label_position() ) {
			$element->add_wrapper_class( 'pc-form__element--label-after' );
		}

		// Set before/after content.
		if ( null !== $field->get_before() && method_exists( $element, 'before' ) ) {
			$element->before( $field->get_before() );
		}

		if ( null !== $field->get_after() && method_exists( $element, 'after' ) ) {
			$element->after( $field->get_after() );
		}

		// Set description via FC's post_description — renders before
		// the notification in FC's template, keeping the correct order.
		if ( '' !== $field->get_description() && method_exists( $element, 'post_description' ) ) {
			$element->post_description( $field->get_description() );
		}

		// Map data-* attributes.
		foreach ( $field->get_attributes() as $attr => $value ) {
			if ( str_starts_with( $attr, 'data-' ) && method_exists( $element, 'data' ) ) {
				$element->data( substr( $attr, 5 ), $value );
			}
		}

		// Apply field-level error notification if there is one for this field.
		if ( isset( $this->field_errors[ $field->get_key() ] ) && method_exists( $element, 'error_notification' ) ) {
			$messages = $this->field_errors[ $field->get_key() ];
			if ( ! empty( $messages ) ) {
				$element->error_notification( implode( ' ', $messages ) );
			}
		}

		// Apply the config callback as a final pass.
		if ( null !== $field->get_config() ) {
			$element = ( $field->get_config() )( $element );
		}

		return $element;
	}

	/**
	 * Maps a Field_Group - renders children with prefixed names.
	 *
	 * Children are rendered as simple HTML inputs since FC's name attribute
	 * uses sanitize_title which mangles bracket notation.
	 *
	 * @param Field_Group $group
	 * @return Custom_Field
	 */
	protected function map_field_group( Field_Group $group ): Custom_Field {
		$group_key = esc_attr( $group->get_key() );
		$html      = '';

		foreach ( $group->get_fields() as $child ) {
			$child_key = esc_attr( $child->get_key() );
			$name      = $group_key . '[' . $child_key . ']';
			$value     = Cast::esc_attr( $child->get_value() );
			$label     = esc_html( $child->get_label() );
			$type      = $child->get_type();

			$label_html = '' !== $label
				? sprintf( '<label class="pc-form__label" for="%s">%s</label>', $name, $label )
				: '';

			$placeholder_value = '';
			if ( method_exists( $child, 'get_placeholder' ) && null !== $child->get_placeholder() && '' !== $child->get_placeholder() ) {
				$placeholder_value = $child->get_placeholder();
			}
			// Always emit placeholder attr (at minimum a space) so CSS
			// :placeholder-shown works for floating label detection.
			$placeholder = ' placeholder="' . esc_attr( $placeholder_value !== '' ? $placeholder_value : ' ' ) . '"';

			$input_html = sprintf(
				'<input type="%s" name="%s" id="%s" value="%s"%s class="form-control pc-form__element__field">',
				esc_attr( $type ),
				$name,
				$name,
				$value,
				$placeholder
			);

			$html .= sprintf(
				'<div class="pc-form__element pc-form__element--%s_input">%s%s</div>',
				esc_attr( $type ),
				$label_html,
				$input_html
			);
		}

		$group_label = '';
		if ( '' !== $group->get_label() ) {
			$group_label = sprintf(
				'<legend class="pc-form__label pc-form__label--group">%s</legend>',
				esc_html( $group->get_label() )
			);
		}

		$desc_html = '';
		if ( '' !== $group->get_description() ) {
			$desc_html = sprintf(
				'<p class="description">%s</p>',
				esc_html( $group->get_description() )
			);
		}

		return Custom_Field::make( $group_key )
			->content( $group_label . $html . $desc_html )
			->disable_kses()
			->add_wrapper_class( 'pc-form__element--field_group' );
	}

	/**
	 * Renders a layout container and its children as a Custom_Field.
	 *
	 * @param Abstract_Layout $layout
	 * @return Custom_Field
	 */
	protected function render_layout( Abstract_Layout $layout ): Custom_Field {
		// Render all children.
		$children_html = $this->render_children( $layout );

		// Build the wrapper HTML based on layout type.
		$html = match ( true ) {
			$layout instanceof Row     => $this->render_row( $layout, $children_html ),
			$layout instanceof Grid    => $this->render_grid( $layout, $children_html ),
			$layout instanceof Stack   => $this->render_stack( $layout, $children_html ),
			$layout instanceof Section => $this->render_section( $layout, $children_html ),
			default                    => $children_html,
		};

		return Custom_Field::make( $layout->get_key() )
			->content( $html )
			->disable_kses()
			->show_wrapper( false );
	}

	/**
	 * Renders all children of a layout container.
	 *
	 * Each child is rendered via the View service (for FC elements)
	 * or recursively (for nested layouts).
	 *
	 * @param Abstract_Layout $layout
	 * @return string
	 */
	protected function render_children( Abstract_Layout $layout ): string {
		$html    = '';
		$factory = new \PinkCrab\Form_Components\Component\Component_Factory();

		foreach ( $layout->get_children() as $child ) {
			$element   = $this->to_element( $child );
			$component = $factory->from_element( $element );

			if ( null !== $this->view ) {
				$html .= $this->view->component( $component, false ) ?? '';
			}
		}
		return $html;
	}

	/**
	 * Render a Row layout wrapper.
	 *
	 * @param Row    $row
	 * @param string $children_html
	 * @return string
	 */
	protected function render_row( Row $row, string $children_html ): string {
		$gap      = esc_attr( $row->get_gap() );
		$align    = esc_attr( $row->get_align() );
		$template = esc_attr( $row->get_grid_template() );

		return sprintf(
			'<div class="pc-form__row" style="display:grid;grid-template-columns:%s;gap:%s;align-items:%s;">%s</div>',
			$template,
			$gap,
			$align,
			$children_html
		);
	}

	/**
	 * Render a Grid layout wrapper.
	 *
	 * @param Grid   $grid
	 * @param string $children_html
	 * @return string
	 */
	protected function render_grid( Grid $grid, string $children_html ): string {
		$cols = (int) $grid->get_columns();
		$gap  = esc_attr( $grid->get_gap() );

		return sprintf(
			'<div class="pc-form__grid pc-form__grid--%d" style="display:grid;grid-template-columns:repeat(%d,1fr);gap:%s;">%s</div>',
			$cols,
			$cols,
			$gap,
			$children_html
		);
	}

	/**
	 * Render a Stack layout wrapper.
	 *
	 * @param Stack  $stack
	 * @param string $children_html
	 * @return string
	 */
	protected function render_stack( Stack $stack, string $children_html ): string {
		$gap = esc_attr( $stack->get_gap() );

		return sprintf(
			'<div class="pc-form__stack" style="display:flex;flex-direction:column;gap:%s;">%s</div>',
			$gap,
			$children_html
		);
	}

	/**
	 * Render a Section layout wrapper.
	 *
	 * @param Section $section
	 * @param string  $children_html
	 * @return string
	 */
	protected function render_section( Section $section, string $children_html ): string {
		$key         = esc_attr( $section->get_key() );
		$collapsible = $section->is_collapsible() ? ' data-collapsible="true"' : '';
		$collapsed   = $section->is_collapsed() ? ' data-collapsed="true"' : '';
		$rtl_class   = $section->is_rtl() ? ' pc-form__section--rtl' : '';

		$header = '';
		if ( '' !== $section->get_title() ) {
			$title  = esc_html( $section->get_title() );
			$toggle = $section->is_collapsible() ? '<span class="pc-form__section-toggle">&#9660;</span>' : '';
			$header = sprintf(
				'<div class="pc-form__section-header"><h3 class="pc-form__section-title">%s</h3>%s</div>',
				$title,
				$toggle
			);
		}

		$desc = '';
		if ( '' !== $section->get_description() ) {
			$desc = sprintf(
				'<p class="pc-form__section-description">%s</p>',
				esc_html( $section->get_description() )
			);
		}

		$body_style = $section->is_collapsed() ? ' style="display:none;"' : '';

		return sprintf(
			'<div id="%s" class="pc-form__section%s"%s%s>%s%s<div class="pc-form__section-body"%s>%s</div></div>',
			$key,
			$rtl_class,
			$collapsible,
			$collapsed,
			$header,
			$desc,
			$body_style,
			$children_html
		);
	}

	/**
	 * Returns a mapping of Form Component sanitised names to original storage keys.
	 *
	 * sanitize_title() in Form Components converts underscores to hyphens,
	 * so we need this map to translate POST keys back to storage keys.
	 *
	 * @param array<string, Field> $fields Flat array of key => Field
	 * @return array<string, string> sanitised_name => original_key
	 */
	public static function get_key_map( array $fields ): array {
		$map = array();
		foreach ( $fields as $key => $field ) {
			$sanitised = \sanitize_title( $key );
			if ( $sanitised !== $key ) {
				$map[ $sanitised ] = $key;
			}
		}
		return $map;
	}
}
