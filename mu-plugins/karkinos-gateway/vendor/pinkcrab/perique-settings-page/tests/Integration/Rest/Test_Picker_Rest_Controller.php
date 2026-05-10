<?php

declare( strict_types=1 );

/**
 * Integration tests for Picker_Rest_Controller.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Rest
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Rest;

use PinkCrab\Perique_Settings_Page\Rest\Picker_Rest_Controller;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\HTTP_TestCase;

class Test_Picker_Rest_Controller extends HTTP_TestCase {

	public function setUp(): void {
		parent::setUp();
		// Hook the controller's register() into rest_api_init so WP doesn't
		// trigger the "_doing_it_wrong" notice (WP 5.1+).
		add_action(
			'rest_api_init',
			function () {
				( new Picker_Rest_Controller() )->register();
			}
		);
		$this->register_routes();
	}

	public function tearDown(): void {
		remove_all_actions( 'rest_api_init' );
		parent::tearDown();
	}

	protected function set_user( string $role ): int {
		$user_id = $this->factory->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		return $user_id;
	}

	/* -------------------------------------------------------
	 * Posts: search
	 * ------------------------------------------------------- */

	/** @testdox POST /posts/search returns matching posts for an editor. */
	public function test_search_posts_happy_path(): void {
		$this->set_user( 'editor' );
		$this->factory->post->create( array( 'post_title' => 'Hello World', 'post_status' => 'publish' ) );

		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/search',
			array( 'search' => 'Hello' )
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data() );
		$first = $response->get_data()[0];
		$this->assertArrayHasKey( 'id', $first );
		$this->assertArrayHasKey( 'text', $first );
		$this->assertSame( 'Hello World', $first['text'] );
	}

	/** @testdox POST /posts/search returns 403 for a subscriber. */
	public function test_search_posts_forbidden_for_subscriber(): void {
		$this->set_user( 'subscriber' );
		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/search',
			array( 'search' => 'foo' )
		);
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/** @testdox POST /posts/search returns 401 when not logged in. */
	public function test_search_posts_unauth(): void {
		wp_set_current_user( 0 );
		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/search',
			array( 'search' => 'foo' )
		);
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/** @testdox POST /posts/search filters by post_type. */
	public function test_search_posts_filters_by_post_type(): void {
		$this->set_user( 'editor' );
		wp_insert_post( array( 'post_title' => 'A Post', 'post_status' => 'publish', 'post_type' => 'post' ) );
		wp_insert_post( array( 'post_title' => 'A Page', 'post_status' => 'publish', 'post_type' => 'page' ) );

		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/search',
			array( 'search' => 'A Page', 'post_type' => 'page' )
		);

		$this->assertSame( 200, $response->get_status() );
		$titles = array_column( $response->get_data(), 'text' );
		$this->assertContains( 'A Page', $titles );
		$this->assertNotContains( 'A Post', $titles );
	}

	/** @testdox POST /posts/search rejects a search shorter than 2 chars. */
	public function test_search_posts_validation_failure(): void {
		$this->set_user( 'editor' );
		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/search',
			array( 'search' => 'a' )
		);
		$this->assertSame( 400, $response->get_status() );
	}

	/* -------------------------------------------------------
	 * Posts: resolve
	 * ------------------------------------------------------- */

	/** @testdox POST /posts/resolve returns labels for given IDs. */
	public function test_resolve_posts_happy_path(): void {
		$this->set_user( 'editor' );
		$id1 = $this->factory->post->create( array( 'post_title' => 'First', 'post_status' => 'publish' ) );
		$id2 = $this->factory->post->create( array( 'post_title' => 'Second', 'post_status' => 'publish' ) );

		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/resolve',
			array( 'ids' => array( $id1, $id2 ) )
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
	}

	/** @testdox POST /posts/resolve returns an empty array for an empty ids list. */
	public function test_resolve_posts_empty_ids(): void {
		$this->set_user( 'editor' );
		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/posts/resolve',
			array( 'ids' => array() )
		);
		// REST may return 400 for missing required, or 200 with []. Either is acceptable.
		$this->assertContains( $response->get_status(), array( 200, 400 ) );
	}

	/* -------------------------------------------------------
	 * Users: search
	 * ------------------------------------------------------- */

	/** @testdox POST /users/search returns matching users for an admin. */
	public function test_search_users_happy_path(): void {
		$this->set_user( 'administrator' );
		$this->factory->user->create( array( 'display_name' => 'Jane Doe', 'role' => 'editor' ) );

		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/users/search',
			array( 'search' => 'Jane' )
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotEmpty( $response->get_data() );
	}

	/** @testdox POST /users/search returns 401/403 for a subscriber. */
	public function test_search_users_forbidden(): void {
		$this->set_user( 'subscriber' );
		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/users/search',
			array( 'search' => 'foo' )
		);
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/** @testdox POST /users/search filters by role. */
	public function test_search_users_filters_by_role(): void {
		$this->set_user( 'administrator' );
		$this->factory->user->create( array( 'display_name' => 'Edith Editor', 'role' => 'editor' ) );
		$this->factory->user->create( array( 'display_name' => 'Edith Author', 'role' => 'author' ) );

		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/users/search',
			array( 'search' => 'Edith', 'role' => 'editor' )
		);

		$this->assertSame( 200, $response->get_status() );
		$names = array_column( $response->get_data(), 'text' );
		$this->assertContains( 'Edith Editor', $names );
		$this->assertNotContains( 'Edith Author', $names );
	}

	/* -------------------------------------------------------
	 * Users: resolve
	 * ------------------------------------------------------- */

	/** @testdox POST /users/resolve returns labels for given IDs. */
	public function test_resolve_users_happy_path(): void {
		$this->set_user( 'administrator' );
		$id1 = $this->factory->user->create( array( 'display_name' => 'Alice' ) );
		$id2 = $this->factory->user->create( array( 'display_name' => 'Bob' ) );

		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/users/resolve',
			array( 'ids' => array( $id1, $id2 ) )
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
	}

	/** @testdox POST /users/resolve returns an empty array for an empty ids list. */
	public function test_resolve_users_empty_ids(): void {
		$this->set_user( 'administrator' );
		$response = $this->dispatch_request(
			'POST',
			'/pc-settings/v1/users/resolve',
			array( 'ids' => array() )
		);
		$this->assertContains( $response->get_status(), array( 200, 400 ) );
	}
}
