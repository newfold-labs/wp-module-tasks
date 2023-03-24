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
		$table_name = MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

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
			updated TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (task_result_id)
		) $charset_collate;";

		maybe_create_table( $wpdb->prefix . $table_name, $sql );
	}

	/**
	 * The constructor to create the table if not already present
	 *
	 * @param int     $task_result_id The task id result if we have to populate the result with an entry from db
	 * @param int     $task_id        The task id for which we are recording the result
	 * @param string  $task_name      The task name for which we are recording the result
	 * @param string  $stacktrace     The stack trace for the error
	 * @param boolean $success        If the task was successfully executed or not
	 */
	public function __construct( $task_result_id, $task_id = null, $task_name = null, $stacktrace = null, $success = null ) {

		global $wpdb;
		$table_name = MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		$this->task_id        = $task_id;
		$this->task_name      = $task_name;
		$this->task_result_id = $task_result_id;
		$this->stacktrace     = $stacktrace;
		$this->success        = $success;

		if ( ! $task_result_id ) {
			// Create an entry
			$wpdb->insert(
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
			$this->updated        = $task_result->updated;
		}
	}

	/**
	 * Function to delete the task results which are way too old
	 */
	public static function delete_obsolete_results() {
		global $wpdb;
		$table_name = MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$result = $wpdb->query(
			'DELETE FROM ' . $wpdb->prefix . $table_name .' WHERE updated < DATE_SUB(NOW(), INTERVAL 24 HOUR)'
		);

		return $result;
	}

	/**
	 * A function that deletes any given task result
	 */
	public function delete() {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . MODULE_TASKS_TASK_RESULTS_TABLE_NAME, array( 'task_result_id' => $this->task_result_id ) );
	}
}
