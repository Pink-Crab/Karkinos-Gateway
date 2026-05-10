<?php

declare( strict_types=1 );

/**
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Field
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Field;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Setting\Field\Post_Picker;

class Test_Post_Picker extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Post_Picker::class;
	}

	/** @testdox set_post_type stores the post type in query args. */
	public function test_set_post_type(): void {
		$field = Post_Picker::new( 'k' )->set_post_type( 'page' );
		$this->assertSame( 'page', $field->get_post_type() );
	}

	/** @testdox get_post_type defaults to "post". */
	public function test_default_post_type(): void {
		$this->assertSame( 'post', Post_Picker::new( 'k' )->get_post_type() );
	}

	/** @testdox get_option_label returns a callable that returns the post title. */
	public function test_default_option_label(): void {
		$post_id  = $this->factory->post->create( array( 'post_title' => 'Hello' ) );
		$post     = get_post( $post_id );
		$callback = Post_Picker::new( 'k' )->get_option_label();
		$this->assertSame( 'Hello', $callback( $post ) );
	}

	/** @testdox get_option_value returns a callable that returns the post ID as a string. */
	public function test_default_option_value(): void {
		$post_id  = $this->factory->post->create();
		$post     = get_post( $post_id );
		$callback = Post_Picker::new( 'k' )->get_option_value();
		$this->assertSame( (string) $post_id, $callback( $post ) );
	}
}
