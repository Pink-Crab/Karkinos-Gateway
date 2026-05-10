<?php
/**
 * MU-Plugin: Bootstraps Perique and registers a test settings page
 * for Playwright E2E testing of the Settings Page module.
 *
 * Loaded by wp-env via .wp-env.json mappings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// Show PHP errors on screen for debugging.
ini_set( 'display_errors', '1' );
ini_set( 'display_startup_errors', '1' );
error_reporting( E_ALL );

// Override wp_die handler to show actual errors instead of the generic "critical error" page.
add_filter(
	'wp_die_handler',
	function () {
		return function ( $message, $title = '', $args = array() ) {
			if ( $message instanceof \WP_Error ) {
				$message = $message->get_error_message();
			}
			echo '<pre style="background:#fff;color:#c00;padding:20px;margin:20px;border:2px solid #c00;">';
			echo '<strong>wp_die:</strong> ' . esc_html( (string) $message ) . "\n";
			if ( $title ) {
				echo '<strong>Title:</strong> ' . esc_html( (string) $title ) . "\n";
			}
			echo '</pre>';
			die();
		};
	}
);

$plugin_path = WP_PLUGIN_DIR . '/Perique-Settings-Page';

if ( ! file_exists( $plugin_path . '/vendor/autoload.php' ) ) {
	$plugins = glob( WP_PLUGIN_DIR . '/*/vendor/pinkcrab/perique-framework-core' );
	if ( ! empty( $plugins ) ) {
		$plugin_path = dirname( $plugins[0], 3 );
	}
}

require_once $plugin_path . '/vendor/autoload.php';

// Load the test page and settings classes.
require_once __DIR__ . '/fixtures/Test_Kitchen_Sink_Settings.php';
require_once __DIR__ . '/fixtures/Test_Kitchen_Sink_Page.php';
require_once __DIR__ . '/fixtures/Test_Layout_Kitchen_Sink_Settings.php';
require_once __DIR__ . '/fixtures/Test_Layout_Kitchen_Sink_Page.php';
require_once __DIR__ . '/fixtures/Test_Repeater_Kitchen_Sink_Settings.php';
require_once __DIR__ . '/fixtures/Test_Repeater_Kitchen_Sink_Page.php';
require_once __DIR__ . '/fixtures/Test_Interactive_Kitchen_Sink_Settings.php';
require_once __DIR__ . '/fixtures/Test_Interactive_Kitchen_Sink_Page.php';
require_once __DIR__ . '/fixtures/Test_Repo_Individual_Settings.php';
require_once __DIR__ . '/fixtures/Test_Repo_Individual_Page.php';
require_once __DIR__ . '/fixtures/Test_Repo_Named_Groups_Settings.php';
require_once __DIR__ . '/fixtures/Test_Repo_Named_Groups_Page.php';
require_once __DIR__ . '/fixtures/Test_Repo_Site_Options_Settings.php';
require_once __DIR__ . '/fixtures/Test_Repo_Site_Options_Page.php';

// Theme showcase — one page per theme, shared settings.
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Settings.php';
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Vanilla_Page.php';
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Material_Page.php';
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Bootstrap_Page.php';
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Bootstrap_Classic_Page.php';
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Wp_Admin_Page.php';
require_once __DIR__ . '/fixtures/showcase/Theme_Showcase_Minimal_Page.php';

// Group discovery — exercises Hooks::GROUPS_PROCESSED listener + issue #58 dedupe.
require_once __DIR__ . '/fixtures/Test_Discovery_Settings_A.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Page_A.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Settings_B.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Page_B.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Settings_C.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Page_C.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Plain_Page.php';
require_once __DIR__ . '/fixtures/Test_Discovery_Group.php';

/*
 * E2E reset endpoint.
 *
 * The e2e spec hits any admin URL with `?kitchen_sink_reset=1` before each
 * test to wipe and re-seed the kitchen sink option to a known set of
 * defaults. We need this because Abstract_Settings::refresh_settings()
 * unconditionally overwrites field values from the repository — so the
 * "defaults" tests assert against must live in the option, not in the
 * Field::set_value() calls (those are dead code at runtime).
 */
