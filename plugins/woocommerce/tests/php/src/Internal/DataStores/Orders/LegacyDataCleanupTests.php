<?php
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\LegacyDataCleanup;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Tests for the {@see LegacyDataCleanup} class.
 */
class LegacyDataCleanupTests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * @var LegacyDataCleanup
	 */
	private $sut;

	/**
	 * Initializes system under test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();

		$this->sut = wc_get_container()->get( LegacyDataCleanup::class );
	}

	/**
	 * Destroys system under test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
	}

	/**
	 * Validates that cleanup is only allowed to be enabled when HPOS is active and compat mode is disabled.
	 *
	 * @testWith [false, false, "no"]
	 *           [false, true, "no"]
	 *           [true, true, "no"]
	 *           [true, false, "yes"]
	 *
	 * @param bool   $hpos_enabled                  Whether to enable HPOS or not.
	 * @param bool   $sync_enabled                  Whether to enable compat mode or not.
	 * @param string $expected_cleanup_option_value Expected value for the legacy cleanup feature option.
	 */
	public function test_conditions_for_enabling( $hpos_enabled, $sync_enabled, $expected_cleanup_option_value ) {
		$this->toggle_cot_authoritative( $hpos_enabled );

		if ( $sync_enabled ) {
			$this->enable_cot_sync();
		} else {
			$this->disable_cot_sync();
		}

		update_option( $this->sut::OPTION_NAME, 'yes' );
		$this->assertEquals( $expected_cleanup_option_value, get_option( $this->sut::OPTION_NAME ) );
		$this->assertEquals( wc_string_to_bool( $expected_cleanup_option_value ), $this->sut->is_flag_set() );
	}

	/**
	 * Tests that the batch processor is correctly enqueued and removed when the option is updated.
	 */
	public function test_batch_process_enqueing() {
		$batch_processing = wc_get_container()->get( BatchProcessingController::class );

		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();
		update_option( $this->sut::OPTION_NAME, 'yes' );
		$this->assertTrue( $batch_processing->is_enqueued( get_class( $this->sut ) ) );
		$this->assertFalse( $batch_processing->is_enqueued( DataSynchronizer::class ) );

		update_option( $this->sut::OPTION_NAME, 'no' );
		$this->assertFalse( $batch_processing->is_enqueued( get_class( $this->sut ) ) );
	}

	/**
	 * Tests that the cleanup process works correctly as a batch processor.
	 */
	public function test_batch_processor_interface() {
		$batch_processing = wc_get_container()->get( BatchProcessingController::class );
		$order_ids        = $this->create_test_orders( 11 ); // 11 test orders.

		$this->disable_cot_sync();
		update_option( $this->sut::OPTION_NAME, 'yes' );

		$this->assertEquals( count( $order_ids ), $this->sut->get_total_pending_count() );

		// Process one small batch.
		$batch_ids = $this->sut->get_next_batch_to_process( 5 );
		$order_ids = array_diff( $order_ids, $batch_ids );
		$this->sut->process_batch( $batch_ids );
		$this->assertEquals( count( $order_ids ), $this->sut->get_total_pending_count() );
		$this->assertNotEquals( 0, $this->sut->get_total_pending_count() );

		// Let the batch processing controller process the rest and confirm.
		do_action( $batch_processing::PROCESS_SINGLE_BATCH_ACTION_NAME, get_class( $this->sut ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle -- this is a test
		$this->assertEquals( 0, $this->sut->get_total_pending_count() );
		$this->assertFalse( $batch_processing->is_enqueued( get_class( $this->sut ) ) );
	}

	/**
	 * Creates a few test HPOS orders with minimal metadata.
	 *
	 * @param int $count Number of orders to generate.
	 * @return int[] Order IDs.
	 */
	private function create_test_orders( $count = 10 ) : array {
		$order_ids = array();

		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = new \WC_Order();
			$order->update_meta_data( 'some_meta', 'some_value' );
			$order_ids[] = $order->save();
		}

		return $order_ids;
	}

}
