<?php
/**
 * Dev Asset post type.
 *
 * Stores developer/manager artefacts (file | link | snippet) associated
 * with a Project. Slug + meta-key aliases resolved through App_Config.
 *
 * @package Karkinos\Gateway\PostType
 */

declare(strict_types=1);

namespace Karkinos\Gateway\PostType;

use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Registerables\Meta_Data;
use PinkCrab\Registerables\Post_Type;
use PinkCrab\WP_Rest_Schema\Argument\Integer_Type;
use PinkCrab\WP_Rest_Schema\Argument\String_Type;
use PinkCrab\WP_Rest_Schema\Parser\Argument_Parser;

class Dev_Asset extends Post_Type {

	// Enum values for the type meta. These are the *values* stored in DB,
	// not aliases — they're not looked up via App_Config.
	public const TYPE_FILE    = 'file';
	public const TYPE_LINK    = 'link';
	public const TYPE_SNIPPET = 'snippet';

	/**
 * @var list<string> Valid values for the type meta.
*/
	public const TYPES = array( self::TYPE_FILE, self::TYPE_LINK, self::TYPE_SNIPPET );

	public string $dashicon = 'dashicons-portfolio';

	public ?bool $public              = false;
	public ?bool $publicly_queryable  = false;
	public ?bool $show_in_nav_menus   = false;
	public ?bool $show_in_admin_bar   = false;
	public ?bool $exclude_from_search = true;

	/**
 * No front-end archive. Parent default is true.
*/
	public $has_archive = false;

	/**
 * Disable rewrites. Parent default is null.
*/
	public $rewrite = false;

	/**
 * Core feature panels enabled for this CPT. Parent default is empty array.
*/
	public array $supports = array( 'title', 'editor', 'custom-fields' );

	/**
	 * Resolve slug + i18n labels through App_Config.
	 *
	 * @param App_Config $app_config Injected by the DI container.
	 */
	public function __construct( private App_Config $app_config ) {
		$this->key      = $app_config->post_types( 'dev_asset' );
		$this->singular = __( 'Dev Asset', 'karkinos-gateway' );
		$this->plural   = __( 'Dev Assets', 'karkinos-gateway' );
	}

	/**
	 * Register the post-meta exposed via REST for this CPT.
	 *
	 * @param Meta_Data[] $collection Accumulator passed by the framework.
	 *
	 * @return Meta_Data[] Same collection with the dev-asset meta appended.
	 */
	public function meta_data( array $collection ): array {
		$post_type     = $this->app_config->post_types( 'dev_asset' );
		$type_key      = $this->app_config->post_meta( 'dev_asset_type' );
		$url_key       = $this->app_config->post_meta( 'dev_asset_url' );
		$attachment_id = $this->app_config->post_meta( 'dev_asset_attachment_id' );

		$collection[] = ( new Meta_Data( $type_key ) )
			->post_type( $post_type )
			->type( 'string' )
			->single()
			->default( self::TYPE_SNIPPET )
			->sanitize( 'sanitize_text_field' )
			->rest_schema(
				Argument_Parser::for_meta_data(
					String_Type::field( $type_key )
						->expected( ...self::TYPES )
						->description( __( 'Asset type: file, link, or snippet.', 'karkinos-gateway' ) )
				)
			);

		$collection[] = ( new Meta_Data( $url_key ) )
			->post_type( $post_type )
			->type( 'string' )
			->single()
			->default( '' )
			->sanitize( 'esc_url_raw' )
			->rest_schema(
				Argument_Parser::for_meta_data(
					String_Type::field( $url_key )
						->format( 'uri' )
						->description( __( 'External URL — used when type = link.', 'karkinos-gateway' ) )
				)
			);

		$collection[] = ( new Meta_Data( $attachment_id ) )
			->post_type( $post_type )
			->type( 'integer' )
			->single()
			->default( 0 )
			->sanitize( 'absint' )
			->rest_schema(
				Argument_Parser::for_meta_data(
					Integer_Type::field( $attachment_id )
						->minimum( 0 )
						->description( __( 'Media Library attachment ID — used when type = file.', 'karkinos-gateway' ) )
				)
			);
		dump( $collection );
		return $collection;
	}
}
