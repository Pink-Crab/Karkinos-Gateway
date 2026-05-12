<?php
/**
 * AI Log post type.
 *
 * Records actions taken by AI agents. Slug + labels resolved through
 * App_Config — alias is the literal 'ai_log' string.
 *
 * Admin-only: no front-end URL, no archive, excluded from search. Exposed
 * via REST so agents can write entries programmatically.
 *
 * @package Karkinos\Gateway\PostType
 */

declare(strict_types=1);

namespace Karkinos\Gateway\PostType;

use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Registerables\Post_Type;

class AI_Log extends Post_Type {

	public string $dashicon = 'dashicons-format-aside';

	public ?bool $public              = false;
	public ?bool $publicly_queryable  = false;
	public ?bool $show_in_nav_menus   = false;
	public ?bool $show_in_admin_bar   = false;
	public ?bool $exclude_from_search = true;

	/**
	 * Map meta caps to capability_type's primitive caps so admins can edit.
	 * Without this, current_user_can('edit_post', $id) fails for non-public
	 * CPTs and you get "Sorry, you are not allowed to edit this post".
	 */
	public ?bool $map_meta_cap = true;

	/** No front-end archive. Parent default is true. */
	public $has_archive = false;

	/** Disable rewrites. Parent default is null. */
	public $rewrite = false;

	/** Core feature panels enabled for this CPT. Parent default is empty array. */
	public array $supports = array( 'title', 'editor', 'custom-fields' );

	/**
	 * Resolve slug + i18n labels through App_Config.
	 *
	 * @param App_Config $app_config Injected by the DI container.
	 */
	public function __construct( App_Config $app_config ) {
		$this->key      = $app_config->post_types( 'ai_log' );
		$this->singular = __( 'AI Log', 'karkinos-gateway' );
		$this->plural   = __( 'AI Logs', 'karkinos-gateway' );
	}
}
