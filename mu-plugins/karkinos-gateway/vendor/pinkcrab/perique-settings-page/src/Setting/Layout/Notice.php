<?php

declare( strict_types=1 );

/**
 * Notice - displays a message/info box within the form.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Layout;

use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Notice implements Renderable {

	/**
	 * Counter for unique keys.
	 *
	 * @var int
	 */
	protected static int $counter = 0;

	/**
	 * The message content.
	 *
	 * @var string
	 */
	protected string $message;

	/**
	 * The notice level: 'info', 'warning', 'error', 'success'.
	 *
	 * @var string
	 */
	protected string $level;

	/**
	 * Instance key.
	 *
	 * @var string
	 */
	protected string $key;

	protected function __construct( string $message, string $level ) {
		$this->message = $message;
		$this->level   = $level;
		$this->key     = 'notice_' . self::$counter++;
	}

	/**
	 * Create an info notice.
	 *
	 * @param string $message
	 * @return static
	 */
	public static function info( string $message ): static {
		return new static( $message, 'info' );
	}

	/**
	 * Create a warning notice.
	 *
	 * @param string $message
	 * @return static
	 */
	public static function warning( string $message ): static {
		return new static( $message, 'warning' );
	}

	/**
	 * Create an error notice.
	 *
	 * @param string $message
	 * @return static
	 */
	public static function error( string $message ): static {
		return new static( $message, 'error' );
	}

	/**
	 * Create a success notice.
	 *
	 * @param string $message
	 * @return static
	 */
	public static function success( string $message ): static {
		return new static( $message, 'success' );
	}

	/**
	 * Get the message.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Get the notice level.
	 *
	 * @return string
	 */
	public function get_level(): string {
		return $this->level;
	}

	/** @inheritDoc */
	public function get_key(): string {
		return $this->key;
	}

	/** @inheritDoc */
	public function get_type(): string {
		return 'layout_notice';
	}
}
