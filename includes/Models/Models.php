<?php
namespace NewfoldLabs\WP\Module\Tasks\Models;

/**
 * This class will be responsible for initializing all the data models.
 * The individual models will be responsible for creating and maintaining
 * their tables and data.
 */
final class Models {

	/**
	 * The models to be initialized in the constructor
	 *
	 * @var array
	 */
	protected $data_models = array(
		'NewfoldLabs\\WP\\Module\\Tasks\\Models\\Data\\Task',
		'NewfoldLabs\\WP\\Module\\Tasks\\Models\\Data\\TaskResult',
	);

	/**
	 * Initialize the data models
	 */
	public function __construct() {
		foreach ( $this->data_models as $data_model ) {
			$data_model::setup();
		}
	}
}
