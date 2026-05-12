<?php
/**
 * Karkinos Gateway — mu-plugin entry file.
 *
 * Bootstraps the Perique App with the modules this plugin needs:
 *
 *   - Form_Components       — required by Settings_Page_Module.
 *   - Admin_Menu             — required by Settings_Page_Module.
 *   - Settings_Page_Module   — Settings → Karkinos Gateway page.
 *   - Route                  — REST endpoints under karkinos-gateway/v1.
 *   - Registerable           — CPTs + Taxonomies.
 *
 * Migrations are fired manually by Migrations_Runner on `init` (the
 * canonical Perique_Migrations module ties to register_activation_hook
 * which never fires for mu-plugins).
 *
 * @wordpress-plugin
 * Plugin Name:  Karkinos Gateway
 * Description:  WordPress-backed proxy between GitHub webhooks and a home server on a rotating ISP IP.
 * Version:      0.1.0
 * Requires PHP: 8.3
 * Author:       Glynn Quelch
 * Text Domain:  karkinos-gateway
 *
 * @package Karkinos\Gateway
 */

declare(strict_types=1);

use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Form_Components\Module\Form_Components;
use PinkCrab\Perique_Admin_Menu\Module\Admin_Menu;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Module;
use PinkCrab\Registerables\Module\Registerable;
use PinkCrab\Route\Module\Route;

defined( 'ABSPATH' ) || exit;

define( 'KARKINOS_GATEWAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'KARKINOS_GATEWAY_URL', plugin_dir_url( __FILE__ ) );
define( 'KARKINOS_GATEWAY_VERSION', '0.1.0' );

if ( ! is_file( KARKINOS_GATEWAY_PATH . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
				esc_html__( 'Karkinos Gateway:', 'karkinos-gateway' ),
				wp_kses(
					sprintf(
						/* translators: %s: composer command to run. */
						__( 'vendor/autoload.php not found — run %s inside the mu-plugin directory.', 'karkinos-gateway' ),
						'<code>composer install</code>'
					),
					array( 'code' => array() )
				)
			);
		}
	);
	return;
}

require_once KARKINOS_GATEWAY_PATH . 'vendor/autoload.php';

$r = ( new App_Factory( __DIR__ ) )
	->default_setup()
	->di_rules( require __DIR__ . '/config/di.php' )
	->app_config( require __DIR__ . '/config/settings.php' )
	->registration_classes( require __DIR__ . '/config/registration.php' )
	->module( Form_Components::class )
	->module( Admin_Menu::class )
	->module( Settings_Page_Module::class )
	->module( Route::class )
	->module( Registerable::class );
	$r->boot();
