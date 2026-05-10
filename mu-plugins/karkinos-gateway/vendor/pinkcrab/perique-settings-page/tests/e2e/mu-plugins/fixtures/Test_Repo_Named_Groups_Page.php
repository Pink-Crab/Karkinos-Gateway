<?php

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Repo_Named_Groups_Page extends Settings_Page {

	protected string $page_slug  = 'repo-named-groups';
	protected string $menu_title = 'Repo: Named Groups';
	protected string $page_title = 'Repository: Named Groups';

	public function settings_class_name(): string {
		return Test_Repo_Named_Groups_Settings::class;
	}

	public function render_view(): callable {
		$parent = parent::render_view();
		return function () use ( $parent ): void {
			$parent();

			// Dump each named group option.
			// Named_Groups stores: ng_general, ng_display as serialised arrays.
			$dump = array(
				'ng_general' => get_option( 'ng_general', null ),
				'ng_display' => get_option( 'ng_display', null ),
			);
			echo '<pre id="repo-named-groups-stored" data-testid="repo-named-groups-stored" style="margin-top:20px;padding:16px;background:#f0f0f0;border:1px solid #ccc;border-radius:6px;font-family:monospace;font-size:13px;">';
			echo esc_html( wp_json_encode( $dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			echo '</pre>';
		};
	}
}
