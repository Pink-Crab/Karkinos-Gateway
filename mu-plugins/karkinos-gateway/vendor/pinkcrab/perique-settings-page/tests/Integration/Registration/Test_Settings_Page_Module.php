<?php

declare( strict_types=1 );

/**
 * Integration tests for Settings_Page_Module.
 *
 * Boots a real Perique App and inspects the real DI container — no PHPUnit
 * mocks. Each method runs in its own PHP process via @runInSeparateProcess
 * so the App singleton's static state cannot leak between tests.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Registration
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Registration;

use ReflectionObject;
use Spy_REST_Server;
use stdClass;
use WP_UnitTestCase;
use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Form_Components\Module\Form_Components;
use PinkCrab\Perique_Admin_Menu\Hooks as Admin_Menu_Hooks;
use PinkCrab\Perique_Admin_Menu\Module\Admin_Menu;
use PinkCrab\Perique_Admin_Menu\Registry\Group_Page_Registry;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Module;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Middleware;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Individual_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Site_Options_Decorator;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Default_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Default_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Override_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Group_With_Settings_Page;
use PinkCrab\Perique_Settings_Page\Tests\Integration\Helper_Factory;

class Test_Settings_Page_Module extends WP_UnitTestCase {

	use Helper_Factory;

	/** @var bool */
	protected $preserveGlobalState = false;

	public function setUp(): void {
		parent::setUp();
		$this->unset_app_instance();
	}

	/** @testdox get_middleware returns the Settings_Page_Middleware FQCN. */
	public function test_get_middleware(): void {
		$this->assertSame(
			Settings_Page_Middleware::class,
			( new Settings_Page_Module() )->get_middleware()
		);
	}

	/**
	 * @testdox pre_boot binds Setting_Repository to WP_Options_Settings_Repository as default.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_pre_boot_binds_setting_repository_to_default(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$repo = $app->get_container()->create( Setting_Repository::class );

		$this->assertInstanceOf( WP_Options_Settings_Repository::class, $repo );
	}

	/**
	 * @testdox A Settings class with no constructor override resolves through DI using the default repo.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_settings_class_with_no_constructor_resolves_via_default_repo(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$settings = $app->get_container()->create( DI_Default_Settings::class );

		$this->assertInstanceOf( DI_Default_Settings::class, $settings );
		$this->assertInstanceOf(
			WP_Options_Settings_Repository::class,
			$this->repo_of( $settings )
		);
	}

	/**
	 * @testdox Consumer-supplied per-class substitutions override the default repo for that one Settings class.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_consumer_per_class_substitution_overrides_default(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->di_rules(
				array(
					DI_Override_Settings::class => array(
						'substitutions' => array(
							Setting_Repository::class => WP_Options_Individual_Repository::class,
						),
					),
				)
			)
			->boot();

		$override = $app->get_container()->create( DI_Override_Settings::class );
		$default  = $app->get_container()->create( DI_Default_Settings::class );

		$this->assertInstanceOf(
			WP_Options_Individual_Repository::class,
			$this->repo_of( $override ),
			'Override class should pick up the substituted repo.'
		);
		$this->assertInstanceOf(
			WP_Options_Settings_Repository::class,
			$this->repo_of( $default ),
			'Other settings classes should still get the default repo.'
		);
	}

	/**
	 * @testdox Two Settings classes can resolve to two different repositories in the same container.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_two_settings_classes_use_two_different_repos(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->di_rules(
				array(
					DI_Default_Settings::class  => array(
						'substitutions' => array(
							Setting_Repository::class => WP_Options_Individual_Repository::class,
						),
					),
					DI_Override_Settings::class => array(
						'substitutions' => array(
							Setting_Repository::class => WP_Site_Options_Decorator::class,
						),
					),
				)
			)
			->boot();

		$a = $app->get_container()->create( DI_Default_Settings::class );
		$b = $app->get_container()->create( DI_Override_Settings::class );

		$this->assertInstanceOf( WP_Options_Individual_Repository::class, $this->repo_of( $a ) );
		$this->assertInstanceOf( WP_Site_Options_Decorator::class, $this->repo_of( $b ) );
	}

	/**
	 * @testdox A decorator stack (Site_Options wrapping Options) wires correctly via per-class substitutions.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_decorator_stack_via_per_class_substitution(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->di_rules(
				array(
					DI_Override_Settings::class => array(
						'substitutions' => array(
							Setting_Repository::class => WP_Site_Options_Decorator::class,
						),
					),
				)
			)
			->boot();

		$repo = $this->repo_of( $app->get_container()->create( DI_Override_Settings::class ) );

		$this->assertInstanceOf( WP_Site_Options_Decorator::class, $repo );

		// The decorator's `$inner` falls through to the default interface
		// binding because no rule was set for the decorator's own dep.
		$inner_prop = ( new ReflectionObject( $repo ) )->getProperty( 'inner' );
		$inner_prop->setAccessible( true );
		$this->assertInstanceOf(
			WP_Options_Settings_Repository::class,
			$inner_prop->getValue( $repo )
		);
	}

	/**
	 * @testdox pre_register registers the Picker_Rest_Controller on rest_api_init.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_pre_register_registers_picker_rest_controller(): void {
		( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		// Fire `init` so the Hook_Loader commits its queued hooks (incl. the
		// pre_register-registered rest_api_init action). See App::finalise().
		do_action( 'init' );

		global $wp_rest_server;
		$wp_rest_server = new Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$routes = $wp_rest_server->get_routes();

		$matched = false;
		foreach ( array_keys( $routes ) as $route ) {
			if ( strpos( $route, '/pc-settings/v1' ) !== false ) {
				$matched = true;
				break;
			}
		}
		$this->assertTrue( $matched, 'Expected a /pc-settings/v1 REST route to be registered.' );
	}

	/**
	 * @testdox The GROUPS_PROCESSED listener tolerates a DI container that returns a non-Settings_Page (e.g. stdClass) for a registered page class — it skips that entry without calling addRule.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_listener_skips_when_di_returns_non_settings_page(): void {
		$registry = new Group_Page_Registry();
		$registry->record( DI_Default_Page::class, new Group_With_Settings_Page() );

		// Mocked container returns a stdClass for any create() call, including
		// the Settings_Page subclass key — exercises the defensive guard branch.
		$di = $this->createMock( DI_Container::class );
		$di->method( 'create' )->willReturn( new stdClass() );
		$di->expects( $this->never() )
			->method( 'addRule' );

		// Wire the listener via pre_register, with a Hook_Loader mock so the
		// rest_api_init queueing doesn't hit anything real. App_Config is
		// final so we instantiate it (constructor accepts an empty settings array).
		$loader = $this->createMock( Hook_Loader::class );
		$config = new App_Config();

		( new Settings_Page_Module() )->pre_register( $config, $loader, $di );

		// Fire the action — listener iterates the registry, hits the guard, continues.
		do_action( Admin_Menu_Hooks::GROUPS_PROCESSED, $registry );

		// If we reach this point without an exception and addRule was never
		// called (mock expectation), the guard worked.
		$this->assertTrue( true );
	}

	/**
	 * Reflects the protected `settings_repository` out of an Abstract_Settings.
	 *
	 * @param object $settings
	 * @return Setting_Repository
	 */
	private function repo_of( object $settings ): Setting_Repository {
		$prop = ( new ReflectionObject( $settings ) )->getProperty( 'settings_repository' );
		$prop->setAccessible( true );
		return $prop->getValue( $settings );
	}
}
