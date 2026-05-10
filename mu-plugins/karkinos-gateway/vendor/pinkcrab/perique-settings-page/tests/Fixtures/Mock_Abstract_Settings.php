<?php

declare( strict_types=1 );

/**
 * Configurable Abstract_Settings fixture for tests.
 *
 * Use Mock_Abstract_Settings::with_fields(...) to construct with arbitrary fields.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Tests\Fixtures;

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Repository;
use PinkCrab\Perique_Settings_Page\Setting\Renderable;

class Mock_Abstract_Settings extends Abstract_Settings {

	/**
	 * Static stash of fields injected into the next instance.
	 *
	 * @var Renderable[]
	 */
	public static array $injected_fields = array();

	/**
	 * Whether the next instance should be grouped.
	 *
	 * @var bool
	 */
	public static bool $grouped = false;

	/**
	 * Group key for the next instance.
	 *
	 * @var string
	 */
	public static string $group_key = 'mock_settings';

	/**
	 * Build an instance with the given fields.
	 *
	 * @param Setting_Repository $repository
	 * @param Renderable         ...$fields
	 * @return self
	 */
	public static function with_fields( Setting_Repository $repository, Renderable ...$fields ): self {
		self::$injected_fields = $fields;
		return new self( $repository );
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		foreach ( self::$injected_fields as $field ) {
			$settings->push( $field );
		}
		return $settings;
	}

	protected function is_grouped(): bool {
		return self::$grouped;
	}

	public function group_key(): string {
		return self::$group_key;
	}
}
