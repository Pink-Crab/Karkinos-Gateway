<?php
/**
 * Registration class list — Hookable classes processed during App boot.
 */

declare(strict_types=1);

return array(
	// Settings.
	\Karkinos\Gateway\Settings\Gateway_Settings_Page::class,
	\Karkinos\Gateway\Settings\Ensure_Settings_Not_Autoloaded::class,

	// REST routes.
	\Karkinos\Gateway\Rest\Settings_Routes::class,
	\Karkinos\Gateway\Rest\Webhook_Routes::class,
	\Karkinos\Gateway\Rest\Ingest_Routes::class,
	\Karkinos\Gateway\Rest\Query_Routes::class,

	// Post types.
	\Karkinos\Gateway\PostType\AI_Log::class,
	\Karkinos\Gateway\PostType\Dev_Asset::class,

	// Taxonomies.
	\Karkinos\Gateway\Taxonomy\Project::class,
	\Karkinos\Gateway\Taxonomy\AI_Log_Tag::class,

	// Admin UI.
	\Karkinos\Gateway\Admin\Project_Term_Form::class,
	\Karkinos\Gateway\Admin\Dev_Asset_Media_Picker::class,

	// Dispatch queue.
	\Karkinos\Gateway\Dispatch\Migrations_Runner::class,
);
