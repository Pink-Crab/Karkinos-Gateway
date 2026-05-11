<?php
/**
 * AI Log Tag taxonomy.
 *
 * Free-form (flat) tags attached only to AI Log entries. Distinct from the
 * Project taxonomy (which is shared with Dev Asset) — tags are for ad-hoc
 * classification of individual log entries: "refactor", "incident", etc.
 *
 * Admin-only: no front-end URL, no archive. Exposed via REST so agents can
 * tag entries programmatically.
 *
 * @package Karkinos\Gateway\Taxonomy
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Taxonomy;

use Karkinos\Gateway\PostType\AI_Log;
use PinkCrab\Registerables\Taxonomy;

class AI_Log_Tag extends Taxonomy {

	/** Registered taxonomy slug. */
	public const SLUG = 'ai_log_tag';

	/** @var string Taxonomy slug used in register_taxonomy(). */
	public string $slug = self::SLUG;

	/** @var ?string Singular label shown in admin UI. */
	public ?string $singular = 'AI Log Tag';

	/** @var string Plural label shown in admin UI. */
	public string $plural = 'AI Log Tags';

	/** @var bool Flat (false) like core 'post_tag'. */
	public bool $hierarchical = false;

	/** @var array<int, string> Post types this taxonomy is attached to. */
	public array $object_type = array( AI_Log::SLUG );

	/** @var bool Public-facing visibility (front-end URLs, archives). Off. */
	public bool $public = false;

	/** @var bool Queryable via URL params. Off. */
	public bool $publicly_queryable = false;

	/** @var bool Render the term-management UI in wp-admin. On. */
	public bool $show_ui = true;

	/** @var bool Show entry under parent menu. On. */
	public bool $show_in_menu = true;

	/** @var bool Add a column on the AI Log list table showing assigned tags. On. */
	public bool $show_admin_column = true;

	/** @var bool Expose via the REST API so agents can read/write tags. On. */
	public bool $show_in_rest = true;
}
