<?php

declare( strict_types=1 );

/**
 * Test fixture: an Abstract_Group whose primary_page is a Settings_Page subclass.
 *
 * Used to verify that a Settings_Page registered ONLY inside a Group's $pages
 * (never reaching Settings_Page_Middleware::process()) still receives DI
 * wiring via the Hooks::GROUPS_PROCESSED listener in Settings_Page_Module.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Admin_Menu\Group\Abstract_Group;

class Group_With_Settings_Page extends Abstract_Group {

	protected $group_title  = 'Group With Settings';
	protected $primary_page = DI_Default_Page::class;
	protected $pages        = array( DI_Default_Page::class );
}
