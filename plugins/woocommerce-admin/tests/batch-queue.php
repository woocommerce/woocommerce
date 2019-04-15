<?php
/**
 * Reports Generation Batch Queue Tests
 *
 * @package WooCommerce\Tests\Reports
 * @since 3.5.0
 */

/**
 * Reports Generation Batch Queue Test Class
 *
 * @package WooCommerce\Tests\Reports
 * @since 3.5.0
 */
class WC_Tests_Reports_Regenerate_Batching extends WC_REST_Unit_Test_Case {
	/**
	 * Queue batch size.
	 *
	 * @var integer
	 */
	public $queue_batch_size = 10;

	/**
	 * Customers batch size.
	 *
	 * @var integer
	 */
	public $customers_batch_size = 5;

	/**
	 * Orders batch size.
	 *
	 * @var integer
	 */
	public $orders_batch_size = 2;

	/**
	 * Force known values for batch size.
	 *
	 * @param int    $batch_size Batch size.
	 * @param string $action Action.
	 * @return int
	 */
	public function filter_batch_size( $batch_size, $action ) {
		switch ( $action ) {
			case WC_Admin_Reports_Sync::QUEUE_BATCH_ACTION:
				return $this->queue_batch_size;
			case WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION:
				return $this->customers_batch_size;
			case WC_Admin_Reports_Sync::ORDERS_BATCH_ACTION:
				return $this->orders_batch_size;
			default:
				return 1;
		}
	}

	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		$this->queue = new WC_Admin_Test_Action_Queue();
		WC_Admin_Reports_Sync::set_queue( $this->queue );
		add_filter( 'wc_admin_report_regenerate_batch_size', array( $this, 'filter_batch_size' ), 10, 2 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();
		WC_Admin_Reports_Sync::set_queue( null );
		$this->queue->actions = array();
		remove_filter( 'wc_admin_report_regenerate_batch_size', array( $this, 'filter_batch_size' ), 10, 2 );
	}

	/**
	 * Test that large batches get split properly.
	 */
	public function test_queue_batches_splits_into_batches_correctly() {
		$num_customers = 1234; // 1234 / 5 = 247 batches
		$num_batches   = ceil( $num_customers / $this->customers_batch_size );

		WC_Admin_Reports_Sync::queue_batches( 1, $num_batches, WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION );

		$this->assertCount( $this->queue_batch_size, $this->queue->actions );
		$this->assertArraySubset(
			array(
				'hook' => WC_Admin_Reports_Sync::QUEUE_BATCH_ACTION,
				'args' => array( 1, 25, WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION ),
			),
			$this->queue->actions[0]
		);
		$this->assertArraySubset(
			array(
				'hook' => WC_Admin_Reports_Sync::QUEUE_BATCH_ACTION,
				'args' => array( 226, 247, WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION ),
			),
			$this->queue->actions[ $this->queue_batch_size - 1 ]
		);
	}

	/**
	 * Test that small enough batches have their "single" action queued.
	 */
	public function test_queue_batches_schedules_single_actions() {
		$num_customers = 45; // 45 / 5 = 9 batches (which is less than the batch queue size)
		$num_batches   = ceil( $num_customers / $this->customers_batch_size );

		WC_Admin_Reports_Sync::queue_batches( 1, $num_batches, WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION );

		$this->assertCount( 9, $this->queue->actions );
		$this->assertArraySubset(
			array(
				'hook' => WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION,
				'args' => array( 1 ),
			),
			$this->queue->actions[0]
		);
		$this->assertArraySubset(
			array(
				'hook' => WC_Admin_Reports_Sync::CUSTOMERS_IMPORT_BATCH_ACTION,
				'args' => array( 9 ),
			),
			$this->queue->actions[8]
		);
	}

	/**
	 * Test that batch dependencies work.
	 */
	public function test_queue_dependent_action() {
		// reset back to using a real queue.
		WC_Admin_Reports_Sync::set_queue( null );

		// insert a blocking job.
		WC_Admin_Reports_Sync::queue()->schedule_single( time(), 'blocking_job', array( 'stuff' ), WC_Admin_Reports_Sync::QUEUE_GROUP );
		// queue an action that depends on blocking job.
		WC_Admin_Reports_Sync::queue_dependent_action( 'dependent_action', array(), 'blocking_job' );
		// verify that the action was properly blocked.
		$this->assertEmpty(
			WC_Admin_Reports_Sync::queue()->search(
				array(
					'hook' => 'dependent_action',
				)
			)
		);
		// verify that a follow up action was queued.
		$this->assertCount(
			1,
			WC_Admin_Reports_Sync::queue()->search(
				array(
					'hook' => WC_Admin_Reports_Sync::QUEUE_DEPEDENT_ACTION,
					'args' => array( 'dependent_action', array(), 'blocking_job' ),
				)
			)
		);

		// queue an action that isn't blocked.
		WC_Admin_Reports_Sync::queue_dependent_action( 'another_dependent_action', array(), 'nonexistant_blocking_job' );
		// verify that the dependent action was queued.
		$this->assertCount(
			1,
			WC_Admin_Reports_Sync::queue()->search(
				array(
					'hook' => 'another_dependent_action',
				)
			)
		);
		// verify that no follow up action was queued.
		$this->assertEmpty(
			WC_Admin_Reports_Sync::queue()->search(
				array(
					'hook' => WC_Admin_Reports_Sync::QUEUE_DEPEDENT_ACTION,
					'args' => array( 'another_dependent_action', array(), 'nonexistant_blocking_job' ),
				)
			)
		);

		// clean up.
		WC_Admin_Reports_Sync::queue()->cancel_all( 'another_dependent_action' );
		WC_Admin_Reports_Sync::queue()->cancel_all( WC_Admin_Reports_Sync::QUEUE_DEPEDENT_ACTION );
	}
}
