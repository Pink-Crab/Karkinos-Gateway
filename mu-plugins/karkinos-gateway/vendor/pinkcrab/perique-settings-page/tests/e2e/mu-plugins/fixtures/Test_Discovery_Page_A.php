<?php
/**
 * E2E fixture: standalone Settings_Page registered ONLY via registration_classes.
 *
 * Baseline scenario — confirms the existing Settings_Page_Middleware::process()
 * path still wires DI rules correctly when no Group is involved.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Discovery_Page_A extends Settings_Page {

	protected string $page_slug  = 'test_discovery_page_a';
	protected string $menu_title = 'Discovery A';
	protected string $page_title = 'Discovery Page A';

	public function settings_class_name(): string {
		return Test_Discovery_Settings_A::class;
	}
}
