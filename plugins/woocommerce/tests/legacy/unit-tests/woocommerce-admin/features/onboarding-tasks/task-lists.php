<?php
/**
 * Test the TaskLists class.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks
 */

/**
 * Test task fixture.
 */
require_once __DIR__ . '/test-task.php';

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;

/**
 * Class WC_Tests_OnboardingTasks_TaskLists
 */
class WC_Tests_OnboardingTasks_TaskLists extends WC_Unit_Test_Case {
	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();
		TaskLists::clear_lists();
	}

	/**
	 * Tests that the "woocommerce_admin_experimental_onboarding_tasklists" filter is able to append tasks to any tasklist.
	 */
	public function test_default_tasklists_can_be_add_by_onboarding_filter() {
		// Filter the default task lists.
		add_filter(
			'woocommerce_admin_experimental_onboarding_tasklists',
			function(
			$task_lists
			) {
				$this->assertIsArray( $task_lists );

				// Add a new task list.
				$task_lists['test'] = new TaskList(
					array(
						'id'       => 'test',
						'title'    => 'Test',
						'tasks'    => array(),
						'isHidden' => false,
					)
				);
				return $task_lists;
			}
		);

		// Initialize the default task lists.
		TaskLists::init_default_lists();

		// Assert that the new task list is added.
		$this->assertNotEmpty( TaskLists::get_list( 'test' ) );
	}
}
