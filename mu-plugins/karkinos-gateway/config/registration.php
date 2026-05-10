<?php
/**
 * Registration class list — Hookable classes processed during App boot.
 */

declare(strict_types=1);

return array(
	\Karkinos\Gateway\Settings\Gateway_Settings_Page::class,
	\Karkinos\Gateway\Settings\Ensure_Settings_Not_Autoloaded::class,
	\Karkinos\Gateway\Rest\Settings_Routes::class,
	\Karkinos\Gateway\Rest\Webhook_Routes::class,
);
