<?php

declare(strict_types=1);

/**
 * Disabled attribute
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

namespace PinkCrab\Perique_Settings_Page\Setting\Field\Attribute;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;

trait Disabled {

	/**
	 * Sets the disabled for this input.
	 *
	 * @param bool $disabled
	 * @return static
	 */
	public function set_disabled( bool $disabled = true ): static {

		// Remove if set to false.
		if ( false === $disabled && $this->is_disabled() ) {
			$key = array_search( 'disabled', $this->flags, true );
			unset( $this->flags[ $key ] );
			return $this;
		}

		$this->flags[] = 'disabled';
		return $this;
	}

	/**
	 * Checks if a disabled exists.
	 *
	 * @return bool
	 */
	public function is_disabled(): bool {
		return \in_array( 'disabled', $this->get_flags(), true );
	}
}
