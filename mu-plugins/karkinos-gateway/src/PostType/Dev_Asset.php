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
use PinkCrab\Registerables\Meta_Box;
use PinkCrab\Registerables\Meta_Data;
use PinkCrab\Registerables\Post_Type;
use PinkCrab\WP_Rest_Schema\Argument\Integer_Type;
use PinkCrab\WP_Rest_Schema\Argument\String_Type;
use PinkCrab\WP_Rest_Schema\Parser\Argument_Parser;
use WP_Post;

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

	private const META_BOX_KEY     = 'kg_dev_asset_fields';
	private const NONCE_ACTION     = 'kg_dev_asset_save';
	private const NONCE_FIELD_NAME = 'kg_dev_asset_nonce';

	public string $dashicon = 'dashicons-portfolio';

	public ?bool $public              = false;
	public ?bool $publicly_queryable  = false;
	public ?bool $show_in_nav_menus   = false;
	public ?bool $show_in_admin_bar   = false;
	public ?bool $exclude_from_search = true;

	/**
	 * Map meta caps (edit_post, delete_post, read_post) to the post-type's
	 * primitive caps. Without this, admins get "Sorry, you are not allowed
	 * to edit this post" because the literal `edit_post` cap doesn't exist
	 * on any role.
	 */
	public ?bool $map_meta_cap = true;

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
		return $collection;
	}

	/**
	 * Register the meta-edit Meta_Box for the post-edit screen.
	 *
	 * The view template (views/meta-box/dev-asset-fields.php) uses Form
	 * Components for each field. The save_post_{cpt} action wires
	 * save_meta() to fire only for this CPT.
	 *
	 * @param Meta_Box[] $meta_boxes Accumulator passed by the framework.
	 *
	 * @return Meta_Box[] Same collection with this CPT's meta box appended.
	 */
	public function meta_boxes( array $meta_boxes ): array {
		$config    = $this->app_config;
		$post_type = $this->key;
		$types     = self::TYPES;
		$nonce_a   = self::NONCE_ACTION;
		$nonce_n   = self::NONCE_FIELD_NAME;

		$meta_boxes[] = Meta_Box::normal( self::META_BOX_KEY )
			->label( __( 'Asset Details', 'karkinos-gateway' ) )
			->view_template( 'meta-box/dev-asset-fields' )
			->view_data_filter(
				static function ( WP_Post $post, array $vars ) use ( $config, $types, $nonce_a, $nonce_n ): array {
					return array_merge(
						$vars,
						array(
							'post'         => $post,
							'config'       => $config,
							'types'        => $types,
							'nonce_action' => $nonce_a,
							'nonce_field'  => $nonce_n,
						)
					);
				}
			)
			->add_action( 'save_post_' . $post_type, array( $this, 'save_meta' ), 10, 1 );

		return $meta_boxes;
	}

	/**
	 * Persist the meta-box fields.
	 *
	 * Verifies nonce + edit_post cap. `type` is whitelisted against TYPES.
	 * URL is esc_url_raw'd. Attachment ID is absint'd.
	 *
	 * @param int $post_id Post being saved.
	 *
	 * @return void
	 */
	public function save_meta( int $post_id ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_FIELD_NAME ] )
			? sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD_NAME ] ) )
			: '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$type_key      = $this->app_config->post_meta( 'dev_asset_type' );
		$url_key       = $this->app_config->post_meta( 'dev_asset_url' );
		$attachment_id = $this->app_config->post_meta( 'dev_asset_attachment_id' );

		$type = isset( $_POST[ $type_key ] )
			? sanitize_text_field( wp_unslash( (string) $_POST[ $type_key ] ) )
			: '';
		if ( in_array( $type, self::TYPES, true ) ) {
			update_post_meta( $post_id, $type_key, $type );
		}

		if ( isset( $_POST[ $url_key ] ) ) {
			update_post_meta(
				$post_id,
				$url_key,
				esc_url_raw( wp_unslash( (string) $_POST[ $url_key ] ) )
			);
		}

		if ( isset( $_POST[ $attachment_id ] ) ) {
			update_post_meta(
				$post_id,
				$attachment_id,
				absint( wp_unslash( (string) $_POST[ $attachment_id ] ) )
			);
		}
	}
}
