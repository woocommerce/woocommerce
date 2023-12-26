<?php
/**
 * Custom task example.
 *
 * @package WooCommerce\Admin
 */

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Custom task class.
 */
class MyTask extends Task {
	/**
	 * Get the task ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'my-task';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'My task', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __( 'Add your task description here for display in the task list.', 'woocommerce' );
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}
}