add_action(
	'admin_init',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( isset( $_GET['kitchen_sink_reset'] ) ) {
			update_option(
				Test_Kitchen_Sink_Settings::OPTION_KEY,
				Test_Kitchen_Sink_Settings::default_values()
			);
			wp_safe_redirect( remove_query_arg( 'kitchen_sink_reset' ) );
			exit;
		}
		if ( isset( $_GET['repeater_reset'] ) ) {
			update_option(
				Test_Repeater_Kitchen_Sink_Settings::OPTION_KEY,
				Test_Repeater_Kitchen_Sink_Settings::default_values()
			);
			wp_safe_redirect( remove_query_arg( 'repeater_reset' ) );
			exit;
		}
		if ( isset( $_GET['interactive_reset'] ) ) {
			update_option(
				Test_Interactive_Kitchen_Sink_Settings::OPTION_KEY,
				Test_Interactive_Kitchen_Sink_Settings::default_values()
			);
			wp_safe_redirect( remove_query_arg( 'interactive_reset' ) );
			exit;
		}
		if ( isset( $_GET['repo_individual_reset'] ) ) {
			foreach ( Test_Repo_Individual_Settings::default_values() as $key => $val ) {
				update_option( 'ind_' . $key, $val );
			}
			wp_safe_redirect( remove_query_arg( 'repo_individual_reset' ) );
			exit;
		}
		if ( isset( $_GET['repo_named_groups_reset'] ) ) {
			// Seed each named group option directly.
			$defaults = Test_Repo_Named_Groups_Settings::default_values();
			update_option( 'ng_general', array(
				'ng_site_name' => $defaults['site_name'],
				'ng_tag_line'  => $defaults['tag_line'],
			) );
			update_option( 'ng_display', array(
				'ng_max_posts'    => $defaults['max_posts'],
				'ng_show_sidebar' => $defaults['show_sidebar'],
			) );
			wp_safe_redirect( remove_query_arg( 'repo_named_groups_reset' ) );
			exit;
		}
		if ( isset( $_GET['repo_site_options_reset'] ) ) {
			update_site_option(
				Test_Repo_Site_Options_Settings::OPTION_KEY,
				Test_Repo_Site_Options_Settings::default_values()
			);
			wp_safe_redirect( remove_query_arg( 'repo_site_options_reset' ) );
			exit;
		}
	}
);

/*
 * Seed test data (post + attachment) for picker and media library specs.
 *
 * Runs once on init; creates a published post titled "E2E Test Post" and
 * a dummy attachment titled "E2E Test Image" so the e2e specs have
 * deterministic data to search for and select.
 */
