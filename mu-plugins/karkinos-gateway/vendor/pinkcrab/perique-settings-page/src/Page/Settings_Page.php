<?php

declare( strict_types=1 );

/**
 * Abstract Settings Page.
 *
 * Extends Menu_Page from Perique Admin Menu to provide a settings page
 * with form definition, persistence, validation and rendering via
 * Form Components.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Page;

use PinkCrab\Perique_Admin_Menu\Page\Page;
use PinkCrab\Perique_Admin_Menu\Page\Menu_Page;
use PinkCrab\Perique_Settings_Page\Setting\Abstract_Settings;
use PinkCrab\Perique_Settings_Page\Mapper\Element_Mapper;
use PinkCrab\Perique_Settings_Page\Handler\Form_Handler;
use PinkCrab\Perique_Settings_Page\Handler\Notice_Handler;
use PinkCrab\Perique_Settings_Page\Handler\Submission_Result;
use PinkCrab\Perique_Settings_Page\Util\File_Helper;
use PinkCrab\Perique_Settings_Page\Util\Form_Helper;
use PinkCrab\Form_Components\Element\Form;
use PinkCrab\Form_Components\Element\Nonce;
use PinkCrab\Form_Components\Element\Field\Input\Hidden;
use PinkCrab\Form_Components\Element\Field\Input\Submit;
use PinkCrab\Form_Components\Component\Component_Factory;

abstract class Settings_Page extends Menu_Page {

	/*
	 * ─── Bundled theme constants ────────────────────────────────
	 *
	 * Each constant resolves to a CSS file under assets/themes/.
	 * Pass one of these to $theme_stylesheet, or use an absolute
	 * path / URL for a completely custom theme.
	 */
	public const STYLE_VANILLA           = 'vanilla';
	public const STYLE_MATERIAL          = 'material';
	public const STYLE_BOOTSTRAP         = 'bootstrap';
	public const STYLE_BOOTSTRAP_CLASSIC = 'bootstrap-classic';
	public const STYLE_WP_ADMIN          = 'wp-admin';
	public const STYLE_MINIMAL           = 'minimal';
	public const STYLE_NONE              = '';

	/**
	 * The HTTP method for the form (POST or GET).
	 *
	 * @var string
	 */
	protected string $method = 'POST';

	/**
	 * The settings data model (injected by middleware).
	 *
	 * @var Abstract_Settings|null
	 */
	protected ?Abstract_Settings $settings = null;

	/**
	 * The result of the last form submission (if any).
	 *
	 * @var Submission_Result|null
	 */
	protected ?Submission_Result $last_result = null;

	/**
	 * Template rendered inside `.wrap` after the heading and before
	 * the form. Set as a property in subclasses for static content,
	 * or populate at runtime in before_render() via set_pre_template().
	 * No-op if null or no view service is available.
	 *
	 * @var string|null
	 */
	protected ?string $pre_template = null;

	/**
	 * Data passed to the pre_template.
	 *
	 * @var array<string, mixed>
	 */
	protected array $pre_data = array();

	/**
	 * Template rendered inside `.wrap` after the form. Same rules as
	 * $pre_template.
	 *
	 * @var string|null
	 */
	protected ?string $post_template = null;

	/**
	 * Data passed to the post_template.
	 *
	 * @var array<string, mixed>
	 */
	protected array $post_data = array();

	/**
	 * Theme stylesheet identifier.
	 *
	 * Use one of the STYLE_* constants for a bundled theme, an absolute
	 * file path, a URL, or STYLE_NONE to load no theme (core only).
	 *
	 * @var string
	 */
	protected string $theme_stylesheet = self::STYLE_VANILLA;

	/**
	 * Returns the fully qualified class name of the Abstract_Settings
	 * subclass that this page uses.
	 *
	 * @return class-string<Abstract_Settings>
	 */
	abstract public function settings_class_name(): string;

	/**
	 * Injects the settings instance into the page and hydrates
	 * the values from the persistence layer.
	 *
	 * @param Abstract_Settings $settings
	 * @return void
	 */
	public function set_settings( Abstract_Settings $settings ): void {
		$this->settings = $settings;
		$this->settings->refresh_settings();
	}

	/**
	 * Get the HTTP method.
	 *
	 * @return string
	 */
	public function get_method(): string {
		return $this->method;
	}

	/**
	 * Get the settings instance.
	 *
	 * @return Abstract_Settings|null
	 */
	public function get_settings(): ?Abstract_Settings {
		return $this->settings;
	}

	/**
	 * Get the nonce handle for this page's form.
	 *
	 * @return string
	 */
	public function get_nonce_handle(): string {
		return Form_Helper::nonce_handle( $this->slug() );
	}

	/**
	 * Get the nonce field name.
	 *
	 * @return string
	 */
	public function get_nonce_field_name(): string {
		return 'pc_settings_nonce';
	}

	/**
	 * Get the submit button label.
	 *
	 * @return string
	 */
	public function get_submit_label(): string {
		return esc_html__( 'Save Settings', 'perique-settings-page' );
	}

	/**
	 * Get the form action URL.
	 *
	 * @return string
	 */
	public function get_form_action(): string {
		return '';
	}

	/**
	 * Set the template rendered above the form.
	 *
	 * @param string               $template View template identifier (passed to the View service).
	 * @param array<string, mixed> $data     Data merged into the view.
	 * @return static
	 */
	public function set_pre_template( string $template, array $data = array() ): static {
		$this->pre_template = $template;
		$this->pre_data     = $data;
		return $this;
	}

	/**
	 * Set the template rendered below the form.
	 *
	 * @param string               $template View template identifier (passed to the View service).
	 * @param array<string, mixed> $data     Data merged into the view.
	 * @return static
	 */
	public function set_post_template( string $template, array $data = array() ): static {
		$this->post_template = $template;
		$this->post_data     = $data;
		return $this;
	}

	/**
	 * Get the pre_template identifier (null when unset).
	 *
	 * @return string|null
	 */
	public function get_pre_template(): ?string {
		return $this->pre_template;
	}

	/**
	 * Get the pre_template data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_pre_data(): array {
		return $this->pre_data;
	}

	/**
	 * Get the post_template identifier (null when unset).
	 *
	 * @return string|null
	 */
	public function get_post_template(): ?string {
		return $this->post_template;
	}

	/**
	 * Get the post_template data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_post_data(): array {
		return $this->post_data;
	}

	/**
	 * Hook fired at the top of render_view()'s callback, before any
	 * HTML is emitted. Override in subclasses to populate pre/post
	 * templates with runtime data (e.g. reading stored settings).
	 *
	 * Default implementation is a no-op.
	 *
	 * @return void
	 */
	protected function before_render(): void {}

	/**
	 * Renders the page view.
	 *
	 * Builds a Form Components form from the settings fields
	 * and renders it.
	 *
	 * @return callable
	 */
	public function render_view(): callable {
		return function (): void {
			// Runtime hook — subclasses populate pre/post templates here.
			$this->before_render();

			$mapper = new Element_Mapper();
			if ( null !== $this->view ) {
				$mapper->set_view( $this->view );
			}

			// Pass any field-level errors from the previous submission.
			if ( null !== $this->last_result && $this->last_result->has_field_errors() ) {
				$mapper->set_field_errors( $this->last_result->get_field_errors() );
			}

			$form = Form::make( $this->slug() )
				->method( $this->method )
				->action( $this->get_form_action() );

			$elements   = array();
			$elements[] = Hidden::make( 'page' )->value( $this->slug() );

			if ( null === $this->settings ) {
				echo '<p>' . esc_html__( 'Settings not initialised.', 'perique-settings-page' ) . '</p>';
				echo '</div>';
				return;
			}

			foreach ( $this->settings->export() as $renderable ) {
				if ( $renderable instanceof \PinkCrab\Perique_Settings_Page\Setting\Renderable ) {
					$elements[] = $mapper->to_element( $renderable );
				}
			}

			$elements[] = Nonce::make( $this->get_nonce_handle(), $this->get_nonce_field_name() );
			$elements[] = Submit::make( 'submit' )->value( $this->get_submit_label() );

			$form->fields( ...$elements );

			echo '<div class="wrap">';
			echo '<h1>' . esc_html( $this->page_title() ?? $this->menu_title() ) . '</h1>';

			// Pre template (above the form).
			if ( null !== $this->view && null !== $this->pre_template ) {
				$this->view->render( $this->pre_template, $this->pre_data );
			}

			$factory   = new Component_Factory();
			$component = $factory->from_element( $form );

			if ( null !== $this->view ) {
				$this->view->component( $component );
			}

			// Post template (below the form).
			if ( null !== $this->view && null !== $this->post_template ) {
				$this->view->render( $this->post_template, $this->post_data );
			}

			echo '</div>';
		};
	}

	/**
	 * Handles form submission on page load.
	 *
	 * @param Page $page
	 * @return void
	 */
	public function load( Page $page ): void {
		if ( null === $this->settings ) {
			return;
		}

		// Only process on the correct HTTP method.
		if ( 'POST' === $this->method && 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		$handler = new Form_Handler(
			$this->settings,
			$this->slug(),
			$this->method,
			$this->get_nonce_handle(),
			$this->get_nonce_field_name()
		);

		$result = $handler->process();

		// Don't show notices if there was no submission.
		if ( ! $result->is_success() && 'No submission to process.' === $result->get_message() ) {
			return;
		}

		// Stash the result so render_view() can show field-level errors.
		$this->last_result = $result;

		Notice_Handler::from_result( $result );
	}

	/**
	 * Enqueues all required scripts and styles for the settings page.
	 *
	 * Loads the default settings page assets, WP media and editor,
	 * then calls scripts() and styles() for custom additions.
	 *
	 * @param Page $page
	 * @return void
	 */
	final public function enqueue( Page $page ): void {
		// Core structural styles (always loaded).
		\wp_enqueue_style(
			'pc-settings-page-core',
			File_Helper::assets_url() . '/core.css',
			array(),
			'2.0.0'
		);

		// Theme stylesheet (visual layer).
		$this->enqueue_theme();

		\wp_enqueue_script(
			'pc-settings-page',
			File_Helper::assets_url() . '/settings-page.js',
			array(),
			'2.0.0',
			true
		);

		\wp_localize_script(
			'pc-settings-page',
			'pcSettingsPage',
			array(
				'restUrl'   => \esc_url_raw( \rest_url( 'pc-settings/v1/' ) ),
				'restNonce' => \wp_create_nonce( 'wp_rest' ),
			)
		);

		// WordPress media and editor.
		\wp_enqueue_media();
		\wp_enqueue_editor();

		// Custom scripts and styles.
		$this->scripts();
		$this->styles();
	}

	/**
	 * Override to enqueue custom scripts.
	 *
	 * @return void
	 */
	protected function scripts(): void {}

	/**
	 * Override to enqueue custom styles.
	 *
	 * @return void
	 */
	protected function styles(): void {}

	/**
	 * Get the current theme stylesheet identifier.
	 *
	 * @return string
	 */
	public function get_theme_stylesheet(): string {
		return $this->theme_stylesheet;
	}

	/**
	 * Enqueues the theme stylesheet.
	 *
	 * Resolves the $theme_stylesheet value:
	 *   - STYLE_NONE (''): no theme loaded (core only).
	 *   - A bundled name (e.g. 'vanilla'): looks in assets/themes/{name}.css.
	 *   - An absolute path starting with '/': uses that file path directly.
	 *   - A URL starting with 'http': uses the URL directly.
	 *
	 * @return void
	 */
	private function enqueue_theme(): void {
		if ( '' === $this->theme_stylesheet ) {
			return;
		}

		// URL — use directly.
		if ( str_starts_with( $this->theme_stylesheet, 'http' ) ) {
			\wp_enqueue_style(
				'pc-settings-page-theme',
				$this->theme_stylesheet,
				array( 'pc-settings-page-core' ),
				'2.0.0'
			);
			return;
		}

		// Absolute path — convert to URL via plugin_dir_url equivalent.
		if ( str_starts_with( $this->theme_stylesheet, '/' ) ) {
			// Turn an absolute file path into a URL.
			$url = \content_url(
				str_replace(
					\wp_normalize_path( (string) \WP_CONTENT_DIR ),
					'',
					\wp_normalize_path( $this->theme_stylesheet )
				)
			);
			\wp_enqueue_style(
				'pc-settings-page-theme',
				$url,
				array( 'pc-settings-page-core' ),
				'2.0.0'
			);
			return;
		}

		// Bundled theme name — look in assets/themes/.
		$url = File_Helper::assets_url() . '/themes/' . $this->theme_stylesheet . '.css';
		\wp_enqueue_style(
			'pc-settings-page-theme',
			$url,
			array( 'pc-settings-page-core' ),
			'2.0.0'
		);
	}
}
