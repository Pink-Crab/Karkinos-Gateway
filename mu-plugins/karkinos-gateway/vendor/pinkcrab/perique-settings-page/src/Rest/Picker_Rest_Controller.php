<?php

declare( strict_types=1 );

/**
 * REST API controller for Picker field search and resolve endpoints.
 *
 * Registers routes under the pc-settings/v1 namespace for async
 * post and user searching used by Post_Picker and User_Picker fields.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Rest;

use WP_REST_Request;
use WP_REST_Response;
use PinkCrab\Perique_Settings_Page\Util\Cast;

class Picker_Rest_Controller {

	/**
	 * REST namespace.
	 */
	public const NAMESPACE = 'pc-settings/v1';

	/**
	 * Registers all picker REST routes.
	 *
	 * @return void
	 */
	public function register(): void {
		// Post search.
		\register_rest_route(
			self::NAMESPACE,
			'/posts/search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'search_posts' ),
				'permission_callback' => function (): bool {
					return \current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'search'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ): bool {
							return is_string( $value ) && \mb_strlen( $value ) >= 2;
						},
					),
					'post_type' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page'  => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ): bool {
							$val = (int) $value;
							return $val >= 1 && $val <= 50;
						},
					),
				),
			)
		);

		// Post resolve.
		\register_rest_route(
			self::NAMESPACE,
			'/posts/resolve',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resolve_posts' ),
				'permission_callback' => function (): bool {
					return \current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => function ( $value ): array {
							return array_map( 'absint', (array) $value );
						},
					),
				),
			)
		);

		// User search.
		\register_rest_route(
			self::NAMESPACE,
			'/users/search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'search_users' ),
				'permission_callback' => function (): bool {
					return \current_user_can( 'list_users' );
				},
				'args'                => array(
					'search'   => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ): bool {
							return is_string( $value ) && \mb_strlen( $value ) >= 2;
						},
					),
					'role'     => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ): bool {
							$val = (int) $value;
							return $val >= 1 && $val <= 50;
						},
					),
				),
			)
		);

		// User resolve.
		\register_rest_route(
			self::NAMESPACE,
			'/users/resolve',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resolve_users' ),
				'permission_callback' => function (): bool {
					return \current_user_can( 'list_users' );
				},
				'args'                => array(
					'ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => function ( $value ): array {
							return array_map( 'absint', (array) $value );
						},
					),
				),
			)
		);
	}

	/**
	 * Searches posts by title.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function search_posts( WP_REST_Request $request ): WP_REST_Response {
		$search    = Cast::to_string( $request->get_param( 'search' ) ) ?? '';
		$post_type = Cast::to_string( $request->get_param( 'post_type' ) ) ?? 'post';
		$per_page  = Cast::to_int( $request->get_param( 'per_page' ), 10 );

		$posts = \get_posts(
			array(
				's'              => $search,
				'post_type'      => $post_type,
				'posts_per_page' => $per_page,
				'post_status'    => 'publish',
				'orderby'        => 'relevance',
				'order'          => 'ASC',
			)
		);

		$results = array_map(
			function ( \WP_Post $post ): array {
				return array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			},
			$posts
		);

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Resolves post IDs to labels.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function resolve_posts( WP_REST_Request $request ): WP_REST_Response {
		/** @var array<int, int> $ids */
		$ids = (array) $request->get_param( 'ids' );

		if ( empty( $ids ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$posts = \get_posts(
			array(
				'post__in'       => $ids,
				'post_type'      => 'any',
				'posts_per_page' => count( $ids ),
				'post_status'    => 'publish',
				'orderby'        => 'post__in',
			)
		);

		$results = array_map(
			function ( \WP_Post $post ): array {
				return array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			},
			$posts
		);

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Searches users by name/email.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function search_users( WP_REST_Request $request ): WP_REST_Response {
		$search   = Cast::to_string( $request->get_param( 'search' ) ) ?? '';
		$role     = Cast::to_string( $request->get_param( 'role' ) ) ?? '';
		$per_page = Cast::to_int( $request->get_param( 'per_page' ), 10 );

		$args = array(
			'search'  => '*' . $search . '*',
			'number'  => $per_page,
			'orderby' => 'display_name',
			'order'   => 'ASC',
		);

		if ( '' !== $role ) {
			$args['role'] = $role;
		}

		$users = \get_users( $args );

		$results = array_map(
			function ( \WP_User $user ): array {
				return array(
					'id'   => $user->ID,
					'text' => $user->display_name,
				);
			},
			$users
		);

		return new WP_REST_Response( $results, 200 );
	}

	/**
	 * Resolves user IDs to labels.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response
	 */
	public function resolve_users( WP_REST_Request $request ): WP_REST_Response {
		/** @var array<int, int> $ids */
		$ids = (array) $request->get_param( 'ids' );

		if ( empty( $ids ) ) {
			return new WP_REST_Response( array(), 200 );
		}

		$users = \get_users(
			array(
				'include' => $ids,
				'number'  => count( $ids ),
				'orderby' => 'include',
			)
		);

		$results = array_map(
			function ( \WP_User $user ): array {
				return array(
					'id'   => $user->ID,
					'text' => $user->display_name,
				);
			},
			$users
		);

		return new WP_REST_Response( $results, 200 );
	}
}
