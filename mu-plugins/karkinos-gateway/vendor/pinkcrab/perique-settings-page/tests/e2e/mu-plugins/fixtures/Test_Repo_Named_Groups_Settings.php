<?php
/**
 * E2E fixture: Named Groups Repository.
 *
 * Fields split across two groups:
 *   ng_general → site_name, tag_line
 *   ng_display → max_posts, show_sidebar
 *
 * is_grouped() → false (named groups handle their own grouping).
 * allow_grouped() → false.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Named_Groups_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Checkbox;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Repo_Named_Groups_Settings extends Abstract_Settings {

	public const PREFIX = 'ng';

	public const GROUPS = array(
		'general' => array( 'ng_site_name', 'ng_tag_line' ),
		'display' => array( 'ng_max_posts', 'ng_show_sidebar' ),
	);

	/**
	 * Seed defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			'site_name'    => 'Named Groups Site',
			'tag_line'     => 'Named Groups tagline',
			'max_posts'    => 15,
			'show_sidebar' => '1',
		);
	}

	public function __construct() {
		parent::__construct(
			new WP_Options_Named_Groups_Repository( self::PREFIX, self::GROUPS )
		);
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Section::of(
				Text::new( 'site_name' )->set_label( 'Site Name' ),
				Text::new( 'tag_line' )->set_label( 'Tag Line' ),
			)->title( 'General' ),

			Section::of(
				Number::new( 'max_posts' )->set_label( 'Max Posts' ),
				Checkbox::new( 'show_sidebar' )
					->set_label( 'Show Sidebar' )
					->set_checked_value( '1' ),
			)->title( 'Display' ),
		);
	}

	protected function is_grouped(): bool {
		return false;
	}

	public function group_key(): string {
		return self::PREFIX;
	}
}
