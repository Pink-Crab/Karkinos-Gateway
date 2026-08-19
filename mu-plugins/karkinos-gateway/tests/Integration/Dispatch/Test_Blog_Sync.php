<?php
/**
 * Integration tests for the blog stubs-section sync.
 *
 * Drives a queued blog job through the real Dispatch_Worker with all HTTP
 * stubbed via pre_http_request, routed by URL: GitHub list endpoints, the
 * post GET (context=edit) and the update POST.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Dispatch;

use Karkinos\Gateway\Dispatch\Blog_Sync;
use Karkinos\Gateway\Dispatch\Dispatch_Job;
use Karkinos\Gateway\Dispatch\Dispatch_Queue;
use Karkinos\Gateway\Dispatch\Dispatch_Worker;
use Karkinos\Gateway\Migration\Create_Dispatch_Jobs_Table;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_Error;
use WP_UnitTestCase;

/**
 * @group integration
 * @group dispatch
 */
class Test_Blog_Sync extends WP_UnitTestCase {

	private const POST_CONTENT = 'INTRO <!-- stub-forge:start -->stale<!-- stub-forge:end --> OUTRO';

	private Dispatch_Worker $worker;
	private Dispatch_Queue $queue;

	/** @var array<string, mixed>|null Captured args of the blog update POST. */
	private ?array $update_args = null;

	/** @var string|null Captured URL of the blog update POST. */
	private ?string $update_url = null;

	public function set_up(): void {
		parent::set_up();
		$this->worker      = App::make( Dispatch_Worker::class );
		$this->queue       = App::make( Dispatch_Queue::class );
		$this->update_args = null;
		$this->update_url  = null;
		$this->truncate_table();
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_http_request' );
		$this->truncate_table();
		parent::tear_down();
	}

	/** @testdox a blog job rebuilds the marker region and marks the job dispatched */
	public function test_success_rebuilds_section_and_marks_dispatched(): void {
		$id = $this->enqueue_blog_job();
		$this->stub_http();

		$summary = $this->worker->run();

		$this->assertSame( 1, $summary['sent'] );
		$job = $this->queue->find( $id );
		$this->assertTrue( $job->is_dispatched() );
		$this->assertSame( 200, $job->response_status );

		$this->assertNotNull( $this->update_args, 'Expected an update POST to the blog.' );

		// Endpoint discovered from the post page's REST link, not assumed.
		$this->assertSame(
			KARKINOS_BLOG_URL . '/wp-json/wp/v2/software/' . KARKINOS_BLOG_POST_ID,
			$this->update_url
		);
		$this->assertSame(
			'Basic ' . base64_encode( KARKINOS_BLOG_USER . ':' . KARKINOS_BLOG_PASS ),
			$this->update_args['headers']['Authorization']
		);

		$body    = json_decode( (string) $this->update_args['body'], true );
		$content = (string) $body['content'];

		// Everything outside the markers is untouched.
		$this->assertStringStartsWith( 'INTRO ' . Blog_Sync::START_MARKER, $content );
		$this->assertStringEndsWith( Blog_Sync::END_MARKER . ' OUTRO', $content );
		$this->assertStringNotContainsString( 'stale', $content );

		// Repos alphabetical, non-stubs repo excluded. rtmedia's heading links
		// to the upstream plugin repo (from its README); jetpack-crm's README
		// has no upstream line so its heading stays plain.
		$jetpack = (int) strpos( $content, '<h3 class="wp-block-heading release-title">jetpack-crm</h3>' );
		$rtmedia = (int) strpos( $content, '<h3 class="wp-block-heading release-title"><a href="https://github.com/rtCamp/rtMedia">rtmedia</a></h3>' );
		$this->assertGreaterThan( 0, $jetpack );
		$this->assertGreaterThan( $jetpack, $rtmedia );
		$this->assertStringNotContainsString( 'perique-framework', $content );

		// Emitted as Gutenberg block markup, not bare HTML.
		$this->assertStringContainsString( '<!-- wp:heading {"level":3,"className":"release-title"} -->', $content );
		$this->assertStringContainsString( '<!-- wp:list {"className":"release-versions"} -->', $content );
		$this->assertStringContainsString( '<ul class="wp-block-list release-versions"><!-- wp:list-item -->', $content );
		$this->assertStringContainsString( '<!-- /wp:list-item --></ul>', $content );

		// Versions newest first — 4.10.0 beats 4.7.9 (version sort, not string).
		$v_new = (int) strpos( $content, 'releases/tag/4.10.0' );
		$v_old = (int) strpos( $content, 'releases/tag/4.7.9' );
		$this->assertGreaterThan( 0, $v_new );
		$this->assertGreaterThan( $v_new, $v_old );

		$this->assertStringContainsString( '<code>composer require --dev pinkcrab/rtmedia_stubs:4.10.0</code>', $content );
		$this->assertStringContainsString(
			'https://github.com/Pink-Crab/rtmedia_stubs/releases/tag/4.7.9',
			$content
		);
	}

