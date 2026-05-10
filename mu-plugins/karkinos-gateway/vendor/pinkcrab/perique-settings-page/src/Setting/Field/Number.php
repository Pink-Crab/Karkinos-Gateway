<?php

declare(strict_types=1);

/**
 * Number field
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

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Range;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Options;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Placeholder;

class Number extends Field {

	/**
	 * The type of field.
	 */
	public const TYPE = 'number';

	// Attributes.
	use Placeholder, Data, Range, Options;

	protected int $decimal_places = 0;

	/**
	 * Static constructor for number input.
	 *
	 * @param string $key
	 * @return static
	 */
	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );

		$this->set_sanitize(
			function( $e ) {
				$e = sanitize_text_field( $e );
				return $this->decimal_places <= 1
					? \intval( $e )
					: round( (float) $e, $this->get_decimal_places() );
			}
		);
	}

	/**
	 * Get the value of decimal_places.
	 *
	 * @return int
	 */
	public function get_decimal_places(): int {
		return $this->decimal_places;
	}

	/**
	 * Set the value of decimal_places.
	 *
	 * @param int $decimal_places
	 * @return static
	 */
	public function set_decimal_places( int $decimal_places ): static {
		$this->decimal_places = $decimal_places;
		return $this;
	}
}
