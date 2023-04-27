<?php
namespace NewfoldLabs\WP\Module\Tasks\Models;

/**
 * Tracks and stores the results for a particular task.
 */
final class TaskResult {

	/**
	 * The task result id
	 *
	 * @var int
	 */
	public $task_result_id;

	/**
	 * The task name for which we are storing the result
	 *
	 * @var string
	 */
	public $task_name;

	/**
	 * Stacktrace in case there was an error while executing the task
	 *
	 * @var string
	 */
	public $stacktrace;

	/**
	 * The task run status
	 *
	 * @var boolean
	 */
	public $success;

	/**
	 * The updated timestamp for a task result
	 *
	 * @var datetime
	 */
	public $updated;

	/**
	 * Setter function to assign a task name
	 *
	 * @param string $task_name the task name to use
	 */
	public function set_task_name( $task_name ) {
		$this->task_name = $task_name;
		return $this;
	}

	/**
	 * Setter function to set the stacktrace
	 *
	 * @param string $stacktrace the stacktrace
	 */
	public function set_stacktrace( $stacktrace ) {
		$this->stacktrace = $stacktrace;
		return $this;
	}

	/**
	 * Setter function to set task status
	 *
	 * @param boolean $success the task's status
	 */
	public function set_success( $success ) {
		$this->success = $success;
		return $this;
	}

	/**
	 * The constructor to create the table if not already present
	 *
	 * @param int $task_result_id The task id result if we have to populate the result with an entry from db
	 */
	public function __construct( $task_result_id = null ) {

		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		$this->task_result_id = $task_result_id;

		if ( $task_result_id ) {
			$task_result = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare( "select * from `{$wpdb->prefix}{$table_name}` where `task_result_id` = %d", $task_result_id )
			);
			$this->task_result_id = $task_result->task_result_id;
			$this->task_name      = $task_result->task_name;
			$this->stacktrace     = $task_result->stacktrace;
			$this->success        = $task_result->success;
			$this->updated        = $task_result->updated;
		}
	}

	/**
	 * Function to insert a task result to db
	 */
	public function record_task_result() {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		if ( ! $this->task_result_id ) {
			// Create an entry
			$wpdb->insert(
				$wpdb->prefix . $table_name,
				array(
					'task_name'  => $this->task_name,
					'stacktrace' => $this->stacktrace,
					'success'    => $this->success,
				)
			);
			$this->task_result_id = $wpdb->insert_id;
		}
	}

	/**
	 * Function to delete the task results which are way too old
	 */
	public static function delete_obsolete_results() {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$result = $wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"DELETE FROM `{$wpdb->prefix}{$table_name}` WHERE updated < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
		);

		return $result;
	}

	/**
	 * A function that deletes any given task result
	 */
	public function delete() {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME, array( 'task_result_id' => $this->task_result_id ) );
	}

	/**
	 * Get failed tasks with task name
	 *
	 * @param string $task_name The task name to get the failed results for
	 */
	public static function get_failed_tasks_by_name( $task_name ) {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$wpdb->prefix}{$table_name}` WHERE task_name = %s AND  success = 0",
				$task_name
			)
		);

		return $results;
	}

	/**
	 * Get succeeded tasks with task name
	 *
	 * @param string $task_name The task name to get the succeeded tasks for
	 */
	public static function get_succeeded_tasks_by_name( $task_name ) {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM `{$wpdb->prefix}{$table_name}` WHERE task_name = %s AND  success = 1",
				$task_name
			)
		);

		return $results;
	}

	/**
	 * A function to get all failed tasks
	 */
	public static function get_failed_tasks() {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_RESULTS_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$results = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM `{$wpdb->prefix}{$table_name}` WHERE success = 0"
		);

		return $results;
	}
}
