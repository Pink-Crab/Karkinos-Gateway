<?php
/**
 * Enqueue WP media + picker glue on the Dev Asset edit screen.
 *
 * Modelled on perique-settings-page's wp_enqueue_media() call in
 * Settings_Page::enqueue() (vendor/.../src/Page/Settings_Page.php) — the
 * meta box markup needs WP's media-library JS available before our own
 * dev-asset-media.js can call wp.media().
 *
 * @package Karkinos\Gateway\Admin
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Admin;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Interfaces\Hookable;

class Dev_Asset_Media_Picker implements Hookable {

	private const SCRIPT_HANDLE = 'kg-dev-asset-media';
	private const SCRIPT_PATH   = 'dev-asset-media.js';
	private const STYLE_HANDLE  = 'kg-dev-asset-media';
	private const STYLE_PATH    = 'dev-asset-media.css';

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Resolves the dev_asset CPT slug + plugin URL.
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Register the admin enqueue hook.
	 *
	 * @param Hook_Loader $loader Perique's hook collector.
	 *
	 * @return void
	 */
	public function register( Hook_Loader $loader ): void {
		$loader->action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Conditionally enqueue wp.media + our picker JS on dev_asset edit
	 * screens (post-new.php + post.php where post_type matches).
	 *
	 * @param string $hook Current admin page hook (e.g. 'post.php').
	 *
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( null === $screen ) {
			return;
		}

		$dev_asset_slug = $this->app_config->post_types( 'dev_asset' );
		if ( $screen->post_type !== $dev_asset_slug ) {
			return;
		}

		wp_enqueue_media();

		// App_Config::url() is typed string|array|null — narrow before concat.
		$assets_url_raw = $this->app_config->url( 'assets' );
		$assets_url     = is_string( $assets_url_raw ) ? $assets_url_raw : '';

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			$assets_url . self::SCRIPT_PATH,
			array( 'media-editor' ),
			$this->app_config->version(),
			true
		);

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$assets_url . self::STYLE_PATH,
			array(),
			$this->app_config->version()
		);
	}
}
