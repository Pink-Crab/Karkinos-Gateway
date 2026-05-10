<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Repo_Site_Options_Page extends Settings_Page {

	protected string $page_slug  = 'repo-site-options';
	protected string $menu_title = 'Repo: Site Options';
	protected string $page_title = 'Repository: Site Options';

	public function settings_class_name(): string {
		return Test_Repo_Site_Options_Settings::class;
	}

	public function render_view(): callable {
		$parent = parent::render_view();
		return function () use ( $parent ): void {
			$parent();

			// On non-multisite get_site_option falls back to wp_options.
			$stored = get_site_option( Test_Repo_Site_Options_Settings::OPTION_KEY, array() );
			echo '<pre id="repo-site-options-stored" data-testid="repo-site-options-stored" style="margin-top:20px;padding:16px;background:#f0f0f0;border:1px solid #ccc;border-radius:6px;font-family:monospace;font-size:13px;">';
			echo esc_html( wp_json_encode( $stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			echo '</pre>';
		};
	}
}
