<a href="https://newfold.com/" target="_blank">
    <img src="https://newfold.com/content/experience-fragments/newfold/site-header/master/_jcr_content/root/header/logo.coreimg.svg/1621395071423/newfold-digital.svg" alt="Newfold Logo" title="Newfold Digital" align="right" 
height="42" />
</a>

# WordPress Tasks Module
A task system for processing asynchronous tasks with wordpress, the tasks could be one-off long running tasks or periodic tasks.

## Module Responsibilities

- Define structures for both one-off and periodic tasks, including necessary parameters such as priority and task type.
- Maintain a queue of pending tasks in database.
- Provide interfaces for adding, updating, and removing tasks.
- Handle task prioritization to ensure that tasks are executed in the order of their priority.
- Implement scheduling mechanisms for both one-off and periodic tasks, ensuring timely execution according to predefined schedules.
- Execute one-off and periodic tasks asynchronously in the background, allowing the system to continue processing tasks without blocking.
- Remove completed tasks from the system after execution to maintain efficiency and prevent unnecessary resource usage.


## Critical Paths

- When a one-off or periodic task is scheduled, the scheduler component is responsible for picking up tasks based on their priority.
- The scheduler initiates the execution of tasks, ensuring that they are processed in the correct order.
- If a task fails during execution, the scheduler handles retries according to predefined policies, ensuring that tasks are eventually completed successfully.
 

## Installation

### 1. Add the Newfold Satis to your `composer.json`.

 ```bash
 composer config repositories.newfold composer https://newfold-labs.github.io/satis
 ```

### 2. Require the `newfold-labs/wp-module-tasks` package.

 ```bash
 composer require newfold-labs/wp-module-tasks
 ```

[More on NewFold WordPress Modules](https://github.com/newfold-labs/wp-module-loader)

## Tasks

The system intends to support one-off and periodic tasks.
<br />
One-off tasks will be the long running tasks which will be executed by the system in background in an asynchronous manner, these tasks will be picked up according to the priority assigned to them, results will be recorded and then the tasks will subsequently be removed from the system, i.e. we will not be tracking these tasks again unless they are inserted back with other parameters. Some examples of tasks like these could be packaging wordpress related items, sending emails, installing a plugin, updating a plugin, bulk installs, bulk updates etc.

### Setting up the module
In order for the module to work, we'd need tables for storing the tasks queue and the task results, please use the following functions in order to automatically create / delete the tables while activating and deactivating the plugin.

```php
register_activation_hook( __FILE__, 'nfd_tasks_setup_tables' );
register_deactivation_hook( __FILE__, 'nfd_tasks_purge_tables' );
```

### Adding a one-off task.

After you include the module as a dependency in your plugin, you can then schedule a one-off task for a function `foo` to be run as below:

```php
use NewfoldLabs\WP\Module\Tasks\Models\Task;

function foo( $args ) {
    // do something.
}
$task = new Task();
// or use any loaded function like \Class::static_func etc.
$task->set_task_name('hello')
    ->set_task_execute('foo')
    ->set_num_retries(2)
    ->set_args( $args )
    ->set_priority( 10 );
// Queue the task by
$task->queue_task();
```

The scheduler will take up tasks based on the task priority and take care of retries in case the task fails. The scheduler will also be responsible for recording the results for the task runs in a db called `wp_task_results`.

### Adding a periodic task.

The installation and including of the module works the same as in the case of one-off tasks, we can add periodic tasks very similar to how we add a one-off task, only difference is in the parameters we pass while creating that module like so:

```php
use NewfoldLabs\WP\Module\Tasks\Models\Task;

function foo( $args ) {
    // do something.
}

$task = new Task();
$task->set_name('foo_task')
    ->set_task_execute('foo')
    ->set_args( $args )
    ->set_interval(30)
$task->queue_task();
```

## Caveats
You can not be sure of the time when the one-off tasks will execute, since the system depends on wp-cron and which in turn depends on page loads, the tasks will not run unless there are enough page loads.



PS: It is possible to simulate page loads with a request to <site>/wp-cron.php, and hook this request with an actual cron to ensure that the tasks keep running when required

Refer: https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/ for more.
