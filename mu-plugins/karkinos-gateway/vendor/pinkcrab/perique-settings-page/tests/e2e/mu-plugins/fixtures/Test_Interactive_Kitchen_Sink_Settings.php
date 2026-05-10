<?php
/**
 * E2E fixture: Interactive Kitchen Sink.
 *
 * Covers fields that need real WP data and user interaction beyond
 * simple fill+submit: Post_Picker, User_Picker, Media_Library, WP_Editor.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\User_Picker;
use PinkCrab\Perique_Settings_Page\Setting\Field\Media_Library;
use PinkCrab\Perique_Settings_Page\Setting\Field\WP_Editor;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Interactive_Kitchen_Sink_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'interactive_kitchen_sink_settings';

	/**
	 * Seed defaults: empty — tests will interact and then assert.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			'pick_post'   => '',
			'pick_user'   => '',
			'media_image' => '',
			'editor'      => '<p>Hello editor.</p>',
		);
	}

	public function __construct() {
		parent::__construct( new WP_Options_Settings_Repository() );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Section::of(
				Post_Picker::new( 'pick_post' )
					->set_label( 'Pick a Post' )
					->set_post_type( 'post' )
					->set_placeholder( 'Search posts…' ),

				User_Picker::new( 'pick_user' )
					->set_label( 'Pick a User' )
					->set_placeholder( 'Search users…' ),
			)->title( 'Pickers' ),

			Section::of(
				Media_Library::new( 'media_image' )
					->set_label( 'Pick an Image' )
					->set_description( 'Select from the media library.' ),
			)->title( 'Media Library' ),

			Section::of(
				WP_Editor::new( 'editor' )
					->set_label( 'Editor' )
					->set_description( 'Rich text content.' ),
			)->title( 'WP Editor' ),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
