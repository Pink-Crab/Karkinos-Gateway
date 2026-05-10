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
 * @group Field
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Field;

use stdClass;
use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;

class Test_Repeater_Value extends WP_UnitTestCase {

	/** @testdox It should be possible to access all values for a key. */
	public function test_can_get_by_field_key(): void {
		$value = new Repeater_Value(
			array(
				'key1' => array( 'a', 'b' ),
				'key2' => array( 1, 2 ),
			)
		);

		// Using getter
		$this->assertNull( $value->get( 'not_set' ) );
		$this->assertEquals( array( 'a', 'b' ), $value->get( 'key1' ) );
		$this->assertEquals( array( 1, 2 ), $value->get( 'key2' ) );

		// Magic getter
		$this->assertNull( $value->not_set );
		$this->assertEquals( array( 'a', 'b' ), $value->key1 );
		$this->assertEquals( array( 1, 2 ), $value->key2 );
	}

	/** @testdox It should be possible to check if a field exists. */
	public function test_can_check_if_field_exists(): void {
		$value = new Repeater_Value(
			array(
				'key1' => array( 'a', 'b' ),
				'key2' => array( 1, 2 ),
			)
		);

		$this->assertFalse( $value->has_field( 'not_set' ) );
		$this->assertTrue( $value->has_field( 'key1' ) );
		$this->assertTrue( $value->has_field( 'key2' ) );

		// Using __isset() magic!
		$this->assertFalse( isset( $value->not_set ) );
		$this->assertTrue( isset( $value->key1 ) );
		$this->assertTrue( isset( $value->key2 ) );
	}

	/** @testdox It should be possible to check how many groups of values set. In the event of an uneven amount per field, the highest value should be returned. */
	public function test_count_group(): void {
		$value = new Repeater_Value(
			array(
				'key1' => array( 'a', 'b' ),
				'key2' => array( 1, 2 ),
			)
		);

		$this->assertEquals( 2, $value->group_count() );

		// Will return the max if uneven.
		$max = new Repeater_Value(
			array(
				'key1' => array( 'a', 'b' ),
				'key2' => array( 1, 2, 3, 4 ),
			)
		);
		$this->assertEquals( 4, $max->group_count() );
	}

	/** @testdox It should be possible to extract the values as a basic object, with the values grouped by field key */
	public function test_get_data_by_field(): void {
		$value = ( new Repeater_Value(
			array(
				'str' => array( 'a', 'b' ),
				'num' => array(
					1,
					2,
				),
			)
		) )->as_fields();

		$this->assertInstanceOf( \stdClass::class, $value );
		$this->assertTrue( \property_exists( $value, 'str' ) );
		$this->assertTrue( \property_exists( $value, 'num' ) );

		// Values.
		$this->assertEquals( array( 'a', 'b' ), $value->str );
		$this->assertEquals( array( 1, 2 ), $value->num );
	}

	/** @testdox It should be possible to access all the values grouped by index. Each index should hold an object with field names as properties. */
	public function test_get_data_by_indexed(): void {
		$value = ( new Repeater_Value(
			array(
				'str' => array( 'a', 'b' ),
				'num' => array( 1, 2 ),
			)
		) )->as_indexed();

		$this->assertTrue( is_array( $value ) );
		$this->assertCount( 2, $value );

		$this->assertEquals( 'a', $value[0]->str );
		$this->assertEquals( 1, $value[0]->num );

		$this->assertEquals( 'b', $value[1]->str );
		$this->assertEquals( 2, $value[1]->num );
	}

	/** @testdox It should be possible to pluck a group of value based on its index. */
	public function test_get_by_index(): void {
		$value = ( new Repeater_Value(
			array(
				'str' => array( 'a', 'b' ),
				'num' => array( 1, 2 ),
			)
		) );

		$this->assertInstanceOf( stdClass::class, $value->get_index( 0 ) );
		$this->assertEquals( 'a', $value->get_index( 0 )->str );
		$this->assertEquals( 1, $value->get_index( 0 )->num );

		$this->assertInstanceOf( stdClass::class, $value->get_index( 1 ) );
		$this->assertEquals( 'b', $value->get_index( 1 )->str );
		$this->assertEquals( 2, $value->get_index( 1 )->num );

		// If index doesn't exist, return null
		$this->assertNull( $value->get_index( 2 ) );

	}

	/** @testdox get_index() returns null for fields that don't have a value at the requested index. */
	public function test_get_index_with_uneven_fields(): void {
		// 'str' has 3 entries, 'num' has only 1.
		$value = new Repeater_Value(
			array(
				'str' => array( 'a', 'b', 'c' ),
				'num' => array( 1 ),
			)
		);

		$group = $value->get_index( 1 );
		$this->assertInstanceOf( stdClass::class, $group );
		$this->assertSame( 'b', $group->str );
		$this->assertNull( $group->num );
	}

	/** @testdox It should be possible to get all the field keys */
	public function test_field_keys(): void {
		$value = ( new Repeater_Value(
			array(
				'str' => array( 'a', 'b' ),
				'num' => array( 1, 2 ),
			)
		) );

		$this->assertEquals( array( 'str', 'num' ), $value->field_keys() );
	}

	/** It should be possible to encode the values using JSON and have the raw data saved. */
	public function test_json_serialize_interface(): void {
		$fields = array(
			'str' => array( 'a', 'b' ),
			'num' => array( 1, 2 ),
		);

		$value = new Repeater_Value( $fields );
		$this->assertEquals( $fields, \json_decode( \json_encode( $value ), true ) );
	}

	/** @testdox It should be possible to recreate the values from a JSON string. */
	public function test_from_json(): void {
		$fields = array(
			'str' => array( 'a', 'b' ),
			'num' => array( 1, 2 ),
		);
		$json   = \json_encode( $fields );

		$value = Repeater_Value::from_json( $json );

		$this->assertInstanceOf( stdClass::class, $value->get_index( 0 ) );
		$this->assertEquals( 'a', $value->get_index( 0 )->str );
		$this->assertEquals( 1, $value->get_index( 0 )->num );

		$this->assertInstanceOf( stdClass::class, $value->get_index( 1 ) );
		$this->assertEquals( 'b', $value->get_index( 1 )->str );
		$this->assertEquals( 2, $value->get_index( 1 )->num );
	}
}
