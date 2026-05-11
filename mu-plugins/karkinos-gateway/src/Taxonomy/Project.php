<?php
/**
 * Project taxonomy.
 *
 * Shared classification across AI Log and Dev Asset entries. Each Project
 * term represents one GitHub repo; the repo identifier is stored as term
 * meta (alias `project_github_repo`).
 *
 * Slug + meta-key aliases resolved through App_Config — alias names are
 * literal strings (no class constants).
 *
 * @package Karkinos\Gateway\Taxonomy
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Taxonomy;

use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Registerables\Meta_Data;
use PinkCrab\Registerables\Taxonomy;
use PinkCrab\WP_Rest_Schema\Argument\String_Type;
use PinkCrab\WP_Rest_Schema\Parser\Argument_Parser;

class Project extends Taxonomy {

	public bool $hierarchical       = false;
	public bool $public             = false;
	public bool $publicly_queryable = false;
	public bool $show_admin_column  = true;
	public bool $show_in_rest       = true;

	/**
	 * Resolve slug, post-type bindings, and i18n labels through App_Config.
	 *
	 * @param App_Config $app_config Injected by the DI container.
	 */
	public function __construct( private App_Config $app_config ) {
		$this->slug        = $app_config->taxonomies( 'project' );
		$this->singular    = __( 'Project', 'karkinos-gateway' );
		$this->plural      = __( 'Projects', 'karkinos-gateway' );
		$this->object_type = array(
			$app_config->post_types( 'ai_log' ),
			$app_config->post_types( 'dev_asset' ),
		);
	}

	/**
	 * Register the github_repo term meta.
	 *
	 * @param Meta_Data[] $collection Accumulator passed by the framework.
	 *
	 * @return Meta_Data[] Same collection with the term-meta appended.
	 */
	public function meta_data( array $collection ): array {
		$taxonomy = $this->app_config->taxonomies( 'project' );
		$meta_key = $this->app_config->term_meta( 'project_github_repo' );

		$collection[] = ( new Meta_Data( $meta_key ) )
			->taxonomy( $taxonomy )
			->type( 'string' )
			->single()
			->default( '' )
			->description( __( 'GitHub repo this project maps to. Use "org/repo" form (matches webhook payload repository.full_name).', 'karkinos-gateway' ) )
			->sanitize( 'sanitize_text_field' )
			->rest_schema(
				Argument_Parser::for_meta_data(
					String_Type::field( $meta_key )
						->description( __( 'GitHub org/repo identifier (matches webhook payload repository.full_name).', 'karkinos-gateway' ) )
				)
			);

		return $collection;
	}
}
