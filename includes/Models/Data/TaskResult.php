<?php
namespace NewfoldLabs\WP\Module\Tasks\Models\Data;

/**
 * Tracks and stores the results for a particular task.
 */
final class TaskResult {

	/**
	 * Make a setup function to be only run when we activate the plugin
	 */
	public static function setup() {
		global $wpdb;
		$table_name      = MODULE_TASKS_TASK_RESULTS_TABLE_NAME;
		$task_table_name = MODULE_TASKS_TASK_TABLE_NAME;

		// Maybe create the table
		if ( ! function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE `{$wpdb->prefix}{$table_name}` (
			task_result_id bigint(20) NOT NULL AUTO_INCREMENT,
			task_id bigint(20) NOT NULL,
			task_name varchar(63) NOT NULL,
			stacktrace longtext,
			success tinyint(1),
			PRIMARY KEY  (task_result_id)
		) $charset_collate;";

		$created = maybe_create_table( $wpdb->prefix . $table_name, $sql );
	}

	/**
	 * The constructor to create the table if not already present
	 *
	 * @param int     $task_id        The task id for which we are recording the result
	 * @param string  $task_name      The task name for which we are recording the result
	 * @param int     $task_result_id The task id result if we have to populate the result with an entry from db
	 * @param string  $stacktrace     The stack trace for the error
	 * @param boolean $success        If the task was successfully executed or not
	 */
	public function __construct( $task_id, $task_name, $task_result_id = null, $stacktrace = null, $success = null ) {

		global $wpdb;
		$table_name = MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		$this->task_id        = $task_id;
		$this->task_name      = $task_name;
		$this->task_result_id = $task_result_id;
		$this->stacktrace     = $stacktrace;
		$this->success        = $success;

		if ( ! $task_result_id ) {
			// Create an entry
			$inserted             = $wpdb->insert(
				$wpdb->prefix . $table_name,
				array(
					'task_id'    => $task_id,
					'task_name'  => $task_name,
					'stacktrace' => $stacktrace,
					'success'    => $success,
				)
			);
			$this->task_result_id = $wpdb->insert_id;
		} else {
			$task_result = $wpdb->get_row(
				// phpcs:ignore
				$wpdb->prepare( "select * from `{$wpdb->prefix}{$table_name}` where `task_result_id` = %d", $task_result_id )
			);
			$this->task_result_id = $task_result->task_result_id;
			$this->task_id        = $task_result->task_id;
			$this->task_name      = $task_result->task_name;
			$this->stacktrace     = $task_result->stacktrace;
			$this->success        = $task_result->success;
		}
	}
}
