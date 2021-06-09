<?php
/**
 * Plugin Name: WooCommerce Admin Add Task Example
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * Register the task list item and the JS.
 */
function add_task_register_script() {

	if (
		! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) ||
		! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page() ||
		! Onboarding::should_show_tasks()
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

	$client_data = array(
		'isComplete' => get_option( 'woocommerce_admin_add_task_example_complete', false ),
	);
	wp_localize_script( 'add-task', 'addTaskData', $client_data );
	wp_enqueue_script( 'add-task' );
	add_filter( 'woocommerce_get_registered_extended_tasks', 'pluginprefix_register_extended_task', 10, 1 );
}

/**
 * Register task.
 *
 * @param array $registered_tasks_list_items List of registered extended task list items.
 */
function pluginprefix_register_extended_task( $registered_tasks_list_items ) {
	$new_task_name = 'woocommerce_admin_add_task_example_name';
	if ( ! in_array( $new_task_name, $registered_tasks_list_items, true ) ) {
		array_push( $registered_tasks_list_items, $new_task_name );
	}
	return $registered_tasks_list_items;
}

add_action( 'admin_enqueue_scripts', 'add_task_register_script' );
