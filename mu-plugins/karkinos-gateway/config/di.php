<?php
/**
 * DI Container rules.
 */

declare(strict_types=1);

use Dice\Dice;
use Karkinos\Gateway\Filesystem\File_Manager;
use Karkinos\Gateway\Filesystem\WP_File_Manager;
use Karkinos\Gateway\Settings\Gateway_Settings;
use Karkinos\Gateway\Settings\Gateway_Settings_Page;

return array(
	// Bind the filesystem boundary to its WP_Filesystem-backed implementation.
	// Tests construct Webhook_Logger directly with an in-memory fake instead
	// of going through DI — this rule is for production only.
	File_Manager::class          => array(
		'instanceOf' => WP_File_Manager::class,
	),

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
