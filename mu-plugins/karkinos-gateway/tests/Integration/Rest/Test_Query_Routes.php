<?php
/**
 * Integration tests for the read-only query endpoints.
 *
 * Covers /projects (list + single), and /search (combined ai-log +
 * dev-asset lookup). Slug + meta keys are resolved through App_Config —
 * never hardcoded.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use Karkinos\Gateway\PostType\Dev_Asset;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group query
 */
class Test_Query_Routes extends WP_UnitTestCase {

	private const PROJECTS_ROUTE = '/karkinos-gateway/v1/projects';
	private const SEARCH_ROUTE   = '/karkinos-gateway/v1/search';

	private int $editor_id = 0;
	private App_Config $config;

	public function set_up(): void {
		parent::set_up();
		$this->config    = App::make( App_Config::class );
		$this->editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	public function tear_down(): void {
		wp_set_current_user( 0 );

		foreach ( array( 'ai_log', 'dev_asset' ) as $alias ) {
			$post_type = $this->config->post_types( $alias );
			foreach ( get_posts( array( 'post_type' => $post_type, 'numberposts' => -1, 'post_status' => 'any' ) ) as $p ) {
				wp_delete_post( $p->ID, true );
			}
		}

		foreach ( array( 'project', 'ai_log_tag' ) as $alias ) {
			$taxonomy = $this->config->taxonomies( $alias );
			foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}

		parent::tear_down();
	}

	/** @testdox GET /projects without auth returns 401 or 403 */
	public function test_list_projects_without_auth_returns_unauthorized(): void {
		wp_set_current_user( 0 );

		$response = $this->get( self::PROJECTS_ROUTE );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/** @testdox GET /projects with no projects returns an empty list */
	public function test_list_projects_empty(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->get( self::PROJECTS_ROUTE );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( array(), $data['items'] );
		$this->assertSame( 0, $data['total'] );
		$this->assertSame( 1, $data['page'] );
		$this->assertSame( 20, $data['per_page'] );
	}

	/** @testdox GET /projects returns all projects ordered by name */
	public function test_list_projects_returns_all_ordered_by_name(): void {
		wp_set_current_user( $this->editor_id );

		$this->create_project( 'zulu', 'Zulu Project' );
		$this->create_project( 'alpha', 'Alpha Project' );
		$this->create_project( 'mike', 'Mike Project' );

		$response = $this->get( self::PROJECTS_ROUTE );

		$this->assertSame( 200, $response->get_status() );
		$slugs = array_column( $response->get_data()['items'], 'slug' );
		$this->assertSame( array( 'alpha', 'mike', 'zulu' ), $slugs );
		$this->assertSame( 3, $response->get_data()['total'] );
	}

	/** @testdox GET /projects?search matches by name */
	public function test_list_projects_search_matches_name(): void {
		wp_set_current_user( $this->editor_id );

		$this->create_project( 'one', 'Distinctive Apple Project' );
		$this->create_project( 'two', 'Banana Project' );

		$response = $this->get( self::PROJECTS_ROUTE, array( 'search' => 'Apple' ) );

		$this->assertSame( 200, $response->get_status() );
		$items = $response->get_data()['items'];
		$this->assertCount( 1, $items );
		$this->assertSame( 'one', $items[0]['slug'] );
	}

	/** @testdox GET /projects?search matches by slug */
	public function test_list_projects_search_matches_slug(): void {
		wp_set_current_user( $this->editor_id );

		$this->create_project( 'unique-slug-marker', 'Some Name' );
		$this->create_project( 'other-project', 'Other Name' );

		$response = $this->get( self::PROJECTS_ROUTE, array( 'search' => 'unique-slug-marker' ) );

		$this->assertSame( 200, $response->get_status() );
		$items = $response->get_data()['items'];
		$this->assertCount( 1, $items );
		$this->assertSame( 'unique-slug-marker', $items[0]['slug'] );
	}

	/** @testdox GET /projects?search matches by github_repo term meta */
	public function test_list_projects_search_matches_github_repo(): void {
		wp_set_current_user( $this->editor_id );

		$repo_alias  = 'project_github_repo';
		$meta_key    = $this->config->term_meta( $repo_alias );

		$id_a = $this->create_project( 'proj-a', 'Project A' );
		update_term_meta( $id_a, $meta_key, 'pinkcrab/something-cool' );

		$id_b = $this->create_project( 'proj-b', 'Project B' );
		update_term_meta( $id_b, $meta_key, 'other-org/elsewhere' );

		$response = $this->get( self::PROJECTS_ROUTE, array( 'search' => 'pinkcrab' ) );

		$this->assertSame( 200, $response->get_status() );
		$slugs = array_column( $response->get_data()['items'], 'slug' );
		$this->assertSame( array( 'proj-a' ), $slugs );
	}

	/** @testdox GET /projects paginates via per_page + page */
	public function test_list_projects_pagination(): void {
		wp_set_current_user( $this->editor_id );

		// 5 projects, slugs ordered alphabetically by name to keep ordering predictable.
		foreach ( array( 'aa', 'bb', 'cc', 'dd', 'ee' ) as $slug ) {
			$this->create_project( $slug, strtoupper( $slug ) . ' name' );
		}

		$response = $this->get( self::PROJECTS_ROUTE, array( 'per_page' => 2, 'page' => 2 ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 5, $data['total'] );
		$this->assertSame( 2, $data['per_page'] );
		$this->assertSame( 2, $data['page'] );
		$this->assertSame( array( 'cc', 'dd' ), array_column( $data['items'], 'slug' ) );
	}

	/** @testdox GET /projects returns ai_log_count + dev_asset_count for each project */
	public function test_list_projects_includes_counts(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'counted', 'Counted Project' );

		$this->create_ai_log( 'Log 1', $project_id );
		$this->create_ai_log( 'Log 2', $project_id );

		$this->create_dev_asset( 'Asset 1', Dev_Asset::TYPE_SNIPPET, $project_id );

		$response = $this->get( self::PROJECTS_ROUTE );

		$this->assertSame( 200, $response->get_status() );
		$items = $response->get_data()['items'];
		$this->assertSame( 'counted', $items[0]['slug'] );
		$this->assertSame( 2, $items[0]['ai_log_count'] );
		$this->assertSame( 1, $items[0]['dev_asset_count'] );
	}

	/** @testdox GET /projects/{slug} returns the project with counts */
	public function test_get_project_returns_single_project(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'one-off', 'One Off' );
		update_term_meta(
			$project_id,
			$this->config->term_meta( 'project_github_repo' ),
			'me/one-off'
		);
		$this->create_ai_log( 'A log', $project_id );

		$response = $this->get( self::PROJECTS_ROUTE . '/one-off' );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'one-off', $data['slug'] );
		$this->assertSame( 'One Off', $data['name'] );
		$this->assertSame( 'me/one-off', $data['github_repo'] );
		$this->assertSame( 1, $data['ai_log_count'] );
		$this->assertSame( 0, $data['dev_asset_count'] );
	}

