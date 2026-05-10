<?php
/**
 * DI Container rules.
 */

declare(strict_types=1);

use Dice\Dice;
use Karkinos\Gateway\Settings\Gateway_Settings;
use Karkinos\Gateway\Settings\Gateway_Settings_Page;

return array(
	// Share the Settings instance between the Settings_Page and the REST controller
	// so they read/write the same hydrated state.
	Gateway_Settings::class      => array(
		'shared' => true,
	),

	// The Settings_Page_Module only wires set_settings() for pages registered
	// inside an Abstract_Group. Standalone pages (parent_slug = 'options-general.php')
	// don't get re-instantiated through DI after the rule is added, so the rendered
	// instance is missing its settings. Pre-declare the rule here so the page is
	// created with set_settings() already bound.
	Gateway_Settings_Page::class => array(
		'shared' => true,
		'call'   => array(
			array(
				'set_settings',
				array( array( Dice::INSTANCE => Gateway_Settings::class ) ),
			),
		),
	),
);
