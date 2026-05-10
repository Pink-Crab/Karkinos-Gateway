<?php

declare( strict_types=1 );

/**
 * Type-safe casting helpers.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Util;

class Cast {

	/**
	 * Casts a mixed value to string if it is scalar or Stringable.
	 *
	 * Returns the fallback if the value cannot be safely cast.
	 *
	 * @param mixed       $value
	 * @param string|null $fallback
	 * @return string|null
	 */
	public static function to_string( mixed $value, ?string $fallback = null ): ?string {
		if ( is_scalar( $value ) || $value instanceof \Stringable ) {
			return (string) $value;
		}
		return $fallback;
	}

	/**
	 * Casts a mixed value to int if it is numeric.
	 *
	 * Returns the fallback if the value cannot be safely cast.
	 *
	 * @param mixed $value
	 * @param int   $fallback
	 * @return int
	 */
	public static function to_int( mixed $value, int $fallback = 0 ): int {
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}
		return $fallback;
	}

	/**
	 * Safely escapes a mixed value for use in an HTML attribute.
	 *
	 * @param mixed  $value
	 * @param string $fallback
	 * @return string
	 */
	public static function esc_attr( mixed $value, string $fallback = '' ): string {
		return \esc_attr( self::to_string( $value, $fallback ) ?? $fallback );
	}
}
