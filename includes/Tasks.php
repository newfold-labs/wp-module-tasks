<?php

namespace NewfoldLabs\WP\Module\Tasks;

use NewfoldLabs\WP\ModuleLoader\Container;

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

		if ( is_readable( MODULE_MAESTRO_DIR . '/vendor/autoload.php' ) ) {
			require_once MODULE_MAESTRO_DIR . '/vendor/autoload.php';
		}
	}

}
