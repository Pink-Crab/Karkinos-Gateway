<?php
/**
 * Lightweight ingest endpoints for AI Log and Dev Asset post types.
 *
 * Two POST endpoints under `karkinos-gateway/v1/`:
 *
 *   - /ai-log     — create an AI Log entry, optionally tagged with a project + tag list.
 *   - /dev-asset  — create a Dev Asset entry (file|link|snippet), optionally project-tagged.
 *
 * Auth: WP user with `edit_posts` (typically via a WP application password
 * issued per agent). Term references are by slug; unknown project / tag
 * slugs are auto-created the first time they're seen.
 *
 * @package Karkinos\Gateway\Rest
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Rest;

use Karkinos\Gateway\PostType\AI_Log;
use Karkinos\Gateway\PostType\Dev_Asset;
use Karkinos\Gateway\Taxonomy\AI_Log_Tag;
use Karkinos\Gateway\Taxonomy\Project;
use PinkCrab\Route\Route_Controller;
use PinkCrab\Route\Route_Factory;
use PinkCrab\WP_Rest_Schema\Argument\Array_Type;
use PinkCrab\WP_Rest_Schema\Argument\Integer_Type;
use PinkCrab\WP_Rest_Schema\Argument\String_Type;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Ingest_Routes extends Route_Controller {

	/** @var ?string Shared REST namespace. */
	protected ?string $namespace = 'karkinos-gateway/v1';

	/**
	 * Declare the ingest routes this controller owns.
	 *
	 * @param Route_Factory $factory Pre-configured with the namespace.
	 *
	 * @return array<int, mixed> Route definitions to register.
	 */
	protected function define_routes( Route_Factory $factory ): array {
		return array(
			$factory->post( '/ai-log', array( $this, 'create_ai_log' ) )
				->authentication( array( $this, 'check_auth' ) )
				->argument( String_Type::field( 'title' )->required() )
				->argument( String_Type::field( 'content' ) )
				->argument( String_Type::field( 'project' ) )
				->argument( Array_Type::field( 'tags' )->string_item() ),

			$factory->post( '/dev-asset', array( $this, 'create_dev_asset' ) )
				->authentication( array( $this, 'check_auth' ) )
				->argument( String_Type::field( 'title' )->required() )
				->argument( String_Type::field( 'content' ) )
				->argument(
					String_Type::field( 'type' )
						->required()
						->expected( ...Dev_Asset::TYPES )
				)
				->argument( String_Type::field( 'url' )->format( 'uri' ) )
				->argument( Integer_Type::field( 'attachment_id' )->minimum( 0 ) )
				->argument( String_Type::field( 'project' ) ),
		);
	}

	/**
	 * Authentication callback shared by both ingest routes.
	 *
	 * @param WP_REST_Request $request Unused; signature required by authentication().
	 *
	 * @return bool True if the current user holds the `edit_posts` capability.
	 */
	public function check_auth( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Create an AI Log entry from validated request input.
	 *
	 * @param WP_REST_Request $request Schema guarantees `title` is non-empty;
	 *                                 `content`, `project`, `tags` are optional.
	 *
	 * @return WP_REST_Response|WP_Error 201 with the created post summary, or
	 *                                   the WP_Error from wp_insert_post on failure.
	 */
	public function create_ai_log( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = wp_insert_post(
			array(
				'post_type'    => AI_Log::SLUG,
				'post_status'  => 'publish',
				'post_title'   => (string) $request->get_param( 'title' ),
				'post_content' => (string) ( $request->get_param( 'content' ) ?? '' ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$project_slug  = $request->get_param( 'project' );
		$assigned_proj = is_string( $project_slug ) && '' !== $project_slug
			? $this->assign_terms( (int) $post_id, Project::SLUG, array( $project_slug ) )
			: array();

		$tags          = (array) ( $request->get_param( 'tags' ) ?? array() );
		$assigned_tags = ! empty( $tags )
			? $this->assign_terms( (int) $post_id, AI_Log_Tag::SLUG, $tags )
			: array();

		return $this->created_response(
			(int) $post_id,
			array(
				'project' => $assigned_proj[0] ?? null,
				'tags'    => $assigned_tags,
			)
		);
	}

	/**
	 * Create a Dev Asset entry from validated request input.
	 *
	 * @param WP_REST_Request $request Schema guarantees `title` non-empty and
	 *                                 `type` one of Dev_Asset::TYPES. Optional:
	 *                                 `content`, `url`, `attachment_id`, `project`.
	 *
	 * @return WP_REST_Response|WP_Error 201 with the created post summary, or
	 *                                   the WP_Error from wp_insert_post on failure.
	 */
	public function create_dev_asset( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$post_id = wp_insert_post(
			array(
				'post_type'    => Dev_Asset::SLUG,
				'post_status'  => 'publish',
				'post_title'   => (string) $request->get_param( 'title' ),
				'post_content' => (string) ( $request->get_param( 'content' ) ?? '' ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$type = (string) $request->get_param( 'type' );
		update_post_meta( (int) $post_id, Dev_Asset::META_TYPE, $type );

		$url = $request->get_param( 'url' );
		if ( is_string( $url ) && '' !== $url ) {
			update_post_meta( (int) $post_id, Dev_Asset::META_URL, esc_url_raw( $url ) );
		}

		$attachment_id = $request->get_param( 'attachment_id' );
		if ( is_numeric( $attachment_id ) && (int) $attachment_id > 0 ) {
			update_post_meta( (int) $post_id, Dev_Asset::META_ATTACHMENT_ID, (int) $attachment_id );
		}

		$project_slug  = $request->get_param( 'project' );
		$assigned_proj = is_string( $project_slug ) && '' !== $project_slug
			? $this->assign_terms( (int) $post_id, Project::SLUG, array( $project_slug ) )
			: array();

		return $this->created_response(
			(int) $post_id,
			array(
				'type'    => $type,
				'project' => $assigned_proj[0] ?? null,
			)
		);
	}

	/**
	 * Resolve term slugs to IDs, creating any that don't exist, then assign
	 * the resulting set to the post.
	 *
	 * @param int      $post_id  Target post ID.
	 * @param string   $taxonomy Taxonomy slug to look up / insert into.
	 * @param string[] $slugs    Slug strings; non-strings + empty values skipped.
	 *
	 * @return string[] Slugs actually assigned (existing + newly-created), in input order.
	 */
	private function assign_terms( int $post_id, string $taxonomy, array $slugs ): array {
		$term_ids    = array();
		$slugs_taken = array();

		foreach ( $slugs as $slug ) {
			if ( ! is_string( $slug ) || '' === $slug ) {
				continue;
			}

			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term instanceof \WP_Term ) {
				$term_ids[]    = (int) $term->term_id;
				$slugs_taken[] = $slug;
				continue;
			}

			$created = wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );
			if ( is_wp_error( $created ) ) {
				continue;
			}

			$term_ids[]    = (int) $created['term_id'];
			$slugs_taken[] = $slug;
		}

		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, $taxonomy );
		}

		return $slugs_taken;
	}

	/**
	 * Build the 201 Created response with id + slug + edit_link plus any
	 * caller-supplied extras (project, tags, type, etc.).
	 *
	 * @param int                  $post_id Just-created post ID.
	 * @param array<string, mixed> $extras  Extra keys to merge into the response body.
	 *
	 * @return WP_REST_Response 201 response with the post summary.
	 */
	private function created_response( int $post_id, array $extras = array() ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'id'        => $post_id,
				'slug'      => (string) get_post_field( 'post_name', $post_id ),
				'edit_link' => (string) get_edit_post_link( $post_id, 'raw' ),
			) + $extras,
			201
		);
	}
}
