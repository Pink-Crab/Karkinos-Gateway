<?php
/**
 * E2E fixture: Individual Repository.
 *
 * Each field stored as its own wp_option row, prefixed with "ind_".
 * is_grouped() → false, allow_grouped() → false.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Individual_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Repo_Individual_Settings extends Abstract_Settings {

	/**
	 * Seed defaults written as individual options.
	 *
	 * Option names in wp_options will be: ind_site_name, ind_tag_line, ind_max_posts
	 * (prefix_key does group_key() . "_" . field_key → "ind_site_name", and the
	 * repo prefix is empty so no double-prefixing).
	 *
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			'site_name' => 'Individual Site',
			'tag_line'  => 'Individual tagline',
			'max_posts' => 25,
		);
	}

	public function __construct() {
		parent::__construct( new WP_Options_Individual_Repository() );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Section::of(
				Text::new( 'site_name' )->set_label( 'Site Name' ),
				Text::new( 'tag_line' )->set_label( 'Tag Line' ),
				Number::new( 'max_posts' )->set_label( 'Max Posts' ),
			)->title( 'Individual Repository' ),
		);
	}

	protected function is_grouped(): bool {
		return false;
	}

	public function group_key(): string {
		return 'ind';
	}
}
