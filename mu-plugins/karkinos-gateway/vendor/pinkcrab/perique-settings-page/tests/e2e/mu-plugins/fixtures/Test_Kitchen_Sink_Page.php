<?php
/**
 * E2E fixture: kitchen sink page.
 *
 * Pairs with Test_Kitchen_Sink_Settings. Renders the parent form and
 * appends a debug block dumping the stored option so spec failures are
 * easy to diagnose when running headed.
 *
 * Exercises BOTH pre/post template paths:
 *   - $pre_template is set as a property default (static path).
 *   - post_template is set at runtime in before_render() with data
 *     pulled from the live settings instance (dynamic path).
 */

declare( strict_types=1 );

use PinkCrab\Perique_Settings_Page\Page\Settings_Page;

class Test_Kitchen_Sink_Page extends Settings_Page {

	protected string $page_slug  = 'kitchen-sink-settings';
	protected string $menu_title = 'Kitchen Sink';
	protected string $page_title = 'Kitchen Sink Settings';
	protected string $theme_stylesheet = Settings_Page::STYLE_VANILLA;

	/**
	 * Static template path — exercises the "set as property default" path.
	 *
	 * @var string|null
	 */
	protected ?string $pre_template = 'kitchen-sink/pre';

	/**
	 * Static template data — exercises the property-default path.
	 *
	 * @var array<string, mixed>
	 */
	protected array $pre_data = array(
		'heading' => 'Pre Template Heading',
	);

	public function settings_class_name(): string {
		return Test_Kitchen_Sink_Settings::class;
	}

	/**
	 * Runtime template setup — exercises the "set in before_render()" path.
	 *
	 * Pulls live values from the settings instance so the rendered
	 * template reflects what's currently persisted.
	 */
	protected function before_render(): void {
		if ( null === $this->settings ) {
			return;
		}

		$this->set_post_template(
			'kitchen-sink/post',
			array(
				'text_basic_value'   => (string) $this->settings->get( 'text_basic', '' ),
				'number_basic_value' => (int) $this->settings->get( 'number_basic', 0 ),
			)
		);
	}

	public function render_view(): callable {
		$parent = parent::render_view();
		return function () use ( $parent ): void {
			$parent();

			// Debug dump — used by the e2e spec to verify persistence.
			$stored = get_option( Test_Kitchen_Sink_Settings::OPTION_KEY, array() );
			echo '<pre id="kitchen-sink-stored" data-testid="kitchen-sink-stored" style="margin-top:20px;padding:16px;background:#f0f0f0;border:1px solid #ccc;border-radius:6px;font-family:monospace;font-size:13px;">';
			echo esc_html( wp_json_encode( $stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
			echo '</pre>';
		};
	}
}
