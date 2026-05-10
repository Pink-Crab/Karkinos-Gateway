<?php

declare( strict_types=1 );

/**
 * Test fixture: a second Group containing DI_Override_Page (a different Settings_Page).
 *
 * Pairs with Group_With_Settings_Page to exercise scenarios with multiple
 * distinct Settings_Page subclasses across multiple groups.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Admin_Menu\Group\Abstract_Group;

class Group_Override_Page extends Abstract_Group {

	protected $group_title  = 'Group With Override Page';
	protected $primary_page = DI_Override_Page::class;
	protected $pages        = array( DI_Override_Page::class );
}
