<?php

declare( strict_types=1 );

/**
 * Renders a Repeater field as HTML.
 *
 * Takes a Repeater field with its child fields and values,
 * renders each group with controls (drag, remove), a hidden
 * template for adding new rows, and a sort order tracker.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Mapper;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;
use PinkCrab\Perique_Settings_Page\Util\Cast;

class Repeater_Renderer {

	/**
	 * The repeater field.
	 *
	 * @var Repeater
	 */
	protected Repeater $repeater;

	/**
	 * The element mapper for rendering child fields.
	 *
	 * @var Element_Mapper
	 */
	protected Element_Mapper $mapper;

	public function __construct( Repeater $repeater, Element_Mapper $mapper ) {
		$this->repeater = $repeater;
		$this->mapper   = $mapper;
	}

	/**
	 * Render the complete repeater HTML.
	 *
	 * @return string
	 */
	public function render(): string {
		$key    = esc_attr( $this->repeater->get_key() );
		$value  = $this->repeater->get_value();
		$fields = $this->repeater->get_fields();

		if ( ! $fields ) {
			return '';
		}

		// Work out how many groups we have.
		$group_count = ( $value instanceof Repeater_Value ) ? $value->group_count() : 0;
		$sort_order  = $group_count > 0 ? implode( ',', range( 0, $group_count - 1 ) ) : '';

		// Render existing groups.
		$groups_html = '';
		if ( $group_count > 0 ) {
			for ( $i = 0; $i < $group_count; $i++ ) {
				$group_values = ( $value instanceof Repeater_Value ) ? $value->get_index( $i ) : null;
				$groups_html .= $this->render_group( $i, $group_values );
			}
		}

		// Render the hidden template for new rows.
		$template_html = $this->render_group( null, null );

		// Build the complete repeater.
		$add_label = esc_html( $this->repeater->get_add_to_group_label() );

		return <<<HTML
<div id="pc-repeater-{$key}" class="pc-repeater" data-repeater="{$key}">
	<div class="pc-repeater__groups" data-repeater-groups="{$key}">
		{$groups_html}
	</div>
	<div class="pc-repeater__actions">
		<button type="button" class="button pc-repeater__add" data-repeater-add="{$key}">{$add_label}</button>
	</div>
	<template data-repeater-template="{$key}">
		{$template_html}
	</template>
	<input type="hidden" name="{$key}[sortorder]" data-repeater-sortorder="{$key}" value="{$sort_order}">
</div>
HTML;
	}

	/**
	 * Render a single group of fields.
	 *
	 * @param int|null      $index  The group index, or null for the template.
	 * @param \stdClass|null $values The group values.
	 * @return string
	 */
	protected function render_group( ?int $index, ?\stdClass $values ): string {
		$key           = esc_attr( $this->repeater->get_key() );
		$index_display = $index ?? '{{INDEX}}';
		$group_id      = "pc-repeater-{$key}--{$index_display}";

		$fields_html = '';
		foreach ( ( $this->repeater->get_fields() ? $this->repeater->get_fields()->to_array() : array() ) as $field ) {
			/** @var Field $field */
			$field_key  = $field->get_key();
			$input_name = "{$key}[{$field_key}][{$index_display}]";
			$field_value = null;

			if ( null !== $values && property_exists( $values, $field_key ) ) {
				$field_value = $values->{$field_key};
			}

			$fields_html .= $this->render_group_field( $field, $input_name, $field_value );
		}

		return <<<HTML
<div id="{$group_id}" class="pc-repeater__group" data-repeater-row="{$index_display}">
	<div class="pc-repeater__group-header">
		<span class="pc-repeater__drag-handle" title="Drag to reorder">&#9776;</span>
		<button type="button" class="pc-repeater__remove" data-repeater-remove="{$group_id}" title="Remove">&times;</button>
	</div>
	<div class="pc-repeater__group-fields">
		{$fields_html}
	</div>
</div>
HTML;
	}

	/**
	 * Render a single field within a group.
	 *
	 * @param Field       $field      The field definition.
	 * @param string      $input_name The full input name (repeater[field][index]).
	 * @param mixed       $value      The current value.
	 * @return string
	 */
	protected function render_group_field( Field $field, string $input_name, $value ): string {
		$label = esc_html( $field->get_label() );
		$type  = esc_attr( $field->get_type() );

		// Build a simple input based on field type.
		$input_html = $this->render_simple_input( $field, $input_name, $value );

		return <<<HTML
<div class="pc-form__element pc-form__element--{$type}_input pc-repeater__field pc-repeater__field--{$type}">
	<label class="pc-form__label pc-repeater__field-label">{$label}</label>
	<div class="pc-repeater__field-input">{$input_html}</div>
</div>
HTML;
	}

	/**
	 * Render a simple HTML input for a repeater child field.
	 *
	 * Repeater child fields are rendered as basic HTML inputs rather
	 * than going through Form Components, since they need custom
	 * name attributes with the repeater index pattern.
	 *
	 * @param Field  $field      The field definition.
	 * @param string $input_name The full input name.
	 * @param mixed  $value      The current value.
	 * @return string
	 */
	protected function render_simple_input( Field $field, string $input_name, $value ): string {
		$name       = esc_attr( $input_name );
		$escaped    = Cast::esc_attr( $value );
		$type       = $field->get_type();
		$attrs      = $field->get_attributes();

		// Always emit a placeholder attr (at minimum a space) so CSS
		// :placeholder-shown works for floating label detection.
		$placeholder_raw = $attrs['placeholder'] ?? '';
		$placeholder     = Cast::esc_attr( '' !== $placeholder_raw ? $placeholder_raw : ' ' );

		$input_class = 'form-control pc-form__element__field pc-repeater__input';

		switch ( $type ) {
			case 'text':
				return "<input type=\"text\" name=\"{$name}\" value=\"{$escaped}\" placeholder=\"{$placeholder}\" class=\"{$input_class}\">";

			case 'number':
				$min  = isset( $attrs['min'] ) ? ' min="' . Cast::esc_attr( $attrs['min'] ) . '"' : '';
				$max  = isset( $attrs['max'] ) ? ' max="' . Cast::esc_attr( $attrs['max'] ) . '"' : '';
				$step = isset( $attrs['step'] ) ? ' step="' . Cast::esc_attr( $attrs['step'] ) . '"' : '';
				return "<input type=\"number\" name=\"{$name}\" value=\"{$escaped}\" placeholder=\"{$placeholder}\"{$min}{$max}{$step} class=\"{$input_class}\">";

			case 'select':
				$options_html = '';
				if ( method_exists( $field, 'get_options' ) ) {
					foreach ( $field->get_options() as $opt_value => $opt_label ) {
						$selected      = ( Cast::to_string( $opt_value ) === Cast::to_string( $value ) ) ? ' selected' : '';
						$options_html .= sprintf(
							'<option value="%s"%s>%s</option>',
							Cast::esc_attr( $opt_value ),
							$selected,
							esc_html( $opt_label )
						);
					}
				}
				return "<select name=\"{$name}\" class=\"form-control pc-form__element__field pc-repeater__input\">{$options_html}</select>";

			case 'checkbox':
				$checked       = ! empty( $value ) ? ' checked' : '';
				$checked_value = method_exists( $field, 'get_checked_value' ) ? esc_attr( $field->get_checked_value() ) : '1';
				return "<input type=\"checkbox\" name=\"{$name}\" value=\"{$checked_value}\"{$checked} class=\"form-control pc-form__element__field pc-repeater__input\">";

			default:
				return "<input type=\"text\" name=\"{$name}\" value=\"{$escaped}\" placeholder=\"{$placeholder}\" class=\"{$input_class}\">";
		}
	}
}
