<?php

namespace NewfoldLabs\WP\Module\Tasks;

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Tasks\Models\Models;
use NewfoldLabs\WP\Module\Tasks\Scheduler;

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
	 * Constructor.
	 *
	 * @param Container $container The module loader container
	 */
	public function __construct( Container $container ) {

		$this->container = $container;

		if ( is_readable( MODULE_TASKS_DIR . '/vendor/autoload.php' ) ) {
			require_once MODULE_TASKS_DIR . '/vendor/autoload.php';
		}

		// Initialize the data models
		new Models();
		new Scheduler();
	}

}
