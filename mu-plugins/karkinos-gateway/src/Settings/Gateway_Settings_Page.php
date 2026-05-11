<?php
/**
 * Admin settings page for Karkinos Gateway.
 *
 * Sub-page under Settings → Karkinos Gateway. Form rendering and persistence
 * are handled by Perique's Settings_Page module against the field set
 * defined in Gateway_Settings. set_settings() is wired by a DI rule in
 * config/di.php (the Settings_Page_Module only auto-wires standalone pages
 * via groups; we hand-wire here).
 *
 * @package Karkinos\Gateway\Settings
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Settings;

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Gateway_Settings_Page extends Settings_Page {

	/** @var ?string Sub-page parent (places it under "Settings" in admin menu). */
	protected ?string $parent_slug = 'options-general.php';

	/** @var string URL slug + WP option-screen identifier. */
	protected string $page_slug = 'karkinos-gateway';

	/** @var string Browser tab title. */
	protected string $page_title = 'Karkinos Gateway';

	/** @var string Sidebar menu label. */
	protected string $menu_title = 'Karkinos Gateway';

	/**
	 * Run __() at instantiation so the labels translate without changing the
	 * property defaults (which can't be function calls in PHP).
	 */
	public function __construct() {
		$this->page_title = __( 'Karkinos Gateway', 'karkinos-gateway' );
		$this->menu_title = __( 'Karkinos Gateway', 'karkinos-gateway' );
	}

	/** @var string WP capability gate. */
	protected string $capability = 'manage_options';

	/** @var string Bundled theme name (WP-admin look). */
	protected string $theme_stylesheet = Settings_Page::STYLE_WP_ADMIN;

	/**
	 * Tell the Settings_Page module which Abstract_Settings subclass owns
	 * the field definitions / persistence for this page.
	 *
	 * @return string Fully qualified class name of the settings model.
	 */
	public function settings_class_name(): string {
		return Gateway_Settings::class;
	}
}
