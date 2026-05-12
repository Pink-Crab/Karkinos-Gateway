<?php
/**
 * Integration tests for the AI Log and Dev Asset ingest endpoints.
 *
 * Goes end-to-end through the REST server: auth check, schema validation,
 * post creation, term auto-create, meta persistence. Slug + meta keys are
 * looked up through App_Config — never hardcoded.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Rest;

use Karkinos\Gateway\PostType\Dev_Asset;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_Term;
use WP_UnitTestCase;

/**
 * @group integration
 * @group rest
 * @group ingest
 */
class Test_Ingest_Routes extends WP_UnitTestCase {

	private const AI_LOG_ROUTE    = '/karkinos-gateway/v1/ai-log';
	private const DEV_ASSET_ROUTE = '/karkinos-gateway/v1/dev-asset';

	private int $editor_id = 0;
	private App_Config $config;

	public function set_up(): void {
		parent::set_up();
		$this->config    = App::make( App_Config::class );
		$this->editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	public function tear_down(): void {
		wp_set_current_user( 0 );

		// Clean any AI Log / Dev Asset posts created during the test.
		foreach ( array( 'ai_log', 'dev_asset' ) as $alias ) {
			$post_type = $this->config->post_types( $alias );
			foreach ( get_posts( array( 'post_type' => $post_type, 'numberposts' => -1, 'post_status' => 'any' ) ) as $p ) {
				wp_delete_post( $p->ID, true );
			}
		}

		// Clean any terms created via auto-create.
		foreach ( array( 'project', 'ai_log_tag' ) as $alias ) {
			$taxonomy = $this->config->taxonomies( $alias );
			foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
				wp_delete_term( $term->term_id, $taxonomy );
			}
		}

		parent::tear_down();
	}

	/** @testdox POST /ai-log creates an AI Log post and returns 201 with id + slug + edit_link */
	public function test_create_ai_log_minimum_payload_returns_201(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post(
			self::AI_LOG_ROUTE,
			array(
				'title'   => 'Refactor of storage layer',
				'content' => 'AI rewrote Foo and Bar.',
			)
		);

		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertGreaterThan( 0, $data['id'] );
		$this->assertSame( 'refactor-of-storage-layer', $data['slug'] );

		$post = get_post( $data['id'] );
		$this->assertInstanceOf( WP_Post::class, $post );
		$this->assertSame( $this->config->post_types( 'ai_log' ), $post->post_type );
		$this->assertSame( 'Refactor of storage layer', $post->post_title );
		$this->assertSame( 'AI rewrote Foo and Bar.', $post->post_content );
	}

