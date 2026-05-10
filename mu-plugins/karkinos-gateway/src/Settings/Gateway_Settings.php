<?php

declare(strict_types=1);

namespace Karkinos\Gateway\Settings;

use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Setting\Setting_Collection;
use PinkCrab\Perique_Settings_Page\Setting\Field\Text;

class Gateway_Settings extends Abstract_Settings {

	public const GROUP_KEY              = 'karkinos_gateway';
	public const FIELD_LOCAL_SERVER_IP  = 'local_server_ip';
	public const OPTION_LOCAL_SERVER_IP = self::GROUP_KEY . '_' . self::FIELD_LOCAL_SERVER_IP;

	protected function is_grouped(): bool {
		return false;
	}

	public function group_key(): string {
		return self::GROUP_KEY;
	}

	protected function fields( Setting_Collection $settings ): Setting_Collection {
		return $settings->push(
			Text::new( self::FIELD_LOCAL_SERVER_IP )
				->set_label( 'Local server IP' )
				->set_description( 'Public IP address of the home server. Updated by the home server itself; manual edit only when needed.' )
				->set_sanitize( static fn( $value ): string => is_string( $value ) ? trim( $value ) : '' )
				->set_validate(
					static fn( $value ): bool => is_string( $value )
						&& ( '' === $value || false !== filter_var( $value, FILTER_VALIDATE_IP ) )
				)
		);
	}
}
