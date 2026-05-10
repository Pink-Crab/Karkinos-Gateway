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
use PinkCrab\Perique_Settings_Page\Setting\Field\User_Picker;

class Test_User_Picker extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return User_Picker::class;
	}

	/** @testdox set_role stores the role. */
	public function test_set_role(): void {
		$field = User_Picker::new( 'k' )->set_role( 'editor' );
		$this->assertSame( 'editor', $field->get_role() );
	}

	/** @testdox Default role is empty string. */
	public function test_default_role(): void {
		$this->assertSame( '', User_Picker::new( 'k' )->get_role() );
	}

	/** @testdox get_option_label returns the user display_name by default. */
	public function test_default_option_label(): void {
		$user_id  = $this->factory->user->create( array( 'display_name' => 'Jane Doe' ) );
		$user     = get_user_by( 'id', $user_id );
		$callback = User_Picker::new( 'k' )->get_option_label();
		$this->assertSame( 'Jane Doe', $callback( $user ) );
	}

	/** @testdox get_option_value returns the user ID as a string by default. */
	public function test_default_option_value(): void {
		$user_id  = $this->factory->user->create();
		$user     = get_user_by( 'id', $user_id );
		$callback = User_Picker::new( 'k' )->get_option_value();
		$this->assertSame( (string) $user_id, $callback( $user ) );
	}

	/** @testdox set_option_label stores a custom label callback. */
	public function test_set_option_label(): void {
		$callback = fn( $u ): string => 'custom';
		$field    = User_Picker::new( 'k' )->set_option_label( $callback );
		$this->assertSame( $callback, $field->get_option_label() );
	}

	/** @testdox set_option_value stores a custom value callback. */
	public function test_set_option_value(): void {
		$callback = fn( $u ): string => 'custom';
		$field    = User_Picker::new( 'k' )->set_option_value( $callback );
		$this->assertSame( $callback, $field->get_option_value() );
	}
}
