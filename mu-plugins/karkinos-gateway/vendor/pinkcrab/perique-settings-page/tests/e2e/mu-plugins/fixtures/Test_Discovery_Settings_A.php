<?php
/**
 * E2E fixture: Settings class for the standalone (registration_classes only) Settings_Page.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;

class Test_Discovery_Settings_A extends Abstract_Settings {

	public const OPTION_KEY = 'pc_discovery_settings_a';

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Text::new( 'name' )->set_label( 'Name' )
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
