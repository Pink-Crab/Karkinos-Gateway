<?php
/**
 * Integration tests for the authorised-actors roster store.
 *
 * @package Karkinos\Gateway\Tests
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Tests\Integration\Auth;

use Karkinos\Gateway\Auth\Authorised_Actors;
use PinkCrab\Perique\Application\App;
use PinkCrab\Perique\Application\App_Config;
use WP_UnitTestCase;

/**
 * @group integration
 * @group auth
 */
class Test_Authorised_Actors extends WP_UnitTestCase {

	private Authorised_Actors $actors;

	public function set_up(): void {
		parent::set_up();
		$this->actors = App::make( Authorised_Actors::class );
		delete_option( App::make( App_Config::class )->additional( 'authorised_actors_option' ) );
	}

	public function tear_down(): void {
		delete_option( App::make( App_Config::class )->additional( 'authorised_actors_option' ) );
		parent::tear_down();
	}

	/** @testdox an empty roster authorises nobody and reports zero */
	public function test_empty_roster(): void {
		$this->assertFalse( $this->actors->is_authorised( 'octocat' ) );
		$this->assertSame( 0, $this->actors->count() );
		$this->assertNull( $this->actors->synced_at() );
		$this->assertSame( '', $this->actors->org() );
	}

	/** @testdox replace stores the roster and is_authorised matches case-insensitively */
	public function test_replace_and_is_authorised_case_insensitive(): void {
		$this->actors->replace( array( 'Octocat', 'Hubot' ), 'Pink-Crab' );

		$this->assertTrue( $this->actors->is_authorised( 'octocat' ) );
		$this->assertTrue( $this->actors->is_authorised( 'OCTOCAT' ) );
		$this->assertTrue( $this->actors->is_authorised( 'hubot' ) );
		$this->assertFalse( $this->actors->is_authorised( 'stranger' ) );

		$this->assertSame( 2, $this->actors->count() );
		$this->assertSame( 'Pink-Crab', $this->actors->org() );
		$this->assertNotNull( $this->actors->synced_at() );
	}

	/** @testdox replace de-duplicates and drops empty logins */
	public function test_replace_dedupes_and_drops_empties(): void {
		$this->actors->replace( array( 'octocat', 'Octocat', '', '   ', 'hubot' ), 'Pink-Crab' );

		$this->assertSame( 2, $this->actors->count() );
		$this->assertContains( 'octocat', $this->actors->all() );
		$this->assertContains( 'hubot', $this->actors->all() );
	}

	/** @testdox an empty login is never authorised */
	public function test_empty_login_never_authorised(): void {
		$this->actors->replace( array( 'octocat' ), 'Pink-Crab' );
		$this->assertFalse( $this->actors->is_authorised( '' ) );
		$this->assertFalse( $this->actors->is_authorised( '   ' ) );
	}

	/** @testdox replace overwrites the previous roster entirely */
	public function test_replace_overwrites(): void {
		$this->actors->replace( array( 'old_member' ), 'Pink-Crab' );
		$this->actors->replace( array( 'new_member' ), 'Pink-Crab' );

		$this->assertFalse( $this->actors->is_authorised( 'old_member' ) );
		$this->assertTrue( $this->actors->is_authorised( 'new_member' ) );
		$this->assertSame( 1, $this->actors->count() );
	}
}
