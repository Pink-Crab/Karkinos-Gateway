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
use PinkCrab\Perique_Settings_Page\Setting\Field\Email;

class Test_Email extends WP_UnitTestCase {

	use Shared_Field_Cases;

	public function get_class_under_test(): string {
		return Email::class;
	}

	/** @testdox Email constructor sets sanitize_email as the default sanitiser. */
	public function test_default_sanitiser(): void {
		$field = Email::new( 'k' );
		$this->assertSame( 'a@b.com', $field->sanitize( 'a@b.com' ) );
	}
}
