<?php
/**
 * Karkinos Gateway.
 *
 * @wordpress-plugin
 * Plugin Name:  Karkinos Gateway
 * Description:  Proxy gateway between GitHub Actions and the home server. Scaffolding only — REST + settings land in follow-up iterations.
 * Version:      0.1.0
 * Requires PHP: 8.0
 * Author:       Glynn Quelch
 * Text Domain:  karkinos-gateway
 */

declare(strict_types=1);

use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Form_Components\Module\Form_Components;
use PinkCrab\Perique_Admin_Menu\Module\Admin_Menu;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Module;
use PinkCrab\Route\Module\Route;

defined( 'ABSPATH' ) || exit;

define( 'KARKINOS_GATEWAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'KARKINOS_GATEWAY_URL', plugin_dir_url( __FILE__ ) );
define( 'KARKINOS_GATEWAY_VERSION', '0.1.0' );

if ( ! is_file( KARKINOS_GATEWAY_PATH . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			echo '<div class="notice notice-error"><p><strong>Karkinos Gateway:</strong> vendor/autoload.php not found — run <code>composer install</code> inside <code>mu-plugins/karkinos-gateway/</code>.</p></div>';
		}
	);
	return;
}

require_once KARKINOS_GATEWAY_PATH . 'vendor/autoload.php';

( new App_Factory( __DIR__ ) )
	->default_setup()
	->di_rules( require __DIR__ . '/config/di.php' )
	->app_config( require __DIR__ . '/config/settings.php' )
	->registration_classes( require __DIR__ . '/config/registration.php' )
	->module( Form_Components::class )
	->module( Admin_Menu::class )
	->module( Settings_Page_Module::class )
	->module( Route::class )
	->boot();
