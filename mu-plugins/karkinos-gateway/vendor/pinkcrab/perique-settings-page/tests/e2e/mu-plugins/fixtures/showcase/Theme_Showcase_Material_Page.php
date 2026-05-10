<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Theme_Showcase_Material_Page extends Settings_Page {

	protected string $page_slug  = 'theme-showcase-material';
	protected string $menu_title = 'Showcase: Material';
	protected string $page_title = 'Theme Showcase — Material';
	protected string $theme_stylesheet = Settings_Page::STYLE_MATERIAL;

	public function settings_class_name(): string {
		return Theme_Showcase_Settings::class;
	}
}
