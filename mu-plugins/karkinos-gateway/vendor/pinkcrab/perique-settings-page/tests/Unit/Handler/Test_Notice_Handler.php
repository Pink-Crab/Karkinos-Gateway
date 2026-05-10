<?php

declare( strict_types=1 );

/**
 * Unit tests for the Notice_Handler class.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 *
 * @group Unit
 * @group Handler
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Unit\Handler;

use WP_UnitTestCase;
use PinkCrab\Perique_Settings_Page\Handler\Notice_Handler;
use PinkCrab\Perique_Settings_Page\Handler\Submission_Result;

class Test_Notice_Handler extends WP_UnitTestCase {

	public function tearDown(): void {
		remove_all_actions( 'admin_notices' );
		parent::tearDown();
	}

	protected function render_notices(): string {
		ob_start();
		do_action( 'admin_notices' );
		return (string) ob_get_clean();
	}

	/** @testdox from_result() with a successful result outputs a success notice. */
	public function test_from_result_success(): void {
		Notice_Handler::from_result( Submission_Result::success( 'Saved' ) );
		$output = $this->render_notices();
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Saved', $output );
	}

	/** @testdox from_result() with an unsuccessful result outputs an error notice. */
	public function test_from_result_failure(): void {
		Notice_Handler::from_result( Submission_Result::nonce_failed() );
		$output = $this->render_notices();
		$this->assertStringContainsString( 'notice-error', $output );
	}

	/** @testdox from_result() groups all field errors into a single notice. */
	public function test_from_result_with_field_errors(): void {
		Notice_Handler::from_result(
			Submission_Result::validation_failed(
				array(
					'name'  => array( 'Name is required' ),
					'email' => array( 'Invalid email' ),
				)
			)
		);
		$output = $this->render_notices();

		// Only one error notice is emitted — not one per field.
		$this->assertSame( 1, substr_count( $output, 'notice-error' ) );

		// All field errors live inside that single notice as <li> items.
		$this->assertStringContainsString( '<ul class="pc-settings-error-list">', $output );
		$this->assertStringContainsString( '<li>Name is required</li>', $output );
		$this->assertStringContainsString( '<li>Invalid email</li>', $output );

		// The main message is the notice header.
		$this->assertStringContainsString( 'Validation failed', $output );
	}

	/** @testdox from_result() with multiple errors on the same field lists them all. */
	public function test_from_result_with_multiple_errors_for_one_field(): void {
		Notice_Handler::from_result(
			Submission_Result::validation_failed(
				array(
					'name' => array( 'Too short', 'Contains invalid characters' ),
				)
			)
		);
		$output = $this->render_notices();

		$this->assertSame( 1, substr_count( $output, 'notice-error' ) );
		$this->assertStringContainsString( '<li>Too short</li>', $output );
		$this->assertStringContainsString( '<li>Contains invalid characters</li>', $output );
	}

	/** @testdox from_result() failure without field errors emits a single plain error notice. */
	public function test_from_result_failure_without_field_errors(): void {
		Notice_Handler::from_result( Submission_Result::nonce_failed() );
		$output = $this->render_notices();

		$this->assertSame( 1, substr_count( $output, 'notice-error' ) );
		// No error list wrapper — this is the "no field errors" path.
		$this->assertStringNotContainsString( 'pc-settings-error-list', $output );
	}

	/** @testdox success() outputs a success notice with the message. */
	public function test_success_static(): void {
		Notice_Handler::success( 'Done' );
		$output = $this->render_notices();
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Done', $output );
	}

	/** @testdox error() outputs an error notice with the message. */
	public function test_error_static(): void {
		Notice_Handler::error( 'Failed' );
		$output = $this->render_notices();
		$this->assertStringContainsString( 'notice-error', $output );
		$this->assertStringContainsString( 'Failed', $output );
	}
}
