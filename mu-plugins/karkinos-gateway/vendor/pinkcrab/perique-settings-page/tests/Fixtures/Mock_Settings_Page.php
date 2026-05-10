<?php

declare( strict_types=1 );

/**
 * Minimal Settings_Page subclass for tests.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Mock_Settings_Page extends Settings_Page {

	protected string $page_slug  = 'mock-page';
	protected string $menu_title = 'Mock';
	protected string $page_title = 'Mock Page';

	public function settings_class_name(): string {
		return Mock_Abstract_Settings::class;
	}
}
