<?php
/**
 * Task Lists Tests.
 *
 * @package Automattic\WooCommerce\Admin\Features
 */

use \Automattic\WooCommerce\Admin\Features\OnboardingTasks\Init as OnboardingTasks;
use \Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use \Automattic\WooCommerce\Admin\PageController;

/**
 * Class WC_Tests_Extended_Task_List
 */
class WC_Tests_Extended_Task_List extends WC_Unit_Test_Case {

	/**
	 * Overridden setUp method from PHPUnit
	 */
	public function setUp() {
		parent::setUp();

		// Test page registration data.
		$test_page = array(
			'id'        => 'woocommerce-test',
			'screen_id' => 'test_page',
			'path'      => add_query_arg( 'post_type', 'test_page', 'test.php' ),
			'title'     => array( 'Test' ),
			'js_page'   => 'test-js-page',
		);

		$controller = PageController::get_instance();

		// Connect test page to wc-admin.
		$controller->connect_page( $test_page );

		set_current_screen( 'test_page' );

		// Set the private current_page variable to test page.
		$reflection = new \ReflectionClass( $controller );
		$property   = $reflection->getProperty( 'current_page' );
		$property->setAccessible( true );
		$property->setValue( $controller, $test_page );
	}

	/**
	 * Verify that the extended task list items are added correctly.
	 */
	public function test_add_extended_task_list_item() {
		$setup_list    = TaskLists::get_list( 'setup' );
		$extended_list = TaskLists::get_list( 'extended' );

		// At least one task list should be visible to add a task.
		if ( $setup_list ) {
			$setup_list->hide();
		}
		if ( $extended_list ) {
			$extended_list->hide();
		}

		add_filter( 'woocommerce_get_registered_extended_tasks', array( $this, 'add_tasks' ), 10, 1 );

		OnboardingTasks::update_option_extended_task_list();

		$task_list                        = get_option( 'woocommerce_extended_task_list_items', array() );
		$task_list_contains_expected_task = in_array( 'test_task', $task_list, true );

		$this->assertEmpty( $task_list );
		$this->assertFalse( $task_list_contains_expected_task );

		if ( $setup_list ) {
			$setup_list->show();
		}

		OnboardingTasks::update_option_extended_task_list();

		$task_list                        = get_option( 'woocommerce_extended_task_list_items', array() );
		$task_list_contains_expected_task = in_array( 'test_task', $task_list, true );

		$this->assertNotEmpty( $task_list );
		$this->assertTrue( $task_list_contains_expected_task );
	}

	/**
	 * Verify that the extended task list items are removed correctly.
	 */
	public function test_remove_extended_task_list_item() {
		remove_filter( 'woocommerce_get_registered_extended_tasks', array( $this, 'add_tasks' ), 10, 1 );

		OnboardingTasks::update_option_extended_task_list();

		$task_list                        = get_option( 'woocommerce_extended_task_list_items', array() );
		$task_list_contains_expected_task = in_array( 'test_task', $task_list, true );

		$this->assertEmpty( $task_list );
		$this->assertFalse( $task_list_contains_expected_task );
	}

	/**
	 * Verify that the unregistered extended task list items are removed correctly.
	 */
	public function test_remove_unregistered_task_items() {
		add_filter( 'woocommerce_get_registered_extended_tasks', array( $this, 'add_tasks' ), 10, 1 );
		update_option( 'woocommerce_extended_task_list_items', array( 'test_task-2', 'test_task-3', 'test_task-4' ) );

		OnboardingTasks::update_option_extended_task_list();

		$task_list                        = get_option( 'woocommerce_extended_task_list_items', array() );
		$task_list_contains_expected_task = in_array( 'test_task', $task_list, true );

		$this->assertNotEmpty( $task_list );
		$this->assertTrue( $task_list_contains_expected_task );
	}

	/**
	 * Creates the method to add tasks.
	 *
	 * @param array $registered_tasks_list_items List of registered tasks.
	 * @return array $registered_tasks_list_items.
	 */
	public function add_tasks( $registered_tasks_list_items ) {
		$new_task_name = 'test_task';
		if ( ! in_array( $new_task_name, $registered_tasks_list_items, true ) ) {
			array_push( $registered_tasks_list_items, $new_task_name );
		}
		return $registered_tasks_list_items;
	}
}
