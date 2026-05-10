<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Theme_Showcase_Wp_Admin_Page extends Settings_Page {

	protected string $page_slug  = 'theme-showcase-wp-admin';
	protected string $menu_title = 'Showcase: WP Admin';
	protected string $page_title = 'Theme Showcase — WP Admin';
	protected string $theme_stylesheet = Settings_Page::STYLE_WP_ADMIN;

	public function settings_class_name(): string {
		return Theme_Showcase_Settings::class;
	}
}
