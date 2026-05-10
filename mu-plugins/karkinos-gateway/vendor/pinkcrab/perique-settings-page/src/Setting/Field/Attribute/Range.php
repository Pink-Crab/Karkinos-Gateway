<?php

declare(strict_types=1);

/**
 * Range attribute
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

trait Range {

	/**
	 * Sets the min for this input.
	 *
	 * @param string $min
	 * @return self
	 */
	public function set_min( $min ): self {
		$this->set_attribute( 'min', (string) $min );
		return $this;
	}

	/**
	 * Checks if a min exists.
	 *
	 * @return bool
	 */
	public function has_min(): bool {
		return \array_key_exists( 'min', $this->get_attributes() );
	}

	/**
	 * Gets the min if set.
	 *
	 * @return string|null
	 */
	public function get_min(): ?string {
		return $this->has_min()
			? Cast::to_string( $this->get_attributes()['min'] )
			: null;
	}

	/**
	 * Sets the max for this input.
	 *
	 * @param string $max
	 * @return self
	 */
	public function set_max( $max ): self {
		$this->set_attribute( 'max', (string) $max );
		return $this;
	}

	/**
	 * Checks if a max exists.
	 *
	 * @return bool
	 */
	public function has_max(): bool {
		return \array_key_exists( 'max', $this->get_attributes() );
	}

	/**
	 * Gets the max if set.
	 *
	 * @return string|null
	 */
	public function get_max(): ?string {
		return $this->has_max()
			? Cast::to_string( $this->get_attributes()['max'] )
			: null;
	}

	/**
	 * Sets the step for this input.
	 *
	 * @param string $step
	 * @return self
	 */
	public function set_step( $step ): self {
		$this->set_attribute( 'step', (string) $step );
		return $this;
	}

	/**
	 * Checks if a step exists.
	 *
	 * @return bool
	 */
	public function has_step(): bool {
		return \array_key_exists( 'step', $this->get_attributes() );
	}

	/**
	 * Gets the step if set.
	 *
	 * @return string|null
	 */
	public function get_step(): ?string {
		return $this->has_step()
			? Cast::to_string( $this->get_attributes()['step'] )
			: null;
	}
}
