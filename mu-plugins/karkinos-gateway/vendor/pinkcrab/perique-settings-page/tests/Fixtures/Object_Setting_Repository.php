<?php

declare(strict_types=1);

/**
 * Default WP Options Settings Repository.
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

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;

class Object_Setting_Repository implements Setting_Repository {

	/**
	 * The key value store.
	 *
	 * @var array
	 */
	public $store = array();

	/**
	 * Single use return value
	 * After each return, this is reset to null.
	 *
	 * @var mixed
	 */
	public static $return_value = null;

	/**
	 * Gets the return value, either frm the single use static property, 
     * or defined in method as a fallback.
	 *
	 * @param mixed $fallback
	 * @return mixed
	 */
	public function return_value( $fallback ) {
		$value       = self::$return_value ?? $fallback;
		self::$return_value = null;
		return $value;
	}

	/**
	 * Sets an option to the options table
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return bool
	 */
	public function set( string $key, $data ): bool {
		$this->store[ $key ] = $data;
		return $this->return_value( array_key_exists( $key, $this->store ) );
	}

	/**
	 * Gets an option from the options table.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( string $key ) {
		return $this->return_value(
			array_key_exists( $key, $this->store ) ? $this->store[ $key ] : false
		);
	}

	/**
	 * Deletes an option from the options table.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete( string $key ): bool {
		unset( $this->store[ $key ] );
		return $this->return_value( ! array_key_exists( $key, $this->store ) );
	}

	/**
	 * Checks if an option is set in the options table.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has( string $key ): bool {
        return $this->return_value( array_key_exists( $key, $this->store ) );
	}

	/**
	 * Allows the use of grouped data.
	 *
	 * @return bool
	 */
	public function allow_grouped(): bool {
		return $this->return_value( true );
	}
}
