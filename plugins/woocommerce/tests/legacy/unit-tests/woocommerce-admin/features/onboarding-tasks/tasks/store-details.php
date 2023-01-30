<?php
/**
 * Test the Store details Task.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks\Tasks
 */

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\StoreDetails;

/**
 * class WC_Admin_Tests_OnboardingTasks_TaskList
 */
class WC_Admin_Tests_OnboardingTasks_Task_StoreDetails extends WC_Unit_Test_Case {


	/**
	 * Task list.
	 *
	 * @var Task|null
	 */
	protected $task = null;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->task_list = new TaskList();
		$this->task      = new StoreDetails( $this->task_list );
	}

	/**
	 * Test get_title function of StoreDetails task.
	 */
	public function test_get_title_default() {
		$this->assertEquals( 'Store details', $this->task->get_title() );
	}

	/**
	 * Test get_title function of StoreDetails task.
	 */
	public function test_get_title_with_use_completed_title_option() {
		$this->task_list->options['use_completed_title'] = true;
		$this->assertEquals( 'Add store details', $this->task->get_title() );
	}

	/**
	 * Test get_title function of StoreDetails task.
	 */
	public function test_completed_task_get_title_with_use_completed_title_option() {
		update_option( 'woocommerce_store_address', 'Market Street' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
		update_option( 'woocommerce_store_postcode', '1234' );
		$this->task_list->options['use_completed_title'] = true;
		$this->assertEquals( 'You added store details', $this->task->get_title() );
	}
}
