<?php

declare(strict_types=1);

/**
 * Autocomplete attribute
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

use PinkCrab\Perique_Settings_Page\Util\Cast;

trait Autocomplete {

	/**
	 * Sets the autocomplete for this input.
	 *
	 * @param string $autocomplete
	 * @return self
	 */
	public function set_autocomplete( string $autocomplete ): self {
		$this->set_attribute( 'autocomplete', $autocomplete );
		return $this;
	}

	/**
	 * Checks if a autocomplete exists.
	 *
	 * @return bool
	 */
	public function has_autocomplete(): bool {
		return \array_key_exists( 'autocomplete', $this->get_attributes() );
	}

	/**
	 * Gets the autocomplete if set.
	 *
	 * @return string|null
	 */
	public function get_autocomplete(): ?string {
		return $this->has_autocomplete()
			? Cast::to_string( $this->get_attributes()['autocomplete'] )
			: null;
	}
}
