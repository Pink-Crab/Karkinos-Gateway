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

// Dispatch-side config. The Karkinos URLs are overridden to fixed test hosts so
// Forward_Target resolves without a saved local_server_ip; the actual HTTP is
// stubbed via the pre_http_request filter, so the cert path only needs to exist
// (is_readable), never to be a real cert.
if ( ! defined( 'KARKINOS_DISPATCH_SECRET' ) ) {
	define( 'KARKINOS_DISPATCH_SECRET', 'phpunit-dispatch-secret' );
}
if ( ! defined( 'KARKINOS_DISPATCH_CA' ) ) {
	define( 'KARKINOS_DISPATCH_CA', __DIR__ . '/fixtures/karkinos-ca.pem' );
}
if ( ! defined( 'KARKINOS_DISPATCH_URL' ) ) {
	define( 'KARKINOS_DISPATCH_URL', 'https://karkinos.test/dispatch' );
}
if ( ! defined( 'KARKINOS_CAPACITY_URL' ) ) {
	define( 'KARKINOS_CAPACITY_URL', 'https://karkinos.test/dispatch/capacity' );
}
if ( ! defined( 'KARKINOS_GH_API_TOKEN' ) ) {
	define( 'KARKINOS_GH_API_TOKEN', 'phpunit-gh-token' );
}

// Blog target for the stub-release sync. Fixed test host — HTTP is stubbed via
// pre_http_request, nothing is ever sent.
if ( ! defined( 'KARKINOS_BLOG_URL' ) ) {
	define( 'KARKINOS_BLOG_URL', 'https://blog.example' );
}
if ( ! defined( 'KARKINOS_BLOG_USER' ) ) {
	define( 'KARKINOS_BLOG_USER', 'phpunit' );
}
if ( ! defined( 'KARKINOS_BLOG_PASS' ) ) {
	define( 'KARKINOS_BLOG_PASS', 'phpunit-app-pass' );
}
if ( ! defined( 'KARKINOS_BLOG_POST_ID' ) ) {
	define( 'KARKINOS_BLOG_POST_ID', 6731 );
}

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require_once dirname( __DIR__ ) . '/karkinos-gateway.php';
	}
);

require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
