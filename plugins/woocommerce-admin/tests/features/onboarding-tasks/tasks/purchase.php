<?php
/**
 * Test the TaskList class.
 *
 * @package WooCommerce\Admin\Tests\OnboardingTasks
 */

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskList;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Purchase;

/**
 * class WC_Tests_OnboardingTasks_TaskList
 */
class WC_Tests_OnboardingTasks_Task_Purchase extends WC_Unit_Test_Case {

	/**
	 * Task list.
	 *
	 * @var Task|null
	 */
	protected $task = null;

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->task = new Purchase( new TaskList() );
		set_transient(
			Onboarding::THEMES_TRANSIENT,
			array(
				'free'            => array( 'slug' => 'free' ),
				'paid'            => array(
					'slug'  => 'paid',
					'id'    => 12312,
					'price' => '&#36;79.00',
					'title' => 'theme title',
				),
				'paid_installed'  => array(
					'slug'         => 'paid_installed',
					'id'           => 12312,
					'price'        => '&#36;79.00',
					'title'        => 'theme title',
					'is_installed' => true,
				),
				'free_with_price' => array(
					'slug'  => 'free_with_price',
					'id'    => 12312,
					'price' => '&#36;0.00',
					'title' => 'theme title',
				),
			)
		);
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();
		delete_transient( Onboarding::THEMES_TRANSIENT );
		delete_option( Onboarding::PROFILE_DATA_OPTION );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_is_complete_if_no_remaining_products() {
		update_option( Onboarding::PROFILE_DATA_OPTION, array( 'product_types' => array( 'physical' ) ) );
		$this->assertEquals( true, $this->task->is_complete() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_is_not_complete_if_remaining_paid_products() {
		update_option( Onboarding::PROFILE_DATA_OPTION, array( 'product_types' => array( 'memberships' ) ) );
		$this->assertEquals( false, $this->task->is_complete() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_is_complete_if_no_paid_themes() {
		update_option(
			Onboarding::PROFILE_DATA_OPTION,
			array(
				'product_types' => array(),
				'theme'         => 'free',
			)
		);
		$this->assertEquals( true, $this->task->is_complete() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_is_not_complete_if_paid_theme_that_is_not_installed() {
		update_option(
			Onboarding::PROFILE_DATA_OPTION,
			array(
				'product_types' => array(),
				'theme'         => 'paid',
			)
		);
		$this->assertEquals( false, $this->task->is_complete() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_is_complete_if_paid_theme_that_is_installed() {
		update_option(
			Onboarding::PROFILE_DATA_OPTION,
			array(
				'product_types' => array(),
				'theme'         => 'paid_installed',
			)
		);
		$this->assertEquals( true, $this->task->is_complete() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_is_complete_if_free_theme_with_set_price() {
		update_option(
			Onboarding::PROFILE_DATA_OPTION,
			array(
				'product_types' => array(),
				'theme'         => 'free_with_price',
			)
		);
		$this->assertEquals( true, $this->task->is_complete() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_get_title_if_single_paid_item() {
		update_option(
			Onboarding::PROFILE_DATA_OPTION,
			array(
				'product_types' => array(),
				'theme'         => 'paid',
			)
		);
		$this->assertEquals( 'Add theme title to my store', $this->task->get_title() );
	}

	/**
	 * Test is_complete function of Purchase task.
	 */
	public function test_get_title_if_multiple_paid_themes() {
		update_option(
			Onboarding::PROFILE_DATA_OPTION,
			array(
				'product_types' => array( 'memberships' ),
				'theme'         => 'paid',
			)
		);
		$this->assertEquals( 'Add paid extensions to my store', $this->task->get_title() );
	}
}
