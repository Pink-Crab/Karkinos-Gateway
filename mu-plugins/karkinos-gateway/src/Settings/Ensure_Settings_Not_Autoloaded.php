<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Settings;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Interfaces\Hookable;

class Ensure_Settings_Not_Autoloaded implements Hookable {

	private const NON_AUTOLOADED_OPTIONS = array(
		Gateway_Settings::OPTION_LOCAL_SERVER_IP,
	);

	public function register( Hook_Loader $loader ): void {
		$loader->action( 'added_option', array( $this, 'flip_autoload' ) );
		$loader->action( 'updated_option', array( $this, 'flip_autoload' ) );
	}

	/**
	 * Force autoload = no on the gateway's persisted options.
	 *
	 * Perique's settings repository writes with autoload=yes; this restores
	 * the intended autoload=no immediately after every write.
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
