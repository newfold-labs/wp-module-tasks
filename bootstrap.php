<?php

use NewfoldLabs\WP\Module\Tasks\Tasks;
use NewfoldLabs\WP\ModuleLoader\Container;
use function NewfoldLabs\WP\ModuleLoader\register;

/**
 * Add the tables
 */
function nfd_tasks_setup_tables() {
	global $wpdb;

	// Maybe create the table
	if ( ! function_exists( 'maybe_create_table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE `{$wpdb->prefix}nfd_tasks` (
		task_id bigint(20) NOT NULL AUTO_INCREMENT,
		task_name varchar(63) NOT NULL,
		task_execute varchar(125),
		args longtext,
		num_retries int(2) UNSIGNED,
		task_interval int(2) UNSIGNED,
		task_priority int(2) UNSIGNED,
		task_status varchar(12),
		updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
		enabled tinyint(1),
		PRIMARY KEY  (task_id)
	) $charset_collate;";

	maybe_create_table( "{$wpdb->prefix}nfd_tasks", $sql );

	$sql = "CREATE TABLE `{$wpdb->prefix}nfd_task_results` (
		task_result_id bigint(20) NOT NULL AUTO_INCREMENT,
		task_name varchar(63) NOT NULL,
		stacktrace longtext,
		success tinyint(1),
		updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (task_result_id)
	) $charset_collate;";

	maybe_create_table( "{$wpdb->prefix}nfd_task_results", $sql );
}


/**
 * Drop the tables on plugin deactivation
 */
function nfd_tasks_purge_tables() {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}nfd_tasks`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}nfd_task_results`" );
}

if ( function_exists( 'add_action' ) ) {
	add_action(
		'plugins_loaded',
		function () {

			// Set Global Constants
			if ( ! defined( 'NFD_MODULE_TASKS_DIR' ) ) {
				define( 'NFD_MODULE_TASKS_DIR', __DIR__ );
			}

			if ( ! defined( 'NFD_MODULE_TASKS_TASK_TABLE_NAME' ) ) {
				define( 'NFD_MODULE_TASKS_TASK_TABLE_NAME', 'nfd_tasks' );
			}

			if ( ! defined( 'NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME' ) ) {
				define( 'NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME', 'nfd_task_results' );
			}

			register(
				[
					'name'     => 'tasks',
					'label'    => __( 'Tasks', 'newfold-tasks-module' ),
					'callback' => function ( Container $container ) {
						new Tasks( $container );
					},
					'isActive' => true,
					'isHidden' => true,
				]
			);

		}
	);

}