	/** @testdox missing markers permanently reject the job with the synthesised 422 */
	public function test_missing_markers_is_permanent_reject(): void {
		$id = $this->enqueue_blog_job();
		$this->stub_http( 'No markers in here.' );

		$summary = $this->worker->run();

		$this->assertSame( 1, $summary['rejected'] );
		$job = $this->queue->find( $id );
		$this->assertTrue( $job->is_dispatched() );
		$this->assertSame( 422, $job->response_status );
		$this->assertSame( 'rejected', $job->error );
		$this->assertNull( $this->update_args, 'No update must be sent without markers.' );
	}

	/** @testdox a post page with no REST link falls back to the posts endpoint */
	public function test_no_rest_link_falls_back_to_posts(): void {
		$this->enqueue_blog_job();
		$this->stub_http( self::POST_CONTENT, false );

		$summary = $this->worker->run();

		$this->assertSame( 1, $summary['sent'] );
		$this->assertSame(
			KARKINOS_BLOG_URL . '/wp-json/wp/v2/posts/' . KARKINOS_BLOG_POST_ID,
			$this->update_url
		);
	}

	/** @testdox a GitHub failure is transient: the job is left pending */
	public function test_github_failure_is_transient(): void {
		$id = $this->enqueue_blog_job();

		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) {
				if ( str_contains( (string) $url, 'api.github.com' ) ) {
					return new WP_Error( 'http_request_failed', 'boom' );
				}
				// The discovery fetch of the post's page succeeds.
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => '<html><head></head><body></body></html>',
					'headers'  => array(),
				);
			},
			10,
			3
		);

		$summary = $this->worker->run();

		$this->assertSame( 'transient', $summary['stopped'] );
		$this->assertFalse( $this->queue->find( $id )->is_dispatched() );
	}

	/**
	 * Queue one blog job addressed at the test blog endpoint.
	 *
	 * @return int Job id.
	 */
	private function enqueue_blog_job(): int {
		return $this->queue->enqueue(
			array(
				'payload'     => '{"repo":"Pink-Crab/rtmedia_stubs","tag":"4.10.0"}',
				'kind'        => Dispatch_Job::KIND_BLOG,
				'source'      => 'github',
				'event'       => 'release',
				'delivery_id' => 'blog-d1',
				'target_url'  => KARKINOS_BLOG_URL . '/wp-json/wp/v2/posts/' . KARKINOS_BLOG_POST_ID,
			)
		);
	}

	/**
	 * Stub GitHub and the blog by URL. The update POST is captured.
	 *
	 * @param string $post_content Raw content the post GET returns.
	 * @param bool   $rest_link    Whether the post's page carries its REST link.
	 *
	 * @return void
	 */
	private function stub_http( string $post_content = self::POST_CONTENT, bool $rest_link = true ): void {
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( $post_content, $rest_link ) {
				$url = (string) $url;

				// The ?p= discovery fetch of the post's own page.
				if ( str_contains( $url, '?p=' ) ) {
					$link = $rest_link
						? '<link rel="alternate" type="application/json" href="' . KARKINOS_BLOG_URL . '/wp-json/wp/v2/software/' . KARKINOS_BLOG_POST_ID . '" />'
						: '';
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => '<html><head>' . $link . '</head><body></body></html>',
						'headers'  => array(),
					);
				}

				if ( str_contains( $url, 'api.github.com/orgs/' ) ) {
					return $this->json_response(
						array(
							array( 'name' => 'rtmedia_stubs' ),
							array( 'name' => 'perique-framework' ),
							array( 'name' => 'jetpack-crm_stubs' ),
						)
					);
				}

				if ( str_contains( $url, '/rtmedia_stubs/tags' ) ) {
					return $this->json_response(
						array(
							array( 'name' => '4.7.9' ),
							array( 'name' => '4.10.0' ),
						)
					);
				}

				if ( str_contains( $url, '/jetpack-crm_stubs/tags' ) ) {
					return $this->json_response( array( array( 'name' => '1.0.0' ) ) );
				}

				// Stubs repo READMEs: rtmedia carries the upstream link,
				// jetpack-crm has none.
				if ( str_contains( $url, '/rtmedia_stubs/readme' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => "# rtMedia Stubs\n\n- Original plugin: <https://github.com/rtCamp/rtMedia>\n",
						'headers'  => array(),
					);
				}
				if ( str_contains( $url, '/jetpack-crm_stubs/readme' ) ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => "# Jetpack CRM Stubs\n",
						'headers'  => array(),
					);
				}

				// Blog: GET fetches the post, POST is the update.
				if ( 'POST' === ( $args['method'] ?? '' ) ) {
					$this->update_args = $args;
					$this->update_url  = $url;
					return $this->json_response( array( 'id' => KARKINOS_BLOG_POST_ID ) );
				}

				return $this->json_response( array( 'content' => array( 'raw' => $post_content ) ) );
			},
			10,
			3
		);
	}

	/**
	 * A 200 JSON response in wp_remote_* shape.
	 *
	 * @param array<int|string, mixed> $data Body to encode.
	 *
	 * @return array<string, mixed>
	 */
	private function json_response( array $data ): array {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => (string) wp_json_encode( $data ),
			'headers'  => array(),
		);
	}

	private function truncate_table(): void {
		global $wpdb;
		$table = App::make( App_Config::class )->db_tables( Create_Dispatch_Jobs_Table::TABLE_ALIAS );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Truncate of a test-owned table.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
}
