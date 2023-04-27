<?php
namespace NewfoldLabs\WP\Module\Tasks\Models;

/**
 * Tracks and stores a task in the task table.
 * A task will have task_id, task_name, args, num_retries, interval and enabled as fields
 */
final class Task {

	/**
	 * The task id
	 *
	 * @var int
	 */
	public $task_id;

	/**
	 * The task name
	 *
	 * @var string
	 */
	public $task_name;

	/**
	 * The arguments to be used when executing the task
	 *
	 * @var array
	 */
	public $args;

	/**
	 * The number of times we need to retry the task
	 *
	 * @var int
	 */
	public $num_retries;

	/**
	 * The intervals in seconds after which we need to retry the task
	 *
	 * @var int
	 */
	public $task_interval;

	/**
	 * The priority for this task, defaults to 1
	 *
	 * @var int
	 */
	public $task_priority;

	/**
	 * If the task is enabled or not
	 *
	 * @var boolean
	 */
	public $enabled;

	/**
	 * The task executor function
	 *
	 * @var string
	 */
	public $task_execute;

	/**
	 * The task status, should be one of queued, processing, and finished.
	 * Defaults to queued.
	 *
	 * @var string
	 */
	public $task_status;

	/**
	 * Updated timestamp
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
	 * Setter function to set the args
	 *
	 * @param array $args the args as an associative array
	 */
	public function set_args( $args ) {
		$this->args = $args;
		return $this;
	}

	/**
	 * Setter function to set num_retries
	 *
	 * @param int $num_retries the number of retries to set for the task
	 */
	public function set_num_retries( $num_retries ) {
		$this->num_retries = $num_retries;
		return $this;
	}

	/**
	 * Setter function to set the task interval
	 *
	 * @param int $interval the interval in seconds to use for task, only required for periodic tasks
	 */
	public function set_task_interval( $interval ) {
		$this->task_interval = $interval;
		return $this;
	}

	/**
	 * Setter function to set the task priority
	 *
	 * @param int $priority the priority to set for task
	 */
	public function set_task_priority( $priority ) {
		$this->task_priority = $priority;
		return $this;
	}

	/**
	 * Setter function to set task enabled status
	 *
	 * @param boolean $enabled the task's enabled status
	 */
	public function set_enabled( $enabled ) {
		$this->enabled = $enabled;
		return $this;
	}

	/**
	 * Setter function to set the task executable
	 *
	 * @param string $task_execute the function to run when executing this task
	 */
	public function set_task_execute( $task_execute ) {
		$this->task_execute = $task_execute;
		return $this;
	}

	/**
	 * Set task status
	 *
	 * @param string $status one of processing, queued and finished
	 */
	public function set_task_status( $status ) {
		$this->task_status = $status;
		return $this;
	}

	/**
	 * The constructor to create the table if not already present
	 *
	 * @param int $id The task id, null by default, only pass it to initialize a task object with id
	 */
	public function __construct( $id = null ) {
		global $wpdb;

		// Initialize the task attributes
		$this->task_id = $id;

		if ( ! function_exists( 'wp_json_encode' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}

		$table_name = NFD_MODULE_TASKS_TASK_TABLE_NAME;

		if ( $id ) {
			// Get and populate the task with the required stuff
			$task = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare( "select * from `{$wpdb->prefix}{$table_name}` where `task_id` = %d", $id )
			);
			// Populate the task details from what we got
			$this->task_id       = $task->task_id;
			$this->task_name     = $task->task_name;
			$this->task_execute  = $task->task_execute;
			$this->args          = json_decode( $task->args, true );
			$this->num_retries   = $task->num_retries;
			$this->task_interval = $task->task_interval;
			$this->task_priority = $task->task_priority;
			$this->enabled       = 1 === $task->enabled;
			$this->task_status   = $task->task_status;
			$this->updated       = $task->updated;
		}
	}

