<?php

declare(strict_types=1);

/**
 * Placeholder attribute
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

trait Query {

	/**
	 * The args for the query
	 *
	 * @var array<string, mixed>
	 */
	protected $query_args = array();

	/**
	 * Sets the query_args for this input.
	 *
	 * @param array<string, mixed> $query_args
	 * @return self
	 */
	public function set_query_args( array $query_args ): self {
		$this->query_args = $query_args;
		return $this;
	}


	/**
	 * Gets the query_args if set.
	 *
	 * @return array<string, mixed>
	 */
	public function get_query_args(): array {
		return $this->query_args;
	}

	/**
	 * Sets the options label callback.
	 *
	 * @param callable $callback
	 * @return self
	 */
	public function set_option_label( callable $callback ): self {
		$this->callbacks['option_label'] = $callback;
		return $this;
	}

	/**
	 * Gets the option label callback for defining.
	 *
	 * @return callable
	 */
	abstract public function get_option_label(): callable;

	/**
	 * Gets the option value callback for defining.
	 *
	 * @return callable
	 */
	abstract public function get_option_value(): callable;
}
