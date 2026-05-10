<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Theme_Showcase_Vanilla_Page extends Settings_Page {

	protected string $page_slug  = 'theme-showcase-vanilla';
	protected string $menu_title = 'Showcase: Vanilla';
	protected string $page_title = 'Theme Showcase — Vanilla';
	protected string $theme_stylesheet = Settings_Page::STYLE_VANILLA;

	public function settings_class_name(): string {
		return Theme_Showcase_Settings::class;
	}
}
