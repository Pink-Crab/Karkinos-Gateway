<?php
/**
 * App_Config values.
 */

declare(strict_types=1);

return array(
	'path'       => array(
		'plugin' => __DIR__ . '/..',
		'view'   => __DIR__ . '/../views',
	),
	'url'        => array(
		'plugin' => plugin_dir_url( __DIR__ . '/..' ),
		'view'   => plugin_dir_url( __DIR__ . '/..' ) . 'views',
	),
	'namespaces' => array(
		'rest' => 'karkinos-gateway/v1',
	),
	'db_tables'  => array(
		'dispatch_jobs' => $GLOBALS['wpdb']->prefix . 'kg_dispatch_jobs',
	),
	'plugin'     => array(
		'version' => '0.1.0',
	),
);
