<?php

declare( strict_types=1 );

/**
 * Test fixture: a Group whose primary_page is a plain Menu_Page (not Settings_Page).
 *
 * Used to verify the GROUPS_PROCESSED listener does not apply Settings_Page DI
 * rules to non-Settings_Page Menu_Pages found inside Groups.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Admin_Menu\Group\Abstract_Group;

class Group_With_Plain_Menu_Page extends Abstract_Group {

	protected $group_title  = 'Group With Plain Page';
	protected $primary_page = Plain_Menu_Page::class;
	protected $pages        = array( Plain_Menu_Page::class );
}
