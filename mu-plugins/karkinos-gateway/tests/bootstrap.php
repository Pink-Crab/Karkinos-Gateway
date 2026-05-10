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

// Webhook secret used across the test suite — Webhook_Routes reads this constant
// to verify X-Hub-Signature-256. Tests compute signatures using the same value.
if ( ! defined( 'KARKINOS_GH_WEBHOOK_SECRET' ) ) {
	define( 'KARKINOS_GH_WEBHOOK_SECRET', 'phpunit-webhook-secret' );
}

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require_once dirname( __DIR__ ) . '/karkinos-gateway.php';
	}
);

require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
