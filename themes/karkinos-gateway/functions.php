<?php
/**
 * Karkinos Gateway theme functions.
 *
 * @package KarkinosGateway
 */

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'karkinos-gateway',
			get_stylesheet_uri(),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}
);

/**
 * Pick a random background image URL from assets/bg.
 *
 * Returns null when the directory has no images.
 */
function karkinos_gateway_random_bg_url(): ?string {
	$dir = get_theme_file_path( 'assets/bg' );

	if ( ! is_dir( $dir ) ) {
		return null;
	}

	$files = glob( $dir . '/*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE );

	if ( empty( $files ) ) {
		return null;
	}

	$pick = $files[ array_rand( $files ) ];

	return get_theme_file_uri( 'assets/bg/' . basename( $pick ) );
}
