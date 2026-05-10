<?php
/**
 * E2E fixture: Layout Kitchen Sink page.
 *
 * Registers at `/wp-admin/admin.php?page=layout-kitchen-sink` and
 * pairs with Test_Layout_Kitchen_Sink_Settings.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Layout_Kitchen_Sink_Page extends Settings_Page {

	protected string $page_slug  = 'layout-kitchen-sink';
	protected string $menu_title = 'Layout Kitchen Sink';
	protected string $page_title = 'Layout Kitchen Sink';
	protected string $theme_stylesheet = Settings_Page::STYLE_MATERIAL;

	public function settings_class_name(): string {
		return Test_Layout_Kitchen_Sink_Settings::class;
	}
}
