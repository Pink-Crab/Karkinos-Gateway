<?php
/**
 * Certificate-identity TLS pinning for outbound calls to Karkinos.
 *
 * The home server answers on a rotating ISP IP with a self-signed cert, so a
 * hostname/IP SAN match is impossible — but the cert itself is a stable
 * identity. The dispatcher passes `sslverify => true` + `sslcertificates`
 * (the pinned PEM) on its requests, which makes cURL verify the peer chains
 * to that exact cert (VERIFYPEER + CAINFO). This hook then disables ONLY the
 * hostname match (VERIFYHOST = 0) for those marked requests, so the gateway
 * accepts the home server's cert on whatever IP it currently answers — and
 * nothing else.
 *
 * Marked requests carry `_karkinos_pinned => true` in their args; the hook is
 * a strict no-op for every other outbound request.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Interfaces\Hookable;

class Karkinos_TLS_Pinning implements Hookable {

	/** Request arg flag the dispatcher sets to opt a request into pinning. */
	public const PIN_ARG = '_karkinos_pinned';

	/**
	 * Register the cURL-handle hook.
	 *
	 * @param Hook_Loader $loader Perique's hook collector.
	 *
	 * @return void
	 */
	public function register( Hook_Loader $loader ): void {
		$loader->action( 'http_api_curl', array( $this, 'apply' ), 10, 3 );
	}

	/**
	 * Disable hostname verification on pinned requests only.
	 *
	 * VERIFYPEER and CAINFO are already set by WP from the request's
	 * `sslverify`/`sslcertificates` args, so peer-identity verification stays
	 * fully on; we only relax the host match the rotating IP can't satisfy.
	 *
	 * @param \CurlHandle           $handle The cURL handle for this request (PHP 8+).
	 * @param array<string, mixed>  $args   The parsed request args.
	 * @param string                $url    The request URL (unused).
	 *
	 * @return void
	 */
	public function apply( $handle, array $args, string $url ): void {
		unset( $url );

		if ( empty( $args[ self::PIN_ARG ] ) ) {
			return;
		}

		curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, 0 );
	}
}
