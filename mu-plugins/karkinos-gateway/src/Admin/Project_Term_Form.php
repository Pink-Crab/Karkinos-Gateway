<?php
/**
 * Term-edit form fields for the Project taxonomy.
 *
 * Adds a single `github_repo` text input to the Project taxonomy's add/edit
 * screens and persists it on create/update. The Registerables module
 * registers the term-meta itself (via Project::meta_data); this Hookable
 * only renders the admin UI.
 *
 * @package Karkinos\Gateway\Admin
 */

declare(strict_types=1);

namespace Karkinos\Gateway\Admin;

use PinkCrab\Loader\Hook_Loader;
use PinkCrab\Perique\Application\App_Config;
use PinkCrab\Perique\Interfaces\Hookable;
use WP_Term;

class Project_Term_Form implements Hookable {

	private const NONCE_ACTION = 'kg_project_term_save';
	private const NONCE_FIELD  = 'kg_project_term_nonce';

	/**
	 * Constructor.
	 *
	 * @param App_Config $app_config Resolves taxonomy slug + term-meta key.
	 */
	public function __construct( private App_Config $app_config ) {}

	/**
	 * Register the add/edit/save actions scoped to the Project taxonomy.
	 *
	 * @param Hook_Loader $loader Perique's hook collector.
	 *
	 * @return void
	 */
	public function register( Hook_Loader $loader ): void {
		$taxonomy = $this->app_config->taxonomies( 'project' );

		$loader->action( "{$taxonomy}_add_form_fields", array( $this, 'render_add_form' ) );
		$loader->action( "{$taxonomy}_edit_form_fields", array( $this, 'render_edit_form' ) );
		$loader->action( "created_{$taxonomy}", array( $this, 'save_meta' ) );
		$loader->action( "edited_{$taxonomy}", array( $this, 'save_meta' ) );
	}

	/**
	 * Render the field on the "Add new project" screen.
	 *
	 * @return void
	 */
	public function render_add_form(): void {
		$meta_key = $this->app_config->term_meta( 'project_github_repo' );
		?>
		<div class="form-field term-<?php echo esc_attr( $meta_key ); ?>-wrap">
			<label for="<?php echo esc_attr( $meta_key ); ?>"><?php esc_html_e( 'GitHub repo', 'karkinos-gateway' ); ?></label>
			<input
				type="text"
				id="<?php echo esc_attr( $meta_key ); ?>"
				name="<?php echo esc_attr( $meta_key ); ?>"
				value=""
				placeholder="org/repo"
			/>
			<p><?php esc_html_e( 'GitHub repo this project maps to (matches webhook payload repository.full_name).', 'karkinos-gateway' ); ?></p>
		</div>
		<?php
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
	}

	/**
	 * Render the field on the "Edit project" screen.
	 *
	 * @param WP_Term $term The term being edited.
	 *
	 * @return void
	 */
	public function render_edit_form( WP_Term $term ): void {
		$meta_key = $this->app_config->term_meta( 'project_github_repo' );
		$current  = (string) get_term_meta( $term->term_id, $meta_key, true );
		?>
		<tr class="form-field term-<?php echo esc_attr( $meta_key ); ?>-wrap">
			<th scope="row">
				<label for="<?php echo esc_attr( $meta_key ); ?>"><?php esc_html_e( 'GitHub repo', 'karkinos-gateway' ); ?></label>
			</th>
			<td>
				<input
					type="text"
					id="<?php echo esc_attr( $meta_key ); ?>"
					name="<?php echo esc_attr( $meta_key ); ?>"
					value="<?php echo esc_attr( $current ); ?>"
					placeholder="org/repo"
				/>
				<p class="description"><?php esc_html_e( 'GitHub repo this project maps to (matches webhook payload repository.full_name).', 'karkinos-gateway' ); ?></p>
			</td>
		</tr>
		<?php
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );
	}

	/**
	 * Persist the github_repo field on term create/update.
	 *
	 * Nonce + manage_categories capability checked before writing.
	 *
	 * @param int $term_id ID of the term just created/edited.
	 *
	 * @return void
	 */
	public function save_meta( int $term_id ): void {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_FIELD ] )
			? sanitize_text_field( wp_unslash( (string) $_POST[ self::NONCE_FIELD ] ) )
			: '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$meta_key = $this->app_config->term_meta( 'project_github_repo' );

		if ( ! isset( $_POST[ $meta_key ] ) ) {
			return;
		}

		$value = sanitize_text_field( wp_unslash( (string) $_POST[ $meta_key ] ) );
		update_term_meta( $term_id, $meta_key, $value );
	}
}
