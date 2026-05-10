<?php
/**
 * E2E fixture: Settings_Page registered in BOTH registration_classes AND a Group's $pages.
 *
 * The duplicate scenario from issue #58. With the claim+registry fix, the page
 * registers exactly once (via the Group dispatch path) and the slug appears in
 * the WP admin menu only once.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Discovery_Page_C extends Settings_Page {

	protected string $page_slug  = 'test_discovery_page_c';
	protected string $menu_title = 'Discovery C';
	protected string $page_title = 'Discovery Page C';

	public function settings_class_name(): string {
		return Test_Discovery_Settings_C::class;
	}
}
