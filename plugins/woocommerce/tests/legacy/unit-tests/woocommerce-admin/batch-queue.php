<?php
/**
 * Reports Generation Batch Queue Tests
 *
 * @package WooCommerce\Admin\Tests\Reports
 * @since 3.5.0
 */

use Automattic\WooCommerce\Internal\Admin\Schedulers\CustomersScheduler;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Reports Generation Batch Queue Test Class
 *
 * @package WooCommerce\Admin\Tests\Reports
 * @since 3.5.0
 */
class WC_Admin_Tests_Reports_Regenerate_Batching extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;

	/**
	 * Queue batch size.
	 *
	 * @var integer
	 */
	public $queue_batch_size = 100;

	/**
	 * Customers batch size.
	 *
	 * @var integer
	 */
	public $customers_batch_size = 5;

	/**
	 * Force known values for batch size.
	 *
	 * @param int    $batch_size Batch size.
	 * @param string $scheduler_name Scheduler name.
	 * @param string $action Action.
	 * @return int
	 */
	public function filter_batch_size( $batch_size, $scheduler_name, $action ) {
		switch ( "{$scheduler_name}_{$action}" ) {
			case 'customers_queue_batches':
			case 'orders_queue_batches':
				return $this->queue_batch_size;
			case 'customers_import_batch':
				return $this->customers_batch_size;
			default:
				return 1;
		}
	}

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->queue = new WC_Admin_Test_Action_Queue();
		CustomersScheduler::set_queue( $this->queue );
		OrdersScheduler::set_queue( $this->queue );
		add_filter( 'woocommerce_admin_scheduler_batch_size', array( $this, 'filter_batch_size' ), 10, 3 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		CustomersScheduler::set_queue( null );
		OrdersScheduler::set_queue( null );
		$this->queue->actions = array();
		remove_filter( 'woocommerce_admin_scheduler_batch_size', array( $this, 'filter_batch_size' ), 10, 3 );
	}

	/**
	 * Test that large batches get split properly.
	 */
	public function test_queue_batches_splits_into_batches_correctly() {
		$num_customers = 1234; // 1234 / 5 = 247 batches
		$num_batches   = (int) ceil( $num_customers / $this->customers_batch_size );
		$chunk_size    = (int) ceil( $num_batches / $this->queue_batch_size );
		$num_chunks    = (int) ceil( $num_batches / $chunk_size );

		CustomersScheduler::queue_batches( 1, $num_batches, 'import_batch' );

		$this->assertCount( $num_chunks, $this->queue->actions );
		$this->assertArraySubset(
			array(
				'hook' => CustomersScheduler::get_action( 'queue_batches' ),
				'args' => array( 1, $chunk_size, 'import_batch' ),
			),
			$this->queue->actions[0]
		);
		$this->assertArraySubset(
			array(
				'hook' => CustomersScheduler::get_action( 'queue_batches' ),
				'args' => array( 247, 247, 'import_batch' ),
			),
			$this->queue->actions[ $num_chunks - 1 ]
		);
	}

	/**
	 * Test that small enough batches have their "single" action queued.
	 */
	public function test_queue_batches_schedules_single_actions() {
		$num_customers = 45; // 45 / 5 = 9 batches (which is less than the batch queue size)
		$num_batches   = ceil( $num_customers / $this->customers_batch_size );

		CustomersScheduler::queue_batches( 1, $num_batches, 'import_batch' );

		$this->assertCount( 9, $this->queue->actions );
		$this->assertArraySubset(
			array(
				'hook' => CustomersScheduler::get_action( 'import_batch' ),
				'args' => array( 1 ),
			),
			$this->queue->actions[0]
		);
		$this->assertArraySubset(
			array(
				'hook' => CustomersScheduler::get_action( 'import_batch' ),
				'args' => array( 9 ),
			),
			$this->queue->actions[8]
		);
	}

	/**
	 * Test that batch dependencies work.
	 */
	public function test_queue_dependent_action() {
		// Reset back to using a real queue.
		CustomersScheduler::set_queue( null );
		OrdersScheduler::set_queue( null );

		// Schedule an action that depends on blocking job.
		OrdersScheduler::schedule_action( 'import_batch_init', array( 1, false ) );
		// Insert a blocking job.
		CustomersScheduler::schedule_action( 'import_batch_init', array( 1, false ) );
		// Verify that the action was properly blocked.
		$this->assertCount(
			1,
			OrdersScheduler::queue()->search(
				array(
					'hook' => OrdersScheduler::get_action( 'import_batch_init' ),
				)
			)
		);
		// Verify that a second follow up action was queued.
		WC_Helper_Queue::run_all_pending();
		$this->assertCount(
			2,
			OrdersScheduler::queue()->search(
				array(
					'hook' => OrdersScheduler::get_action( 'import_batch_init' ),
				)
			)
		);

		// Queue an action that isn't blocked.
		OrdersScheduler::schedule_action( 'import', array( 0 ) );
		// Verify that the dependent action was queued.
		$this->assertCount(
			1,
			OrdersScheduler::queue()->search(
				array(
					'status' => 'pending',
					'hook'   => OrdersScheduler::get_action( 'import' ),
				)
			)
		);
		// Verify that no follow up action was queued.
		WC_Helper_Queue::run_all_pending();
		$this->assertCount(
			0,
			OrdersScheduler::queue()->search(
				array(
					'status' => 'pending',
					'hook'   => OrdersScheduler::get_action( 'import' ),
				)
			)
		);

		// clean up.
		CustomersScheduler::clear_queued_actions();
		OrdersScheduler::clear_queued_actions();
	}
}
