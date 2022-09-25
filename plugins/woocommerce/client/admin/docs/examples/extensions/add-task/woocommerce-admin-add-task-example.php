<?php
/**
 * Plugin Name: WooCommerce Admin Add Task Example
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Internal\Admin\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;

/**
 * Register the task.
 */
function add_task_my_task() {
	TaskLists::add_task(
		'extended',
		array(
			'id'             => 'my-task',
			'title'          => __( 'My task', 'woocommerce-admin' ),
			'content'        => __(
				'Add your task description here for display in the task list.',
				'woocommerce-admin'
			),
			'action_label'   => __( 'Do action', 'woocommerce-admin' ),
			'is_complete'    => Task::is_task_actioned( 'my-task' ),
			'can_view'       => 'US' === WC()->countries->get_base_country(),
			'time'           => __( '2 minutes', 'woocommerce-admin' ),
			'is_dismissable' => true,
			'is_snoozeable'  => true,
		)
	);
}

add_action( 'init', 'add_task_my_task' );

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
