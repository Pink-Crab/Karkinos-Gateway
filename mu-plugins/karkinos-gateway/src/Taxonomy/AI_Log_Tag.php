<?php
/**
 * AI Log Tag taxonomy.
 *
 * Free-form (flat) tags attached only to AI Log entries. Slug + labels
 * resolved through App_Config — alias names are literal strings.
 * Admin-only.
 *
 * @package Karkinos\Gateway\Taxonomy
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Taxonomy;

use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Registerables\Taxonomy;

class AI_Log_Tag extends Taxonomy {

	public bool $hierarchical       = false;
	public bool $public             = false;
	public bool $publicly_queryable = false;
	public bool $show_admin_column  = true;
	public bool $show_in_rest       = true;

	/**
	 * Resolve slug, post-type binding, and i18n labels through App_Config.
	 *
	 * @param App_Config $app_config Injected by the DI container.
	 */
	public function __construct( App_Config $app_config ) {
		$this->slug        = $app_config->taxonomies( 'ai_log_tag' );
		$this->singular    = __( 'AI Log Tag', 'karkinos-gateway' );
		$this->plural      = __( 'AI Log Tags', 'karkinos-gateway' );
		$this->object_type = array( $app_config->post_types( 'ai_log' ) );
	}
}
