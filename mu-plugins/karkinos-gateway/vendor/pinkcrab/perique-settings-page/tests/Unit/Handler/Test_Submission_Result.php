<?php

declare( strict_types=1 );

/**
 * Unit tests for the Submission_Result value object.
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
use PinkCrab\Perique_Settings_Page\Handler\Submission_Result;

class Test_Submission_Result extends WP_UnitTestCase {

	/** @testdox success() returns a successful result with the default message. */
	public function test_success_default(): void {
		$result = Submission_Result::success();
		$this->assertTrue( $result->is_success() );
		$this->assertNotEmpty( $result->get_message() );
	}

	/** @testdox success() accepts a custom message. */
	public function test_success_custom_message(): void {
		$result = Submission_Result::success( 'Saved!' );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Saved!', $result->get_message() );
	}

	/** @testdox nonce_failed() returns an unsuccessful result. */
	public function test_nonce_failed(): void {
		$result = Submission_Result::nonce_failed();
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_message() );
		$this->assertFalse( $result->has_field_errors() );
	}

	/** @testdox validation_failed() stores per-field errors. */
	public function test_validation_failed(): void {
		$errors = array( 'name' => array( 'Required' ) );
		$result = Submission_Result::validation_failed( $errors );
		$this->assertFalse( $result->is_success() );
		$this->assertTrue( $result->has_field_errors() );
		$this->assertSame( $errors, $result->get_field_errors() );
	}

	/** @testdox persistence_failed() returns an unsuccessful result. */
	public function test_persistence_failed_default(): void {
		$result = Submission_Result::persistence_failed();
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_message() );
	}

	/** @testdox persistence_failed() accepts a custom message. */
	public function test_persistence_failed_custom(): void {
		$result = Submission_Result::persistence_failed( 'Custom failure' );
		$this->assertSame( 'Custom failure', $result->get_message() );
	}

	/** @testdox no_submission() returns an unsuccessful result. */
	public function test_no_submission(): void {
		$result = Submission_Result::no_submission();
		$this->assertFalse( $result->is_success() );
	}

	/** @testdox has_field_errors() returns false when no errors stored. */
	public function test_has_field_errors_false_by_default(): void {
		$this->assertFalse( Submission_Result::success()->has_field_errors() );
	}

	/** @testdox get_errors_for() returns errors for a specific field. */
	public function test_get_errors_for_existing_field(): void {
		$errors = array( 'name' => array( 'Required', 'Too short' ) );
		$result = Submission_Result::validation_failed( $errors );
		$this->assertSame( array( 'Required', 'Too short' ), $result->get_errors_for( 'name' ) );
	}

	/** @testdox get_errors_for() returns empty array for an unknown field. */
	public function test_get_errors_for_missing_field(): void {
		$result = Submission_Result::validation_failed( array() );
		$this->assertSame( array(), $result->get_errors_for( 'missing' ) );
	}
}