add_action(
	'init',
	function () {
		// Idempotent seed — check via a WP_Query exact title match.
		global $wpdb;

		$existing_post = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'post' AND post_status = 'publish' LIMIT 1",
				'E2E Test Post'
			)
		);

		if ( ! $existing_post ) {
			wp_insert_post( array(
				'post_title'   => 'E2E Test Post',
				'post_content' => 'Content for the e2e test post.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			) );
		}

		$existing_attachment = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'attachment' LIMIT 1",
				'E2E Test Image'
			)
		);

		if ( ! $existing_attachment ) {
			// Write a real 1x1 transparent PNG to uploads so the media modal
			// treats this as a full-fledged attachment (matters on fresh CI DBs).
			$upload_dir  = wp_upload_dir();
			$target_path = trailingslashit( $upload_dir['basedir'] ) . 'e2e-test-image.png';

			error_log( '[settings-page-bootstrap] seeding E2E Test Image attachment' );
			error_log( '[settings-page-bootstrap] uploads basedir: ' . $upload_dir['basedir'] );
			error_log( '[settings-page-bootstrap] target path: ' . $target_path );
			error_log( '[settings-page-bootstrap] uploads dir writable: ' . ( is_writable( $upload_dir['basedir'] ) ? 'YES' : 'NO' ) );
			if ( ! empty( $upload_dir['error'] ) ) {
				error_log( '[settings-page-bootstrap] wp_upload_dir error: ' . $upload_dir['error'] );
			}

			if ( ! file_exists( $target_path ) ) {
				$mkdir_ok = wp_mkdir_p( $upload_dir['basedir'] );
				error_log( '[settings-page-bootstrap] wp_mkdir_p: ' . ( $mkdir_ok ? 'OK' : 'FAILED' ) );

				$bytes_written = file_put_contents(
					$target_path,
					base64_decode(
						'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
					)
				);
				error_log( '[settings-page-bootstrap] file_put_contents: ' . var_export( $bytes_written, true ) . ' bytes' );
			} else {
				error_log( '[settings-page-bootstrap] file already exists' );
			}

			$attachment_id = wp_insert_attachment(
				array(
					'post_title'     => 'E2E Test Image',
					'post_mime_type' => 'image/png',
					'post_status'    => 'inherit',
				),
				$target_path,
				0
			);

			if ( is_wp_error( $attachment_id ) ) {
				error_log( '[settings-page-bootstrap] wp_insert_attachment WP_Error: ' . $attachment_id->get_error_message() );
			} elseif ( ! $attachment_id ) {
				error_log( '[settings-page-bootstrap] wp_insert_attachment returned 0 (failed)' );
			} else {
				error_log( '[settings-page-bootstrap] wp_insert_attachment OK, ID: ' . $attachment_id );

				require_once ABSPATH . 'wp-admin/includes/image.php';
				$metadata = wp_generate_attachment_metadata( $attachment_id, $target_path );
				error_log( '[settings-page-bootstrap] generated metadata keys: ' . implode( ',', array_keys( (array) $metadata ) ) );
				wp_update_attachment_metadata( $attachment_id, $metadata );
			}
		} else {
			error_log( '[settings-page-bootstrap] attachment already exists, ID: ' . $existing_attachment );
		}

		// Seed the theme showcase option if it doesn't exist yet.
		// Once seeded, the user is free to edit — we won't overwrite.
		if ( false === get_option( Theme_Showcase_Settings::OPTION_KEY ) ) {
			update_option(
				Theme_Showcase_Settings::OPTION_KEY,
				Theme_Showcase_Settings::default_values()
			);
		}
	}
);

use PinkCrab\Perique\Application\App_Factory;
use PinkCrab\Form_Components\Module\Form_Components;
use PinkCrab\Perique_Admin_Menu\Module\Admin_Menu;
use PinkCrab\Perique_Settings_Page\Registration\Settings_Page_Module;

( new App_Factory( $plugin_path ) )
	->set_base_view_path( __DIR__ . '/views' )
	->default_setup()
	->module( Form_Components::class )
	->module( Settings_Page_Module::class )
	->module( Admin_Menu::class )
	->registration_classes( array(
		Test_Kitchen_Sink_Page::class,
		Test_Layout_Kitchen_Sink_Page::class,
		Test_Repeater_Kitchen_Sink_Page::class,
		Test_Interactive_Kitchen_Sink_Page::class,
		Test_Repo_Individual_Page::class,
		Test_Repo_Named_Groups_Page::class,
		Test_Repo_Site_Options_Page::class,
		Theme_Showcase_Vanilla_Page::class,
		Theme_Showcase_Material_Page::class,
		Theme_Showcase_Bootstrap_Page::class,
		Theme_Showcase_Bootstrap_Classic_Page::class,
		Theme_Showcase_Wp_Admin_Page::class,
		Theme_Showcase_Minimal_Page::class,

		// Group discovery scenarios.
		Test_Discovery_Page_A::class,    // Settings_Page registered as a single (no Group involvement).
		Test_Discovery_Page_C::class,    // Duplicate — also declared inside Test_Discovery_Group's $pages.
		Test_Discovery_Group::class,     // Group: primary=Page_B, pages=[Page_B, Page_C, Plain_Page].
	) )
	->boot();
