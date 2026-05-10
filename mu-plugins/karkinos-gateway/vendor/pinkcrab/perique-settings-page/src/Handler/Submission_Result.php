<?php

declare( strict_types=1 );

/**
 * Immutable value object representing the result of a form submission.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Handler;

class Submission_Result {

	/**
	 * Whether the submission was successful.
	 *
	 * @var bool
	 */
	protected bool $success;

	/**
	 * A human-readable message.
	 *
	 * @var string
	 */
	protected string $message;

	/**
	 * Per-field error messages.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $field_errors;

	/**
	 * @param bool   $success
	 * @param string $message
	 * @param array<string, array<int, string>> $field_errors
	 */
	protected function __construct( bool $success, string $message, array $field_errors = array() ) {
		$this->success      = $success;
		$this->message      = $message;
		$this->field_errors = $field_errors;
	}

	/**
	 * Create a success result.
	 *
	 * @param string $message
	 * @return static
	 */
	public static function success( ?string $message = null ): static {
		return new static( true, $message ?? __( 'Settings saved.', 'perique-settings-page' ) );
	}

	/**
	 * Create a nonce failure result.
	 *
	 * @return static
	 */
	public static function nonce_failed(): static {
		return new static( false, __( 'Invalid or missing nonce.', 'perique-settings-page' ) );
	}

	/**
	 * Create a validation failure result.
	 *
	 * @param array<string, array<int, string>> $field_errors
	 * @return static
	 */
	public static function validation_failed( array $field_errors ): static {
		return new static( false, __( 'Validation failed.', 'perique-settings-page' ), $field_errors );
	}

	/**
	 * Create a persistence failure result.
	 *
	 * @param string|null $message
	 * @return static
	 */
	public static function persistence_failed( ?string $message = null ): static {
		return new static( false, $message ?? __( 'Failed to save settings.', 'perique-settings-page' ) );
	}

	/**
	 * Create a result for when there is no submission to process.
	 *
	 * @return static
	 */
	public static function no_submission(): static {
		return new static( false, __( 'No submission to process.', 'perique-settings-page' ) );
	}

	/**
	 * Whether the submission was successful.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Get the message.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Get per-field error messages.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function get_field_errors(): array {
		return $this->field_errors;
	}

	/**
	 * Whether there are field-level errors.
	 *
	 * @return bool
	 */
	public function has_field_errors(): bool {
		return ! empty( $this->field_errors );
	}

	/**
	 * Get errors for a specific field.
	 *
	 * @param string $key
	 * @return array<int, string>
	 */
	public function get_errors_for( string $key ): array {
		return $this->field_errors[ $key ] ?? array();
	}
}
