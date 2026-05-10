<?php
/**
 * E2E fixture: an Abstract_Group containing a mix of Settings_Pages and a plain Menu_Page.
 *
 * Pages declared inside the group:
 *   - Test_Discovery_Page_B: primary, Settings_Page, NOT in registration_classes (Group-only path).
 *   - Test_Discovery_Page_C: Settings_Page, ALSO in registration_classes (duplicate path).
 *   - Test_Discovery_Plain_Page: non-Settings Menu_Page (filtered out by GROUPS_PROCESSED listener).
 */

declare( strict_types=1 );

use PinkCrab\Perique_Admin_Menu\Group\Abstract_Group;

class Test_Discovery_Group extends Abstract_Group {

	protected $group_title  = 'Discovery Group';
	protected $primary_page = Test_Discovery_Page_B::class;
	protected $pages        = array(
		Test_Discovery_Page_B::class,
		Test_Discovery_Page_C::class,
		Test_Discovery_Plain_Page::class,
	);
}
