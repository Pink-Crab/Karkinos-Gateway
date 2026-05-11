<?php
/**
 * Project taxonomy.
 *
 * Shared classification across AI Log and Dev Asset entries. Each Project
 * term represents one GitHub repo; the repo identifier is stored as term
 * meta (`kg_project_github_repo`, format "org/repo") so we can join
 * incoming webhook deliveries to their owning project.
 *
 * Flat (non-hierarchical) — projects are independent units, no parent /
 * child structure. Admin-only.
 *
 * @package Karkinos\Gateway\Taxonomy
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Taxonomy;

use Karkinos\Gateway\PostType\AI_Log;
use Karkinos\Gateway\PostType\Dev_Asset;
use PinkCrab\Registerables\Meta_Data;
use PinkCrab\Registerables\Taxonomy;

class Project extends Taxonomy {

	/** Registered taxonomy slug. */
	public const SLUG = 'project';

	/** Term meta key storing the GitHub org/repo identifier. */
	public const META_GITHUB_REPO = 'kg_project_github_repo';

	/** @var string Taxonomy slug passed to register_taxonomy(). */
	public string $slug = self::SLUG;

	/** @var ?string Singular admin label. */
	public ?string $singular = 'Project';

	/** @var string Plural admin label. */
	public string $plural = 'Projects';

	/** @var bool Flat — no parent/child terms. */
	public bool $hierarchical = false;

	/** @var array<int, string> Post types this taxonomy attaches to. */
	public array $object_type = array( AI_Log::SLUG, Dev_Asset::SLUG );

	/** @var bool Public-facing visibility. Off. */
	public bool $public = false;

	/** @var bool Queryable from URL params. Off. */
	public bool $publicly_queryable = false;

	/** @var bool Render admin UI for managing terms. On. */
	public bool $show_ui = true;

	/** @var bool Show in side menu. On. */
	public bool $show_in_menu = true;

	/** @var bool Add a column showing assigned terms on the CPT list table. On. */
	public bool $show_admin_column = true;

	/** @var bool Expose terms via REST. On. */
	public bool $show_in_rest = true;

	/**
	 * Register term-meta for this taxonomy. We only need one meta key:
	 * the GitHub repo identifier in "org/repo" form.
	 *
	 * @param Meta_Data[] $collection Accumulator passed by the framework.
	 *
	 * @return Meta_Data[] Same collection with the github_repo meta appended.
	 */
	public function meta_data( array $collection ): array {
		$collection[] = ( new Meta_Data( self::META_GITHUB_REPO ) )
			->taxonomy( self::SLUG )
			->type( 'string' )
			->single()
			->default( '' )
			->description( 'GitHub repo this project maps to. Use the "org/repo" form (matches the repository.full_name field in GitHub webhook payloads).' )
			->sanitize( 'sanitize_text_field' )
			->rest_schema(
				array(
					'type'        => 'string',
					'description' => 'GitHub org/repo identifier.',
				)
			);

		return $collection;
	}
}
