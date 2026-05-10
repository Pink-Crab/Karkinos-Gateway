<?php
/**
 * E2E fixture: Interactive Kitchen Sink page.
 *
 * Registers at `/wp-admin/admin.php?page=interactive-kitchen-sink`.
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Interactive_Kitchen_Sink_Page extends Settings_Page {

	protected string $page_slug  = 'interactive-kitchen-sink';
	protected string $menu_title = 'Interactive Sink';
	protected string $page_title = 'Interactive Kitchen Sink';

	public function settings_class_name(): string {
		return Test_Interactive_Kitchen_Sink_Settings::class;
	}

	public function render_view(): callable {
		$parent = parent::render_view();
		return function () use ( $parent ): void {
			$parent();

			// Debug dump.
			$stored = get_option( Test_Interactive_Kitchen_Sink_Settings::OPTION_KEY, array() );
			echo '<pre id="interactive-stored" data-testid="interactive-stored" style="margin-top:20px;padding:16px;background:#f0f0f0;border:1px solid #ccc;border-radius:6px;font-family:monospace;font-size:13px;">';
			echo esc_html( wp_json_encode( $stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			echo '</pre>';
		};
	}
}
