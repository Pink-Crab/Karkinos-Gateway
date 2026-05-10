<?php

declare( strict_types=1 );

/**
 * Helper trait for integration tests that boot a real Perique App.
 *
 * Provides unset_app_instance() — reflection-based reset of the App
 * singleton's internal state so each test starts from a clean slate.
 * Mirrors the pattern from PinkCrab\Perique_Admin_Menu\Tests\Integration\Helper_Factory.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration;

use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique\Application\App;

trait Helper_Factory {

	/**
	 * Resets any existing App instance with default properties.
	 *
	 * @return void
	 */
	protected static function unset_app_instance(): void {
		$app = new App( __DIR__ );
		Objects::set_property( $app, 'app_config', null );
		Objects::set_property( $app, 'container', null );
		Objects::set_property( $app, 'module_manager', null );
		Objects::set_property( $app, 'loader', null );
		Objects::set_property( $app, 'booted', false );
		$app = null;
	}
}