	/** @testdox POST /ai-log auto-creates a missing project term and assigns it */
	public function test_create_ai_log_auto_creates_project_term(): void {
		wp_set_current_user( $this->editor_id );

		$project_tax = $this->config->taxonomies( 'project' );
		$this->assertFalse( get_term_by( 'slug', 'brand-new-project', $project_tax ) );

		$response = $this->post(
			self::AI_LOG_ROUTE,
			array(
				'title'   => 'Initial setup',
				'project' => 'brand-new-project',
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'brand-new-project', $response->get_data()['project'] );

		$term = get_term_by( 'slug', 'brand-new-project', $project_tax );
		$this->assertInstanceOf( WP_Term::class, $term );

		$post_id = $response->get_data()['id'];
		$this->assertTrue( has_term( $term->term_id, $project_tax, $post_id ) );
	}

	/** @testdox POST /ai-log auto-creates multiple tags and assigns them all */
	public function test_create_ai_log_auto_creates_tags(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post(
			self::AI_LOG_ROUTE,
			array(
				'title' => 'Tagged entry',
				'tags'  => array( 'refactor', 'storage', 'critical' ),
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$this->assertEqualsCanonicalizing(
			array( 'refactor', 'storage', 'critical' ),
			$response->get_data()['tags']
		);

		$tag_tax = $this->config->taxonomies( 'ai_log_tag' );
		$post_id = $response->get_data()['id'];
		foreach ( array( 'refactor', 'storage', 'critical' ) as $slug ) {
			$term = get_term_by( 'slug', $slug, $tag_tax );
			$this->assertInstanceOf( WP_Term::class, $term, "Tag '$slug' should exist" );
			$this->assertTrue( has_term( $term->term_id, $tag_tax, $post_id ) );
		}
	}

	/** @testdox POST /ai-log without auth returns 401 or 403 */
	public function test_create_ai_log_without_auth_returns_unauthorized(): void {
		wp_set_current_user( 0 );

		$response = $this->post( self::AI_LOG_ROUTE, array( 'title' => 'Anon' ) );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/** @testdox POST /ai-log without title returns 400 (schema rejects) */
	public function test_create_ai_log_without_title_returns_400(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post( self::AI_LOG_ROUTE, array( 'content' => 'No title' ) );

		$this->assertSame( 400, $response->get_status() );
	}

	/** @testdox POST /dev-asset with type=snippet stores content + type meta */
	public function test_create_dev_asset_snippet(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post(
			self::DEV_ASSET_ROUTE,
			array(
				'title'   => 'Auth handler snippet',
				'content' => 'function handle_auth() {}',
				'type'    => Dev_Asset::TYPE_SNIPPET,
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( Dev_Asset::TYPE_SNIPPET, $data['type'] );

		$post_id  = $data['id'];
		$type_key = $this->config->post_meta( 'dev_asset_type' );
		$this->assertSame( Dev_Asset::TYPE_SNIPPET, get_post_meta( $post_id, $type_key, true ) );
		$this->assertSame( 'function handle_auth() {}', get_post( $post_id )->post_content );
	}

	/** @testdox POST /dev-asset with type=link stores the url meta */
	public function test_create_dev_asset_link(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post(
			self::DEV_ASSET_ROUTE,
			array(
				'title' => 'Design doc',
				'type'  => Dev_Asset::TYPE_LINK,
				'url'   => 'https://example.com/doc.pdf',
			)
		);

		$this->assertSame( 201, $response->get_status() );

		$post_id  = $response->get_data()['id'];
		$type_key = $this->config->post_meta( 'dev_asset_type' );
		$url_key  = $this->config->post_meta( 'dev_asset_url' );
		$this->assertSame( Dev_Asset::TYPE_LINK, get_post_meta( $post_id, $type_key, true ) );
		$this->assertSame( 'https://example.com/doc.pdf', get_post_meta( $post_id, $url_key, true ) );
	}

	/** @testdox POST /dev-asset with an invalid type returns 400 */
	public function test_create_dev_asset_rejects_unknown_type(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post(
			self::DEV_ASSET_ROUTE,
			array(
				'title' => 'Weird thing',
				'type'  => 'something_else',
			)
		);

		$this->assertSame( 400, $response->get_status() );
	}

	/** @testdox POST /dev-asset rejects an attachment_id that points at a non-attachment post */
	public function test_create_dev_asset_rejects_non_attachment_id(): void {
		wp_set_current_user( $this->editor_id );

		// A regular post (not an attachment) — must not be linkable.
		$regular_post_id = self::factory()->post->create();

		$response = $this->post(
			self::DEV_ASSET_ROUTE,
			array(
				'title'         => 'Sneaky asset',
				'type'          => Dev_Asset::TYPE_FILE,
				'attachment_id' => $regular_post_id,
			)
		);

		$this->assertSame( 400, $response->get_status() );

		// And no Dev Asset row should have been created (pre-insert validation).
		$assets = get_posts(
			array(
				'post_type'   => $this->config->post_types( 'dev_asset' ),
				'numberposts' => -1,
				'post_status' => 'any',
			)
		);
		$this->assertEmpty( $assets, 'A failed validation must not orphan a Dev Asset row.' );
	}

	/** @testdox POST /dev-asset accepts a real attachment_id and stores it */
	public function test_create_dev_asset_accepts_real_attachment_id(): void {
		wp_set_current_user( $this->editor_id );

		$attachment_id = self::factory()->attachment->create_object(
			'real-asset.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_title'     => 'Real asset',
			)
		);

		$response = $this->post(
			self::DEV_ASSET_ROUTE,
			array(
				'title'         => 'Linked asset',
				'type'          => Dev_Asset::TYPE_FILE,
				'attachment_id' => $attachment_id,
			)
		);

		$this->assertSame( 201, $response->get_status() );

		$post_id          = $response->get_data()['id'];
		$attachment_meta  = $this->config->post_meta( 'dev_asset_attachment_id' );
		$this->assertSame( $attachment_id, (int) get_post_meta( $post_id, $attachment_meta, true ) );
	}

	/** @testdox POST /dev-asset auto-creates a missing project term */
	public function test_create_dev_asset_auto_creates_project(): void {
		wp_set_current_user( $this->editor_id );

		$response = $this->post(
			self::DEV_ASSET_ROUTE,
			array(
				'title'   => 'Project file',
				'type'    => Dev_Asset::TYPE_SNIPPET,
				'project' => 'another-project',
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'another-project', $response->get_data()['project'] );

		$term = get_term_by( 'slug', 'another-project', $this->config->taxonomies( 'project' ) );
		$this->assertInstanceOf( WP_Term::class, $term );
	}

	/**
	 * Build + dispatch a JSON POST through the REST server.
	 *
	 * @param string               $route REST route (e.g. '/karkinos-gateway/v1/ai-log').
	 * @param array<string, mixed> $body  JSON body.
	 *
	 * @return WP_REST_Response Dispatched response.
	 */
	private function post( string $route, array $body ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $body ) );

		return rest_do_request( $request );
	}
}
