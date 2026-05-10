<?php

declare( strict_types=1 );

/**
 * Test fixture: Settings class with no constructor override.
 *
 * Relies entirely on DI to resolve the Setting_Repository dep, exercising
 * the Settings_Page_Module::pre_boot() default binding.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;

class DI_Default_Settings extends Abstract_Settings {

	public const OPTION_KEY = 'di_default_settings';

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Text::new( 'name' )->set_label( 'Name' ),
		);
	}

	protected function is_grouped(): bool {
		return true;
	}

	public function group_key(): string {
		return self::OPTION_KEY;
	}
}
