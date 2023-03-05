<?php

use NewfoldLabs\WP\Module\Tasks\Tasks;
use NewfoldLabs\WP\ModuleLoader\Container;
use function NewfoldLabs\WP\ModuleLoader\register;

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
				define( 'MODULE_TASKS_TASK_TABLE_NAME', 'tasks' );
			}

			if ( ! defined( 'MODULE_TASKS_TASK_RESULTS_TABLE_NAME' ) ) {
				define( 'MODULE_TASKS_TASK_RESULTS_TABLE_NAME', 'task_results' );
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
