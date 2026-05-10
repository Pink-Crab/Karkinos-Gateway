<?php
/**
 * E2E fixture: Settings class for the Group-only Settings_Page (B).
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;

class Test_Discovery_Settings_B extends Abstract_Settings {

	public const OPTION_KEY = 'pc_discovery_settings_b';

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Text::new( 'label' )->set_label( 'Label' )
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
