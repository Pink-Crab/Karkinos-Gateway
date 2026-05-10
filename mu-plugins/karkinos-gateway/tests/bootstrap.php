<?php
/**
 * PHPUnit bootstrap file.
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

try {
	$dotenv = Dotenv\Dotenv::createUnsafeImmutable( __DIR__ );
	$dotenv->load();
} catch ( \Throwable $th ) {
	// No .env present — fine in CI.
}

define( 'TEST_WP_ROOT', dirname( __DIR__ ) . '/wordpress' );

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require_once dirname( __DIR__ ) . '/karkinos-gateway.php';
	}
);

require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
