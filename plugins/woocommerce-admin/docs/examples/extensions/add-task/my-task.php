<?php

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

class MyTask extends Task {
	public function get_id() {
		return 'my-task';
	}

	public function get_title() {
		return __( 'My task', 'woocommerce' );
	}

	public function get_content() {
		return __( 'Add your task description here for display in the task list.', 'woocommerce');
	}

	public function get_time() {
		return __( '2 minutes', 'woocommerce' );
	}
}
