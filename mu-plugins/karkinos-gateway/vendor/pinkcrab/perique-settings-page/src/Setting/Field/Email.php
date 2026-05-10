<?php

declare( strict_types=1 );

/**
 * Email field.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Pattern;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Placeholder;

class Email extends Field {

	public const TYPE = 'email';

	use Placeholder, Data, Pattern;

	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );
		$this->set_sanitize( 'sanitize_email' );
	}
}
