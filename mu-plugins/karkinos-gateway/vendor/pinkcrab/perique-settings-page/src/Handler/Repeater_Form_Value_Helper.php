<?php

declare(strict_types=1);

/**
 * Helper class for getting and parsing Repeater values when handling form submission
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Handler;

use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater;
use PinkCrab\Perique_Settings_Page\Setting\Field\Repeater_Value;

class Repeater_Form_Value_Helper {

	/**
	 * The repeater field.
	 *
	 * @var Repeater
	 */
	protected $repeater;

	/**
	 * The values as found in the current $_POST
	 *
	 * @var array<string, mixed>
	 */
	protected array $raw_post;

    // phpcs:ignore WordPress.Security.NonceVerification.Missing
	public function __construct( Repeater $repeater ) {
		$this->repeater = $repeater;

		// Set from Post.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_data = $_POST[ $repeater->get_key() ] ?? array();
		$this->raw_post = is_array( $post_data ) ? $post_data : array();
	}

	/**
	 * Processes the current global POST and generates
	 * a populated Repeater_Value object.
	 *
	 * @return Repeater_Value
	 */
	public function process(): Repeater_Value {
		/** @var array<string, array<int, string>> $reordered */
		$reordered = $this->reorder_values();
		return new Repeater_Value( $reordered );
	}

	/**
	 * Gets the sort order either from the post
	 * or based on the highest index from values (from 0)
	 *
	 * @return int[]
	 */
	protected function get_sort_order(): array {
		// If sortorder is present in POST (even if empty) it is authoritative.
		if ( array_key_exists( 'sortorder', $this->raw_post ) && is_string( $this->raw_post['sortorder'] ) ) {
			$raw = \sanitize_text_field( $this->raw_post['sortorder'] );
			if ( '' === $raw ) {
				return array();
			}
			return array_map( 'intval', \explode( ',', $raw ) );
		}

		// No sortorder in POST — fall back to existing repeater values.
		$from_values = $this->repeater->get_value();
		if ( $from_values instanceof Repeater_Value && $from_values->group_count() > 0 ) {
			return array_map( 'intval', \range( 0, ( count( $from_values->as_indexed() ) - 1 ) ) );
		}

		return array();
	}

	/**
	 * Gets an array of all values from the global post
	 * after being run through the corresponding fields sanitize callback.
	 *
	 * @return array<string, array<int, mixed>|null>
	 */
	protected function get_sanitized_post_values(): array {
		$fields    = $this->repeater->get_fields();
		$sanitized = array();

		if ( null === $fields ) {
			return $sanitized;
		}

		foreach ( $fields->to_array() as $key => $field ) {
			/** @var \PinkCrab\Perique_Settings_Page\Setting\Field\Field $field */
			$field_key             = is_string( $key ) ? $key : $field->get_key();
			$sanitized[ $field_key ] = array_key_exists( $field_key, $this->raw_post ) && is_array( $this->raw_post[ $field_key ] )
				? array_map( array( $field, 'sanitize' ), $this->raw_post[ $field_key ] )
				: null;
		}

		return $sanitized;
	}

	/**
	 * Reorders the values from the global post
	 * based on the defined sort order.
	 *
	 * @return array<string, mixed[]>
	 */
	protected function reorder_values(): array {
		$sanitized  = $this->get_sanitized_post_values();
		$sort_order = $this->get_sort_order();

		// Initialise each field key with an empty array.
		$new_data = array_fill_keys( array_keys( $sanitized ), array() );

		// If there is no sort order (no rows submitted), return empty arrays.
		if ( empty( $sort_order ) ) {
			return $new_data;
		}

		// For each index in the sort order, map each sanitised value.
		foreach ( $sort_order as $sort_index ) {
			foreach ( $sanitized as $key => $value ) {
				$new_data[ $key ][] = is_array( $value ) && array_key_exists( (int) $sort_index, $value )
					? $value[ (int) $sort_index ]
					: null;
			}
		}

		return $new_data;
	}
}
