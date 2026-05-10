<?php
/**
 * Test WordPress config. Used by WP_PHPUNIT.
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/wordpress/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );

// Suppress the WP 6.8+ E_USER_NOTICE about wp_is_block_theme being called too early.
// @see https://core.trac.wordpress.org/ticket/63086
set_error_handler(
	function ( $errno, $errstr ) {
		if ( $errno === E_USER_NOTICE && strpos( $errstr, 'wp_is_block_theme' ) !== false ) {
			return true;
		}
		return false;
	},
	E_USER_NOTICE
);

// CI branch matches the GitHub Actions workflow standard (ci.md): mysql service
// on 0.0.0.0 with root/crab and DB `pc_core_tests`. Locally we read from .env so
// every Perique repo shares one dev DB.
if ( getenv( 'environment_github' ) ) {
	define( 'DB_NAME', 'pc_core_tests' );
	define( 'DB_USER', 'root' );
	define( 'DB_PASSWORD', 'crab' );
	define( 'DB_HOST', '0.0.0.0' );
	define( 'DB_CHARSET', 'utf8' );
	define( 'DB_COLLATE', '' );
} else {
	define( 'DB_NAME', getenv( 'WP_DB_NAME' ) );
	define( 'DB_USER', getenv( 'WP_DB_USER' ) );
	define( 'DB_PASSWORD', getenv( 'WP_DB_PASS' ) );
	define( 'DB_HOST', getenv( 'WP_DB_HOST' ) );
	define( 'DB_CHARSET', 'utf8' );
	define( 'DB_COLLATE', '' );
}

define( 'AUTH_KEY', 'put your unique phrase here' );
define( 'SECURE_AUTH_KEY', 'put your unique phrase here' );
define( 'LOGGED_IN_KEY', 'put your unique phrase here' );
define( 'NONCE_KEY', 'put your unique phrase here' );
define( 'AUTH_SALT', 'put your unique phrase here' );
define( 'SECURE_AUTH_SALT', 'put your unique phrase here' );
define( 'LOGGED_IN_SALT', 'put your unique phrase here' );
define( 'NONCE_SALT', 'put your unique phrase here' );

$table_prefix = 'wpphpunittests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Karkinos Gateway Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
