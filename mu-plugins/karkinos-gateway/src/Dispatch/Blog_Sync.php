<?php
/**
 * Rebuilds the stubs section of the blog post from GitHub.
 *
 * The section is owned by two HTML comment markers inside the post; everything
 * between them is regenerated on every run, everything outside is never
 * touched. The content is derived entirely from the org's `*_stubs` repos and
 * their tags, so a run is idempotent — a backfilled old version or a manual
 * edit inside the markers is simply rebuilt correctly next time.
 *
 * A run: list the org's repos → keep `*_stubs` → list each repo's tags →
 * render the section → GET the post (context=edit) → splice between the
 * markers → POST the content back. The final blog response is returned in
 * wp_remote_* shape so Dispatch_Worker applies its usual terminal/transient
 * handling; conditions with no HTTP response of their own (markers missing,
 * malformed bodies) are reported as a synthesised 422 so the job is
 * permanently rejected rather than retried forever.
 *
 * @package Karkinos\Gateway\Dispatch
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Dispatch;

use WP_Error;

class Blog_Sync {

	/** Org used when KARKINOS_GH_ORG is not defined. */
	public const DEFAULT_ORG = 'Pink-Crab';

	/** Composer vendor used when KARKINOS_BLOG_VENDOR is not defined. */
	public const DEFAULT_VENDOR = 'pinkcrab';

	/** Everything between these two markers is owned (and rewritten) by the sync. */
	public const START_MARKER = '<!-- stub-forge:start -->';
	public const END_MARKER   = '<!-- stub-forge:end -->';

	/** Repo-name suffix that marks a stubs repo. */
	private const STUBS_SUFFIX = '_stubs';

	/** GitHub REST API base. */
	private const API_BASE = 'https://api.github.com';

	/** Per-page cap GitHub allows for list endpoints. */
	private const PER_PAGE = 100;

	/** Hard stop on pages followed, so a runaway Link chain can't loop forever. */
	private const MAX_PAGES = 50;

	/** Request timeout in seconds. */
	private const TIMEOUT = 15;

	/**
	 * Constructor.
	 *
	 * @param Blog_Target $target Resolves the blog post endpoint + basic auth.
	 */
	public function __construct( private Blog_Target $target ) {}

	/**
	 * Run one full rebuild of the stubs section.
	 *
	 * @return array<string, mixed>|WP_Error The final blog response (or a
	 *         synthesised one), or a WP_Error on GitHub/transport failure so
	 *         the worker treats the job as transient.
	 */
	public function run(): array|WP_Error {
		$endpoint = $this->target->post_endpoint();
		if ( '' === $endpoint ) {
			return new WP_Error( 'karkinos_gateway_no_blog_target', 'Blog target is not configured.' );
		}

		$repos = $this->stub_repos();
		if ( is_wp_error( $repos ) ) {
			return $repos;
		}

		$sections = array();
		foreach ( $repos as $repo ) {
			$tags = $this->tags( $repo );
			if ( is_wp_error( $tags ) ) {
				return $tags;
			}
			if ( array() !== $tags ) {
				$sections[ $repo ] = $tags;
			}
		}

		$post = wp_remote_get(
			add_query_arg( 'context', 'edit', $endpoint ),
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => $this->target->auth_header(),
					'Accept'        => 'application/json',
					'User-Agent'    => 'karkinos-gateway',
				),
			)
		);

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$code = (int) wp_remote_retrieve_response_code( $post );
		if ( 200 !== $code ) {
			// Auth/permission problems surface here — hand the response back so
			// a 4xx permanently rejects instead of retrying with bad creds.
			return $post;
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $post ), true );
		$content = $decoded['content']['raw'] ?? null;
		if ( ! is_string( $content ) ) {
			return $this->synth( 422, 'Post response carried no content.raw — is the auth user allowed context=edit?' );
		}

		$spliced = $this->splice( $content, $this->render( $sections ) );
		if ( null === $spliced ) {
			return $this->synth( 422, 'Post content does not contain the stub-forge start/end markers.' );
		}

		return wp_remote_post(
			$endpoint,
			array(
				'timeout' => self::TIMEOUT,
				'body'    => (string) wp_json_encode( array( 'content' => $spliced ) ),
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => $this->target->auth_header(),
					'Accept'        => 'application/json',
					'User-Agent'    => 'karkinos-gateway',
				),
			)
		);
	}

	/**
	 * Names of the org's stubs repos, alphabetical.
	 *
	 * @return list<string>|WP_Error Repo names (e.g. 'rtmedia_stubs').
	 */
	private function stub_repos(): array|WP_Error {
		$repos = $this->github_paged(
			sprintf( '%s/orgs/%s/repos?per_page=%d', self::API_BASE, rawurlencode( $this->org() ), self::PER_PAGE )
		);
		if ( is_wp_error( $repos ) ) {
			return $repos;
		}

		$names = array();
		foreach ( $repos as $repo ) {
			$name = is_array( $repo ) && isset( $repo['name'] ) && is_string( $repo['name'] ) ? $repo['name'] : '';
			if ( '' !== $name && str_ends_with( strtolower( $name ), self::STUBS_SUFFIX ) ) {
				$names[] = $name;
			}
		}

		sort( $names, SORT_STRING | SORT_FLAG_CASE );
		return $names;
	}

	/**
	 * Tag names for one stubs repo, newest version first.
	 *
	 * @param string $repo Repo name (without the org).
	 *
	 * @return list<string>|WP_Error
	 */
	private function tags( string $repo ): array|WP_Error {
		$tags = $this->github_paged(
			sprintf(
				'%s/repos/%s/%s/tags?per_page=%d',
				self::API_BASE,
				rawurlencode( $this->org() ),
				rawurlencode( $repo ),
				self::PER_PAGE
			)
		);
		if ( is_wp_error( $tags ) ) {
			return $tags;
		}

		$names = array();
		foreach ( $tags as $tag ) {
			if ( is_array( $tag ) && isset( $tag['name'] ) && is_string( $tag['name'] ) ) {
				$names[] = $tag['name'];
			}
		}

		usort(
			$names,
			static fn( string $a, string $b ): int => version_compare( ltrim( $b, 'vV' ), ltrim( $a, 'vV' ) )
		);

		return $names;
	}

	/**
	 * Render the owned section as Gutenberg block markup: one heading block
	 * per repo, then a list block with one list-item block per version,
	 * each linking to its release with the composer require line alongside.
	 *
	 * @param array<string, list<string>> $sections Repo name => tags (newest first).
	 *
	 * @return string Raw block markup for between the markers.
	 */
	private function render( array $sections ): string {
		$org    = $this->org();
		$vendor = $this->vendor();
		$blocks = array();

		foreach ( $sections as $repo => $tags ) {
			$title = substr( $repo, 0, -strlen( self::STUBS_SUFFIX ) );

			$blocks[] = '<!-- wp:heading {"level":3,"className":"release-title"} -->' . "\n"
				. '<h3 class="wp-block-heading release-title">' . esc_html( $title ) . "</h3>\n"
				. '<!-- /wp:heading -->';

			$items = array();
			foreach ( $tags as $tag ) {
				$release = sprintf( 'https://github.com/%s/%s/releases/tag/%s', $org, $repo, $tag );
				$items[] = "<!-- wp:list-item -->\n"
					. '<li><a href="' . esc_url( $release ) . '">' . esc_html( $tag ) . '</a>'
					. ' - ' . esc_html( sprintf( 'composer require --dev %s/%s:%s', $vendor, strtolower( $repo ), $tag ) ) . "</li>\n"
					. '<!-- /wp:list-item -->';
			}

			$blocks[] = '<!-- wp:list {"className":"release-versions"} -->' . "\n"
				. '<ul class="wp-block-list release-versions">'
				. implode( "\n\n", $items )
				. "</ul>\n"
				. '<!-- /wp:list -->';
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Replace everything between the markers with the rendered section.
	 *
	 * @param string $content Full post content (raw).
	 * @param string $section Rendered replacement.
	 *
	 * @return string|null New content, or null when either marker is missing
	 *                     or they are out of order.
	 */
	private function splice( string $content, string $section ): ?string {
		$start = strpos( $content, self::START_MARKER );
		$end   = strpos( $content, self::END_MARKER );

		if ( false === $start || false === $end || $end < $start ) {
			return null;
		}

		return substr( $content, 0, $start + strlen( self::START_MARKER ) )
			. "\n" . $section
			. substr( $content, $end );
	}

	/**
	 * GET a paginated GitHub list endpoint, following Link rel="next".
	 *
	 * @param string $url First page URL.
	 *
	 * @return list<mixed>|WP_Error Concatenated items across pages.
	 */
	private function github_paged( string $url ): array|WP_Error {
		$token = $this->token();
		if ( null === $token ) {
			return new WP_Error(
				'karkinos_gateway_missing_gh_token',
				'KARKINOS_GH_API_TOKEN is not defined; cannot list stub repos.'
			);
		}

		$items = array();
		$pages = 0;

		while ( '' !== $url && $pages < self::MAX_PAGES ) {
			++$pages;

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'Authorization'        => 'Bearer ' . $token,
						'Accept'               => 'application/vnd.github+json',
						'X-GitHub-Api-Version' => '2022-11-28',
						'User-Agent'           => 'karkinos-gateway',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 200 !== $code ) {
				return new WP_Error(
					'karkinos_gateway_gh_http_error',
					sprintf( 'GitHub returned HTTP %d for %s.', $code, $url ),
					array( 'status' => $code )
				);
			}

			$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $decoded ) ) {
				return new WP_Error(
					'karkinos_gateway_gh_bad_payload',
					'GitHub list response was not a JSON array.'
				);
			}

			foreach ( $decoded as $item ) {
				$items[] = $item;
			}

			$url = $this->next_page_url( wp_remote_retrieve_header( $response, 'link' ) );
		}

		return $items;
	}

	/**
	 * Extract the `rel="next"` URL from a GitHub `Link` header, if present.
	 *
	 * @param string|array<int, string> $link_header Raw Link header value(s).
	 *
	 * @return string Next-page URL, or '' when there is no next page.
	 */
	private function next_page_url( string|array $link_header ): string {
		$link = is_array( $link_header ) ? implode( ', ', $link_header ) : $link_header;
		if ( '' === $link ) {
			return '';
		}

		foreach ( explode( ',', $link ) as $segment ) {
			if ( ! str_contains( $segment, 'rel="next"' ) ) {
				continue;
			}
			if ( preg_match( '/<([^>]+)>/', $segment, $matches ) ) {
				return trim( $matches[1] );
			}
		}

		return '';
	}

	/**
	 * A wp_remote_*-shaped response for failures with no HTTP response of
	 * their own, so the worker's status handling applies uniformly.
	 *
	 * @param int    $code    Status code to report (4xx = permanent reject).
	 * @param string $message Human-readable reason, recorded on the job.
	 *
	 * @return array<string, mixed>
	 */
	private function synth( int $code, string $message ): array {
		return array(
			'response' => array(
				'code'    => $code,
				'message' => $message,
			),
			'body'     => (string) wp_json_encode( array( 'error' => $message ) ),
			'headers'  => array(),
		);
	}

	/**
	 * Resolve the org from the wp-config constant, defaulting to Pink-Crab.
	 *
	 * @return string
	 */
	private function org(): string {
		if ( defined( 'KARKINOS_GH_ORG' ) ) {
			$org = constant( 'KARKINOS_GH_ORG' );
			if ( is_string( $org ) && '' !== trim( $org ) ) {
				return trim( $org );
			}
		}
		return self::DEFAULT_ORG;
	}

	/**
	 * Composer vendor prefix, defaulting to pinkcrab.
	 *
	 * @return string
	 */
	private function vendor(): string {
		if ( defined( 'KARKINOS_BLOG_VENDOR' ) ) {
			$vendor = constant( 'KARKINOS_BLOG_VENDOR' );
			if ( is_string( $vendor ) && '' !== trim( $vendor ) ) {
				return strtolower( trim( $vendor ) );
			}
		}
		return self::DEFAULT_VENDOR;
	}

	/**
	 * Resolve the PAT from the wp-config constant.
	 *
	 * @return string|null Non-empty token string, or null if absent/empty.
	 */
	private function token(): ?string {
		if ( ! defined( 'KARKINOS_GH_API_TOKEN' ) ) {
			return null;
		}
		$token = constant( 'KARKINOS_GH_API_TOKEN' );
		return is_string( $token ) && '' !== $token ? $token : null;
	}
}
