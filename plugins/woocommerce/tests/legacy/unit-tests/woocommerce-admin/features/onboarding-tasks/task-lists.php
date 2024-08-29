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

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

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
	 * Tear down
	 */
	public function tearDown(): void {
		TaskLists::clear_lists();
		TaskLists::init_default_lists();

		parent::tearDown();
	}

	/**
	 * Tests that the "woocommerce_admin_experimental_onboarding_tasklists" filter is able to append tasks to any tasklist.
	 */
	public function test_default_tasklists_can_be_add_by_onboarding_filter() {
		// Filter the default task lists.
		add_filter(
			'woocommerce_admin_experimental_onboarding_tasklists',
			function (
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

	/**
	 * Tests that hidden task lists don't return their tasks.
	 */
	public function test_tasklists_get_json_hidden_list() {
		// Create a new task list.
		$task_list = new TaskList(
			array(
				'id'    => 'test',
				'title' => 'Test',
			)
		);

		// Create a new task.
		$task = new TestTask(
			$task_list,
			array(
				'id' => 'wc-unit-test_tasklists_get_json_hidden_list',
			)
		);

		// Add task to task list.
		$task_list->add_task( $task );

		// Hide the task list.
		$task_list->hide();

		// Get the task list as JSON.
		$json = $task_list->get_json();

		// Assert that the task list is empty because it is hidden.
		$this->assertEmpty( $json['tasks'] );

		// Assert list is hidden.
		$this->assertTrue( $json['isHidden'] );
	}

	/**
	 * Tests that visible tasks do return their tasks.
	 */
	public function test_tasklists_get_json_visible_list() {
		// Create a new task list.
		$task_list = new TaskList(
			array(
				'id'    => 'test',
				'title' => 'Test',
			)
		);

		// Create a new task.
		$task = new TestTask(
			$task_list,
			array(
				'id' => 'wc-unit-test_tasklists_get_json_visible_list',
			)
		);

		// Add task to task list.
		$task_list->add_task( $task );

		// Make sure the list is visible.
		$task_list->unhide();

		// Get the task list as JSON.
		$json = $task_list->get_json();

		// Assert that the task list has one task.
		$this->assertCount( 1, $json['tasks'] );

		// Assert we have the task we added.
		$this->assertEquals( 'wc-unit-test_tasklists_get_json_visible_list', $json['tasks'][0]['id'] );
	}

	/**
	 * Tests the setup_tasks_remaining method.
	 */
	public function test_setup_tasks_remaining() {
		// Initialize the default task lists.
		TaskLists::add_list(
			array(
				'id'                      => 'setup',
				'title'                   => 'Setup',
				'tasks'                   => array(),
				'display_progress_header' => true,
				'event_prefix'            => 'tasklist_',
				'options'                 => array(
					'use_completed_title' => true,
				),
				'visible'                 => true,
			)
		);

		$setup_list = TaskLists::get_list( 'setup' );

		for ( $i = 1; $i <= 3; $i++ ) {
			TaskLists::add_task(
				'setup',
				new TestTask(
					$setup_list,
					array(
						'id' => "setup-task-{$i}",
					)
				)
			);
		}

		// Test when no tasks are completed.
		$this->assertEquals( 3, TaskLists::setup_tasks_remaining() );

		// Complete one task.
		update_option( Task::COMPLETED_OPTION, array( 'setup-task-1' ) );
		$this->assertEquals( 2, TaskLists::setup_tasks_remaining() );

		// Complete all tasks.
		update_option( Task::COMPLETED_OPTION, array( 'setup-task-1', 'setup-task-2', 'setup-task-3' ) );
		$this->assertEquals( 0, TaskLists::setup_tasks_remaining() );

		// Test when the setup list is hidden.
		$setup_list->hide();
		$this->assertNull( TaskLists::setup_tasks_remaining() );

		// Test when the setup list has been previously completed.
		$setup_list->unhide();
		update_option( TaskList::COMPLETED_OPTION, array( 'setup' ) );
		$this->assertNull( TaskLists::setup_tasks_remaining() );
	}
}
