<?php

declare(strict_types=1);

/**
 * Unit tests for the parent functionality of the Abstract_Setting class.
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
 * @group Unit
 * @group Settings
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Setting;

use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Valid_Settings\Valid_Settings_Not_Grouped;

class Test_Abstract_Setting extends WP_UnitTestCase {

	/**
	 * @var Abstract_Settings
	 */
	protected $settings;

	/**
	 * @var Object_Setting_Repository
	 */
	protected $repository;

	public function setup(): void {
		parent::setUp();
		$this->repository = new Object_Setting_Repository();
		$this->settings   = new Valid_Settings_Not_Grouped( $this->repository );
	}

	public function tearDown(): void {
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped   = false;
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$group_key = 'mock_settings';
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$injected_fields = array();
		parent::tearDown();
	}

	public function setting_collection(): Setting_Collection {
		return Objects::get_property( $this->settings, 'settings' );
	}

	/** @testdox It should be possible to set a prefix that is applied to setting keys to avoid naming conflicts. */
	public function test_prefix_key(): void {
		$this->assertEquals( 'Valid_Settings_Not_Grouped_prefix-me', $this->settings->prefix_key( 'prefix-me' ) );
	}

	/** @testdox It should be possible to check if a setting exists in the collection. */
	public function test_has_setting(): void {
		$this->assertTrue( $this->settings->has( 'number' ) );
		$this->assertFalse( $this->settings->has( 'should_fail' ) );
	}

	/** @testdox It should be possible to get the setting object from its key. */
	public function test_find(): void {
		$this->assertInstanceOf( Number::class, $this->settings->find( 'number' ) );
		$this->assertNull( $this->settings->find( 'should_fail' ) );
	}

	/** @testdox It should be possible to get a value from the settings, if the key doesnt exist, it will return null, unless fallback provided. */
	public function test_can_get_setting_value(): void {
		$this->setting_collection()->set_value( 'number', '24' );
		$this->assertEquals( '24', $this->settings->get( 'number' ) );
		// Null if not set.
		$this->assertNull( $this->settings->get( 'should_fail' ) );
		$this->assertEquals( 'but fallback', $this->settings->get( 'should_fail', 'but fallback' ) );
	}

	/** @testdox It should be possible to set a value based on its key. */
	public function test_can_set_value_to_key(): void {
		$result = $this->settings->set( 'number', '42' );

		$this->assertTrue( $result );

		$this->assertArrayHasKey( 'Valid_Settings_Not_Grouped_number', $this->repository->store );
		$this->assertEquals( '42', $this->repository->store['Valid_Settings_Not_Grouped_number'] );
	}

	/** @testdox It should be possible to delete a value. */
	public function test_can_delete_value() {
		$this->settings->set( 'number', '42' );
		if ( '42' !== $this->settings->get( 'number' ) ) {
			throw new \Exception( 'Failed to set value to Number setting', 1 );
		}
		$this->settings->delete( 'number' );
		$this->assertEquals( '', $this->settings->get( 'number' ) );

	}

	/** @testdox It should be possible to export the collection of settings as an array of settings. */
	public function test_it_should_possible_to_export_settings_as_an_array(): void {
		$exported = $this->settings->export();

		// Check contains the same as the actual settings.
		foreach ( $exported as $key => $field ) {
			$this->assertSame( $this->settings->find( $key ), $field );
		}

		// Ensure all objects match (should have same number as exported)
		$intersecting = Objects::get_property( $this->settings, 'settings' )->intersect( $exported );
		$this->assertCount( count( $exported ), $intersecting );
	}

	/** @testdox It should be possible to get all the keys from the settings. */
	public function test_get_all_keys(): void {
		$keys   = $this->settings->get_keys();
		$values = Valid_Settings_Not_Grouped::FIELD_KEYS;
		$this->assertContains( $values['Number'], $keys );
		$this->assertContains( $values['Text'], $keys );
		$this->assertContains( $values['Select'], $keys );
		$this->assertContains( $values['Media_Library'], $keys );
		$this->assertContains( $values['Checkbox'], $keys );
		$this->assertContains( $values['WP_Editor'], $keys );
		$this->assertContains( $values['Post_Picker'], $keys );
		$this->assertContains( $values['Checkbox_Group'], $keys );
		$this->assertContains( $values['Radio'], $keys );
		$this->assertContains( $values['Colour'], $keys );
	}

	/** @testdox It should be possible to define a sanitization callable and process a value. */
	public function test_can_sanitize_value(): void {
		$values = Valid_Settings_Not_Grouped::FIELD_KEYS;
		$this->assertEquals( 15, $this->settings->find( $values['Number'] )->sanitize( 'foo' ) );
		$this->assertEquals( 'bar', $this->settings->find( $values['Text'] )->sanitize( '<p>bar</p>' ) );
	}

	/** @testdox It should be possible to validate a value using the defined validate method. */
	public function test_can_validate_value(): void {
		$this->assertTrue( $this->settings->find( Valid_Settings_Not_Grouped::FIELD_KEYS['Number'] )->validate( 4 ) );
		$this->assertFalse( $this->settings->find( Valid_Settings_Not_Grouped::FIELD_KEYS['Number'] )->validate( 41 ) );
	}

	/** @testdox When grouped, set() persists all fields under a single key via store_grouped(). */
	public function test_grouped_set_persists_under_single_key(): void {
		$repo                                = new \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository();
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped = true;
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$group_key = 'group_test';

		$settings = \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::with_fields(
			$repo,
			\PinkCrab\Perique_Settings_Page\Setting\Field\Text::new( 'name' )
		);

		$result = $settings->set( 'name', 'value' );
		$this->assertTrue( $result );
		$this->assertArrayHasKey( 'group_test', $repo->store );
		$this->assertSame( 'value', $repo->store['group_test']['name'] );

		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped = false;
	}

	/** @testdox When grouped, delete() removes the value via delete_grouped(). */
	public function test_grouped_delete(): void {
		$repo                                = new \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository();
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped = true;
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$group_key = 'group_delete';

		$settings = \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::with_fields(
			$repo,
			\PinkCrab\Perique_Settings_Page\Setting\Field\Text::new( 'name' )
		);

		$settings->set( 'name', 'value' );
		$result = $settings->delete( 'name' );
		$this->assertTrue( $result );

		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped = false;
	}

	/** @testdox delete() returns null when the key does not exist. */
	public function test_delete_unknown_key(): void {
		$this->assertNull( $this->settings->delete( 'missing_key' ) );
	}

	/** @testdox set() returns false when the key does not exist. */
	public function test_set_unknown_key(): void {
		$this->assertFalse( $this->settings->set( 'missing_key', 'value' ) );
	}

	/** @testdox get_all_fields() recursively flattens fields nested in layouts. */
	public function test_get_all_fields_with_nested_layout(): void {
		$repo = new \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository();
		$settings = \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::with_fields(
			$repo,
			\PinkCrab\Perique_Settings_Page\Setting\Layout\Section::of(
				\PinkCrab\Perique_Settings_Page\Setting\Field\Text::new( 'inside_section' )
			),
			\PinkCrab\Perique_Settings_Page\Setting\Field\Text::new( 'top_level' )
		);

		$all_fields = $settings->get_all_fields();
		$this->assertArrayHasKey( 'inside_section', $all_fields );
		$this->assertArrayHasKey( 'top_level', $all_fields );
	}

	/** @testdox When grouped, refresh_settings hydrates fields from the grouped option. */
	public function test_grouped_refresh_hydrates_fields(): void {
		$repo                                                                            = new \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository();
		$repo->store['group_refresh']                                                    = array( 'name' => 'stored' );
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped  = true;
		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$group_key = 'group_refresh';

		$settings = \PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::with_fields(
			$repo,
			\PinkCrab\Perique_Settings_Page\Setting\Field\Text::new( 'name' )
		);
		$settings->refresh_settings();
		$this->assertSame( 'stored', $settings->get( 'name' ) );

		\PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings::$grouped = false;
	}
}
