<?php
/**
 * Force autoload=no on settings the Perique repo writes with autoload=yes.
 *
 * The Settings_Page module's repository unconditionally calls
 * update_option(…, true). For settings we never read on every request (e.g.
 * the local server IP — only consulted on webhook dispatch), autoloading is
 * wasted memory and a small leak surface. This Hookable listens on the
 * added_option / updated_option actions and flips autoload back to 'no'
 * for any option in NON_AUTOLOADED_OPTIONS.
 *
 * @package Karkinos\Gateway\Settings
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Settings;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Interfaces\Hookable;

class Ensure_Settings_Not_Autoloaded implements Hookable {

	/**
	 * Option keys this class polices. Anything outside this list is left at
	 * whatever autoload the repository chose.
	 *
	 * @var list<string>
	 */
	private const NON_AUTOLOADED_OPTIONS = array(
		Gateway_Settings::OPTION_LOCAL_SERVER_IP,
	);

	/**
	 * Register the listener on add + update.
	 *
	 * @param Hook_Loader $loader Perique's hook collector.
	 *
	 * @return void
	 */
	public function register( Hook_Loader $loader ): void {
		$loader->action( 'added_option', array( $this, 'flip_autoload' ) );
		$loader->action( 'updated_option', array( $this, 'flip_autoload' ) );
	}

	/**
	 * Flip autoload to 'no' for the named option if it's in our allow-list.
	 *
	 * No-op for options outside the list. Silently no-op on WP < 6.4 where
	 * wp_set_option_autoload_values() doesn't exist.
	 *
	 * @param string $option Option name as supplied by the WP action.
	 *
	 * @return void
	 */
	public function flip_autoload( string $option ): void {
		if ( ! in_array( $option, self::NON_AUTOLOADED_OPTIONS, true ) ) {
			return;
		}

		if ( function_exists( 'wp_set_option_autoload_values' ) ) {
			wp_set_option_autoload_values( array( $option => 'no' ) );
		}
	}
}
