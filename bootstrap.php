<?php

use NewfoldLabs\WP\Module\Tasks\Tasks;
use NewfoldLabs\WP\ModuleLoader\Container;
use function NewfoldLabs\WP\ModuleLoader\register;

/**
 * Add the task table
 *
 * @param string $table_name The table name to be used for tasks
 */
function setup_task_table( $table_name ) {
	global $wpdb;

	// Maybe create the table
	if ( ! function_exists( 'maybe_create_table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE `{$wpdb->prefix}{$table_name}` (
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

	maybe_create_table( $wpdb->prefix . $table_name, $sql );
}

/**
 * Add the task results table
 *
 * @param string $table_name The table name to be used for task results
 */
function setup_task_results_table( $table_name ) {
	global $wpdb;

	// Maybe create the table
	if ( ! function_exists( 'maybe_create_table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE `{$wpdb->prefix}{$table_name}` (
		task_result_id bigint(20) NOT NULL AUTO_INCREMENT,
		task_name varchar(63) NOT NULL,
		stacktrace longtext,
		success tinyint(1),
		updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (task_result_id)
	) $charset_collate;";

	maybe_create_table( $wpdb->prefix . $table_name, $sql );
}

/**
 * Drop the tables on plugin deactivation
 */
function purge_tables( $task_table_name, $task_result_table_name ) {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS `$wpdb->prefix}{$task_table_name}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `$wpdb->prefix}{$task_result_table_name}`" );
}

if ( function_exists( 'add_action' ) ) {

	add_action(
		'plugins_loaded',
		function () {

			// Set Global Constants
			if ( ! defined( 'MODULE_TASKS_VERSION' ) ) {
				define( 'MODULE_TASKS_VERSION', '0.0.1' );
			}

			if ( ! defined( 'MODULE_TASKS_DIR' ) ) {
				define( 'MODULE_TASKS_DIR', __DIR__ );
			}

			if ( ! defined( 'MODULE_TASKS_TASK_TABLE_NAME' ) ) {
				define( 'MODULE_TASKS_TASK_TABLE_NAME', 'nfd_tasks' );
			}

			if ( ! defined( 'MODULE_TASKS_TASK_RESULTS_TABLE_NAME' ) ) {
				define( 'MODULE_TASKS_TASK_RESULTS_TABLE_NAME', 'nfd_task_results' );
			}

			// Make the table setup calls
			setup_task_table( MODULE_TASKS_TASK_TABLE_NAME );
			setup_task_results_table( MODULE_TASKS_TASK_RESULTS_TABLE_NAME );

			register_deactivation_hook( __FILE__, 'purge_tables' );

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
