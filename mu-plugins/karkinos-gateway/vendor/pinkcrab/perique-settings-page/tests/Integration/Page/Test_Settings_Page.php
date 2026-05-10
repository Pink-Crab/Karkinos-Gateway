<?php

declare( strict_types=1 );

/**
 * Integration tests for Settings_Page.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Page;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Page\Settings_Page;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Settings_Page;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository;

class Test_Settings_Page extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		Mock_Abstract_Settings::$injected_fields = array();
		Mock_Abstract_Settings::$grouped         = false;
		Mock_Abstract_Settings::$group_key       = 'mock_settings';
	}

	public function tearDown(): void {
		// Ensure a clean WP styles/scripts state between tests — wp_deregister_*
		// only removes from `registered`, so handles can leak into the `queue`.
		foreach ( array( 'pc-settings-page-core', 'pc-settings-page-theme' ) as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
		wp_dequeue_script( 'pc-settings-page' );
		wp_deregister_script( 'pc-settings-page' );

		parent::tearDown();
	}

	/** @testdox settings_class_name() returns the FQCN of the settings class. */
	public function test_settings_class_name(): void {
		$this->assertSame( Mock_Abstract_Settings::class, ( new Mock_Settings_Page() )->settings_class_name() );
	}

	/** @testdox set_settings stores the settings instance and refreshes from the repository. */
	public function test_set_settings(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );
		$repo->store['mock_settings_name']       = 'stored';

		$page     = new Mock_Settings_Page();
		$settings = new Mock_Abstract_Settings( $repo );
		$page->set_settings( $settings );

		$this->assertSame( $settings, $page->get_settings() );
		$this->assertSame( 'stored', $settings->get( 'name' ) );
	}

	/** @testdox get_settings returns null before set_settings is called. */
	public function test_get_settings_null_before_set(): void {
		$this->assertNull( ( new Mock_Settings_Page() )->get_settings() );
	}

	/** @testdox get_method returns POST by default. */
	public function test_get_method_default(): void {
		$this->assertSame( 'POST', ( new Mock_Settings_Page() )->get_method() );
	}

	/** @testdox get_nonce_handle returns a slug-prefixed handle. */
	public function test_get_nonce_handle(): void {
		$this->assertStringContainsString( 'mock-page', ( new Mock_Settings_Page() )->get_nonce_handle() );
	}

	/** @testdox get_nonce_field_name returns the default field name. */
	public function test_get_nonce_field_name(): void {
		$this->assertSame( 'pc_settings_nonce', ( new Mock_Settings_Page() )->get_nonce_field_name() );
	}

	/** @testdox get_submit_label returns a non-empty translated label. */
	public function test_get_submit_label(): void {
		$this->assertNotEmpty( ( new Mock_Settings_Page() )->get_submit_label() );
	}

	/** @testdox get_form_action returns an empty string by default. */
	public function test_get_form_action(): void {
		$this->assertSame( '', ( new Mock_Settings_Page() )->get_form_action() );
	}

	/** @testdox render_view returns a callable. */
	public function test_render_view_returns_callable(): void {
		$this->assertIsCallable( ( new Mock_Settings_Page() )->render_view() );
	}

	/** @testdox render_view closure renders settings-not-initialised when settings is null. */
	public function test_render_view_without_settings(): void {
		$page    = new Mock_Settings_Page();
		$render  = $page->render_view();
		ob_start();
		$render();
		$output = (string) ob_get_clean();
		$this->assertStringContainsString( 'Settings not initialised', $output );
	}

	/** @testdox load() returns early when settings is null. */
	public function test_load_without_settings(): void {
		$page = new Mock_Settings_Page();
		// Should not throw.
		$this->assertNull( $page->load( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) ) );
	}

	/** @testdox load() returns early when not a POST request and method is POST. */
	public function test_load_wrong_method(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$this->assertNull( $page->load( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) ) );
	}

	/** @testdox load() short-circuits when there is no submission. */
	public function test_load_no_submission(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array();

		$this->assertNull( $page->load( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) ) );
	}

	/** @testdox load() processes a valid submission and stashes the result. */
	public function test_load_processes_submission(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array(
			'page'              => 'mock-page',
			'pc_settings_nonce' => wp_create_nonce( $page->get_nonce_handle() ),
			'name'              => 'value',
		);

		$page->load( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) );
		$this->assertSame( 'value', $repo->store['mock_settings_name'] );

		$_POST                     = array();
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	/** @testdox enqueue() registers core CSS, the default vanilla theme, and the JS. */
	public function test_enqueue(): void {
		$page = new Mock_Settings_Page();
		$page->enqueue( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) );

		$this->assertTrue( wp_style_is( 'pc-settings-page-core', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'pc-settings-page-theme', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'pc-settings-page', 'enqueued' ) );

		wp_dequeue_style( 'pc-settings-page-core' );
		wp_dequeue_style( 'pc-settings-page-theme' );
		wp_dequeue_script( 'pc-settings-page' );
	}

	/** @testdox enqueue() with STYLE_NONE skips the theme stylesheet. */
	public function test_enqueue_no_theme(): void {
		// Deregister so we're testing this test's enqueue, not leftover state.
		wp_deregister_style( 'pc-settings-page-core' );
		wp_deregister_style( 'pc-settings-page-theme' );
		wp_deregister_script( 'pc-settings-page' );

		$page = new Mock_Settings_Page_No_Theme();
		$page->enqueue( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) );

		$this->assertTrue( wp_style_is( 'pc-settings-page-core', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'pc-settings-page-theme', 'enqueued' ) );

		wp_deregister_style( 'pc-settings-page-core' );
		wp_deregister_script( 'pc-settings-page' );
	}

	/** @testdox get_theme_stylesheet() returns the current theme identifier. */
	public function test_get_theme_stylesheet(): void {
		$this->assertSame( Settings_Page::STYLE_VANILLA, ( new Mock_Settings_Page() )->get_theme_stylesheet() );
	}

	/** @testdox enqueue() with a URL theme loads the stylesheet from that URL directly. */
	public function test_enqueue_url_theme(): void {
		// Fully deregister so wp_enqueue_style isn't a no-op.
		wp_deregister_style( 'pc-settings-page-core' );
		wp_deregister_style( 'pc-settings-page-theme' );
		wp_deregister_script( 'pc-settings-page' );

		$page = new Mock_Settings_Page_Url_Theme();
		$page->enqueue( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) );

		$this->assertTrue( wp_style_is( 'pc-settings-page-theme', 'enqueued' ) );
		$src = wp_styles()->registered['pc-settings-page-theme']->src ?? '';
		$this->assertSame( 'https://cdn.example.com/my-theme.css', $src );

		wp_deregister_style( 'pc-settings-page-core' );
		wp_deregister_style( 'pc-settings-page-theme' );
		wp_deregister_script( 'pc-settings-page' );
	}

	/** @testdox enqueue() with an absolute path resolves to a content URL. */
	public function test_enqueue_absolute_path_theme(): void {
		wp_deregister_style( 'pc-settings-page-core' );
		wp_deregister_style( 'pc-settings-page-theme' );
		wp_deregister_script( 'pc-settings-page' );

		$page = new Mock_Settings_Page_Path_Theme();
		$page->enqueue( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) );

		$this->assertTrue( wp_style_is( 'pc-settings-page-theme', 'enqueued' ) );
		$src = wp_styles()->registered['pc-settings-page-theme']->src ?? '';
		$this->assertStringContainsString( 'custom-theme.css', (string) $src );

		wp_deregister_style( 'pc-settings-page-core' );
		wp_deregister_style( 'pc-settings-page-theme' );
		wp_deregister_script( 'pc-settings-page' );
	}

	/** @testdox render_view closure renders the full page when settings are set. */
	public function test_render_view_with_settings(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' )->set_label( 'Name' ) );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );

		$render = $page->render_view();
		ob_start();
		$render();
		$output = (string) ob_get_clean();
		$this->assertStringContainsString( 'Mock Page', $output );
	}

	/** @testdox render_view passes the view service to the mapper and renders components when set. */
	public function test_render_view_with_view_service(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' )->set_label( 'Name' ) );

		$view = $this->getMockBuilder( \PinkCrab\Perique\Services\View\View::class )
			->disableOriginalConstructor()
			->getMock();
		$view->method( 'component' )->willReturn( '<rendered/>' );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );
		$page->set_view( $view );

		$render = $page->render_view();
		ob_start();
		$render();
		$output = (string) ob_get_clean();
		$this->assertStringContainsString( 'Mock Page', $output );
	}

	/** @testdox render_view passes field errors from a previous failed submission to the mapper. */
	public function test_render_view_with_field_errors(): void {
		$repo                                    = new Object_Setting_Repository();
		// Field with a validator that always fails.
		Mock_Abstract_Settings::$injected_fields = array(
			Text::new( 'name' )->set_validate( fn( $v ): bool => false ),
		);

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );

		// Trigger a failed submission via load() so last_result has field errors.
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = array(
			'page'              => 'mock-page',
			'pc_settings_nonce' => wp_create_nonce( $page->get_nonce_handle() ),
			'name'              => 'value',
		);
		$page->load( $this->createMock( \PinkCrab\Perique_Admin_Menu\Page\Page::class ) );

		// Now render the view — it should pull errors from $last_result.
		$render = $page->render_view();
		ob_start();
		$render();
		$output = (string) ob_get_clean();
		$this->assertStringContainsString( 'Mock Page', $output );

		$_POST                     = array();
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	/** @testdox pre/post template getters return null/[] by default. */
	public function test_templates_default_null(): void {
		$page = new Mock_Settings_Page();
		$this->assertNull( $page->get_pre_template() );
		$this->assertSame( array(), $page->get_pre_data() );
		$this->assertNull( $page->get_post_template() );
		$this->assertSame( array(), $page->get_post_data() );
	}

	/** @testdox set_pre_template stores the template and data. */
	public function test_set_pre_template(): void {
		$page = new Mock_Settings_Page();
		$page->set_pre_template( 'pre/view', array( 'foo' => 'bar' ) );
		$this->assertSame( 'pre/view', $page->get_pre_template() );
		$this->assertSame( array( 'foo' => 'bar' ), $page->get_pre_data() );
	}

	/** @testdox set_post_template stores the template and data. */
	public function test_set_post_template(): void {
		$page = new Mock_Settings_Page();
		$page->set_post_template( 'post/view', array( 'x' => 1 ) );
		$this->assertSame( 'post/view', $page->get_post_template() );
		$this->assertSame( array( 'x' => 1 ), $page->get_post_data() );
	}

	/** @testdox render_view calls before_render() before emitting any HTML. */
	public function test_render_view_calls_before_render(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );

		$page = new Mock_Settings_Page_With_Before_Render();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );

		$render = $page->render_view();
		ob_start();
		$render();
		ob_end_clean();

		$this->assertTrue( $page->before_render_called );
	}

	/** @testdox render_view prints pre/post templates via the view service when set. */
	public function test_render_view_renders_pre_and_post_templates(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' )->set_label( 'Name' ) );

		$view = $this->getMockBuilder( \PinkCrab\Perique\Services\View\View::class )
			->disableOriginalConstructor()
			->getMock();

		// Expect: pre template then post template. Record order via a closure.
		$calls = array();
		$view->method( 'render' )->willReturnCallback(
			function ( string $template, iterable $data ) use ( &$calls ) {
				$calls[] = array( 'template' => $template, 'data' => iterator_to_array( (function () use ( $data ) { yield from $data; } )() ) );
				echo "<!-- rendered:{$template} -->";
				return null;
			}
		);
		$view->method( 'component' )->willReturnCallback(
			function () {
				echo '<!-- form -->';
				return null;
			}
		);

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );
		$page->set_view( $view );
		$page->set_pre_template( 'above/intro', array( 'p' => 1 ) );
		$page->set_post_template( 'below/help', array( 'q' => 2 ) );

		$render = $page->render_view();
		ob_start();
		$render();
		$output = (string) ob_get_clean();

		// Both templates were passed to view->render() in the right order.
		$this->assertCount( 2, $calls );
		$this->assertSame( 'above/intro', $calls[0]['template'] );
		$this->assertSame( array( 'p' => 1 ), $calls[0]['data'] );
		$this->assertSame( 'below/help', $calls[1]['template'] );
		$this->assertSame( array( 'q' => 2 ), $calls[1]['data'] );

		// And the printed output has pre → form → post order.
		$pre_pos  = strpos( $output, '<!-- rendered:above/intro -->' );
		$form_pos = strpos( $output, '<!-- form -->' );
		$post_pos = strpos( $output, '<!-- rendered:below/help -->' );
		$this->assertNotFalse( $pre_pos );
		$this->assertNotFalse( $form_pos );
		$this->assertNotFalse( $post_pos );
		$this->assertLessThan( $form_pos, $pre_pos );
		$this->assertLessThan( $post_pos, $form_pos );
	}

	/** @testdox render_view does not call view->render for templates when view is null. */
	public function test_render_view_no_view_skips_templates(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );
		$page->set_pre_template( 'pre', array() );
		$page->set_post_template( 'post', array() );

		// No view set — should not fatal.
		$render = $page->render_view();
		ob_start();
		$render();
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'pre', $output );
		$this->assertStringNotContainsString( 'post', $output );
	}

	/** @testdox render_view skips template rendering when templates are null even if view is set. */
	public function test_render_view_with_view_but_no_templates(): void {
		$repo                                    = new Object_Setting_Repository();
		Mock_Abstract_Settings::$injected_fields = array( Text::new( 'name' ) );

		$view = $this->getMockBuilder( \PinkCrab\Perique\Services\View\View::class )
			->disableOriginalConstructor()
			->getMock();
		$view->expects( $this->never() )->method( 'render' );
		$view->method( 'component' )->willReturn( null );

		$page = new Mock_Settings_Page();
		$page->set_settings( new Mock_Abstract_Settings( $repo ) );
		$page->set_view( $view );

		$render = $page->render_view();
		ob_start();
		$render();
		ob_end_clean();
	}
}

/**
 * Subclass that tracks whether before_render() was called.
 */
class Mock_Settings_Page_With_Before_Render extends Mock_Settings_Page {
	public bool $before_render_called = false;

	protected function before_render(): void {
		$this->before_render_called = true;
	}
}

/**
 * Subclass with no theme (STYLE_NONE).
 */
class Mock_Settings_Page_No_Theme extends Mock_Settings_Page {
	protected string $theme_stylesheet = Settings_Page::STYLE_NONE;
}

/**
 * Subclass that points $theme_stylesheet at a URL.
 */
class Mock_Settings_Page_Url_Theme extends Mock_Settings_Page {
	protected string $theme_stylesheet = 'https://cdn.example.com/my-theme.css';
}

/**
 * Subclass that points $theme_stylesheet at an absolute file path
 * under WP_CONTENT_DIR so the enqueue path-to-URL conversion runs.
 */
class Mock_Settings_Page_Path_Theme extends Mock_Settings_Page {
	protected string $theme_stylesheet = WP_CONTENT_DIR . '/themes/custom-theme.css';
}
