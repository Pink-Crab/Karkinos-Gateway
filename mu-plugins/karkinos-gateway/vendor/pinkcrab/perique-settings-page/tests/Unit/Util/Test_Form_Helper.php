<?php

declare( strict_types=1 );

/**
 * Unit tests for the Form_Helper class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Util
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Util;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Util\Form_Helper;

class Test_Form_Helper extends WP_UnitTestCase {

	public function tearDown(): void {
		remove_all_actions( 'admin_notices' );
		parent::tearDown();
	}

	/** @testdox nonce_handle() generates a prefixed handle from a page slug. */
	public function test_nonce_handle(): void {
		$this->assertSame( 'perique_settings_page_my-page', Form_Helper::nonce_handle( 'my-page' ) );
	}

	/** @testdox nonce_handle() handles an empty slug. */
	public function test_nonce_handle_with_empty_slug(): void {
		$this->assertSame( 'perique_settings_page_', Form_Helper::nonce_handle( '' ) );
	}

	/** @testdox success_notice() registers an admin_notices action. */
	public function test_success_notice_registers_action(): void {
		Form_Helper::success_notice( 'Saved' );
		$this->assertNotFalse( has_action( 'admin_notices' ) );
	}

	/** @testdox success_notice() outputs a success notice with the message. */
	public function test_success_notice_outputs_message(): void {
		Form_Helper::success_notice( 'Saved' );
		ob_start();
		do_action( 'admin_notices' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Saved', $output );
	}

	/** @testdox error_notice() registers an admin_notices action. */
	public function test_error_notice_registers_action(): void {
		Form_Helper::error_notice( 'Failed' );
		$this->assertNotFalse( has_action( 'admin_notices' ) );
	}

	/** @testdox error_notice() outputs an error notice with the message. */
	public function test_error_notice_outputs_message(): void {
		Form_Helper::error_notice( 'Failed' );
		ob_start();
		do_action( 'admin_notices' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice-error', $output );
		$this->assertStringContainsString( 'Failed', $output );
	}

	/** @testdox success_notice() escapes HTML in the message. */
	public function test_success_notice_escapes_html(): void {
		Form_Helper::success_notice( '<script>alert(1)</script>' );
		ob_start();
		do_action( 'admin_notices' );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}
}