	/**
	 * A function to queue a task, this will ensure the db entry for the task
	 */
	public function queue_task() {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_TABLE_NAME;

		if ( ! $this->task_id ) {
			// Create an entry
			$wpdb->insert(
				$wpdb->prefix . $table_name,
				array(
					'task_name'     => $this->task_name,
					'task_execute'  => $this->task_execute,
					'args'          => wp_json_encode( $this->args ),
					'num_retries'   => $this->num_retries,
					'task_interval' => $this->task_interval,
					'task_priority' => $this->task_priority || 10,
					'enabled'       => $this->enabled ? 1 : 0,
					'task_status'   => 'queued',
				)
			);
			$this->task_id     = $wpdb->insert_id;
			$this->task_status = 'queued';

			// If the task is periodic, just add the filter for WordPress cron to pick it up
			if ( $this->task_interval ) {
				$this->add_periodic_task_as_cron();
			}
		}
	}

	/**
	 * Add a test execute function. Only usable for one-off tasks
	 *
	 * @throws \Exception Exception when we cannot find the task executable.
	 */
	public function execute() {
		if ( ! is_callable( $this->task_execute ) ) {
			throw new \Exception( 'Unable to load task' );
		}

		call_user_func( $this->task_execute, $this->args );
	}

	/**
	 * A function that deletes any given task
	 */
	public function delete() {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . NFD_MODULE_TASKS_TASK_TABLE_NAME, array( 'task_id' => $this->task_id ) );
	}

	/**
	 * Function to add an arbitrary number of seconds as cron interval.
	 * To be used in add_filter when setting up the cron
	 */
	public function add_interval_schedule() {
		$interval = $this->task_interval;
		$key      = "{$interval}_seconds";
		$message  = "Once every {$interval} seconds";

		$current_schedules = get_option( 'wp_module_tasks_schedules', array() );

		if ( ! $current_schedules ) {
			add_option( 'wp_module_tasks_schedules', array() );
		}

		// Adds the schedule for the given intervals in seconds
		if ( ! array_key_exists( $key, $current_schedules ) || $interval !== $current_schedules[ $key ]['interval'] ) {
			$current_schedules[ $key ] = array(
				'interval' => $interval,
				'display'  => $message,
			);
		}

		update_option( 'wp_module_tasks_schedules', $current_schedules );
	}

	/**
	 * Function to update the task's status
	 *
	 * @param string $status One of queued, processing, and finished.
	 */
	public function update_task_status( $status ) {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_TABLE_NAME;

		if ( ! in_array( $status, array( 'processing', 'queued', 'finished' ), true ) ) {
			return;
		}

		$wpdb->update(
			$wpdb->prefix . $table_name,
			array( 'task_status' => $status ),
			array( 'task_id' => $this->task_id )
		);
	}

	/**
	 * Function to get all the tasks with a given task name
	 *
	 * @param string $task_name The task name
	 */
	public static function get_tasks_with_name( $task_name ) {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_TABLE_NAME;

		$required_tasks = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"select * from `{$wpdb->prefix}{$table_name}` where task_name = %s",
				$task_name
			)
		);

		return $required_tasks;
	}

	/**
	 * Function to get the tasks which are in processing state for quite some time
	 */
	public static function get_timed_out_tasks() {
		global $wpdb;
		$table_name = NFD_MODULE_TASKS_TASK_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$stuck_tasks = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT * FROM `{$wpdb->prefix}{$table_name}` WHERE task_status = \'processing\' AND updated < DATE_SUB(NOW(), INTERVAL 10 MINUTE)"
		);

		return $stuck_tasks;
	}

	/**
	 * Add the task to WordPress cron
	 */
	public function add_periodic_task_as_cron() {
		// Add the cron schedule
		$this->add_interval_schedule();

		// Register the cron task
		if ( ! wp_next_scheduled( 'task_execution_hook', array( $this->task_id ) ) ) {
			wp_schedule_event(
				time(),
				"{$this->task_interval}_seconds",
				'task_execution_hook',
				array( $this->task_id )
			);
		}
	}
}
