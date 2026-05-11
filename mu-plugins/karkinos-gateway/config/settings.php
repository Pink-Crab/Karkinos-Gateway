<?php
/**
 * App_Config values — source of truth for all physical names.
 *
 * Slugs, meta keys, table names, paths, and option keys all live here.
 * Code references them by alias via App_Config:
 *
 *   $config->post_types( 'ai_log' )
 *   $config->taxonomies( 'project' )
 *   $config->post_meta( 'dev_asset_type' )
 *   $config->term_meta( 'project_github_repo' )
 *   $config->db_tables( 'dispatch_jobs' )
 *   $config->path( 'webhook_logs' )
 *   $config->additional( 'webhook_log_files_option' )
 *
 * @package Karkinos\Gateway
 */

declare(strict_types=1);

use PinkCrab\Perique\Application\App_Config;

return array(
	'path'       => array(
		'plugin'       => __DIR__ . '/..',
		'view'         => __DIR__ . '/../views',
		'webhook_logs' => WP_CONTENT_DIR . '/karkinos-gateway-logs',
	),
	'url'        => array(
		'plugin' => plugin_dir_url( __DIR__ . '/..' ),
		'view'   => plugin_dir_url( __DIR__ . '/..' ) . 'views',
	),
	'namespaces' => array(
		'rest' => 'karkinos-gateway/v1',
	),
	'post_types' => array(
		'ai_log'    => 'ai_log',
		'dev_asset' => 'dev_asset',
	),
	'taxonomies' => array(
		'project'    => 'project',
		'ai_log_tag' => 'ai_log_tag',
	),
	'meta'       => array(
		App_Config::POST_META => array(
			'dev_asset_type'          => 'kg_dev_asset_type',
			'dev_asset_url'           => 'kg_dev_asset_url',
			'dev_asset_attachment_id' => 'kg_dev_asset_attachment_id',
		),
		App_Config::TERM_META => array(
			'project_github_repo' => 'kg_project_github_repo',
		),
	),
	'db_tables'  => array(
		'dispatch_jobs' => $GLOBALS['wpdb']->prefix . 'kg_dispatch_jobs',
	),
	'additional' => array(
		'webhook_log_files_option' => 'karkinos_gateway_webhook_log_files',
	),
	'plugin'     => array(
		'version' => '0.1.0',
	),
);
