<?php

declare( strict_types=1 );

/**
 * Test fixture: a Group whose $pages array names DI_Default_Page six times.
 *
 * Stress-tests the first-write-wins dedupe on Group_Page_Registry::record()
 * and ensures the Settings_Page_Module listener is idempotent against the
 * same page class appearing multiple times.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Admin_Menu\Group\Abstract_Group;

class Group_With_Page_Six_Times extends Abstract_Group {

	protected $group_title  = 'Group With Page Six Times';
	protected $primary_page = DI_Default_Page::class;
	protected $pages        = array(
		DI_Default_Page::class,
		DI_Default_Page::class,
		DI_Default_Page::class,
		DI_Default_Page::class,
		DI_Default_Page::class,
		DI_Default_Page::class,
	);
}
