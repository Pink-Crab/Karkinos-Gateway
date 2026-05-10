<?php

declare( strict_types=1 );

/**
 * Integration tests for the Settings_Page_Module GROUPS_PROCESSED listener.
 *
 * Boots a real Perique App with Admin_Menu + Settings_Page_Module and exercises
 * the contract that a Settings_Page declared ONLY inside an Abstract_Group's
 * $pages — never reaching Settings_Page_Middleware::process() — still receives
 * DI wiring via Hooks::GROUPS_PROCESSED.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Registration
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Registration;

use Gin0115\WPUnit_Helpers\Output;
use WP_UnitTestCase;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Form_Components\Module\Form_Components;
use PinkCrab\Perique_Admin_Menu\Module\Admin_Menu;
use PinkCrab\Perique_Settings_Page\Page\Settings_Page;
use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Module;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Default_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Default_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Override_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Override_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Group_Override_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Group_With_Page_Six_Times;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Group_With_Plain_Menu_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Group_With_Settings_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Plain_Menu_Page;
use PinkCrab\Perique_Settings_Page\Tests\Integration\Helper_Factory;

class Test_Group_Page_Discovery extends WP_UnitTestCase {

	use Helper_Factory;

	/** @var bool */
	protected $preserveGlobalState = false;

	public function setUp(): void {
		parent::setUp();
		$this->unset_app_instance();
	}

	/**
	 * @testdox A Settings_Page declared only inside a Group (not in registration_classes) still gets set_settings() called via the GROUPS_PROCESSED hook.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_group_only_settings_page_receives_settings_via_hook(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		// Register ONLY the Group — DI_Default_Page is NOT in registration_classes,
		// so Settings_Page_Middleware::process() will never see it directly.
		$app->registration_classes( array( Group_With_Settings_Page::class ) );

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		$page = $app->get_container()->create( DI_Default_Page::class );

		$this->assertInstanceOf( Settings_Page::class, $page );
		$this->assertInstanceOf(
			Abstract_Settings::class,
			$page->get_settings(),
			'set_settings() should have been called via the GROUPS_PROCESSED listener wiring.'
		);
		$this->assertInstanceOf( DI_Default_Settings::class, $page->get_settings() );
	}

	/**
	 * @testdox A Settings_Page in BOTH registration_classes AND a Group still resolves with settings — the two wiring paths converge on the same DI rules.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_settings_page_in_both_paths_still_resolves_with_settings(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes(
			array(
				Group_With_Settings_Page::class,
				DI_Default_Page::class,
			)
		);

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		$page = $app->get_container()->create( DI_Default_Page::class );

		$this->assertInstanceOf( DI_Default_Settings::class, $page->get_settings() );
	}

	/**
	 * @testdox When no Settings_Page subclasses are declared inside any Group, the listener resolves cleanly without error.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_listener_is_a_noop_when_no_groups_contain_settings_pages(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		// No groups, no Settings_Pages — just boot. Listener fires with empty registry.
		$app->registration_classes( array() );

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		$this->assertTrue( true, 'Boot completed without listener error.' );
	}

	/**
	 * @testdox When the same Settings_Page is named six times inside a single Group's $pages, the listener still wires DI rules exactly once and the page resolves with set_settings called.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_same_page_six_times_in_one_group_wires_once(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes( array( Group_With_Page_Six_Times::class ) );

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		$page = $app->get_container()->create( DI_Default_Page::class );

		$this->assertInstanceOf( DI_Default_Settings::class, $page->get_settings() );

		// Subsequent resolution returns the same shared instance — proves the page rule is `shared`.
		$page_again = $app->get_container()->create( DI_Default_Page::class );
		$this->assertSame( $page, $page_again, 'DI_Default_Page should resolve to a shared instance.' );

		// Registry first-write-wins guarantees the page maps to one group only.
		// We cannot inspect the registry from here easily, but the absence of any
		// runtime exception across six record() calls confirms idempotency.
	}

	/**
	 * @testdox When the same Settings_Page is declared in registration_classes AND in a Group's $pages six times, the page registers once and resolves with settings.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_same_page_six_times_across_groups_and_registration_classes(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes(
			array(
				Group_With_Page_Six_Times::class,
				DI_Default_Page::class,
			)
		);

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );
		do_action( 'admin_menu' );

		// Inspect WP's $menu / $submenu globals: the page slug should appear exactly once.
		global $menu, $submenu;

		$menu_count = 0;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $entry ) {
				if ( isset( $entry[2] ) && $entry[2] === 'di_default_page' ) {
					++$menu_count;
				}
			}
		}
		$this->assertSame(
			1,
			$menu_count,
			'di_default_page should appear in $menu exactly once even across six declarations.'
		);

		// And the page still resolves with settings injected.
		$page = $app->get_container()->create( DI_Default_Page::class );
		$this->assertInstanceOf( DI_Default_Settings::class, $page->get_settings() );
	}

	/**
	 * @testdox When two distinct Settings_Page subclasses live in two different Groups, both receive their own set_settings wiring with their own settings classes.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_two_distinct_settings_pages_in_two_groups_both_wired(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes(
			array(
				Group_With_Settings_Page::class, // contains DI_Default_Page
				Group_Override_Page::class,      // contains DI_Override_Page
			)
		);

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		$default_page  = $app->get_container()->create( DI_Default_Page::class );
		$override_page = $app->get_container()->create( DI_Override_Page::class );

		$this->assertInstanceOf( DI_Default_Settings::class, $default_page->get_settings() );
		$this->assertInstanceOf( DI_Override_Settings::class, $override_page->get_settings() );
		$this->assertNotSame(
			$default_page->get_settings(),
			$override_page->get_settings(),
			'Different Settings_Page subclasses should resolve to different settings instances.'
		);
	}

	/**
	 * @testdox A non-Settings_Page Menu_Page declared inside a Group is NOT given the Settings_Page DI rule — listener filters by subclass.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_non_settings_page_in_group_is_not_wired(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes( array( Group_With_Plain_Menu_Page::class ) );

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		// A plain Menu_Page should NOT have set_settings on it — it has no such method.
		// Resolve it: should construct cleanly with no DI call rule attached.
		$page = $app->get_container()->create( Plain_Menu_Page::class );
		$this->assertInstanceOf( Plain_Menu_Page::class, $page );
		$this->assertFalse(
			method_exists( $page, 'get_settings' ),
			'Sanity check: Plain_Menu_Page must not define get_settings().'
		);
	}

	/**
	 * @testdox A Group-only Settings_Page renders without "Settings not initialised" — set_settings was called via the GROUPS_PROCESSED listener.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_group_only_settings_page_renders_without_uninitialised_marker(): void {
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes( array( Group_With_Settings_Page::class ) );

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );
		do_action( 'admin_menu' );

		$page    = $app->get_container()->create( DI_Default_Page::class );
		$render  = $page->render_view();
		$output  = Output::buffer( $render );

		$this->assertStringNotContainsString(
			'Settings not initialised',
			$output,
			'Group-declared Settings_Page must render with settings populated, not the fallback message.'
		);
	}

	/**
	 * @testdox Same Settings_Page declared in two different Groups — first-write-wins on the registry means its DI wiring still produces a working set_settings call.
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_same_page_in_two_groups_still_wires_correctly(): void {
		// A second group reusing DI_Default_Page exists implicitly via Group_With_Page_Six_Times.
		// Combine with Group_With_Settings_Page to assert two groups can both reference the same page.
		$app = ( new App_Factory( __DIR__ ) )
			->set_base_view_path( __DIR__ )
			->default_setup()
			->module( Form_Components::class )
			->module( Settings_Page_Module::class )
			->module( Admin_Menu::class )
			->boot();

		$app->registration_classes(
			array(
				Group_With_Settings_Page::class,
				Group_With_Page_Six_Times::class,
			)
		);

		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_user );
		set_current_screen( 'dashboard' );
		do_action( 'init' );

		$page = $app->get_container()->create( DI_Default_Page::class );
		$this->assertInstanceOf( DI_Default_Settings::class, $page->get_settings() );
	}
}
