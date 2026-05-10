<?php
/**
 * E2E fixture: Settings_Page registered ONLY inside a Group as the primary page.
 *
 * Critical scenario — without the Settings_Page_Module GROUPS_PROCESSED listener,
 * this page renders "Settings not initialised." because nothing wires its DI
 * call rule for set_settings(). With the listener in place, the form renders
 * normally.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Discovery_Page_B extends Settings_Page {

	protected string $page_slug  = 'test_discovery_page_b';
	protected string $menu_title = 'Discovery B';
	protected string $page_title = 'Discovery Page B';

	public function settings_class_name(): string {
		return Test_Discovery_Settings_B::class;
	}
}
