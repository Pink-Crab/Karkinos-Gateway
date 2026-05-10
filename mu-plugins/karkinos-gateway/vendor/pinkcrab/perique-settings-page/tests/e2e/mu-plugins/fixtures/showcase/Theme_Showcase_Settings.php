<?php
/**
 * E2E fixture: Theme Showcase Settings.
 *
 * One big kitchen sink used by every theme showcase page. Covers
 * every field type, layout, and complex block with seeded defaults
 * so themes can be visually reviewed without having to fill anything.
 *
 * All showcase pages share this one option — saving on one theme
 * affects the others. That's intentional for visual comparison.
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
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Row;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Grid;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Divider;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Notice;

class Theme_Showcase_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'theme_showcase_settings';

	/**
	 * Seed defaults written on init.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			'first_name'       => 'Ada',
			'last_name'        => 'Lovelace',
			'email'            => 'ada@example.com',
			'phone'            => '+44 7700 900000',
			'website'          => 'https://example.com',
			'api_key'          => 'sk-abc123def456',
			'tagline'          => 'Crafting elegant software since 1843.',
			'bio'              => "Mathematician. Writer.\nWorking on the analytical engine.",
			'internal_id'      => 'showcase-v1',
			'posts_per_page'   => 10,
			'max_uploads'      => 5,
			'price'            => 19.99,
			'theme_colour'     => 'dark',
			'categories'       => array( 'tutorials', 'reviews' ),
			'layout'           => 'sidebar-left',
			'show_sidebar'     => '1',
			'newsletter'       => 'yes',
			'features'         => array( 'comments', 'related', 'sharing' ),
			'primary_colour'   => '#3858e9',
			'secondary_colour' => '#d63638',
			'about_html'       => '<p>A short introduction to <strong>the settings showcase</strong>.</p><p>Edit me to see rich text persistence.</p>',
			'header_image'     => '',
			'featured_post'    => 1,
			'post_author'      => 1,
			'address'          => array(
				'line_1'   => '10 Downing Street',
				'line_2'   => '',
				'city'     => 'London',
				'postcode' => 'SW1A 2AA',
			),
			'social_links'     => new Repeater_Value( array(
				'platform' => array( 'Twitter', 'GitHub', 'LinkedIn' ),
				'url'      => array(
					'https://twitter.com/example',
					'https://github.com/example',
					'https://linkedin.com/in/example',
				),
			) ),
			'advanced_note'    => '',
		);
	}

	public function __construct() {
		parent::__construct( new WP_Options_Settings_Repository() );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(

			Section::of(
				Row::of(
					Text::new( 'first_name' )->set_label( 'First Name' )->set_required(),
					Text::new( 'last_name' )->set_label( 'Last Name' ),
				),
				Row::of(
					Email::new( 'email' )->set_label( 'Email' )->set_required(),
					Phone::new( 'phone' )->set_label( 'Phone' ),
				),
				Url::new( 'website' )->set_label( 'Website' ),
				Textarea::new( 'bio' )
					->set_label( 'Biography' )
					->set_description( 'Tell us a bit about yourself.' )
					->set_rows( 4 ),
			)->title( 'Personal Details' )
			 ->description( 'Your contact information and a short bio.' ),

			Section::of(
				Text::new( 'tagline' )
					->set_label( 'Site Tagline' )
					->set_description( 'A short description of your site.' ),

				Row::of(
					Number::new( 'posts_per_page' )->set_label( 'Posts Per Page' )->set_min( 1 )->set_max( 100 ),
					Number::new( 'max_uploads' )->set_label( 'Max Uploads' )->set_min( 1 )->set_max( 20 ),
					Number::new( 'price' )->set_label( 'Default Price' )->set_decimal_places( 2 )->set_step( 0.01 ),
				),

				Select::new( 'theme_colour' )
					->set_label( 'Theme Colour' )
					->set_option( 'light', 'Light' )
					->set_option( 'dark', 'Dark' )
					->set_option( 'auto', 'Auto' ),

				Row::of(
					Colour::new( 'primary_colour' )
						->set_label( 'Primary Colour' )
						->set_description( 'Used for links and buttons.' ),
					Colour::new( 'secondary_colour' )
						->set_label( 'Secondary Colour' )
						->set_description( 'Accent colour.' ),
				),

				Password::new( 'api_key' )
					->set_label( 'API Key' )
					->set_description( 'Your secret API key — kept safe.' ),

				Hidden::new( 'internal_id' ),
			)->title( 'Site Settings' )
			 ->description( 'General configuration values.' ),

			Section::of(
				Notice::info( 'These settings control how content is presented.' ),

				Grid::of(
					Checkbox::new( 'show_sidebar' )
						->set_label( 'Show Sidebar' )
						->set_checked_value( '1' )
						->label_after(),
					Checkbox::new( 'newsletter' )
						->set_label( 'Newsletter Signup' )
						->set_checked_value( 'yes' )
						->label_after(),
				)->columns( 2 ),

				Divider::make(),

				Checkbox_Group::new( 'features' )
					->set_label( 'Enabled Features' )
					->set_option( 'comments', 'Comments' )
					->set_option( 'sharing', 'Social Sharing' )
					->set_option( 'related', 'Related Posts' )
					->set_option( 'newsletter', 'Newsletter' )
					->set_description( 'Select which features are enabled site-wide.' ),

				Radio::new( 'layout' )
					->set_label( 'Layout Style' )
					->set_option( 'full', 'Full Width' )
					->set_option( 'sidebar-left', 'Left Sidebar' )
					->set_option( 'sidebar-right', 'Right Sidebar' )
					->set_option( 'boxed', 'Boxed' ),

				Select::new( 'categories' )
					->set_label( 'Default Categories' )
					->set_option( 'news', 'News' )
					->set_option( 'tutorials', 'Tutorials' )
					->set_option( 'reviews', 'Reviews' )
					->set_option( 'opinion', 'Opinion' )
					->set_multiple()
					->set_description( 'Shown in the category filter.' ),
			)->title( 'Display Options' )
			 ->collapsible(),

			Section::of(
				WP_Editor::new( 'about_html' )
					->set_label( 'About Page HTML' )
					->set_description( 'Rich text for your about page.' ),

				Media_Library::new( 'header_image' )
					->set_label( 'Header Image' )
					->set_description( 'Displayed at the top of every page.' ),
			)->title( 'Content' ),

			Section::of(
				Row::of(
					Post_Picker::new( 'featured_post' )
						->set_label( 'Featured Post' )
						->set_post_type( 'post' )
						->set_placeholder( 'Search posts…' )
						->set_description( 'Shown on the homepage.' ),
					User_Picker::new( 'post_author' )
						->set_label( 'Default Author' )
						->set_role( 'administrator' )
						->set_placeholder( 'Search users…' )
						->set_description( 'Used when no author is set.' ),
				),
			)->title( 'Relationships' ),

			Section::of(
				Field_Group::of(
					'address',
					Text::new( 'line_1' )->set_label( 'Line 1' ),
					Text::new( 'line_2' )->set_label( 'Line 2' ),
					Text::new( 'city' )->set_label( 'City' ),
					Text::new( 'postcode' )->set_label( 'Postcode' ),
				)->set_label( 'Business Address' )
				 ->set_description( 'Your registered business address.' ),
			)->title( 'Address' ),

			Section::of(
				Repeater::new( 'social_links' )
					->set_label( 'Social Links' )
					->set_description( 'Add your social media profiles.' )
					->set_add_to_group_label( 'Add Link' )
					->add_field(
						Text::new( 'platform' )
							->set_label( 'Platform' )
							->set_attribute( 'placeholder', 'e.g. Twitter' )
					)
					->add_field(
						Text::new( 'url' )
							->set_label( 'URL' )
							->set_attribute( 'placeholder', 'https://…' )
					),
			)->title( 'Social Links' )
			 ->collapsible(),

			Section::of(
				Notice::warning( 'This section is for advanced users.' ),
				Text::new( 'advanced_note' )
					->set_label( 'Note' ),
			)->title( 'Advanced' )
			 ->collapsible()
			 ->collapsed(),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
