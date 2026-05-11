<?php
/**
 * Dev Asset post type.
 *
 * Stores developer/manager artefacts associated with a Project (the GitHub
 * repo). Each entry has a `type` of `file`, `link`, or `snippet`:
 *
 *   - `file`     : an attachment from the Media Library (id stored in meta).
 *   - `link`     : an external URL (stored in meta).
 *   - `snippet`  : free-text content lives in post_content (the editor).
 *
 * Admin-only: no front-end URL, no archive. Exposed via REST so callers can
 * create / read entries programmatically.
 *
 * @package Karkinos\Gateway\PostType
 */

declare(strict_types=1);

namespace Karkinos\Gateway\PostType;

use PinkCrab\Registerables\Meta_Data;
use PinkCrab\Registerables\Post_Type;

class Dev_Asset extends Post_Type {

	/** Registered post type key. */
	public const SLUG = 'dev_asset';

	/** Meta key holding the asset's type (one of TYPE_* below). */
	public const META_TYPE = 'kg_dev_asset_type';

	/** Meta key holding an external URL (used when type = link). */
	public const META_URL = 'kg_dev_asset_url';

	/** Meta key holding a Media Library attachment ID (used when type = file). */
	public const META_ATTACHMENT_ID = 'kg_dev_asset_attachment_id';

	public const TYPE_FILE    = 'file';
	public const TYPE_LINK    = 'link';
	public const TYPE_SNIPPET = 'snippet';

	/** @var list<string> Valid values for the META_TYPE meta. */
	public const TYPES = array( self::TYPE_FILE, self::TYPE_LINK, self::TYPE_SNIPPET );

	/** @var string Post type key passed to register_post_type(). */
	public string $key = self::SLUG;

	/** @var string Singular admin label. */
	public string $singular = 'Dev Asset';

	/** @var string Plural admin label. */
	public string $plural = 'Dev Assets';

	/** @var string WP admin menu icon. */
	public string $dashicon = 'dashicons-portfolio';

	/** @var bool|null Public-facing visibility. Off. */
	public ?bool $public = false;

	/** @var bool|null Queryable via URL params. Off. */
	public ?bool $publicly_queryable = false;

	/** @var bool|null Visible in nav-menu picker. Off. */
	public ?bool $show_in_nav_menus = false;

	/** @var bool|null Show in WP admin bar. Off. */
	public ?bool $show_in_admin_bar = false;

	/** @var bool|null Hide from front-end search. On. */
	public ?bool $exclude_from_search = true;

	/** @var bool|null Hierarchical (parent/child) entries. Off. */
	public ?bool $hierarchical = false;

	/** @var bool|array<string, mixed>|null Front-end archive. Off. */
	public $has_archive = false;

	/** @var bool|array<string, mixed>|null Pretty-permalink rewriting. Off. */
	public $rewrite = false;

	/** @var bool|string Custom URL query_var. Off. */
	public $query_var = false;

	/** @var bool|null Expose via REST + enable Gutenberg. On. */
	public ?bool $show_in_rest = true;

	/** @var array<int, string> Core feature panels enabled for this CPT. */
	public array $supports = array( 'title', 'editor', 'custom-fields' );

	/**
	 * Register the post-meta exposed via REST for this CPT.
	 *
	 * Each meta has its REST schema declared inline so consumers (and the
	 * default REST controller) get proper validation + visibility.
	 *
	 * @param Meta_Data[] $collection Accumulator passed by the framework.
	 *
	 * @return Meta_Data[] Same collection with the dev-asset meta appended.
	 */
	public function meta_data( array $collection ): array {
		$collection[] = ( new Meta_Data( self::META_TYPE ) )
			->post_type( self::SLUG )
			->type( 'string' )
			->single()
			->default( self::TYPE_SNIPPET )
			->sanitize( 'sanitize_text_field' )
			->rest_schema(
				array(
					'type' => 'string',
					'enum' => self::TYPES,
				)
			);

		$collection[] = ( new Meta_Data( self::META_URL ) )
			->post_type( self::SLUG )
			->type( 'string' )
			->single()
			->default( '' )
			->sanitize( 'esc_url_raw' )
			->rest_schema(
				array(
					'type'   => 'string',
					'format' => 'uri',
				)
			);

		$collection[] = ( new Meta_Data( self::META_ATTACHMENT_ID ) )
			->post_type( self::SLUG )
			->type( 'integer' )
			->single()
			->default( 0 )
			->sanitize( 'absint' )
			->rest_schema( array( 'type' => 'integer' ) );

		return $collection;
	}
}
