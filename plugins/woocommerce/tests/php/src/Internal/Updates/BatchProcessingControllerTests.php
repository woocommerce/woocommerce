<?php
/**
 * Tests for BatchProcessingController class.
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\Updates\BatchProcessingController;

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
		$this->sut = wc_get_container()->get( BatchProcessingController::class );
		$this->test_process = wc_get_container()->get( DataSynchronizer::class );
		$this->sut->force_clear_all_processes();
	}

	/**
	 * Test that processed are registered correctly.
	 */
	public function test_enqueue_processor() {
		$this->assertFalse( $this->sut->is_batch_process_pending( get_class( $this->test_process ) ) );

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->assertTrue( $this->sut->is_batch_process_pending( get_class( $this->test_process ) ) );
	}

	/**
	 * Test that processes are scheduled via action scheduler as expected.
	 */
	public function test_schedule_processes() {
		$this->assertFalse( $this->sut->already_scheduled( get_class( $this->test_process ) ) );

		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::CONTROLLER_CRON_NAME );

		$this->assertTrue( $this->sut->already_scheduled( get_class( $this->test_process ) ) );
	}

	/**
	 * Test that when an action is run, then batch processing takes place. Also test that another instance is scheduled when there are pending actions.
	 */
	public function test_process_single_update_unfinished() {
		$test_process_mock = $this->getMockBuilder( get_class( $this->test_process ) )->getMock();
		$test_process_mock->expects( $this->once() )->method( 'process_batch' )->willReturn( true );

		add_filter(
			'woocommerce_get_batch_processor',
			function() use ( $test_process_mock ) {
				return $test_process_mock;
			}
		);
		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		do_action( $this->sut::SINGLE_PROCESS_MIGRATION_ACTION, get_class( $this->test_process ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertTrue( $this->sut->already_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_batch_process_pending( get_class( $this->test_process ) ) );
	}

	/**
	 * Test that when an action is run, then batch processing takes place. Also checks that no further actions are scheduled when batch completes.
	 */
	public function test_process_single_update_finished() {
		$test_process_mock = $this->getMockBuilder( get_class( $this->test_process ) )->getMock();
		$test_process_mock->expects( $this->once() )->method( 'process_batch' )->willReturn( false );
		$test_process_mock->expects( $this->once() )->method( 'mark_process_complete' );

		add_filter(
			'woocommerce_get_batch_processor',
			function() use ( $test_process_mock ) {
				return $test_process_mock;
			}
		);
		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		do_action( $this->sut::SINGLE_PROCESS_MIGRATION_ACTION, get_class( $this->test_process ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

		$this->assertFalse( $this->sut->already_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_batch_process_pending( get_class( $this->test_process ) ) );
	}
}
