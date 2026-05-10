<?php

declare( strict_types=1 );

/**
 * Test fixture: a Menu_Page that is NOT a Settings_Page subclass.
 *
 * Used to verify the GROUPS_PROCESSED listener filters by Settings_Page::class
 * — a plain Menu_Page declared inside a Group must NOT receive the
 * shared+set_settings DI rule.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Admin_Menu\Page\Menu_Page;

class Plain_Menu_Page extends Menu_Page {

	protected string $page_slug  = 'plain_menu_page';
	protected string $menu_title = 'Plain';
	protected string $page_title = 'Plain Page';
}
