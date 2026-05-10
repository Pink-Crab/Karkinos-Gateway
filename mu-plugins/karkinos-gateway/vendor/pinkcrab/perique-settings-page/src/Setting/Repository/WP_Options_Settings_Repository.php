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

namespace PinkCrab\Perique_Settings_Page\Setting\Repository;

use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;

class WP_Options_Settings_Repository implements Setting_Repository {

	/**
	 * Sets an option to the options table
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return bool
	 */
	public function set( string $key, $data ): bool {
		return \update_option( $key, $data, true );
	}

	/**
	 * Gets an option from the options table.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get( string $key ) {
		return \get_option( $key );
	}

	/**
	 * Deletes an option from the options table.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return \delete_option( $key );
	}

	/**
	 * Checks if an option is set in the options table.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has( string $key ): bool {
		$option = \get_option( $key, $this );

		return $option !== $this;
	}

	/**
	 * Allows the use of grouped data.
	 *
	 * @return bool
	 */
	public function allow_grouped(): bool {
		return true;
	}
}
