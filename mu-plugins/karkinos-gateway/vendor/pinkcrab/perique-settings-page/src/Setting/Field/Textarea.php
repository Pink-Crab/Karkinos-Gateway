<?php

declare( strict_types=1 );

/**
 * Textarea field.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Placeholder;

class Textarea extends Field {

	public const TYPE = 'textarea';

	use Placeholder, Data;

	/**
	 * Number of visible rows.
	 *
	 * @var int|null
	 */
	protected ?int $rows = null;

	/**
	 * Number of visible columns.
	 *
	 * @var int|null
	 */
	protected ?int $cols = null;

	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );
		$this->set_sanitize( 'sanitize_textarea_field' );
	}

	/**
	 * Set the number of visible rows.
	 *
	 * @param int $rows
	 * @return static
	 */
	public function set_rows( int $rows ): static {
		$this->rows = $rows;
		return $this;
	}

	/**
	 * Get the number of visible rows.
	 *
	 * @return int|null
	 */
	public function get_rows(): ?int {
		return $this->rows;
	}

	/**
	 * Set the number of visible columns.
	 *
	 * @param int $cols
	 * @return static
	 */
	public function set_cols( int $cols ): static {
		$this->cols = $cols;
		return $this;
	}

	/**
	 * Get the number of visible columns.
	 *
	 * @return int|null
	 */
	public function get_cols(): ?int {
		return $this->cols;
	}
}
