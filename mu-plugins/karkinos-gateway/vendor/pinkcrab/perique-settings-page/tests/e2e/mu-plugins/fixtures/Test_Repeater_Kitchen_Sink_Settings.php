<?php
/**
 * E2E fixture: Repeater Kitchen Sink.
 *
 * Exercises repeater interactions: add, remove, drag-sort, multi-row
 * persistence, zero-row submission, and the sort-order contract.
 *
 * Defaults are seeded via the reset endpoint (see bootstrap) so that
 * the page loads with 2 pre-populated rows.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;
use PinkCrab\Perique_Settings_Page\Setting\Layout\Section;

class Test_Repeater_Kitchen_Sink_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'repeater_kitchen_sink_settings';

	/**
	 * Seed defaults: two pre-populated rows.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_values(): array {
		return array(
			// Repeater values must be stored as Repeater_Value instances —
			// refresh_settings() calls set_value() with whatever comes from
			// the option, and Repeater_Renderer checks instanceof
			// Repeater_Value. A raw array would render 0 rows.
			'links' => new Repeater_Value( array(
				'platform' => array( 'Twitter', 'GitHub' ),
				'url'      => array( 'https://twitter.com', 'https://github.com' ),
			) ),
		);
	}

	public function __construct() {
		parent::__construct( new WP_Options_Settings_Repository() );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Section::of(
				Repeater::new( 'links' )
					->set_label( 'Links' )
					->set_description( 'Add your links.' )
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
			)->title( 'Repeater' ),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
