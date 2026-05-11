<?php
/**
 * Settings model for Karkinos Gateway.
 *
 * Defines the single field exposed on Settings → Karkinos Gateway: the
 * home server's public IP. Storage is per-field (is_grouped() = false) so
 * the value lives in its own option row named `karkinos_gateway_local_server_ip`.
 *
 * @package Karkinos\Gateway\Settings
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Settings;

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;

class Gateway_Settings extends Abstract_Settings {

	/** Group prefix the Settings_Page module uses to build option names. */
	public const GROUP_KEY = 'karkinos_gateway';

	/** Field key for the home server IP. */
	public const FIELD_LOCAL_SERVER_IP = 'local_server_ip';

	/** Full wp_options key the IP is stored under (group + field). */
	public const OPTION_LOCAL_SERVER_IP = self::GROUP_KEY . '_' . self::FIELD_LOCAL_SERVER_IP;

	/**
	 * Per-field storage (one wp_options row per field) rather than a single
	 * grouped option holding an array.
	 *
	 * @return bool Always false.
	 */
	protected function is_grouped(): bool {
		return false;
	}

	/**
	 * Prefix for option keys (and the "group" label shown in the admin UI).
	 *
	 * @return string Group key.
	 */
	public function group_key(): string {
		return self::GROUP_KEY;
	}

	/**
	 * Build the field collection rendered on the settings page.
	 *
	 * @param Setting_Collection $settings Empty collection supplied by the module.
	 *
	 * @return Setting_Collection Populated with the local-server-IP field.
	 */
	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Text::new( self::FIELD_LOCAL_SERVER_IP )
				->set_label( __( 'Local server IP', 'karkinos-gateway' ) )
				->set_description(
					__(
						'Public IP address of the home server. Updated by the home server itself; manual edit only when needed.',
						'karkinos-gateway'
					)
				)
				->set_sanitize( static fn( $value ): string => is_string( $value ) ? trim( $value ) : '' )
				->set_validate(
					static fn( $value ): bool => is_string( $value )
						&& ( '' === $value || false !== filter_var( $value, FILTER_VALIDATE_IP ) )
				)
		);
	}
}
