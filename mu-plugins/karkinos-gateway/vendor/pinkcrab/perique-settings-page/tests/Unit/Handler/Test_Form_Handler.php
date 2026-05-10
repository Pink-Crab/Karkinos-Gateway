<?php

declare( strict_types=1 );

/**
 * Unit tests for the Form_Handler class.
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
use PinkCrab\Perique_Settings_Page\Handler\Form_Handler;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Object_Setting_Repository;
use PinkCrab\Perique_Settings_Page\Tests\Fixtures\Mock_Abstract_Settings;

class Test_Form_Handler extends WP_UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Mock_Abstract_Settings::$injected_fields = array();
		Mock_Abstract_Settings::$grouped         = false;
		Mock_Abstract_Settings::$group_key       = 'mock_settings';
		$_POST = array();
		$_GET  = array();
	}

	protected function tearDown(): void {
		$_POST = array();
		$_GET  = array();
		parent::tearDown();
	}

	protected function build( Object_Setting_Repository $repo, ...$fields ): Form_Handler {
		$settings = Mock_Abstract_Settings::with_fields( $repo, ...$fields );
		return new Form_Handler( $settings, 'test-page' );
	}

	/** @testdox process() returns no_submission when the page param is missing. */
	public function test_no_submission_when_page_missing(): void {
		$handler = $this->build( new Object_Setting_Repository(), Text::new( 'name' ) );
		$result  = $handler->process();
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'No submission to process.', $result->get_message() );
	}

	/** @testdox process() returns no_submission when the page param does not match the slug. */
	public function test_no_submission_when_slug_mismatch(): void {
		$_POST['page'] = 'wrong';
		$handler       = $this->build( new Object_Setting_Repository(), Text::new( 'name' ) );
		$result        = $handler->process();
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'No submission to process.', $result->get_message() );
	}

	/** @testdox process() returns nonce_failed when the nonce field is missing. */
	public function test_nonce_failed_when_nonce_missing(): void {
		$_POST['page'] = 'test-page';
		$handler       = $this->build( new Object_Setting_Repository(), Text::new( 'name' ) );
		$result        = $handler->process();
		$this->assertFalse( $result->is_success() );
	}

	/** @testdox process() returns nonce_failed when the nonce is invalid. */
	public function test_nonce_failed_when_invalid(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = 'invalid-nonce';
		$handler                    = $this->build( new Object_Setting_Repository(), Text::new( 'name' ) );
		$result                     = $handler->process();
		$this->assertFalse( $result->is_success() );
	}

	/** @testdox process() returns persistence_failed when there are no fields. */
	public function test_persistence_failed_when_no_fields(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$handler                    = $this->build( new Object_Setting_Repository() );
		$result                     = $handler->process();
		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'No fields', $result->get_message() );
	}

	/** @testdox process() returns success when fields validate and persist. */
	public function test_success_path(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['name']              = 'value';

		$handler = $this->build( new Object_Setting_Repository(), Text::new( 'name' ) );
		$result  = $handler->process();
		$this->assertTrue( $result->is_success() );
	}

	/** @testdox process() returns validation_failed when a field fails validation. */
	public function test_validation_failed(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['name']              = 'wrong';

		$field = Text::new( 'name' )->set_validate( fn( $v ): bool => 'expected' === $v );

		$handler = $this->build( new Object_Setting_Repository(), $field );
		$result  = $handler->process();
		$this->assertFalse( $result->is_success() );
		$this->assertTrue( $result->has_field_errors() );
		$this->assertArrayHasKey( 'name', $result->get_field_errors() );
	}

	/** @testdox process() handles a Field_Group via sanitize() and persistence. */
	public function test_field_group_processed(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['address']           = array(
			'line_1' => '1 Foo St',
			'city'   => 'London',
		);

		$repo  = new Object_Setting_Repository();
		$group = Field_Group::of( 'address', Text::new( 'line_1' ), Text::new( 'city' ) );
		$handler = $this->build( $repo, $group );

		$result = $handler->process();
		$this->assertTrue( $result->is_success() );
		$this->assertArrayHasKey( 'mock_settings_address', $repo->store );
	}

	/** @testdox process() returns validation errors for a Field_Group with failing children. */
	public function test_field_group_validation_failure(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['address']           = array(
			'line_1' => 'wrong',
		);

		$line_1 = Text::new( 'line_1' )->set_validate( fn( $v ): bool => 'expected' === $v );
		$group  = Field_Group::of( 'address', $line_1 );

		$handler = $this->build( new Object_Setting_Repository(), $group );
		$result  = $handler->process();
		$this->assertFalse( $result->is_success() );
		$this->assertTrue( $result->has_field_errors() );
	}

	/** @testdox process() handles a Repeater field. */
	public function test_repeater_processed(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['rep']               = array( 'sortorder' => '0' );

		$repeater = Repeater::new( 'rep' )->add_field( Text::new( 'child' ) );
		$repo     = new Object_Setting_Repository();
		$handler  = $this->build( $repo, $repeater );

		$result = $handler->process();
		$this->assertTrue( $result->is_success() );
	}

	/** @testdox GET method reads from $_GET instead of $_POST. */
	public function test_get_method(): void {
		$_GET['page']              = 'test-page';
		$_GET['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_GET['name']              = 'value';

		$settings = Mock_Abstract_Settings::with_fields( new Object_Setting_Repository(), Text::new( 'name' ) );
		$handler  = new Form_Handler( $settings, 'test-page', 'GET' );
		$result   = $handler->process();
		$this->assertTrue( $result->is_success() );
	}

	/** @testdox Custom nonce handle is honoured. */
	public function test_custom_nonce_handle(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'my_custom_handle' );
		$_POST['name']              = 'value';

		$settings = Mock_Abstract_Settings::with_fields( new Object_Setting_Repository(), Text::new( 'name' ) );
		$handler  = new Form_Handler( $settings, 'test-page', 'POST', 'my_custom_handle' );
		$result   = $handler->process();
		$this->assertTrue( $result->is_success() );
	}

	/** @testdox Custom nonce field name is honoured. */
	public function test_custom_nonce_field_name(): void {
		$_POST['page']         = 'test-page';
		$_POST['my_nonce']     = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['name']         = 'value';

		$settings = Mock_Abstract_Settings::with_fields( new Object_Setting_Repository(), Text::new( 'name' ) );
		$handler  = new Form_Handler( $settings, 'test-page', 'POST', '', 'my_nonce' );
		$result   = $handler->process();
		$this->assertTrue( $result->is_success() );
	}

	/** @testdox A missing field value is treated as empty string. */
	public function test_missing_field_value(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		// No 'name' in POST.

		$repo = new Object_Setting_Repository();
		$settings = Mock_Abstract_Settings::with_fields( $repo, Text::new( 'name' ) );
		$handler  = new Form_Handler( $settings, 'test-page' );

		$result = $handler->process();
		$this->assertTrue( $result->is_success() );
		$this->assertSame( '', $repo->store['mock_settings_name'] );
	}

	/** @testdox When the repository fails to persist, validation_failed is returned with field errors. */
	public function test_persistence_failure_returns_field_errors(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['name']              = 'value';

		$repo = new Object_Setting_Repository();
		// Make the next set() call return false.
		Object_Setting_Repository::$return_value = false;

		$settings = Mock_Abstract_Settings::with_fields( $repo, Text::new( 'name' )->set_label( 'My Name' ) );
		$handler  = new Form_Handler( $settings, 'test-page' );

		$result = $handler->process();
		$this->assertFalse( $result->is_success() );
		$this->assertTrue( $result->has_field_errors() );
		$this->assertArrayHasKey( 'name', $result->get_field_errors() );
	}

	/** @testdox get_field_value falls back to the sanitized key when the original is missing. */
	public function test_get_field_value_uses_sanitized_key(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		// Storage key is 'My Field' (with space) — sanitize_title produces 'my-field'.
		$storage_key      = 'My Field';
		$_POST['my-field'] = 'sanitized-value';

		$repo     = new Object_Setting_Repository();
		$settings = Mock_Abstract_Settings::with_fields( $repo, Text::new( $storage_key ) );
		$handler  = new Form_Handler( $settings, 'test-page' );

		$result = $handler->process();
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'sanitized-value', $repo->store[ 'mock_settings_' . $storage_key ] );
	}

	/** @testdox get_field_value uses the key_map to translate form names to storage keys. */
	public function test_get_field_value_uses_key_map(): void {
		$_POST['page']              = 'test-page';
		$_POST['pc_settings_nonce'] = wp_create_nonce( 'perique_settings_page_test-page' );
		$_POST['form_field_name']   = 'mapped';

		$repo     = new Object_Setting_Repository();
		$settings = Mock_Abstract_Settings::with_fields( $repo, Text::new( 'storage_key' ) );
		$handler  = new Form_Handler( $settings, 'test-page', 'POST', '', 'pc_settings_nonce', array( 'form_field_name' => 'storage_key' ) );

		$result = $handler->process();
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'mapped', $repo->store['mock_settings_storage_key'] );
	}
}
