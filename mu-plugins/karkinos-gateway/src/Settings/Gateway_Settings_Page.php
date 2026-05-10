<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Settings;

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Gateway_Settings_Page extends Settings_Page {

	protected ?string $parent_slug      = 'options-general.php';
	protected string $page_slug         = 'karkinos-gateway';
	protected string $page_title        = 'Karkinos Gateway';
	protected string $menu_title        = 'Karkinos Gateway';
	protected string $capability        = 'manage_options';
	protected string $theme_stylesheet  = Settings_Page::STYLE_WP_ADMIN;

	public function settings_class_name(): string {
		return Gateway_Settings::class;
	}
}
