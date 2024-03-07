<?php

namespace Automattic\WooCommerce\Blocks\Domain\Services\OnboardingTasks;

use Automattic\WooCommerce\Blocks\Domain\Services\OnboardingTasks\ReviewCheckoutTask;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;

/**
 * Onboarding Tasks Controller
 */
class TasksController {

	/**
	 * Init tasks.
	 */
	public function init() {
		add_action( 'init', [ $this, 'register_tasks' ] );
	}

	/**
	 * Register tasks.
	 */
	public function register_tasks() {
		TaskLists::add_task(
			'extended',
			new ReviewCheckoutTask()
		);
	}
}
