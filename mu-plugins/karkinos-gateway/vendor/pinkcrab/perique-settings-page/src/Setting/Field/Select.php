<?php

declare(strict_types=1);

/**
 * Select field
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

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use PinkCrab\Perique_Settings_Page\Util\Cast;
use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Options;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Multiple;

class Select extends Field {

	/**
	 * The type of field.
	 */
	public const TYPE = 'select';

	// Attributes.
	use Multiple, Data, Options;

	/**
	 * Static constructor for select field.
	 *
	 * @param string $key
	 * @return static
	 */
	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );

		// Default sanitiser. WP's sanitize_text_field() returns '' for
		// arrays, which would wipe multi-select values on save — so we
		// map over arrays instead of delegating them directly. Values
		// are run through Util\Cast::to_string() to satisfy phpstan's
		// mixed-to-string narrowing.
		$this->set_sanitize(
			static function ( $data ) {
				if ( is_array( $data ) ) {
					return array_values(
						array_map(
							static fn( $v ): string => sanitize_text_field( Cast::to_string( $v, '' ) ?? '' ),
							$data
						)
					);
				}
				return sanitize_text_field( Cast::to_string( $data, '' ) ?? '' );
			}
		);
	}
}
