<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Repo_Individual_Page extends Settings_Page {

	protected string $page_slug  = 'repo-individual';
	protected string $menu_title = 'Repo: Individual';
	protected string $page_title = 'Repository: Individual';

	public function settings_class_name(): string {
		return Test_Repo_Individual_Settings::class;
	}

	public function render_view(): callable {
		$parent = parent::render_view();
		return function () use ( $parent ): void {
			$parent();

			// Dump each individual option.
			// prefix_key('site_name') → "ind_site_name" (group_key + _ + field_key).
			// The repo has no prefix, so the wp_options key IS ind_site_name.
			$dump = array();
			foreach ( array_keys( Test_Repo_Individual_Settings::default_values() ) as $key ) {
				$option_name          = 'ind_' . $key;
				$dump[ $option_name ] = get_option( $option_name, null );
			}
			echo '<pre id="repo-individual-stored" data-testid="repo-individual-stored" style="margin-top:20px;padding:16px;background:#f0f0f0;border:1px solid #ccc;border-radius:6px;font-family:monospace;font-size:13px;">';
			echo esc_html( wp_json_encode( $dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			echo '</pre>';
		};
	}
}
