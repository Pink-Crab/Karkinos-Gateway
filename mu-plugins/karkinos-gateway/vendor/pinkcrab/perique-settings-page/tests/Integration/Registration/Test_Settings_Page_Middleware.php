<?php

declare( strict_types=1 );

/**
 * Integration tests for Settings_Page_Middleware.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Registration
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Registration;

use stdClass;
use Throwable;
use WP_UnitTestCase;
use PinkCrab\Perique\Interfaces\DI_Container;
use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Form_Components\Module\Form_Components;
use PinkCrab\Perique_Admin_Menu\Module\Admin_Menu;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Module;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Middleware;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Default_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\DI_Default_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Settings_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository;
use PinkCrab\Perique_Settings_Page\Tests\Integration\Helper_Factory;

class Test_Settings_Page_Middleware extends WP_UnitTestCase {

	use Helper_Factory;

	/** @var bool */
	protected $preserveGlobalState = false;

	public function setUp(): void {
		parent::setUp();
		Mock_Abstract_Settings::$injected_fields = array();
		Mock_Abstract_Settings::$grouped         = false;
		Mock_Abstract_Settings::$group_key       = 'mock_settings';
		$this->unset_app_instance();
	}

	protected function build_container(): DI_Container {
		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )->willReturn( new Mock_Abstract_Settings( new Object_Setting_Repository() ) );
		return $container;
	}

	/** @testdox setup() and tear_down() are no-ops. */
	public function test_setup_and_teardown(): void {
		$middleware = new Settings_Page_Middleware( $this->createMock( DI_Container::class ) );
		$middleware->setup();
		$middleware->tear_down();
		$this->assertTrue( true );
	}

	/** @testdox process() returns non-Settings_Page instances unchanged. */
	public function test_process_passes_through_non_settings_page(): void {
		$middleware = new Settings_Page_Middleware( $this->createMock( DI_Container::class ) );
		$obj        = new stdClass();
		$this->assertSame( $obj, $middleware->process( $obj ) );
	}

	/** @testdox process() registers the settings class as shared and adds a call rule on the page class. */
	public function test_process_registers_dice_rules(): void {
		$container = $this->createMock( DI_Container::class );
		$container->expects( $this->atLeastOnce() )
			->method( 'addRule' );
		$container->method( 'create' )
			->willReturn( new Mock_Abstract_Settings( new Object_Setting_Repository() ) );

		$middleware = new Settings_Page_Middleware( $container );
		$page       = new Mock_Settings_Page();
		$middleware->process( $page );
	}

	/** @testdox process() returns the same Settings_Page instance it received. */
	public function test_process_returns_same_instance(): void {
		$middleware = new Settings_Page_Middleware( $this->build_container() );
		$page       = new Mock_Settings_Page();
		$this->assertSame( $page, $middleware->process( $page ) );
	}

	/** @testdox process() does not register a call rule when create() returns a non-Abstract_Settings. */
	public function test_process_skips_call_rule_when_settings_invalid(): void {
		$container = $this->createMock( DI_Container::class );
		$container->method( 'create' )->willReturn( null );

		// Should still call addRule once for the settings class shared registration.
		$container->expects( $this->once() )
			->method( 'addRule' );

		$middleware = new Settings_Page_Middleware( $container );
		$middleware->process( new Mock_Settings_Page() );
	}

	/**
	 * Regression test for the original consumer-side ArgumentCountError.
	 *
	 * Boots a real App with a registered Settings_Page subclass whose Settings
	 * class has no constructor override — the exact shape that previously
	 * crashed during Settings_Page_Middleware::process() when DICE tried to
	 * construct the Settings class with no rule binding the Setting_Repository
	 * interface.
	 *
	 * @testdox Real boot resolves a Settings_Page whose Settings class has no constructor override (regression for ArgumentCountError).
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_real_boot_resolves_settings_page_without_argument_count_error(): void {
		try {
			$app = ( new App_Factory( __DIR__ ) )
				->set_base_view_path( __DIR__ )
				->default_setup()
				->module( Form_Components::class )
				->module( Settings_Page_Module::class )
				->module( Admin_Menu::class )
				->registration_classes( array( DI_Default_Page::class ) )
				->boot();

			do_action( 'init' );
		} catch ( Throwable $e ) {
			$this->fail(
				'Booting a Settings_Page with a no-constructor Settings class threw: '
				. get_class( $e ) . ': ' . $e->getMessage()
			);
		}

		$page = $app->get_container()->create( DI_Default_Page::class );
		$this->assertInstanceOf( DI_Default_Page::class, $page );

		$settings = $app->get_container()->create( DI_Default_Settings::class );
		$this->assertInstanceOf( DI_Default_Settings::class, $settings );
	}
}
