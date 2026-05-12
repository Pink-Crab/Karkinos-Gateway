<?php
/**
 * Read-only query endpoints for projects and combined ai-log + dev-asset
 * search.
 *
 * Slugs and meta keys are looked up through App_Config — no hardcoded
 * strings. Auth: WP user with `edit_posts` (typically a WP application
 * password). Same auth strategy as Ingest_Routes — both controllers
 * share identical access requirements.
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\PostType\Dev_Asset;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use PinkCrab\WP_Rest_Schema\Argument\Integer_Type;
use PinkCrab\WP_Rest_Schema\Argument\String_Type;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_Term;

class Query_Routes extends Route_Controller {

	/** @var ?string Shared REST namespace. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	private const DEFAULT_PER_PAGE = 20;
	private const MAX_PER_PAGE     = 100;

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Source of truth for CPT slugs, taxonomy
	 *                               slugs, and post-meta keys.
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Declare the query routes this controller owns.
	 *
	 * @param Route_Factory $factory Pre-configured with the namespace.
	 *
	 * @return array<int, mixed> Route definitions to register.
	 */
	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->get( '/projects', array( $this, 'list_projects' ) )
				->authentication( array( $this, 'check_auth' ) )
				->argument( String_Type::field( 'search' ) )
				->argument(
					Integer_Type::field( 'per_page' )
						->minimum( 1 )
						->maximum( self::MAX_PER_PAGE )
						->default( self::DEFAULT_PER_PAGE )
				)
				->argument( Integer_Type::field( 'page' )->minimum( 1 )->default( 1 ) ),

			$factory->get( '/projects/(?P<slug>[a-z0-9_-]+)', array( $this, 'get_project' ) )
				->authentication( array( $this, 'check_auth' ) ),

			$factory->get( '/search', array( $this, 'search' ) )
				->authentication( array( $this, 'check_auth' ) )
				->argument( String_Type::field( 'project' )->required() )
				->argument( String_Type::field( 'tags' ) )
				->argument(
					String_Type::field( 'match' )
						->expected( 'any', 'all' )
						->default( 'any' )
				)
				->argument(
					Integer_Type::field( 'per_page' )
						->minimum( 1 )
						->maximum( self::MAX_PER_PAGE )
						->default( self::DEFAULT_PER_PAGE )
				)
				->argument( Integer_Type::field( 'page' )->minimum( 1 )->default( 1 ) ),
		);
	}

	/**
	 * Authentication callback shared by every query route. Matches the
	 * Ingest_Routes auth strategy — both controllers require the same
	 * capability.
	 *
	 * @param WP_REST_Request $request Unused; signature required by authentication().
	 *
	 * @return bool True if the current user holds the `edit_posts` capability.
	 */
	public function check_auth( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * GET /projects — paginated list of Project terms with optional search
	 * across name, slug, and github_repo term meta.
	 *
	 * @param WP_REST_Request $request Schema-validated; per_page/page have defaults.
	 *
	 * @return WP_REST_Response 200 with { items, total, page, per_page }.
	 */
	public function list_projects( WP_REST_Request $request ): WP_REST_Response {
		$search   = trim( (string) ( $request->get_param( 'search' ) ?? '' ) );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$matched_ids = $this->find_project_term_ids( $search );
		$total       = count( $matched_ids );

		$paged_ids = array_slice( $matched_ids, ( $page - 1 ) * $per_page, $per_page );
		$terms     = $this->load_terms_in_order( $paged_ids );

		$items = array();
		foreach ( $terms as $term ) {
			$items[] = $this->format_project( $term );
		}

		return new WP_REST_Response(
			array(
				'items'    => $items,
				'total'    => $total,
				'page'     => $page,
				'per_page' => $per_page,
			),
			200
		);
	}

	/**
	 * GET /projects/{slug} — single Project term with counts.
	 *
	 * @param WP_REST_Request $request URL param `slug` is provided by the route regex.
	 *
	 * @return WP_REST_Response|WP_Error 200 with the project, or 404 if missing.
	 */
	public function get_project( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$slug = (string) $request->get_param( 'slug' );
		$term = get_term_by( 'slug', $slug, $this->app_config->taxonomies( 'project' ) );

		if ( ! $term instanceof WP_Term ) {
			return new WP_Error(
				'karkinos_gateway_project_not_found',
				'Project not found.',
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $this->format_project( $term ), 200 );
	}

	/**
	 * GET /search — combined paginated lookup of AI Logs + Dev Assets for a
	 * Project. Tags filter only AI Logs; Dev Assets are returned by Project
	 * alone. Each list paginates independently against the same per_page/page.
	 *
	 * @param WP_REST_Request $request Schema guarantees `project` non-empty,
	 *                                 `match` is one of any|all (default any).
	 *
	 * @return WP_REST_Response|WP_Error 200 with the combined response, or
	 *                                   404 if the project slug doesn't exist.
	 */
	public function search( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$project_slug = (string) $request->get_param( 'project' );
		$project_term = get_term_by( 'slug', $project_slug, $this->app_config->taxonomies( 'project' ) );

		if ( ! $project_term instanceof WP_Term ) {
			return new WP_Error(
				'karkinos_gateway_project_not_found',
				'Project not found.',
				array( 'status' => 404 )
			);
		}

		$tags_param = (string) ( $request->get_param( 'tags' ) ?? '' );
		$tag_slugs  = '' !== $tags_param
			? array_values( array_filter( array_map( 'trim', explode( ',', $tags_param ) ) ) )
			: array();

		$match    = (string) $request->get_param( 'match' );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$ai_logs    = $this->query_ai_logs( $project_term->term_id, $tag_slugs, $match, $per_page, $page );
		$dev_assets = $this->query_dev_assets( $project_term->term_id, $per_page, $page );

		return new WP_REST_Response(
			array(
				'project'    => $this->format_project( $project_term ),
				'ai_logs'    => $ai_logs,
				'dev_assets' => $dev_assets,
			),
			200
		);
	}

	/**
	 * Resolve the set of Project term IDs that match the search string. With
	 * no search, returns all Project term IDs ordered by name. With a search,
	 * unions the LIKE matches across name+slug (via WP_Term_Query) and
	 * github_repo term meta, ordered by name.
	 *
	 * @param string $search Free-form search string; '' means "all projects".
	 *
	 * @return int[] Term IDs ordered by term name (alphabetic).
	 */
	private function find_project_term_ids( string $search ): array {
		$taxonomy = $this->app_config->taxonomies( 'project' );

		if ( '' === $search ) {
			$ids = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);
			return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
		}

		$by_name_slug = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'search'     => $search,
				'hide_empty' => false,
				'fields'     => 'ids',
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$by_repo = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'orderby'    => 'name',
				'order'      => 'ASC',
				'meta_query' => array(
					array(
						'key'     => $this->app_config->term_meta( 'project_github_repo' ),
						'value'   => $search,
						'compare' => 'LIKE',
					),
				),
			)
		);

		$merged = array_unique(
			array_merge(
				is_array( $by_name_slug ) ? array_map( 'intval', $by_name_slug ) : array(),
				is_array( $by_repo ) ? array_map( 'intval', $by_repo ) : array()
			)
		);

		if ( empty( $merged ) ) {
			return array();
		}

		// Re-sort the unioned set by name for a stable response.
		$ordered = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'orderby'    => 'name',
				'order'      => 'ASC',
				'include'    => array_values( $merged ),
			)
		);

		return is_array( $ordered ) ? array_map( 'intval', $ordered ) : array();
	}

	/**
	 * Hydrate term IDs back into WP_Term objects, preserving the input order.
	 * `get_terms` with `include` ignores the order of the include array, so
	 * we sort the loaded terms back into the requested sequence ourselves.
	 *
	 * @param int[] $ids Term IDs in the order they should appear in the response.
	 *
	 * @return WP_Term[] WP_Term objects in the same order as $ids.
	 */
	private function load_terms_in_order( array $ids ): array {
		if ( empty( $ids ) ) {
			return array();
		}

		$loaded = get_terms(
			array(
				'taxonomy'   => $this->app_config->taxonomies( 'project' ),
				'hide_empty' => false,
				'include'    => $ids,
			)
		);

		if ( ! is_array( $loaded ) ) {
			return array();
		}

		$by_id = array();
		foreach ( $loaded as $term ) {
			if ( $term instanceof WP_Term ) {
				$by_id[ (int) $term->term_id ] = $term;
			}
		}

		$ordered = array();
		foreach ( $ids as $id ) {
			if ( isset( $by_id[ (int) $id ] ) ) {
				$ordered[] = $by_id[ (int) $id ];
			}
		}
		return $ordered;
	}

	/**
	 * Build the Project response shape: slug, name, github_repo, and counts
	 * of published AI Logs + Dev Assets attached to this term.
	 *
	 * @param WP_Term $term Project term.
	 *
	 * @return array<string, mixed>
	 */
	private function format_project( WP_Term $term ): array {
		$github_repo = (string) get_term_meta(
			$term->term_id,
			$this->app_config->term_meta( 'project_github_repo' ),
			true
		);

		return array(
			'slug'            => (string) $term->slug,
			'name'            => (string) $term->name,
			'github_repo'     => $github_repo,
			'ai_log_count'    => $this->count_posts_in_term( $this->app_config->post_types( 'ai_log' ), (int) $term->term_id ),
			'dev_asset_count' => $this->count_posts_in_term( $this->app_config->post_types( 'dev_asset' ), (int) $term->term_id ),
		);
	}

	/**
	 * Count published posts of a given type assigned to a Project term.
	 *
	 * Uses WP_Query with posts_per_page=1 + found_posts so the actual rows
	 * aren't fetched — only the count.
	 *
	 * @param string $post_type Resolved post-type slug.
	 * @param int    $term_id   Project term ID.
	 *
	 * @return int Count of matching posts.
	 */
	private function count_posts_in_term( string $post_type, int $term_id ): int {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => $this->app_config->taxonomies( 'project' ),
						'field'    => 'term_id',
						'terms'    => array( $term_id ),
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	/**
	 * Run the AI Log query for /search and format the results.
	 *
	 * @param int      $project_term_id Project term ID (already resolved).
	 * @param string[] $tag_slugs       Tag slugs; empty means "no tag filter".
	 * @param string   $match           any|all — IN vs AND when filtering by tags.
	 * @param int      $per_page        Page size.
	 * @param int      $page            1-based page number.
	 *
	 * @return array<string, mixed> { items, total, page, per_page }
	 */
	private function query_ai_logs( int $project_term_id, array $tag_slugs, string $match, int $per_page, int $page ): array {
		$tax_query = array(
			array(
				'taxonomy' => $this->app_config->taxonomies( 'project' ),
				'field'    => 'term_id',
				'terms'    => array( $project_term_id ),
			),
		);

		if ( ! empty( $tag_slugs ) ) {
			$tax_query[] = array(
				'taxonomy' => $this->app_config->taxonomies( 'ai_log_tag' ),
				'field'    => 'slug',
				'terms'    => $tag_slugs,
				'operator' => 'all' === $match ? 'AND' : 'IN',
			);
			$tax_query['relation'] = 'AND';
		}

		$query = new WP_Query(
			array(
				'post_type'      => $this->app_config->post_types( 'ai_log' ),
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'tax_query'      => $tax_query,
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$items[] = $this->format_ai_log( $post );
			}
		}

		return array(
			'items'    => $items,
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Run the Dev Asset query for /search and format the results.
	 *
	 * @param int $project_term_id Project term ID (already resolved).
	 * @param int $per_page        Page size.
	 * @param int $page            1-based page number.
	 *
	 * @return array<string, mixed> { items, total, page, per_page }
	 */
	private function query_dev_assets( int $project_term_id, int $per_page, int $page ): array {
		$query = new WP_Query(
			array(
				'post_type'      => $this->app_config->post_types( 'dev_asset' ),
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'tax_query'      => array(
					array(
						'taxonomy' => $this->app_config->taxonomies( 'project' ),
						'field'    => 'term_id',
						'terms'    => array( $project_term_id ),
					),
				),
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$items[] = $this->format_dev_asset( $post );
			}
		}

		return array(
			'items'    => $items,
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/**
	 * Format an AI Log post for the /search response.
	 *
	 * @param WP_Post $post AI Log post.
	 *
	 * @return array<string, mixed>
	 */
	private function format_ai_log( WP_Post $post ): array {
		return array(
			'id'        => (int) $post->ID,
			'slug'      => (string) $post->post_name,
			'title'     => (string) $post->post_title,
			'content'   => (string) $post->post_content,
			'date'      => (string) $post->post_date_gmt,
			'edit_link' => (string) get_edit_post_link( $post->ID, 'raw' ),
			'project'   => $this->first_term_summary( (int) $post->ID, $this->app_config->taxonomies( 'project' ) ),
			'tags'      => $this->all_term_summaries( (int) $post->ID, $this->app_config->taxonomies( 'ai_log_tag' ) ),
		);
	}

	/**
	 * Format a Dev Asset post for the /search response. Resolves a unified
	 * `url` field: stored URL meta for type=link, wp_get_attachment_url() for
	 * type=file, null for type=snippet.
	 *
	 * @param WP_Post $post Dev Asset post.
	 *
	 * @return array<string, mixed>
	 */
	private function format_dev_asset( WP_Post $post ): array {
		$type_key      = $this->app_config->post_meta( 'dev_asset_type' );
		$url_key       = $this->app_config->post_meta( 'dev_asset_url' );
		$attachment_id = $this->app_config->post_meta( 'dev_asset_attachment_id' );

		$type = (string) get_post_meta( $post->ID, $type_key, true );
		$url  = $this->resolve_dev_asset_url( (int) $post->ID, $type, $url_key, $attachment_id );

		return array(
			'id'        => (int) $post->ID,
			'slug'      => (string) $post->post_name,
			'title'     => (string) $post->post_title,
			'content'   => (string) $post->post_content,
			'date'      => (string) $post->post_date_gmt,
			'edit_link' => (string) get_edit_post_link( $post->ID, 'raw' ),
			'project'   => $this->first_term_summary( (int) $post->ID, $this->app_config->taxonomies( 'project' ) ),
			'type'      => $type,
			'url'       => $url,
		);
	}

	/**
	 * Resolve the public URL for a Dev Asset based on its type.
	 *
	 * @param int    $post_id          Dev Asset post ID.
	 * @param string $type             One of Dev_Asset::TYPES.
	 * @param string $url_meta_key     Resolved meta key for the link URL.
	 * @param string $attachment_meta  Resolved meta key for the attachment ID.
	 *
	 * @return string|null URL string, or null when no URL applies (snippet, or
	 *                     file with no/invalid attachment).
	 */
	private function resolve_dev_asset_url( int $post_id, string $type, string $url_meta_key, string $attachment_meta ): ?string {
		if ( Dev_Asset::TYPE_LINK === $type ) {
			$stored = (string) get_post_meta( $post_id, $url_meta_key, true );
			return '' !== $stored ? $stored : null;
		}

		if ( Dev_Asset::TYPE_FILE === $type ) {
			$attachment_id = (int) get_post_meta( $post_id, $attachment_meta, true );
			if ( $attachment_id <= 0 ) {
				return null;
			}
			$resolved = wp_get_attachment_url( $attachment_id );
			return is_string( $resolved ) && '' !== $resolved ? $resolved : null;
		}

		return null;
	}

	/**
	 * Return a {slug, name} summary of the first term in $taxonomy attached
	 * to $post_id, or null if no terms are attached.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Resolved taxonomy slug.
	 *
	 * @return array{slug: string, name: string}|null
	 */
	private function first_term_summary( int $post_id, string $taxonomy ): ?array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return null;
		}

		$first = $terms[0];
		if ( ! $first instanceof WP_Term ) {
			return null;
		}

		return array(
			'slug' => (string) $first->slug,
			'name' => (string) $first->name,
		);
	}

	/**
	 * Return {slug, name} summaries for every term in $taxonomy attached to
	 * $post_id.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Resolved taxonomy slug.
	 *
	 * @return array<int, array{slug: string, name: string}>
	 */
	private function all_term_summaries( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$out[] = array(
					'slug' => (string) $term->slug,
					'name' => (string) $term->name,
				);
			}
		}
		return $out;
	}
}
