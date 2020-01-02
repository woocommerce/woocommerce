<?php
/**
 * Reports Generation Batch Queue Prioritizaion Tests
 *
 * @package WooCommerce\Tests\Reports
 * @since 3.5.0
 */

use Automattic\WooCommerce\Admin\ReportsSync;
use Automattic\WooCommerce\Admin\Overrides\WPPostStore;
use Automattic\WooCommerce\Admin\Schedulers\OrdersScheduler;

/**
 * Reports Generation Batch Queue Prioritizaion Test Class
 *
 * @package WooCommerce\Tests\Reports
 * @since 3.5.0
 */
class WC_Tests_Reports_Queue_Prioritization extends WC_REST_Unit_Test_Case {
	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		if ( class_exists( 'ActionScheduler' ) ) {
			remove_action( 'action_scheduler_run_queue', array( ActionScheduler::runner(), 'run' ) );
		}
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();
		if ( class_exists( 'ActionScheduler' ) ) {
			add_action( 'action_scheduler_run_queue', array( ActionScheduler::runner(), 'run' ) );
		}
	}

	/**
	 * Test that we're setting a priority on our actions.
	 */
	public function test_queue_action_sets_priority() {
		OrdersScheduler::schedule_action( 'import' );

		$actions = OrdersScheduler::queue()->search(
			array(
				'status'  => 'pending',
				'claimed' => false,
				'hook'    => OrdersScheduler::get_action( 'import' ),
			)
		);

		$this->assertCount( 1, $actions );

		$action_ids = array_keys( $actions );
		$action_id  = $action_ids[0];
		$action     = get_post( $action_id );

		$this->assertEquals( WPPostStore::JOB_PRIORITY, $action->menu_order );

		OrdersScheduler::clear_queued_actions();
	}
}


