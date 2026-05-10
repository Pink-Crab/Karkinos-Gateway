<?php

declare(strict_types=1);

/**
 * Unit tests for Form Handler
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
 * @group Application
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Application;

use stdClass;
use WP_UnitTestCase;
use Gin0115\WPUnit_Helpers\Objects;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Number;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;
use PinkCrab\Perique_Settings_Page\Handler\Repeater_Form_Value_Helper;

class Test_Repeater_Form_Value_Helper extends WP_UnitTestCase {


	public function clear_post( $key ): void {
		if ( array_key_exists( $key, $_POST ) ) {
			unset( $_POST[ $key ] );
		}
	}

	/** @testdox It should be possible to get the sort order for a repeater from the global post. This should be treated as integers, regardless of being a string in post. */
	public function test_can_get_sort_order_from_post(): void {
		// Make sure casts regardless of type in post.
		$_POST['mock_repeater']['sortorder'] = '1,0, 2';

		$helper     = new Repeater_Form_Value_Helper( Repeater::new( 'mock_repeater' ) );
		$sort_order = Objects::invoke_method( $helper, 'get_sort_order', array() );

		$this->assertCount( 3, $sort_order );
		$this->assertEquals( 1, $sort_order[0] );
		$this->assertEquals( 0, $sort_order[1] );
		$this->assertEquals( 2, $sort_order[2] );

		// CLEAR POST.
		$this->clear_post( 'mock_repeater' );
	}

	/** @testdox If no sort order is set in the global post, a range covering all existing values held for the repeater should be used. */
	public function test_can_get_sort_order_from_repeater_values_if_not_set_in_post(): void {
		$repeater   = Repeater::new( 'mock_repeater_no_post' )
			->set_value(
				new Repeater_Value(
					array(
						'one' => array( 1, 3, 6, 4, 5 ),
						'two' => array( 17, 22, 65, 45, 58 ),
					)
				)
			);
		$helper     = new Repeater_Form_Value_Helper( $repeater );
		$sort_order = Objects::invoke_method( $helper, 'get_sort_order', array() );

		$this->assertCount( 5, $sort_order );
		$this->assertEquals( 0, $sort_order[0] );
		$this->assertEquals( 1, $sort_order[1] );
		$this->assertEquals( 2, $sort_order[2] );
		$this->assertEquals( 3, $sort_order[3] );
		$this->assertEquals( 4, $sort_order[4] );
	}

	/** @testdox When falling back to using the repeaters previous values, if none are set return an empty array (no rows). */
	public function test_can_get_sort_order_of_empty_if_not_set_in_post_and_no_previous_repeater_values(): void {
		$helper     = new Repeater_Form_Value_Helper( Repeater::new( 'mock_repeater_no_values' ) );
		$sort_order = Objects::invoke_method( $helper, 'get_sort_order', array() );

		$this->assertCount( 0, $sort_order );
	}

	/** @testdox When falling back to using the repeater previous values, if the previous values are empty, return an empty array (no rows). */
	public function test_can_get_sort_order_of_empty_if_not_set_in_post_and_empty_previous_repeater_values(): void {
		$repeater   = Repeater::new( 'mock_repeater_no_post_empty' )
			->set_value(
				new Repeater_Value(
					array()
				)
			);
		$helper     = new Repeater_Form_Value_Helper( $repeater );
		$sort_order = Objects::invoke_method( $helper, 'get_sort_order', array() );

		$this->assertCount( 0, $sort_order );
	}

	/** @testdox When sortorder is present in POST but empty (form submitted with no rows), the result should be no rows. */
	public function test_can_get_sort_order_of_empty_when_post_sortorder_is_empty_string(): void {
		$_POST['empty_sortorder']['sortorder'] = '';

		$helper     = new Repeater_Form_Value_Helper( Repeater::new( 'empty_sortorder' ) );
		$sort_order = Objects::invoke_method( $helper, 'get_sort_order', array() );

		$this->assertCount( 0, $sort_order );

		$this->clear_post( 'empty_sortorder' );
	}

	/** @testdox When getting the data from the post, it should be run through the supplied sanitize callback for each value. */
	public function test_get_sanitized_values_with_sanitize_callback_set() {
		$_POST['with_sanitize']['num']  = array( 1, 2, 3 );
		$_POST['with_sanitize']['text'] = array( '1', '2', '3' );

		$repeater = Repeater::new( 'with_sanitize' )
			->add_field(
				Number::new( 'num' )
				->set_sanitize(
					function( $e ) {
						return "NUM:{$e}";
					}
				)
			)
			->add_field(
				Text::new( 'text' )
					->set_sanitize(
						function( $e ) {
							return "TEXT:{$e}";
						}
					)
			);

		$helper    = new Repeater_Form_Value_Helper( $repeater );
		$sanitized = Objects::invoke_method( $helper, 'get_sanitized_post_values', array() );

		$this->assertCount( 2, $sanitized );
		$this->assertArrayHasKey( 'num', $sanitized );
		$this->assertEquals( 'NUM:1', $sanitized['num'][0] );
		$this->assertEquals( 'NUM:2', $sanitized['num'][1] );
		$this->assertEquals( 'NUM:3', $sanitized['num'][2] );

		$this->assertArrayHasKey( 'text', $sanitized );
		$this->assertEquals( 'TEXT:1', $sanitized['text'][0] );
		$this->assertEquals( 'TEXT:2', $sanitized['text'][1] );
		$this->assertEquals( 'TEXT:3', $sanitized['text'][2] );

		// CLEAR POST.
		$this->clear_post( 'with_sanitize' );
	}

	/** @testdox When getting data from the post, if no sanitize value is passed, they should be passed as is. */
	public function test_get_sanitized_returns_values_as_is_if_no_callback(): void {
		$_POST['without_sanitize']['text'] = array( 1, 2, '<h3>3</h3>' );
		$_POST['without_sanitize']['num']  = array( '<h1>1.123456789</h1>', '2.333444', '3.00009' );

		$repeater = Repeater::new( 'without_sanitize' )
			->add_field(
				Number::new( 'num' )
					->set_decimal_places( 5 )
			)
			->add_field( Text::new( 'text' ) );

		$helper    = new Repeater_Form_Value_Helper( $repeater );
		$sanitized = Objects::invoke_method( $helper, 'get_sanitized_post_values', array() );

		$this->assertCount( 2, $sanitized );
		$this->assertArrayHasKey( 'num', $sanitized );
		$this->assertEquals( 1.12346, $sanitized['num'][0] );
		$this->assertEquals( 2.33344, $sanitized['num'][1] );
		$this->assertEquals( 3.00009, $sanitized['num'][2] );

		$this->assertArrayHasKey( 'text', $sanitized );
		$this->assertEquals( '1', $sanitized['text'][0] );
		$this->assertEquals( '2', $sanitized['text'][1] );
		$this->assertEquals( '3', $sanitized['text'][2] );

		// CLEAR POST.
		$this->clear_post( 'without_sanitize' );
	}

	/** @testdox When a repeater field is processed, the order the of values should be based on the passed sort order not the order they appear in the global post array */
	public function test_reorder_values_with_out_of_sync_values_per_field() {
		$repeater = Repeater::new( 'mock_repeater' )
			->add_field( Text::new( 'text' ) )
			->add_field( Number::new( 'number' ) );

		$_POST['mock_repeater'] = array(
			'text'      => array(
				0 => 'c',
				2 => 'b',
				1 => 'a',
			),
			'number'    => array(
				2 => 2,
				0 => 3,
				1 => 1,
			),
			'sortorder' => '2,1,0',
		);

		$helper = new Repeater_Form_Value_Helper( $repeater );
		$sorted = Objects::invoke_method( $helper, 'reorder_values', array() );

		// Check the order is maintained, but with the keys reset.
		$this->assertEquals( 'b', $sorted['text'][0] );
		$this->assertEquals( 'a', $sorted['text'][1] );
		$this->assertEquals( 'c', $sorted['text'][2] );
		$this->assertEquals( 2, $sorted['number'][0] );
		$this->assertEquals( 1, $sorted['number'][1] );
		$this->assertEquals( 3, $sorted['number'][2] );

		// CLEAR POST.
		$this->clear_post( 'mock_repeater' );
	}

	 /** @testdox When a repeater field is processed, any fields which are not submitted (and not included in the global post) should be set to null */
	public function test_reorder_values_with_missing_values() {
		$repeater = Repeater::new( 'mock_repeater' )
			->add_field( Text::new( 'text' ) )
			->add_field( Number::new( 'number' ) );

		$_POST['mock_repeater'] = array(
			'text'      => array(
				0 => 'c',
				1 => 'a',
			),
			'number'    => array(
				2 => 2,
				1 => 1,
			),
			'sortorder' => '2,1,0',
		);

		$helper = new Repeater_Form_Value_Helper( $repeater );
		$sorted = Objects::invoke_method( $helper, 'reorder_values', array() );

		// Check the order is maintained, but with the keys reset.
		$this->assertEquals( null, $sorted['text'][0] );
		$this->assertEquals( 'a', $sorted['text'][1] );
		$this->assertEquals( 'c', $sorted['text'][2] );
		$this->assertEquals( 2, $sorted['number'][0] );
		$this->assertEquals( 1, $sorted['number'][1] );
		$this->assertEquals( null, $sorted['number'][2] );

		// CLEAR POST.
		$this->clear_post( 'mock_repeater' );
	}

	/** @testdox When a repeater field is processed, should have no problems if an entire field is passed as null in the post  */
	public function test_reorder_values_with_one_field_with_no_values() {
		$repeater = Repeater::new( 'mock_repeater' )
			->add_field( Text::new( 'text' ) )
			->add_field( Number::new( 'number' ) );

		$_POST['mock_repeater'] = array(
			'text'      => array(
				0 => 'foo',
				1 => 'bar',
			),
			'number'    => null,
			'sortorder' => '0,1',
		);

		$helper = new Repeater_Form_Value_Helper( $repeater );
		$sorted = Objects::invoke_method( $helper, 'reorder_values', array() );

		// Check the order is maintained, but with the keys reset.
		$this->assertEquals( 'foo', $sorted['text'][0] );
		$this->assertEquals( 'bar', $sorted['text'][1] );
		$this->assertEquals( null, $sorted['number'][0] );
		$this->assertEquals( null, $sorted['number'][1] );

		// CLEAR POST.
		$this->clear_post( 'mock_repeater' );
	}

	/** @testdox When a repeater field is processed, should have no problems if an entire field is missing in the post. It should still be populated with all values as null.  */
	public function test_sort_repeater_values_with_field_missing() {
		$repeater = Repeater::new( 'mock_repeater' )
			->add_field( Text::new( 'text' ) )
			->add_field( Number::new( 'number' ) );

		$_POST['mock_repeater'] = array(
			'text'   => array(
				0 => 'apple',
				1 => 'pear',
			),
			'sortorder' => '0,1',
		);

		$helper = new Repeater_Form_Value_Helper( $repeater );
		$sorted = Objects::invoke_method( $helper, 'reorder_values', array() );

		// Check the order is maintained, but with the keys reset.
		$this->assertEquals( 'apple', $sorted['text'][0] );
		$this->assertEquals( 'pear', $sorted['text'][1] );
		$this->assertEquals( null, $sorted['number'][0] );
		$this->assertEquals( null, $sorted['number'][1] );

		// CLEAR POST.
		$this->clear_post( 'mock_repeater' );
	}

	/** @testdox It should be possible to create a populated Repeater_Value object based on a repeater field and the matching post data. */
	public function test_can_populate_repeater_value() {
		$repeater = Repeater::new( 'mock_repeater' )
			->add_field( Text::new( 'text' ) )
			->add_field( Number::new( 'number' ) );

		$_POST['mock_repeater'] = array(
			'text'      => array(
				0 => 'c',
				2 => 'b',
				1 => 'a',
			),
			'number'    => array(
				2 => 2,
				0 => 3,
				1 => 1,
			),
			'sortorder' => '2,1,0',
		);

		$helper = new Repeater_Form_Value_Helper( $repeater );

		// Process as indexed.
		$indexed = $helper->process()->as_indexed();

		$this->assertCount(3, $indexed);

		$this->assertInstanceOf(stdClass::class, $indexed[0]);
		$this->assertEquals('b', $indexed[0]->text);
		$this->assertEquals(2, $indexed[0]->number);

		$this->assertInstanceOf(stdClass::class, $indexed[1]);
		$this->assertEquals('a', $indexed[1]->text);
		$this->assertEquals(1, $indexed[1]->number);
		
		$this->assertInstanceOf(stdClass::class, $indexed[2]);
		$this->assertEquals('c', $indexed[2]->text);
		$this->assertEquals(3, $indexed[2]->number);

		// CLEAR POST.
		$this->clear_post( 'mock_repeater' );
	}

	/** @testdox When the repeater has no child fields, get_sanitized_post_values returns an empty array. */
	public function test_get_sanitized_values_with_no_fields(): void {
		// Repeater with no add_field() calls — get_fields() returns null.
		$repeater  = Repeater::new( 'no_fields' );
		$helper    = new Repeater_Form_Value_Helper( $repeater );
		$sanitized = Objects::invoke_method( $helper, 'get_sanitized_post_values', array() );
		$this->assertSame( array(), $sanitized );
	}

	/** @testdox When sort order is empty, reorder_values returns the empty-initialised data. */
	public function test_reorder_values_with_empty_sort_order(): void {
		$repeater = Repeater::new( 'empty_sort' )->add_field( Text::new( 'name' ) );
		$_POST['empty_sort'] = array( 'sortorder' => '' );

		$helper   = new Repeater_Form_Value_Helper( $repeater );
		$reordered = Objects::invoke_method( $helper, 'reorder_values', array() );

		// With an empty sort_order, the per-key arrays should be empty.
		$this->assertArrayHasKey( 'name', $reordered );
		$this->assertSame( array(), $reordered['name'] );

		$this->clear_post( 'empty_sort' );
	}

}
