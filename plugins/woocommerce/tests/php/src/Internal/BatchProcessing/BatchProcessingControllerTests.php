<?php
/**
 * Tests for BatchProcessingController class.
 */

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;

/**
 * Class BatchProcessingControllerTests.
 */
class BatchProcessingControllerTests extends WC_Unit_Test_Case {

	/**
	 * Instance of BatchProcessingController.
	 *
	 * @var BatchProcessingController;
	 */
	private $sut;

	/**
	 * @var DataSynchronizer
	 */
	private $test_process;

	/**
	 * Setup.
	 */
	public function setUp() : void {
		parent::setUp();
		$this->sut          = wc_get_container()->get( BatchProcessingController::class );
		$this->test_process = wc_get_container()->get( DataSynchronizer::class );
		$this->sut->force_clear_all_processes();
	}

	/**
	 * @testdox Processors are enqueued correctly.
	 */
	public function test_enqueue_processor() {
		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox Processors are scheduled via action scheduler as expected.
	 */
	public function test_schedule_processes() {
		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );

		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox When an action is run, then batch processing takes place. Also another instance is scheduled when there are pending actions.
	 */
	public function test_process_single_update_unfinished() {
		$test_process_mock = $this->getMockBuilder( get_class( $this->test_process ) )->getMock();
		$test_process_mock->expects( $this->once() )->method( 'process_batch' )->willReturn( true );
		$test_process_mock->method( 'get_total_pending_count' )->willReturn( 10 );
		$test_process_mock->expects( $this->once() )->method( 'get_next_batch_to_process' )->willReturn( array( 'dummy_id' ) );

		add_filter(
			'woocommerce_get_batch_processor',
			function() use ( $test_process_mock ) {
				return $test_process_mock;
			}
		);
		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		do_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, get_class( $this->test_process ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}

	/**
	 * @testdox When an action is run, then batch processing takes place. Also no further actions are scheduled when batch completes.
	 */
	public function test_process_single_update_finished() {
		$test_process_mock = $this->getMockBuilder( get_class( $this->test_process ) )->getMock();
		$test_process_mock->expects( $this->once() )->method( 'process_batch' )->willReturn( true );
		$test_process_mock->method( 'get_total_pending_count' )->willReturn( 0 );
		$test_process_mock->expects( $this->once() )->method( 'get_next_batch_to_process' )->willReturn( array( 'dummy_id' ) );

		add_filter(
			'woocommerce_get_batch_processor',
			function() use ( $test_process_mock ) {
				return $test_process_mock;
			}
		);
		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		do_action( $this->sut::PROCESS_SINGLE_BATCH_ACTION_NAME, get_class( $this->test_process ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
	}
}