	/** @testdox GET /projects/{slug} returns 404 when the slug is unknown */
	public function test_get_project_not_found_returns_404(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->get( self::PROJECTS_ROUTE . '/no-such-thing' );

		$this->assertSame( 404, $response->get_status() );
	}

	/** @testdox GET /search without auth returns 401 or 403 */
	public function test_search_without_auth_returns_unauthorized(): void {
		wp_set_current_user( 0 );

		$response = $this->get( self::SEARCH_ROUTE, array( 'project' => 'anything' ) );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/** @testdox GET /search without project param returns 400 */
	public function test_search_without_project_returns_400(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->get( self::SEARCH_ROUTE );

		$this->assertSame( 400, $response->get_status() );
	}

	/** @testdox GET /search with unknown project slug returns 404 */
	public function test_search_unknown_project_returns_404(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->get( self::SEARCH_ROUTE, array( 'project' => 'never-existed' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	/** @testdox GET /search returns ai_logs and dev_assets for the project */
	public function test_search_returns_ai_logs_and_dev_assets(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'mixed', 'Mixed Project' );
		$this->create_ai_log( 'First log', $project_id );
		$this->create_ai_log( 'Second log', $project_id );
		$this->create_dev_asset( 'A snippet', Dev_Asset::TYPE_SNIPPET, $project_id );

		// Noise — another project's data must not appear in the response.
		$other_project = $this->create_project( 'other', 'Other' );
		$this->create_ai_log( 'Should be excluded', $other_project );

		$response = $this->get( self::SEARCH_ROUTE, array( 'project' => 'mixed' ) );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'mixed', $data['project']['slug'] );

		$this->assertSame( 2, $data['ai_logs']['total'] );
		$titles = array_column( $data['ai_logs']['items'], 'title' );
		$this->assertEqualsCanonicalizing( array( 'First log', 'Second log' ), $titles );

		$this->assertSame( 1, $data['dev_assets']['total'] );
		$this->assertSame( 'A snippet', $data['dev_assets']['items'][0]['title'] );
	}

	/** @testdox GET /search filters ai_logs by tags (match=any, default) */
	public function test_search_tags_match_any(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'tagged', 'Tagged Project' );
		$log_a      = $this->create_ai_log( 'Log A', $project_id, array( 'refactor' ) );
		$log_b      = $this->create_ai_log( 'Log B', $project_id, array( 'storage' ) );
		$this->create_ai_log( 'Log C (no matching tag)', $project_id, array( 'unrelated' ) );

		$response = $this->get(
			self::SEARCH_ROUTE,
			array( 'project' => 'tagged', 'tags' => 'refactor,storage' )
		);

		$this->assertSame( 200, $response->get_status() );
		$ids = array_column( $response->get_data()['ai_logs']['items'], 'id' );
		$this->assertEqualsCanonicalizing( array( $log_a, $log_b ), $ids );
	}

	/** @testdox GET /search with match=all returns ai_logs tagged with every tag */
	public function test_search_tags_match_all(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'allmatch', 'AllMatch Project' );
		$both       = $this->create_ai_log( 'Has both tags', $project_id, array( 'refactor', 'storage' ) );
		$this->create_ai_log( 'Has only refactor', $project_id, array( 'refactor' ) );
		$this->create_ai_log( 'Has only storage', $project_id, array( 'storage' ) );

		$response = $this->get(
			self::SEARCH_ROUTE,
			array(
				'project' => 'allmatch',
				'tags'    => 'refactor,storage',
				'match'   => 'all',
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$ids = array_column( $response->get_data()['ai_logs']['items'], 'id' );
		$this->assertSame( array( $both ), $ids );
	}

	/** @testdox GET /search returns resolved file URL for type=file dev_assets */
	public function test_search_dev_asset_file_returns_resolved_url(): void {
		wp_set_current_user( $this->editor_id );

		$project_id    = $this->create_project( 'files', 'Files Project' );
		$attachment_id = self::factory()->attachment->create_object(
			'test-asset.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_title'     => 'Test asset',
			)
		);

		$asset_id = $this->create_dev_asset( 'A file asset', Dev_Asset::TYPE_FILE, $project_id );
		update_post_meta(
			$asset_id,
			$this->config->post_meta( 'dev_asset_attachment_id' ),
			$attachment_id
		);

		$response = $this->get( self::SEARCH_ROUTE, array( 'project' => 'files' ) );

		$this->assertSame( 200, $response->get_status() );
		$items = $response->get_data()['dev_assets']['items'];
		$this->assertCount( 1, $items );
		$this->assertSame( Dev_Asset::TYPE_FILE, $items[0]['type'] );
		$this->assertSame( wp_get_attachment_url( $attachment_id ), $items[0]['url'] );
	}

	/** @testdox GET /search returns stored URL meta for type=link dev_assets */
	public function test_search_dev_asset_link_returns_stored_url(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'links', 'Links Project' );
		$asset_id   = $this->create_dev_asset( 'A link asset', Dev_Asset::TYPE_LINK, $project_id );
		update_post_meta(
			$asset_id,
			$this->config->post_meta( 'dev_asset_url' ),
			'https://example.com/design-doc'
		);

		$response = $this->get( self::SEARCH_ROUTE, array( 'project' => 'links' ) );

		$this->assertSame( 200, $response->get_status() );
		$items = $response->get_data()['dev_assets']['items'];
		$this->assertCount( 1, $items );
		$this->assertSame( Dev_Asset::TYPE_LINK, $items[0]['type'] );
		$this->assertSame( 'https://example.com/design-doc', $items[0]['url'] );
	}

