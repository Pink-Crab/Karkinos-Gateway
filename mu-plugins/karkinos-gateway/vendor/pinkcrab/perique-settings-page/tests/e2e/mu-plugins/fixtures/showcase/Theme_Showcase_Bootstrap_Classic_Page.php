<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Theme_Showcase_Bootstrap_Classic_Page extends Settings_Page {

	protected string $page_slug  = 'theme-showcase-bootstrap-classic';
	protected string $menu_title = 'Showcase: BS Classic';
	protected string $page_title = 'Theme Showcase — Bootstrap Classic';
	protected string $theme_stylesheet = Settings_Page::STYLE_BOOTSTRAP_CLASSIC;

	public function settings_class_name(): string {
		return Theme_Showcase_Settings::class;
	}
}
