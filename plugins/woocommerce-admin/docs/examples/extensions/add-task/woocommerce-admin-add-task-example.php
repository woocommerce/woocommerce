<?php
/**
 * Plugin Name: WooCommerce Admin Add Task Example
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the task.
 */
function add_my_task() {
	require_once __DIR__ . '/class-mytask.php';
	$task_lists = \Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists::instance();

	// Add the task to the extended list.
	$task_lists::add_task(
		'extended',
		new MyTask(
			$task_lists::get_list( 'extended' ),
		)
	);
}

add_action( 'init', 'add_my_task' );

/**
 * Register the scripts to fill the task content on the frontend.
 */
function add_task_register_script() {
	if (
		! class_exists( 'Automattic\WooCommerce\Internal\Admin\Loader' ) ||
		! \Automattic\WooCommerce\Admin\PageController::is_admin_or_embed_page()
	) {
		return;
	}

	$asset_file = require __DIR__ . '/dist/index.asset.php';
	wp_register_script(
		'add-task',
		plugins_url( '/dist/index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	wp_enqueue_script( 'add-task' );
}

add_action( 'admin_enqueue_scripts', 'add_task_register_script' );
