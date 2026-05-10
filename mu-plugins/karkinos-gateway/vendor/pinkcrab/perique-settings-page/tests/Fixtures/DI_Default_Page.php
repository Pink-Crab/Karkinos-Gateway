<?php

declare( strict_types=1 );

/**
 * Test fixture: Settings_Page paired with DI_Default_Settings.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class DI_Default_Page extends Settings_Page {

	protected string $page_slug  = 'di_default_page';
	protected string $menu_title = 'DI Default';
	protected string $page_title = 'DI Default Page';

	public function settings_class_name(): string {
		return DI_Default_Settings::class;
	}
}
