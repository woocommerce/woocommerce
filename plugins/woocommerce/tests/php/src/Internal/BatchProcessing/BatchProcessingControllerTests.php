<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\BatchProcessing;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;

/**
 * Class BatchProcessingControllerTests.
 */
class BatchProcessingControllerTests extends \WC_Unit_Test_Case {

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
	 * @testdox 'remove_processor' dequeues and unschedules a processor, but the watchdog is kept alive if more processors are still enqueued.
	 */
	public function test_remove_processor_when_others_are_still_enqueued() {
		$second_processor = $this->get_processor_stub();

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->sut->enqueue_processor( get_class( $second_processor ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );

		$this->sut->remove_processor( get_class( $second_processor ) );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );
	}

	/**
	 * @testdox 'remove_processor' dequeues and unschedules a processor, and unschedules the watchdog if no more more processors are enqueued.
	 */
	public function test_remove_processor_when_no_others_remain_enqueued() {
		$this->sut->enqueue_processor( get_class( $this->test_process ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );

		$this->sut->remove_processor( get_class( $this->test_process ) );

		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );
	}

	/**
	 * Get a no-op batch processor.
	 *
	 * @return BatchProcessorInterface
	 */
	private function get_processor_stub(): BatchProcessorInterface {
		//phpcs:disable Squiz.Commenting
		return new class() implements BatchProcessorInterface {
			public function get_name(): string {
				return '';
			}

			public function get_description(): string {
				return '';
			}

			public function get_total_pending_count(): int {
				return 1;
			}

			public function get_next_batch_to_process( int $size ): array {
				return array();
			}

			public function process_batch( array $batch ): void {
			}

			public function get_default_batch_size(): int {
				return 1;
			}
		};
		//phpcs:enable Squiz.Commenting
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
		$test_process_mock->method( 'get_total_pending_count' )->willReturn( 10 );
		$test_process_mock->expects( $this->exactly( 2 ) )->method( 'get_next_batch_to_process' )->willReturn( array( 'dummy_id' ) );

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
		$test_process_mock->method( 'get_total_pending_count' )->willReturn( 0 );
		$test_process_mock
			->expects( $this->exactly( 2 ) )
			->method( 'get_next_batch_to_process' )
			->willReturnCallback(
				function ( $batch_size ) {
					return 1 === $batch_size ? array() : array( 'dummy_id' );
				}
			);
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

	/**
	 * @testdox 'test_force_clear_all_processes' dequeues and unschedules all the processors, and unschedules the watchdog.
	 */
	public function test_force_clear_all_processes() {
		$second_processor = $this->get_processor_stub();

		$this->sut->enqueue_processor( get_class( $this->test_process ) );
		$this->sut->enqueue_processor( get_class( $second_processor ) );

		//phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( $this->sut::WATCHDOG_ACTION_NAME );

		$this->assertTrue( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertTrue( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertTrue( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertTrue( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );

		$this->sut->force_clear_all_processes();

		$this->assertFalse( $this->sut->is_enqueued( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $this->test_process ) ) );
		$this->assertFalse( $this->sut->is_enqueued( get_class( $second_processor ) ) );
		$this->assertFalse( $this->sut->is_scheduled( get_class( $second_processor ) ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::WATCHDOG_ACTION_NAME ) );
	}
}
