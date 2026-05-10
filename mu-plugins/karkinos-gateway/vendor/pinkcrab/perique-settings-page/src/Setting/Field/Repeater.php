<?php

declare(strict_types=1);

/**
 * Radio field
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
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Disabled;

class Repeater extends Field {
	/**
	 * The type of field.
	 */
	public const TYPE = 'repeater';

	/**
	 * The label to show when adding a new row
	 *
	 * @var string
	 */
	protected string $add_to_group_label = 'Add';

	/**
	 * Denote the layout
	 *  Options ['row', 'columns']
	 *
	 * @var string
	 */
	protected string $layout = 'rows';

	/**
	 * Customer wrapper class for the group.
	 *
	 * @var string
	 */
	protected string $group_class = 'repeater-group';

	/**
	 * Collection of fields
	 *
	 * @var Setting_Collection|null
	 */
	protected ?Setting_Collection $fields = null;

	// Attributes.
	use Disabled, Data;

	/**
	 * Static constructor for field.
	 *
	 * @param string $key
	 * @return static
	 */
	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );
	}

	/**
	 * Get the label to show when adding a new row
	 *
	 * @return string
	 */
	public function get_add_to_group_label(): string {
		return $this->add_to_group_label;
	}

	/**
	 * Set the label to show when adding a new row
	 *
	 * @param string $add_to_group_label  The label to show when adding a new row
	 *
	 * @return static
	 */
	public function set_add_to_group_label( string $add_to_group_label ): static {
		$this->add_to_group_label = esc_html( $add_to_group_label );
		return $this;
	}

	/**
	 * Get collection of fields
	 *
	 * @return Setting_Collection|null
	 */
	public function get_fields(): ?Setting_Collection {
		return $this->fields;
	}

	/**
	 * Set collection of fields
	 *
	 * @param Field $field  The field to add.
	 *
	 * @return static
	 */
	public function add_field( Field $field ): static {

		if ( is_a( $field, self::class ) ) {
			throw new \Exception( 'A repeater can not be added as a repeater field.', 1 );
		}

		// Initialise collection of not set.
		if ( is_null( $this->fields ) ) {
			$this->fields = new Setting_Collection();
		}

		$this->fields->push( $field );
		return $this;
	}

	/**
	 * Get options ['row', 'columns']
	 *
	 * @return string
	 */
	public function get_layout(): string {
		return $this->layout;
	}

	/**
	 * Set options ['row', 'columns']
	 *
	 * @param string $layout  Options ['row', 'columns']
	 *
	 * @return static
	 */
	public function set_layout( string $layout ): static {
		if ( in_array( $layout, array( 'row', 'columns' ), true ) ) {
			$this->layout = $layout;
		}
		return $this;
	}

	/**
	 * Get customer wrapper class for the group.
	 *
	 * @return string
	 */
	public function get_group_class(): string {
		return $this->group_class;
	}

	/**
	 * Set customer wrapper class for the group.
	 *
	 * @param string $group_class  Customer wrapper class for the group.
	 *
	 * @return static
	 */
	public function set_group_class( string $group_class ): static {
		$this->group_class = esc_html( $group_class );
		return $this;
	}
}
