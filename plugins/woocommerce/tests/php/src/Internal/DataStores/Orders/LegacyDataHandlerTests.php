<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Internal\DataStores\Orders\LegacyDataHandler;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class OrdersTableQueryTests.
 */
class LegacyDataHandlerTests extends WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * @var LegacyDataHandler
	 */
	private $sut;

	/**
	 * Initializes system under test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->setup_cot();

		$this->sut = wc_get_container()->get( LegacyDataHandler::class );
	}

	/**
	 * Destroys system under test.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
	}

	/**
	 * Tests the cleanup of legacy data.
	 */
	public function test_post_data_cleanup() {
		$this->enable_cot_sync();
		$orders = array(
			OrderHelper::create_complex_data_store_order(),
			OrderHelper::create_order(),
		);
		$this->disable_cot_sync();

		// Confirm orders have been synced up (i.e. are not placeholders) and contain metadata.
		foreach ( $orders as $order ) {
			$this->assertEquals( 'shop_order', get_post_type( $order->get_id() ) );
			$this->assertNotEmpty( get_post_meta( $order->get_id() ) );
		}

		// Check that counts are working ok.
		$this->assertEquals( 1, $this->sut->count_orders_for_cleanup( array( $orders[0]->get_id() ) ) );
		$this->assertEquals( 2, $this->sut->count_orders_for_cleanup() );

		// Cleanup one of the orders.
		$this->sut->cleanup_post_data( $orders[0]->get_id() );

		// Confirm metadata has been removed and post type has been reset to placeholder.
		$this->assertEmpty( get_post_meta( $orders[0]->get_id() ) );
		$this->assertEquals( 'shop_order_placehold', get_post_type( $orders[0]->get_id() ) );

		// Check counts.
		$this->assertEquals( 0, $this->sut->count_orders_for_cleanup( array( $orders[0]->get_id() ) ) );
		$this->assertEquals( 1, $this->sut->count_orders_for_cleanup() );

		// Confirm that we now have 1 unsynced order (due to the removal of the backup data).
		$this->assertEquals( 1, wc_get_container()->get( DataSynchronizer::class )->get_current_orders_pending_sync_count() );
	}

}
