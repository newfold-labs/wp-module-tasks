<?php
namespace NewfoldLabs\WP\Module\Tasks\Models\Data;

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
	 * The task executor path
	 *
	 * @var string
	 */
	public $task_executor_path;

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
	 * Make a setup function to be only run when we activate the plugin
	 */
	public static function setup() {
		global $wpdb;
		$table_name = MODULE_TASKS_TASK_TABLE_NAME;

		// Maybe create the table
		if ( ! function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE `{$wpdb->prefix}{$table_name}` (
			task_id bigint(20) NOT NULL AUTO_INCREMENT,
			task_name varchar(63) NOT NULL,
			task_executor_path varchar(125),
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
	 * The constructor to create the table if not already present
	 *
	 * @param int     $id                 The task id, null by default, only pass it to initialize a task object with id
	 * @param string  $task_name          The human readable name for the task
	 * @param string  $task_executor_path The task executor path
	 * @param string  $task_execute       Function to be executed for the task
	 * @param array   $args               The arguments to be used while executing the task
	 * @param int     $num_retries        The number of times we should retry the task, default: 0
	 * @param int     $task_interval      The interval in seconds task be configured for, defaults to null for one-off tasks
	 * @param int     $task_priority      The priority for the task execution, higher number means higher priority, can be 2 digits as max, defaults to 1
	 * @param boolean $enabled            Toggle to enable / disable a task, defaults to true
	 *
	 * @throws TaskModuleException Thrown when we encounter an error.
	 */
	public function __construct(
		$id = null,
		$task_name = null,
		$task_executor_path = null,
		$task_execute = null,
		$args = null,
		$num_retries = 0,
		$task_interval = null,
		$task_priority = null,
		$enabled = true
	) {
		global $wpdb;

		// Initialize the task attributes
		$this->task_id            = $id;
		$this->task_name          = $task_name;
		$this->task_executor_path = $task_executor_path;
		$this->task_execute       = $task_execute;
		$this->args               = $args;
		$this->num_retries        = $num_retries;
		$this->task_interval      = $task_interval;
		$this->task_priority      = $task_priority;
		$this->enabled            = $enabled;

		if ( ! function_exists( 'wp_json_encode' ) ) {
			require_once ABSPATH . 'wp-includes/functions.php';
		}

		$table_name = MODULE_TASKS_TASK_TABLE_NAME;

		if ( ! $id ) {
			// Create an entry
			$wpdb->insert(
				$wpdb->prefix . $table_name,
				array(
					'task_name'          => $task_name,
					'task_executor_path' => $task_executor_path,
					'task_execute'       => $task_execute,
					'args'               => wp_json_encode( $args ),
					'num_retries'        => $num_retries,
					'task_interval'      => $task_interval,
					'task_priority'      => $task_priority,
					'enabled'            => $enabled ? 1 : 0,
					'task_status'        => 'queued',
				)
			);
			$this->task_id     = $wpdb->insert_id;
			$this->task_status = 'queued';

			// If the task is periodic, just add the filter for WordPress cron to pick it up
			if ( $this->task_interval ) {
				$this->add_periodic_task_as_cron();
			}
		} else {
			// Get and populate the task with the required stuff
			$task = $wpdb->get_row(
				// phpcs:ignore
				$wpdb->prepare( "select * from `{$wpdb->prefix}{$table_name}` where `task_id` = %d", $id )
			);
			// Populate the task details from what we got
			$this->task_id            = $task->task_id;
			$this->task_name          = $task->task_name;
			$this->task_executor_path = $task->task_executor_path;
			$this->task_execute       = $task->task_execute;
			$this->args               = json_decode( $task->args, true );
			$this->num_retries        = $task->num_retries;
			$this->task_interval      = $task->task_interval;
			$this->task_priority      = $task->task_priority;
			$this->enabled            = 1 === $task->enabled;
			$this->task_status        = $task->task_status;
			$this->updated            = $task->updated;
		}
	}

	/**
	 * Add a test execute function. Only usable for one-off tasks
	 *
	 * @throws \Exception Exception when we cannot find the task executable.
	 */
	public function execute() {
		if ( ! function_exists( $this->task_execute ) ) {
			require_once $this->task_executor_path;
		}

		if ( ! function_exists( $this->task_execute ) ) {
			throw new \Exception( 'Unable to load task' );
		}

		( $this->task_execute )( $this->args );
	}

	/**
	 * A function that deletes any given task
	 */
	public function delete() {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . MODULE_TASKS_TASK_TABLE_NAME, array( 'task_id' => $this->task_id ) );
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
				// phpcs:ignore
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
		$table_name = MODULE_TASKS_TASK_TABLE_NAME;

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
		$table_name = MODULE_TASKS_TASK_TABLE_NAME;

		$required_tasks = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore
				"select * from `{$wpdb->prefix}{$table_name}` where task_name = %s", $task_name
			)
		);

		return $required_tasks;
	}

	/**
	 * Function to get the tasks which are in processing state for quite some time
	 */
	public static function get_timed_out_tasks() {
		global $wpdb;
		$table_name = MODULE_TASKS_TASK_TABLE_NAME;

		// Get the tasks with processing status and updated more than 2 hours
		$stuck_tasks = $wpdb->get_results(
			//phpcs:ignore
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
