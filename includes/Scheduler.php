<?php

namespace NewfoldLabs\WP\Module\Tasks;

use NewfoldLabs\WP\Module\Tasks\Models\Data\TaskResult;
use NewfoldLabs\WP\Module\Tasks\Models\Data\Task;

/**
 * We will be handling the task check and execution from this class.
 * This will be added as a cron in WP-Cron with a 5 second interval.
 */
class Scheduler {

	/**
	 * The constructor is just supposed to execute the task and queue it in for a retry.
	 */
	public function __construct() {
		// Ensure there is a thirty second option in the cron schedules
		add_filter( 'cron_schedules', array( $this, 'add_interval_schedule' ) );

		add_action( 'scheduler_task_runner', array( $this, 'run_next_task' ) );

		add_action( 'cleanup_tasks', array( $this, 'cleanup' ) );

		// Register the cron task
		if ( ! wp_next_scheduled( 'scheduler_task_runner' ) ) {
			wp_schedule_event( time(), 'twenty_seconds', 'scheduler_task_runner' );
		}

		// Register the cleanup task
		if ( ! wp_next_scheduled( 'cleanup_tasks' ) ) {
			wp_schedule_event( time(), 'ten_minutes', 'cleanup_tasks' );
		}
	}

	/**
	 * Add a 20 seconds interval
	 *
	 * @param array $schedules The existing interval schedules
	 */
	public function add_interval_schedule( $schedules ) {
		// Adds the schedule for the given intervals in seconds
		if ( ! array_key_exists( 'twenty_seconds', $schedules ) || 20 !== $schedules[ 'twenty_seconds' ]['interval'] ) {
			$schedules[ 'twenty_seconds' ] = array(
				'interval' => 20,
				'display'  => __( 'Cron to run once every twenty seconds' ),
			);
		}

		// Adds the schedule for the given intervals in seconds
		if ( ! array_key_exists( 'ten_minutes', $schedules ) || 600 !== $schedules[ 'ten_minutes' ]['interval'] ) {
			$schedules[ 'ten_minutes' ] = array(
				'interval' => 600,
				'display'  => __( 'Cron to run once every ten minutes' ),
			);
		}

		return $schedules;
	}

	/**
	 * Get the tasks, and execute while handling the errors and retires
	 */
	public function run_next_task () {
		$table_name = MODULE_TASKS_TASK_TABLE_NAME;

		// Get the task we need to execute on the bases of priority
		global $wpdb;

		$task_data = $wpdb->get_row(
			// phpcs:ignore
			$wpdb->prepare(
				"select * from `{$wpdb->prefix}{$table_name}` where task_interval is null order by task_priority desc limit 1"
			)
		);

		// Increase the php execution time
		ini_set( 'max_execution_time', 300 );

		if ( ! $task_data ) {
			return;
		}

		if ( $task_data->task_status === 'processing' ) {
			return;
		}

		if ( ! function_exists( 'wp_json_encode' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}

		// Make a task object from the task data
		$task = new Task( $task_data->task_id );

		try {
			$task->update_task_status( 'processing' );
			$task->execute();
			new TaskResult( null, $task->task_id, $task->task_name, null, true );
		} catch ( \Exception $exception ) {
			new TaskResult(
				null,
				$task->task_id,
				$task->task_name,
				$exception->getMessage() . $exception->getTraceAsString(),
				false
			);
			if ( $task->num_retries > 1 ) {
				// Add the task to table again with lower priority
				new Task(
					null,
					$task->task_name,
					$task->task_executor_path,
					$task->task_execute,
					$task->args,
					$task->num_retries - 1,
					null,
					$task->task_priority >= 2 ? $task->task_priority - 1 : 1
				);
			}
		} finally {
			// Delete the task irrespective of if it failed or not because we would have already
			// queued it for retry with another entry if required
			$task->delete();
		}
	}

	/**
	 * Clean up the task result table and the timed out tasks.
	 */
	public function cleanup() {
		$stuck_tasks = Task::get_timed_out_tasks();

		foreach( $stuck_tasks as $stuck_task ) {
			$task = new Task( $stuck_task->task_id );
			// Mark the task as failed
			new TaskResult(
				null,
				$task->task_id,
				$task->task_name,
				'Task aborted due to timeout',
				false
			);
			if ( $task->num_retries > 1 ) {
				// Add the task to table again with lower priority
				new Task(
					null,
					$task->task_name,
					$task->task_executor_path,
					$task->task_execute,
					$task->args,
					$task->num_retries - 1,
					null,
					$task->task_priority >= 2 ? $task->task_priority - 1 : 1
				);
			}
			$task->delete();
		}

		TaskResult::delete_obsolete_results();
	}
}
