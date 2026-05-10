<?php
/**
 * E2E fixture: kitchen sink settings.
 *
 * Two variants of every supported field type — `*_basic` (minimum config)
 * and `*_full` (every applicable attribute set) — so the spec can assert
 * both default rendering and full-config rendering, and a fill+submit+reload
 * round-trip exercises persistence through the repository.
 *
 * IMPORTANT — defaults
 * ────────────────────
 * Don't be tempted to use ->set_value() on the field definitions.
 * Settings_Page::set_settings() calls Abstract_Settings::refresh_settings(),
 * which unconditionally overwrites every field's value from the repository.
 * If the option doesn't exist, every field becomes `null`.
 *
 * So the "defaults" come from `default_values()` below — that's what the
 * mu-plugin reset endpoint writes to the WP option before each test runs,
 * and what the spec assertions are pinned against.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Email;
use PinkCrab\Perique_Settings_Page\Setting\Field\Phone;
use PinkCrab\Perique_Settings_Page\Setting\Field\Url;
use PinkCrab\Perique_Settings_Page\Setting\Field\Password;
use PinkCrab\Perique_Settings_Page\Setting\Field\Textarea;
use PinkCrab\Perique_Settings_Page\Setting\Field\Hidden;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Select;
use PinkCrab\Perique_Settings_Page\Setting\Field\Radio;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox_Group;
use PinkCrab\Perique_Settings_Page\Setting\Field\Colour;
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;
use PinkCrab\Perique_Settings_Page\Setting\Field\Media_Library;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\User_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Kitchen_Sink_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'kitchen_sink_settings';

	/**
	 * Source of truth for "default" values shown on first render.
	 *
	 * Written to the WP option by the mu-plugin reset endpoint
	 * (see settings-page-bootstrap.php).
	 *
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			'text_basic'           => 'Hello World',
			'text_full'            => 'Initial',
			'email_basic'          => 'basic@example.com',
			'email_full'           => 'full@example.com',
			'phone_basic'          => '+44 1234 567890',
			'phone_full'           => '+44 7700 900000',
			'url_basic'            => 'https://example.com',
			'url_full'             => 'https://example.org',
			'password_basic'       => '',
			'password_full'        => '',
			'textarea_basic'       => "Line one\nLine two",
			'textarea_full'        => 'Initial multiline content.',
			'hidden_basic'         => 'hidden-default-value',
			'number_basic'         => 42,
			'number_full'          => 10,
			'select_basic'         => 'green',
			'select_full'          => array( 'apple', 'cherry' ),
			'radio_basic'          => 'b',
			'radio_full'           => 'medium',
			'checkbox_basic'       => '1',
			'checkbox_full'        => '',
			'checkbox_group_basic' => array( 'one', 'three' ),
			'checkbox_group_full'  => array(),
			'colour_basic'         => '#ff0000',
			'colour_full'          => '#3858e9',
			'wp_editor_basic'      => '<p>Initial editor content.</p>',
			'media_library_basic'  => '',
			'post_picker_basic'    => '',
			'user_picker_basic'    => '',
			'field_group_address'  => array(
				'line_1'   => '',
				'city'     => '',
				'postcode' => '',
			),
			'repeater_basic'       => array(),
		);
	}

	public function __construct() {
		parent::__construct( new WP_Options_Settings_Repository() );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Section::of(
				// ─────────────── Text ───────────────
				Text::new( 'text_basic' )->set_label( 'Text Basic' ),

				Text::new( 'text_full' )
					->set_label( 'Text Full' )
					->set_description( 'A text field with every option set.' )
					->set_placeholder( 'Type something…' )
					->set_pattern( '[A-Za-z0-9 ]+' )
					->set_required()
					->add_class( 'my-text-class' )
					->set_data( 'foo', 'bar' )
					// Server-side validator for e2e: passes for strings of length ≥ 3.
					// Default seed "Initial" (7) and happy-path "updated full" (12)
					// both pass; an empty submission fails.
					->set_validate( static fn( $v ): bool => is_string( $v ) && strlen( $v ) >= 3 ),

				// ─────────────── Email ───────────────
				Email::new( 'email_basic' )->set_label( 'Email Basic' ),

				Email::new( 'email_full' )
					->set_label( 'Email Full' )
					->set_description( 'Email with placeholder, pattern and required.' )
					->set_placeholder( 'name@example.com' )
					->set_required(),

				// ─────────────── Phone ───────────────
				Phone::new( 'phone_basic' )->set_label( 'Phone Basic' ),

				Phone::new( 'phone_full' )
					->set_label( 'Phone Full' )
					->set_description( 'Phone with placeholder + pattern.' )
					->set_placeholder( '+44 …' )
					->set_pattern( '\+?[0-9 ]+' ),

				// ─────────────── URL ───────────────
				Url::new( 'url_basic' )->set_label( 'URL Basic' ),

				Url::new( 'url_full' )
					->set_label( 'URL Full' )
					->set_description( 'URL with placeholder + required.' )
					->set_placeholder( 'https://…' )
					->set_required(),

				// ─────────────── Password ───────────────
				Password::new( 'password_basic' )->set_label( 'Password Basic' ),

				Password::new( 'password_full' )
					->set_label( 'Password Full' )
					->set_description( 'Password with placeholder.' )
					->set_placeholder( 'Enter a password' )
					->set_required(),

				// ─────────────── Textarea ───────────────
				Textarea::new( 'textarea_basic' )->set_label( 'Textarea Basic' ),

				Textarea::new( 'textarea_full' )
					->set_label( 'Textarea Full' )
					->set_description( 'Textarea with rows, cols, placeholder.' )
					->set_placeholder( 'Tell us about yourself…' )
					->set_rows( 6 )
					->set_cols( 40 ),

				// ─────────────── Hidden ───────────────
				Hidden::new( 'hidden_basic' ),

				// ─────────────── Number ───────────────
				Number::new( 'number_basic' )->set_label( 'Number Basic' ),

				Number::new( 'number_full' )
					->set_label( 'Number Full' )
					->set_description( 'Number with min/max/step.' )
					->set_min( 0 )
					->set_max( 100 )
					->set_step( 5 )
					// Server-side validator for e2e: passes for numeric values >= 5.
					// Default seed 10 and happy-path 25 both pass; 0 fails.
					->set_validate( static fn( $v ): bool => is_numeric( $v ) && (int) $v >= 5 ),

				// ─────────────── Select ───────────────
				Select::new( 'select_basic' )
					->set_label( 'Select Basic' )
					->set_option( 'red', 'Red' )
					->set_option( 'green', 'Green' )
					->set_option( 'blue', 'Blue' ),

				Select::new( 'select_full' )
					->set_label( 'Select Full (multiple)' )
					->set_description( 'Select with multiple selection.' )
					->set_option( 'apple', 'Apple' )
					->set_option( 'banana', 'Banana' )
					->set_option( 'cherry', 'Cherry' )
					->set_option( 'date', 'Date' )
					->set_multiple(),

				// ─────────────── Radio ───────────────
				Radio::new( 'radio_basic' )
					->set_label( 'Radio Basic' )
					->set_option( 'a', 'Option A' )
					->set_option( 'b', 'Option B' )
					->set_option( 'c', 'Option C' ),

				Radio::new( 'radio_full' )
					->set_label( 'Radio Full' )
					->set_description( 'Radio with three layout options.' )
					->set_option( 'small', 'Small' )
					->set_option( 'medium', 'Medium' )
					->set_option( 'large', 'Large' ),

				// ─────────────── Checkbox ───────────────
				Checkbox::new( 'checkbox_basic' )
					->set_label( 'Checkbox Basic' )
					->set_checked_value( '1' ),

				Checkbox::new( 'checkbox_full' )
					->set_label( 'Checkbox Full' )
					->set_description( 'Checkbox with custom checked value + label-after.' )
					->set_checked_value( 'yes' )
					->label_after(),

				// ─────────────── Checkbox Group ───────────────
				Checkbox_Group::new( 'checkbox_group_basic' )
					->set_label( 'Checkbox Group Basic' )
					->set_option( 'one', 'One' )
					->set_option( 'two', 'Two' )
					->set_option( 'three', 'Three' ),

				Checkbox_Group::new( 'checkbox_group_full' )
					->set_label( 'Checkbox Group Full' )
					->set_description( 'Checkbox group with description.' )
					->set_option( 'comments', 'Comments' )
					->set_option( 'sharing', 'Sharing' )
					->set_option( 'related', 'Related Posts' )
					->set_option( 'newsletter', 'Newsletter' ),

				// ─────────────── Colour ───────────────
				Colour::new( 'colour_basic' )->set_label( 'Colour Basic' ),

				Colour::new( 'colour_full' )
					->set_label( 'Colour Full' )
					->set_description( 'Colour with autocomplete.' )
					->set_autocomplete( 'off' ),

				// ─────────────── WP Editor ───────────────
				WP_Editor::new( 'wp_editor_basic' )
					->set_label( 'WP Editor' )
					->set_description( 'Rich text editor.' ),

				// ─────────────── Media Library ───────────────
				Media_Library::new( 'media_library_basic' )
					->set_label( 'Media Library' )
					->set_description( 'Media library upload field.' ),

				// ─────────────── Post Picker ───────────────
				Post_Picker::new( 'post_picker_basic' )
					->set_label( 'Post Picker' )
					->set_description( 'Async post search.' )
					->set_post_type( 'post' )
					->set_placeholder( 'Search posts…' ),

				// ─────────────── User Picker ───────────────
				User_Picker::new( 'user_picker_basic' )
					->set_label( 'User Picker' )
					->set_description( 'Async user search.' )
					->set_role( 'administrator' )
					->set_placeholder( 'Search users…' ),

				// ─────────────── Field Group ───────────────
				Field_Group::of(
					'field_group_address',
					Text::new( 'line_1' )->set_label( 'Address Line 1' ),
					Text::new( 'city' )->set_label( 'City' ),
					Text::new( 'postcode' )->set_label( 'Postcode' ),
				)->set_label( 'Field Group: Address' )
				 ->set_description( 'Three child fields stored under one key.' ),

				// ─────────────── Repeater ───────────────
				Repeater::new( 'repeater_basic' )
					->set_label( 'Repeater' )
					->set_description( 'Add multiple platform/url pairs.' )
					->set_add_to_group_label( 'Add Link' )
					->add_field(
						Text::new( 'platform' )
							->set_label( 'Platform' )
							->set_attribute( 'placeholder', 'Twitter' )
					)
					->add_field(
						Text::new( 'url' )
							->set_label( 'URL' )
							->set_attribute( 'placeholder', 'https://…' )
					),
			)->title( 'Kitchen Sink' )
			 ->description( 'Every supported field type, basic and full variants.' ),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
