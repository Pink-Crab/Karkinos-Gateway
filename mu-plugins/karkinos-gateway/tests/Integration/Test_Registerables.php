<?php
/**
 * Integration test confirming all CPTs + taxonomies are registered after
 * App boot, using slugs that match App_Config aliases.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration;

use Gin0115\WPUnit_Helpers\WP\Meta_Data_Inspector;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_UnitTestCase;

/**
 * @group integration
 * @group registerables
 */
class Test_Registerables extends WP_UnitTestCase {

	private App_Config $config;

	public function set_up(): void {
		parent::set_up();
		$this->config = App::make( App_Config::class );
	}

	/** @testdox AI Log CPT is registered with the slug from App_Config */
	public function test_ai_log_post_type_is_registered(): void {
		$slug = $this->config->post_types( 'ai_log' );

		$this->assertTrue( post_type_exists( $slug ) );

		$pt = get_post_type_object( $slug );
		$this->assertNotNull( $pt );
		$this->assertFalse( $pt->public );
		$this->assertFalse( $pt->publicly_queryable );
		$this->assertTrue( $pt->show_in_rest );
	}

	/** @testdox Dev Asset CPT is registered with the slug from App_Config */
	public function test_dev_asset_post_type_is_registered(): void {
		$slug = $this->config->post_types( 'dev_asset' );

		$this->assertTrue( post_type_exists( $slug ) );

		$pt = get_post_type_object( $slug );
		$this->assertNotNull( $pt );
		$this->assertFalse( $pt->public );
		$this->assertTrue( $pt->show_in_rest );
	}

	/** @testdox Project taxonomy is registered and bound to both CPTs */
	public function test_project_taxonomy_is_registered(): void {
		$tax = $this->config->taxonomies( 'project' );

		$this->assertTrue( taxonomy_exists( $tax ) );

		$taxonomy = get_taxonomy( $tax );
		$this->assertNotNull( $taxonomy );
		$this->assertFalse( $taxonomy->hierarchical );

		$ai_log_slug    = $this->config->post_types( 'ai_log' );
		$dev_asset_slug = $this->config->post_types( 'dev_asset' );

		$this->assertContains( $ai_log_slug, $taxonomy->object_type );
		$this->assertContains( $dev_asset_slug, $taxonomy->object_type );
	}

	/** @testdox AI Log Tag taxonomy is registered and bound only to AI Log */
	public function test_ai_log_tag_taxonomy_is_registered(): void {
		$tax = $this->config->taxonomies( 'ai_log_tag' );

		$this->assertTrue( taxonomy_exists( $tax ) );

		$taxonomy = get_taxonomy( $tax );
		$this->assertNotNull( $taxonomy );

		$ai_log_slug = $this->config->post_types( 'ai_log' );
		$this->assertContains( $ai_log_slug, $taxonomy->object_type );
		$this->assertNotContains(
			$this->config->post_types( 'dev_asset' ),
			$taxonomy->object_type
		);
	}

	/** @testdox Dev Asset's three post-meta keys are registered for the CPT */
	public function test_dev_asset_meta_keys_registered_with_rest(): void {
		$inspector = Meta_Data_Inspector::initialise();
		$post_type = $this->config->post_types( 'dev_asset' );

		foreach ( array( 'dev_asset_type', 'dev_asset_url', 'dev_asset_attachment_id' ) as $alias ) {
			$key  = $this->config->post_meta( $alias );
			$meta = $inspector->find_post_meta( $post_type, $key );

			$this->assertNotNull(
				$meta,
				"Expected meta '$key' to be registered for post type '$post_type'."
			);
		}
	}

	/** @testdox Project taxonomy's github_repo term meta is registered */
	public function test_project_github_repo_term_meta_registered(): void {
		$inspector = Meta_Data_Inspector::initialise();
		$taxonomy  = $this->config->taxonomies( 'project' );
		$key       = $this->config->term_meta( 'project_github_repo' );

		$meta = $inspector->find_term_meta( $taxonomy, $key );

		$this->assertNotNull(
			$meta,
			"Expected term meta '$key' to be registered for taxonomy '$taxonomy'."
		);
	}
}
