<?php

declare( strict_types=1 );

/**
 * Handles WordPress admin notices for form submission results.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Handler;

class Notice_Handler {

	/**
	 * Registers admin notices based on a Submission_Result.
	 *
	 * Success results emit a single success notice. Failure results
	 * emit a single grouped error notice — if field errors are present
	 * they're rolled up into a `<ul>` inside the same notice rather
	 * than firing one notice per field.
	 *
	 * @param Submission_Result $result
	 * @return void
	 */
	public static function from_result( Submission_Result $result ): void {
		if ( $result->is_success() ) {
			self::success( $result->get_message() );
			return;
		}

		if ( ! $result->has_field_errors() ) {
			self::error( $result->get_message() );
			return;
		}

		// Build a single grouped error notice containing every field error.
		$items = '';
		foreach ( $result->get_field_errors() as $errors ) {
			foreach ( $errors as $error ) {
				$items .= sprintf( '<li>%s</li>', esc_html( $error ) );
			}
		}

		$html = sprintf(
			'<div class="notice notice-error is-dismissible"><p>%s</p><ul class="pc-settings-error-list">%s</ul></div>',
			esc_html( $result->get_message() ),
			$items
		);

		add_action(
			'admin_notices',
			static function () use ( $html ): void {
				// Already escaped above; echo as a single block so the
				// <ul> sits inside the .notice wrapper.
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		);
	}

	/**
	 * Display a success notice.
	 *
	 * @param string $message
	 * @return void
	 */
	public static function success( string $message ): void {
		add_action(
			'admin_notices',
			function () use ( $message ): void {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					wp_kses_post( $message )
				);
			}
		);
	}

	/**
	 * Display an error notice.
	 *
	 * @param string $message
	 * @return void
	 */
	public static function error( string $message ): void {
		add_action(
			'admin_notices',
			function () use ( $message ): void {
				printf(
					'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
					wp_kses_post( $message )
				);
			}
		);
	}
}
