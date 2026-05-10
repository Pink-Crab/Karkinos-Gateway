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
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;

class Test_Text extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Text::class;
	}

	/** @testdox Text constructor sets sanitize_text_field as the default sanitiser. */
	public function test_default_sanitiser(): void {
		$field = Text::new( 'k' );
		$this->assertSame( 'cleaned', $field->sanitize( '<p>cleaned</p>' ) );
	}
}
