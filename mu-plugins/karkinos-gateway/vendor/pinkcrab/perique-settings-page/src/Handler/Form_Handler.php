<?php

declare( strict_types=1 );

/**
 * Handles form submissions for settings pages.
 *
 * Verifies the nonce, extracts values from the request, validates
 * and sanitises each field, then persists via Abstract_Settings.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Handler;

use PinkCrab\Perique_Settings_Page\Util\Cast;
use PinkCrab\Perique_Settings_Page\Util\Form_Helper;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field_Group;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;

class Form_Handler {

	/**
	 * The settings data model.
	 *
	 * @var Abstract_Settings
	 */
	protected Abstract_Settings $settings;

	/**
	 * The page slug.
	 *
	 * @var string
	 */
	protected string $slug;

	/**
	 * The HTTP method (POST or GET).
	 *
	 * @var string
	 */
	protected string $method;

	/**
	 * The nonce handle.
	 *
	 * @var string
	 */
	protected string $nonce_handle;

	/**
	 * The nonce field name in the request.
	 *
	 * @var string
	 */
	protected string $nonce_field_name;

	/**
	 * Mapping of sanitised form names back to storage keys.
	 *
	 * @var array<string, string>
	 */
	protected array $key_map;

	/**
	 * @param array<string, string> $key_map
	 */
	public function __construct(
		Abstract_Settings $settings,
		string $slug,
		string $method = 'POST',
		string $nonce_handle = '',
		string $nonce_field_name = 'pc_settings_nonce',
		array $key_map = array()
	) {
		$this->settings         = $settings;
		$this->slug             = $slug;
		$this->method           = strtoupper( $method );
		$this->nonce_handle     = '' !== $nonce_handle ? $nonce_handle : Form_Helper::nonce_handle( $slug );
		$this->nonce_field_name = $nonce_field_name;
		$this->key_map          = $key_map;
	}

	/**
	 * Process the form submission.
	 *
	 * @return Submission_Result
	 */
	public function process(): Submission_Result {
		$request_data = $this->get_request_data();

		// Check if this is a valid submission for this page.
		if ( ! $this->is_valid_request( $request_data ) ) {
			return Submission_Result::no_submission();
		}

		// Verify nonce.
		if ( ! $this->verify_nonce( $request_data ) ) {
			return Submission_Result::nonce_failed();
		}

		// Get all fields (including nested in layouts).
		$all_fields = $this->settings->get_all_fields();

		if ( empty( $all_fields ) ) {
			return Submission_Result::persistence_failed(
				__( 'No fields found to process.', 'perique-settings-page' )
			);
		}

		// Validate all fields first, collect errors.
		// Note: Field_Group is not a Field but is handled inside validate_all_fields().
		$field_errors = $this->validate_all_fields( $all_fields, $request_data );
		if ( ! empty( $field_errors ) ) {
			return Submission_Result::validation_failed( $field_errors );
		}

		// Sanitise and persist each field.
		$saved  = false;
		$errors = array();

		foreach ( $all_fields as $key => $field ) {
			if ( $field instanceof Repeater ) {
				$this->update_repeater( $field );
				$saved = true;
				continue;
			}

			if ( $field instanceof Field_Group ) {
				$raw_group = $this->get_field_value( $key, $request_data );
				$raw_group = is_array( $raw_group ) ? $raw_group : array();
				$sanitised = $field->sanitize( $raw_group );
				$result    = $this->settings->set( $key, $sanitised );
				if ( $result ) {
					$saved = true;
				}
				continue;
			}

			$raw_value = $this->get_field_value( $key, $request_data );
			$sanitised = $field->sanitize( $raw_value );
			$result    = $this->settings->set( $key, $sanitised );

			if ( $result ) {
				$saved = true;
			} else {
				$errors[ $key ] = array(
					sprintf(
						/* translators: %s: field label or key */
						__( 'Failed to save "%s".', 'perique-settings-page' ),
						'' !== $field->get_label() ? $field->get_label() : $key
					),
				);
			}
		}

		if ( ! empty( $errors ) ) {
			return Submission_Result::validation_failed( $errors );
		}

		return $saved
			? Submission_Result::success()
			: Submission_Result::persistence_failed(
				__( 'No values were changed.', 'perique-settings-page' )
			);
	}

	/**
	 * Get the request data based on the HTTP method.
	 *
	 * @return array<string, mixed>
	 */
	protected function get_request_data(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Nonce is verified in verify_nonce().
		return 'GET' === $this->method ? $_GET : $_POST;
	}

	/**
	 * Check if the request is for this page.
	 *
	 * @param array<string, mixed> $request_data
	 * @return bool
	 */
	protected function is_valid_request( array $request_data ): bool {
		if ( ! array_key_exists( 'page', $request_data ) ) {
			return false;
		}

		return \sanitize_text_field( Cast::to_string( $request_data['page'] ) ?? '' ) === $this->slug;
	}

	/**
	 * Verify the nonce.
	 *
	 * @param array<string, mixed> $request_data
	 * @return bool
	 */
	protected function verify_nonce( array $request_data ): bool {
		if ( ! array_key_exists( $this->nonce_field_name, $request_data ) ) {
			return false;
		}

		return (bool) \wp_verify_nonce(
			\sanitize_text_field( Cast::to_string( $request_data[ $this->nonce_field_name ] ) ?? '' ),
			$this->nonce_handle
		);
	}

	/**
	 * Validate all fields and collect errors.
	 *
	 * @param array<string, Field|Field_Group> $fields
	 * @param array<string, mixed>             $request_data
	 * @return array<string, array<int, string>> Field key => error messages
	 */
	protected function validate_all_fields( array $fields, array $request_data ): array {
		$errors = array();

		foreach ( $fields as $key => $field ) {
			if ( $field instanceof Repeater ) {
				continue;
			}

			if ( $field instanceof Field_Group ) {
				$raw_group    = $this->get_field_value( $key, $request_data );
				$raw_group    = is_array( $raw_group ) ? $raw_group : array();
				$group_errors = $field->validate( $raw_group );
				foreach ( $group_errors as $child_key => $error ) {
					$errors[ $key . '[' . $child_key . ']' ] = array( $error );
				}
				continue;
			}

			$raw_value = $this->get_field_value( $key, $request_data );

			if ( ! $field->validate( $raw_value ) ) {
				$label          = '' !== $field->get_label() ? $field->get_label() : $key;
				$errors[ $key ] = array(
					sprintf(
						/* translators: %s: field label or key */
						__( 'Validation failed for %s.', 'perique-settings-page' ),
						$label
					),
				);
			}
		}

		return $errors;
	}

	/**
	 * Get a field's value from the request data, handling key map translation.
	 *
	 * @param string               $key
	 * @param array<string, mixed> $request_data
	 * @return mixed
	 */
	protected function get_field_value( string $key, array $request_data ) {
		// Try the original key first.
		if ( array_key_exists( $key, $request_data ) ) {
			return $request_data[ $key ];
		}

		// Try the sanitised version (Form Components mangles underscores to hyphens).
		$sanitised = \sanitize_title( $key );
		if ( array_key_exists( $sanitised, $request_data ) ) {
			return $request_data[ $sanitised ];
		}

		// Check the reverse key map.
		foreach ( $this->key_map as $form_name => $storage_key ) {
			if ( $storage_key === $key && array_key_exists( $form_name, $request_data ) ) {
				return $request_data[ $form_name ];
			}
		}

		// Missing field (e.g. unchecked checkbox).
		return '';
	}

	/**
	 * Update a repeater field from the request data.
	 *
	 * @param Repeater $repeater
	 * @return void
	 */
	protected function update_repeater( Repeater $repeater ): void {
		$this->settings->set(
			$repeater->get_key(),
			( new Repeater_Form_Value_Helper( $repeater ) )->process()
		);
	}
}
