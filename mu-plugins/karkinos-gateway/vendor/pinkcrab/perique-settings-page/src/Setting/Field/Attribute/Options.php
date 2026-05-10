<?php

declare(strict_types=1);

/**
 * Options attribute
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

trait Options {

	/**
	 * The options array.
	 *
	 * @var array<string, array<string, string>>
	 */
	protected $options = array();

	/**
	 * Sets the option for this input.
	 *
	 * @param string $value
	 * @param string $label
	 * @param string $group
	 * @return self
	 */
	public function set_option( string $value, string $label, string $group = '' ): self {
		if ( ! array_key_exists( $group, $this->options ) ) {
			$this->options[ $group ] = array();
		}

		$this->options[ $group ][ $value ] = $label;
		return $this;
	}

	/**
	 * Checks if a option exists.
	 *
	 * @return bool
	 */
	public function has_option( string $value, string $group = '' ): bool {
		return \array_key_exists( $group, $this->options )
		&& \array_key_exists( $value, $this->options[ $group ] );
	}

	/**
	 * Gets the option if set.
	 *
	 * @return array<string, string|array<string,string>>
	 */
	public function get_options(): array {
		// If we have only 1 group with no label, return as a single array.
		if ( count( $this->options ) <= 1 && array_key_exists( '', $this->options ) ) {
			return $this->options[''];
		}

		return $this->options;
	}

}
