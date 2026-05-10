<?php
/**
 * E2E fixture: Site Options Decorator Repository.
 *
 * Wraps WP_Options_Settings_Repository with the multisite decorator.
 * On non-multisite (wp-env default), get_site_option/update_site_option
 * fall back to wp_options — so this proves the decorator doesn't break
 * the standard grouped flow.
 *
 * is_grouped() → true, allow_grouped() → true (inner grouped repo).
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Site_Options_Decorator;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Repo_Site_Options_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'site_opts_settings';

	/**
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			'site_name' => 'Site Options Site',
			'tag_line'  => 'Site Options tagline',
			'max_posts' => 30,
		);
	}

	public function __construct() {
		parent::__construct(
			new WP_Site_Options_Decorator(
				new WP_Options_Settings_Repository()
			)
		);
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Section::of(
				Text::new( 'site_name' )->set_label( 'Site Name' ),
				Text::new( 'tag_line' )->set_label( 'Tag Line' ),
				Number::new( 'max_posts' )->set_label( 'Max Posts' ),
			)->title( 'Site Options Decorator' ),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
