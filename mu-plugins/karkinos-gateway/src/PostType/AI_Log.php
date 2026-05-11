<?php
/**
 * AI Log post type.
 *
 * Records actions taken by AI agents (refactors, deployments, reviews, etc.)
 * Each entry is associated with a Project (the GitHub repo) and zero-or-more
 * AI Log Tag terms.
 *
 * Admin-only: no front-end URL, no archive, excluded from search. Exposed
 * via REST so agents can write entries programmatically.
 *
 * @package Karkinos\Gateway\PostType
 */

declare(strict_types=1);

namespace Karkinos\Gateway\PostType;

use PinkCrab\Registerables\Post_Type;

class AI_Log extends Post_Type {

	/** Registered post type key. */
	public const SLUG = 'ai_log';

	/** @var string Post type key passed to register_post_type(). */
	public string $key = self::SLUG;

	/** @var string Singular label shown in admin UI. */
	public string $singular = 'AI Log';

	/** @var string Plural label shown in admin UI. */
	public string $plural = 'AI Logs';

	/** @var string WP admin menu icon (dashicon class). */
	public string $dashicon = 'dashicons-format-aside';

	/** @var bool|null Public-facing visibility (front-end URL + archive). Off. */
	public ?bool $public = false;

	/** @var bool|null Queryable via URL params on the front end. Off. */
	public ?bool $publicly_queryable = false;

	/** @var bool|null Visible in nav-menu picker. Off (admin-only). */
	public ?bool $show_in_nav_menus = false;

	/** @var bool|null Show in WP admin bar. Off. */
	public ?bool $show_in_admin_bar = false;

	/** @var bool|null Hide from front-end search results. On. */
	public ?bool $exclude_from_search = true;

	/** @var bool|null Hierarchical (parent/child) entries. Off. */
	public ?bool $hierarchical = false;

	/** @var bool|array<string, mixed>|null Front-end archive page. Off. */
	public $has_archive = false;

	/** @var bool|array<string, mixed>|null Pretty-permalink rewriting. Off. */
	public $rewrite = false;

	/** @var bool|string Custom query_var for URL queries. Off. */
	public $query_var = false;

	/** @var bool|null Expose via REST + enable Gutenberg. On. */
	public ?bool $show_in_rest = true;

	/** @var array<int, string> Core feature panels enabled for this CPT. */
	public array $supports = array( 'title', 'editor', 'custom-fields' );
}
