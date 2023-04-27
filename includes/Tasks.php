<?php

namespace NewfoldLabs\WP\Module\Tasks;

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Tasks\Scheduler;
use NewfoldLabs\WP\Module\Tasks\Models\Task;
use NewfoldLabs\WP\Module\Tasks\Models\TaskResult;

/**
 * Tasks's container to initialize the functionality
 */
class Tasks {

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * A function to add cron schedules from a WordPress action
	 *
	 * @param array $schedules Current schedules in WP
	 */
	public function sync_crons( $schedules ) {
		$task_system_schedules = get_option( 'wp_module_tasks_schedules', array() );

		if ( ! is_array( $task_system_schedules ) ) {
			return $schedules;
		}

		return array_merge( $task_system_schedules, $schedules );
	}

	/**
	 * This function will act as an action to execute a periodic task and record corresponding
	 * results. Only use it for periodic tasks.
	 *
	 * @param int $task_id The task id for the periodic task
	 */
	public function execute_periodic_task( $task_id ) {
		// Make a task object from the task data
		$task = new Task( $task_id );
		try {
			$task->execute();
			new TaskResult( $task->task_id, $task->task_name, null, null, true );
		} catch ( \Exception $exception ) {
			new TaskResult(
				$task->task_id,
				$task->task_name,
				null,
				$exception->getMessage() . $exception->getTraceAsString(),
				false
			);
		}
	}

	/**
	 * Constructor.
	 *
	 * @param Container $container The module loader container
	 */
	public function __construct( Container $container ) {

		$this->container = $container;

		if ( is_readable( NFD_MODULE_TASKS_DIR . '/vendor/autoload.php' ) ) {
			require_once NFD_MODULE_TASKS_DIR . '/vendor/autoload.php';
		}

		// Add the filter for syncing custom crons
		add_filter( 'cron_schedules', array( $this, 'sync_crons' ) );

		// Add the filter to add executable as a hook
		add_action( 'task_execution_hook', array( $this, 'execute_periodic_task' ), 10, 1 );

		// Initialize the scheduler
		new Scheduler();
	}

}
