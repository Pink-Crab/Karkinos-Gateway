<?php

declare(strict_types=1);

/**
 * Integration test for the WP Options repository.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Integration
 * @group Application
 * @group Settings
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Integration\Application;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Repository\WP_Options_Settings_Repository;

class Test_WP_Options_Settings_Repository extends WP_UnitTestCase {

	public function repository(): WP_Options_Settings_Repository {
		return new WP_Options_Settings_Repository();
	}

	/** @testdox It should be possible to set and update a key value pair in the WP Options table. */
	public function test_set(): void {
		$this->repository()->set( 'test_set', '42' );
		$this->assertEquals( '42', \get_option( 'test_set' ) );

		$this->repository()->set( 'test_set', 'foo' );
		$this->assertEquals( 'foo', \get_option( 'test_set' ) );
	}

	/** @testdox It should be possible to get a value based on its key from the WP Options table.*/
	public function test_get(): void {
		\update_option( 'test_get', 'bar' );
		$this->assertEquals( 'bar', $this->repository()->get( 'test_get' ) );

		// Return false if value not set.
		$this->assertFalse( $this->repository()->get( 'NOT_EXIST' ) );
	}

	/** @testdox It should be possible to delete a value based on its key in the WP Options table. */
	public function test_delete(): void {
		\update_option( 'to_delete', 'anything' );
		$this->repository()->delete( 'to_delete' );
		$this->assertFalse( \get_option( 'to_delete' ) );
	}

	/** @testdox It should be possible to check if a key and value pair is set in the WP Options table. */
	public function test_has(): void {
		\update_option( 'test_has', 'something' );
		$this->assertTrue( $this->repository()->has( 'test_has' ) );
		$this->assertFalse( $this->repository()->has( 'NOT_EXIST' ) );
	}

	/** @testdox Repository allows the use of an array of options as the value in the WP Options table. */
	public function test_allow_grouped(): void {
		$this->assertTrue( $this->repository()->allow_grouped() );
	}
}
