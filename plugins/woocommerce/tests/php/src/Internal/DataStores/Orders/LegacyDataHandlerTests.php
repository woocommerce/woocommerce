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
		parent::tearDown();
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
	}

	/**
	 * Tests the cleanup of legacy data.
	 */
	public function test_post_data_cleanup() {
		$this->enable_cot_sync();
		$orders       = array(
			OrderHelper::create_order(),
			OrderHelper::create_order(),
		);
		$refund_order = wc_create_refund(
			array(
				'order_id' => $orders[0]->get_id(),
				'amount'   => 10,
			)
		);
		$this->disable_cot_sync();

		// Confirm orders have been synced up (i.e. are not placeholders) and contain metadata.
		foreach ( $orders as $order ) {
			$this->assertEquals( 'shop_order', get_post_type( $order->get_id() ) );
			$this->assertNotEmpty( get_post_meta( $order->get_id() ) );
		}
		$this->assertEquals( 'shop_order_refund', get_post_type( $refund_order->get_id() ) );

		// Check that counts are working ok.
		$this->assertEquals( 1, $this->sut->count_orders_for_cleanup( array( $orders[0]->get_id() ) ) );
		$this->assertEquals( 3, $this->sut->count_orders_for_cleanup() );

		// Cleanup one of the orders.
		$this->sut->cleanup_post_data( $orders[0]->get_id() );

		// Confirm metadata has been removed and post type has been reset to placeholder.
		$this->assertEmpty( get_post_meta( $orders[0]->get_id() ) );
		$this->assertEquals( 'shop_order_placehold', get_post_type( $orders[0]->get_id() ) );

		// Check counts.
		$this->assertEquals( 0, $this->sut->count_orders_for_cleanup( array( $orders[0]->get_id() ) ) );
		$this->assertEquals( 2, $this->sut->count_orders_for_cleanup() );

		// Confirm that we now have 1 unsynced order (due to the removal of the backup data).
		$this->assertEquals( 1, wc_get_container()->get( DataSynchronizer::class )->get_current_orders_pending_sync_count() );

		// Cleanup the refund order.
		$this->sut->cleanup_post_data( $refund_order->get_id() );
		$this->assertEquals( 1, $this->sut->count_orders_for_cleanup() );
		$this->assertEquals( 2, wc_get_container()->get( DataSynchronizer::class )->get_current_orders_pending_sync_count() );
	}

	/**
	 * Tests that cleanup for a non-existent order throws an exception.
	 */
	public function test_post_data_cleanup_non_existent() {
		$this->expectException( \Exception::class );
		$this->sut->cleanup_post_data( 0 );
	}

	/**
	 * Tests `get_orders_for_cleanup()` with various arguments, including ranges of orders and individual order IDs.
	 */
	public function test_get_orders_for_cleanup() {
		// Create a few orders.
		$this->enable_cot_sync();
		$order_ids = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$order_id    = OrderHelper::create_order()->get_id();
			$order_ids[] = $order_id;
		}
		$this->disable_cot_sync();

		$this->assertCount( 0, $this->sut->get_orders_for_cleanup( array( max( $order_ids ) + 1 ) ) );
		$this->assertCount( 10, $this->sut->get_orders_for_cleanup() );
		$this->assertCount( 10, $this->sut->get_orders_for_cleanup( $order_ids ) );

		$interval = min( $order_ids ) . '-' . max( $order_ids );
		$this->assertCount( 10, $this->sut->get_orders_for_cleanup( array( $interval ) ) );
		$this->assertCount( 0, $this->sut->get_orders_for_cleanup( array( '300-2' ) ) );

		$slice    = array_slice( $order_ids, 5 );
		$interval = min( $slice ) . '-' . max( $slice );
		$this->assertCount( 5, $this->sut->get_orders_for_cleanup( $slice ) );
		$this->assertCount( 5, $this->sut->get_orders_for_cleanup( array( $interval ) ) );
		$this->assertCount( 7, $this->sut->get_orders_for_cleanup( array( $order_ids[0], $order_ids[1], $interval ) ) );
		$this->assertCount( 10, $this->sut->get_orders_for_cleanup( array( $interval, '0-' . min( $slice ) ) ) );
	}

	/**
	 * Checks that `get_order_from_datastore()` correctly fetches an order from either the HPOS or CPT datastore.
	 */
	public function test_get_order_from_datastore() {
		// Test order.
		$this->enable_cot_sync();
		$order = new \WC_Order();
		$order->update_meta_data( 'meta_key', 'hpos' );
		$order->save();
		$this->disable_cot_sync();

		// The order, obtained from either datastore should contain 'meta_key' = 'hpos'.
		$order_hpos = $this->sut->get_order_from_datastore( $order->get_id(), 'hpos' );
		$this->assertEquals( $order_hpos->get_meta( 'meta_key' ), 'hpos' );

		$order_cpt = $this->sut->get_order_from_datastore( $order->get_id(), 'posts' );
		$this->assertEquals( $order_cpt->get_meta( 'meta_key' ), 'hpos' );

		// Update the CPT version outside of WC.
		update_post_meta( $order->get_id(), 'meta_key', 'posts' );

		// Confirm that the HPOS order still has the meta value set to 'hpos' while the CPT version has been set to 'posts'.
		$order_hpos = $this->sut->get_order_from_datastore( $order->get_id(), 'hpos' );
		$this->assertEquals( $order_hpos->get_meta( 'meta_key' ), 'hpos' );

		$order_cpt = $this->sut->get_order_from_datastore( $order->get_id(), 'posts' );
		$this->assertEquals( $order_cpt->get_meta( 'meta_key' ), 'posts' );
	}

	/**
	 * Tests that `backfill_order_to_datastore()` correctly backfills from/to either datastore.
	 *
	 * @testWith ["hpos"]
	 *           ["posts"]
	 *
	 * @param string $source_data_store Datastore to use as source for the backfill.
	 * @return void
	 */
	public function test_datastore_manual_backfill( $source_data_store = 'hpos' ) {
		// Test order.
		$this->enable_cot_sync();
		$order = new \WC_Order();
		$order->save();
		$this->disable_cot_sync();

		$destination_data_store = 'hpos' === $source_data_store ? 'posts' : 'hpos';

		// Make some changes to the source version.
		$order_from_source = $this->sut->get_order_from_datastore( $order->get_id(), $source_data_store );
		$order_from_source->set_billing_first_name( 'Mr. HPOS' );
		$order_from_source->update_meta_data( 'meta_key', 'hpos' );
		$order_from_source->save();

		// Fetch the destination version and make sure it's different.
		$order_from_dest = $this->sut->get_order_from_datastore( $order->get_id(), $destination_data_store );
		$this->assertNotEquals( $order_from_source->get_billing_first_name(), $order_from_dest->get_billing_first_name() );
		$this->assertNotEquals( $order_from_source->get_meta( 'meta_key' ), $order_from_dest->get_meta( 'meta_key' ) );

		// Backfill to the destination.
		$this->sut->backfill_order_to_datastore( $order->get_id(), $source_data_store, $destination_data_store );
		
		// Confirm data is now the same.
		$order_from_dest = $this->sut->get_order_from_datastore( $order->get_id(), $destination_data_store );
		$this->assertEquals( $order_from_source->get_billing_first_name(), $order_from_dest->get_billing_first_name() );
		$this->assertEquals( $order_from_source->get_meta( 'meta_key' ), $order_from_dest->get_meta( 'meta_key' ) );
	}

	/**
	 * Checks that partial backfills from/to either datastore work correctly.
	 *
	 * @since 8.8.0
	 *
	 * @return void
	 */
	public function test_datastore_partial_backfill() {
		// Test order.
		$this->enable_cot_sync();
		$order = new \WC_Order();
		$order->set_status( 'on-hold' );
		$order->add_meta_data( 'my_meta', 'hpos+posts' );
		$order->save();
		$this->disable_cot_sync();

		$order_hpos = $this->sut->get_order_from_datastore( $order->get_id(), 'hpos' );
		$order_hpos->set_status( 'completed' );
		$order_hpos->set_billing_first_name( 'Mr. HPOS' );
		$order_hpos->set_billing_address_1( 'HPOS Street' );
		$order_hpos->update_meta_data( 'my_meta', 'hpos' );
		$order_hpos->update_meta_data( 'other_meta', 'hpos' );
		$order_hpos->save();

		// Fetch the posts version and make sure it's different.
		$order_cpt = $this->sut->get_order_from_datastore( $order->get_id(), 'posts' );
		$this->assertNotEquals( $order_hpos->get_billing_first_name(), $order_cpt->get_billing_first_name() );
		$this->assertNotEquals( $order_hpos->get_status(), $order_cpt->get_status() );
		$this->assertNotEquals( $order_hpos->get_meta( 'my_meta' ), $order_cpt->get_meta( 'my_meta' ) );

		// Backfill "my_meta" to posts and confirm it has been backfilled.
		$this->sut->backfill_order_to_datastore( $order->get_id(), 'hpos', 'posts', array( 'meta_keys' => array( 'my_meta' ) ) );
		$order_cpt = $this->sut->get_order_from_datastore( $order->get_id(), 'posts' );
		$this->assertNotEquals( $order_hpos->get_billing_first_name(), $order_cpt->get_billing_first_name() );
		$this->assertNotEquals( $order_hpos->get_status(), $order_cpt->get_status() );
		$this->assertEquals( $order_hpos->get_meta( 'my_meta' ), $order_cpt->get_meta( 'my_meta' ) );

		// Backfill status and confirm it has been backfilled.
		$this->sut->backfill_order_to_datastore( $order->get_id(), 'hpos', 'posts', array( 'props' => array( 'status' ) ) );
		$order_cpt = $this->sut->get_order_from_datastore( $order->get_id(), 'posts' );
		$this->assertNotEquals( $order_hpos->get_billing_first_name(), $order_cpt->get_billing_first_name() );
		$this->assertEquals( $order_hpos->get_status(), $order_cpt->get_status() );

		// Update the CPT version now.
		$order_cpt->set_billing_first_name( 'Mr. Post' );
		$order_cpt->set_billing_address_1( 'CPT Street' );
		$order_cpt->save();

		// Re-load the HPOS version and confirm billing first name is different.
		$order_hpos = $this->sut->get_order_from_datastore( $order->get_id(), 'hpos' );
		$this->assertNotEquals( $order_hpos->get_billing_first_name(), $order_cpt->get_billing_first_name() );

		// Backfill name and confirm.
		$this->sut->backfill_order_to_datastore( $order->get_id(), 'posts', 'hpos', array( 'props' => array( 'billing_first_name' ) ) );
		$order_hpos = $this->sut->get_order_from_datastore( $order->get_id(), 'hpos' );
		$this->assertEquals( $order_hpos->get_billing_first_name(), $order_cpt->get_billing_first_name() );
		$this->assertEquals( $order_hpos->get_status(), $order_cpt->get_status() );

		// Re-enable sync, trigger an order sync and confirm that meta/props that we didn't partially backfill already are correctly synced.
		$this->enable_cot_sync();
		wc_get_container()->get( DataSynchronizer::class )->process_batch( array( $order_cpt->get_id() ) );
		$order_cpt = $this->sut->get_order_from_datastore( $order->get_id(), 'posts' );
		$this->assertEquals( $order_cpt->get_billing_address_1(), $order_hpos->get_billing_address_1() );
		$this->assertEquals( $order_cpt->get_meta( 'other_meta', true, 'edit' ), $order_hpos->get_meta( 'other_meta', true, 'edit' ) );
	}

}
