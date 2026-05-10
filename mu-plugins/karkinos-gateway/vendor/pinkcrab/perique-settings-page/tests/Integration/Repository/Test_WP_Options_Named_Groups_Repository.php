<?php

declare( strict_types=1 );

/**
 * Integration tests for WP_Options_Named_Groups_Repository.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Repository
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Repository;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Named_Groups_Repository;

class Test_WP_Options_Named_Groups_Repository extends WP_UnitTestCase {

	protected function build(): WP_Options_Named_Groups_Repository {
		return new WP_Options_Named_Groups_Repository(
			'myapp',
			array(
				'general' => array( 'site_name', 'tagline' ),
				'api'     => array( 'api_key', 'api_secret' ),
			)
		);
	}

	public function tearDown(): void {
		\delete_option( 'myapp_general' );
		\delete_option( 'myapp_api' );
		\delete_option( 'myapp__default' );
		parent::tearDown();
	}

	/** @testdox set() stores a key under the correct group option. */
	public function test_set_in_correct_group(): void {
		$repo = $this->build();
		$repo->set( 'site_name', 'My Site' );
		$repo->set( 'api_key', 'sk-123' );

		$general = \get_option( 'myapp_general' );
		$api     = \get_option( 'myapp_api' );

		$this->assertSame( 'My Site', $general['site_name'] );
		$this->assertSame( 'sk-123', $api['api_key'] );
	}

	/** @testdox set() stores keys not in any group under the _default group. */
	public function test_set_unknown_key_uses_default_group(): void {
		$repo = $this->build();
		$repo->set( 'orphan', 'value' );

		$default = \get_option( 'myapp__default' );
		$this->assertSame( 'value', $default['orphan'] );
	}

	/** @testdox get() retrieves a value from its group. */
	public function test_get(): void {
		$repo = $this->build();
		$repo->set( 'site_name', 'Hello' );
		$this->assertSame( 'Hello', $repo->get( 'site_name' ) );
	}

	/** @testdox get() returns null for a missing key. */
	public function test_get_missing(): void {
		$repo = $this->build();
		$this->assertNull( $repo->get( 'nonexistent_key_in_group' ) );
	}

	/** @testdox has() returns true for a stored key. */
	public function test_has_existing(): void {
		$repo = $this->build();
		$repo->set( 'site_name', 'Yes' );
		$this->assertTrue( $repo->has( 'site_name' ) );
	}

	/** @testdox has() returns false for a missing key. */
	public function test_has_missing(): void {
		$repo = $this->build();
		$this->assertFalse( $repo->has( 'site_name' ) );
	}

	/** @testdox delete() removes the key from its group. */
	public function test_delete(): void {
		$repo = $this->build();
		$repo->set( 'site_name', 'doomed' );
		$this->assertTrue( $repo->delete( 'site_name' ) );
		$this->assertFalse( $repo->has( 'site_name' ) );
	}

	/** @testdox delete() returns false when the key was not set. */
	public function test_delete_missing(): void {
		$repo = $this->build();
		$this->assertFalse( $repo->delete( 'never_set' ) );
	}

	/** @testdox Multiple keys in the same group are stored together. */
	public function test_multiple_keys_in_group(): void {
		$repo = $this->build();
		$repo->set( 'site_name', 'Hello' );
		$repo->set( 'tagline', 'World' );

		$general = \get_option( 'myapp_general' );
		$this->assertSame( 'Hello', $general['site_name'] );
		$this->assertSame( 'World', $general['tagline'] );
	}

	/** @testdox allow_grouped() returns false. */
	public function test_allow_grouped(): void {
		$this->assertFalse( $this->build()->allow_grouped() );
	}
}