	/** @testdox GET /search returns null URL for type=snippet dev_assets */
	public function test_search_dev_asset_snippet_returns_null_url(): void {
		wp_set_current_user( $this->editor_id );

		$project_id = $this->create_project( 'snippets', 'Snippets Project' );
		$this->create_dev_asset( 'A snippet', Dev_Asset::TYPE_SNIPPET, $project_id );

		$response = $this->get( self::SEARCH_ROUTE, array( 'project' => 'snippets' ) );

		$this->assertSame( 200, $response->get_status() );
		$items = $response->get_data()['dev_assets']['items'];
		$this->assertCount( 1, $items );
		$this->assertSame( Dev_Asset::TYPE_SNIPPET, $items[0]['type'] );
		$this->assertNull( $items[0]['url'] );
	}

	/**
	 * Build + dispatch a GET request through the REST server.
	 *
	 * @param string               $route REST route (e.g. '/karkinos-gateway/v1/projects').
	 * @param array<string, mixed> $query Query string params.
	 *
	 * @return WP_REST_Response Dispatched response.
	 */
	private function get( string $route, array $query = array() ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', $route );
		foreach ( $query as $k => $v ) {
			$request->set_param( $k, $v );
		}
		return rest_do_request( $request );
	}

	/**
	 * Create a Project term and return its term_id.
	 *
	 * @param string $slug Term slug.
	 * @param string $name Display name.
	 *
	 * @return int term_id.
	 */
	private function create_project( string $slug, string $name ): int {
		$result = wp_insert_term(
			$name,
			$this->config->taxonomies( 'project' ),
			array( 'slug' => $slug )
		);
		$this->assertNotInstanceOf( \WP_Error::class, $result );
		return (int) $result['term_id'];
	}

