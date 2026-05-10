<?php
/**
 * E2E fixture: a plain admin Menu_Page (NOT a Settings_Page) registered inside
 * the Group alongside Settings_Page subclasses.
 *
 * Verifies the GROUPS_PROCESSED listener correctly filters by Settings_Page::class
 * and does NOT apply Settings_Page DI rules to non-Settings_Page Menu_Pages.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Admin_Menu\Page\Menu_Page;

class Test_Discovery_Plain_Page extends Menu_Page {

	protected string $page_slug     = 'test_discovery_plain_page';
	protected string $menu_title    = 'Discovery Plain';
	protected string $page_title    = 'Discovery Plain Page';
	protected string $view_template = 'discovery-plain';
	/** @var array<string, mixed> */
	protected array $view_data = array(
		'marker' => 'PLAIN_PAGE_MARKER_OK',
	);
}