	/**
	 * Create an AI Log post, optionally attaching it to a Project and one or
	 * more AI Log Tags.
	 *
	 * @param string   $title      Post title.
	 * @param int|null $project_id Project term_id (or null to skip assignment).
	 * @param string[] $tag_slugs  AI log tag slugs to create + attach.
	 *
	 * @return int Post ID.
	 */
	private function create_ai_log( string $title, ?int $project_id = null, array $tag_slugs = array() ): int {
		$post_id = wp_insert_post(
			array(
				'post_type'   => $this->config->post_types( 'ai_log' ),
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);
		$this->assertNotInstanceOf( \WP_Error::class, $post_id );

		if ( null !== $project_id ) {
			wp_set_object_terms( (int) $post_id, array( $project_id ), $this->config->taxonomies( 'project' ) );
		}

		if ( ! empty( $tag_slugs ) ) {
			$tag_tax  = $this->config->taxonomies( 'ai_log_tag' );
			$term_ids = array();
			foreach ( $tag_slugs as $slug ) {
				$existing = get_term_by( 'slug', $slug, $tag_tax );
				if ( $existing ) {
					$term_ids[] = (int) $existing->term_id;
					continue;
				}
				$created    = wp_insert_term( $slug, $tag_tax, array( 'slug' => $slug ) );
				$term_ids[] = (int) $created['term_id'];
			}
			wp_set_object_terms( (int) $post_id, $term_ids, $tag_tax );
		}

		return (int) $post_id;
	}

	/**
	 * Create a Dev Asset post, optionally attaching it to a Project. Caller
	 * sets type-specific meta (url / attachment_id) after creation.
	 *
	 * @param string   $title      Post title.
	 * @param string   $type       One of Dev_Asset::TYPES.
	 * @param int|null $project_id Project term_id (or null to skip assignment).
	 *
	 * @return int Post ID.
	 */
	private function create_dev_asset( string $title, string $type, ?int $project_id = null ): int {
		$post_id = wp_insert_post(
			array(
				'post_type'   => $this->config->post_types( 'dev_asset' ),
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);
		$this->assertNotInstanceOf( \WP_Error::class, $post_id );

		update_post_meta( (int) $post_id, $this->config->post_meta( 'dev_asset_type' ), $type );

		if ( null !== $project_id ) {
			wp_set_object_terms( (int) $post_id, array( $project_id ), $this->config->taxonomies( 'project' ) );
		}

		return (int) $post_id;
	}
}
