<?php

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * WC_Order_Factory_Test Class.
 */
class WC_Order_Factory_Test extends WC_Unit_Test_Case {

	use \Automattic\WooCommerce\RestApi\UnitTests\SerializingCacheTrait;

	/**
	 * Store COT state at the start of the test so we can restore it later.
	 *
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Disable COT before the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->cot_state = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		OrderHelper::toggle_cot_feature_and_usage( false );
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
	}

	/**
	 * Restore COT state after the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		wp_cache_flush();
		OrderHelper::toggle_cot_feature_and_usage( $this->cot_state );
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
	}

	/**
	 * @testDox get_orders should be able to return multiple orders of different types.
	 */
	public function test_get_orders_with_multiple_order_type() {
		$order1 = OrderHelper::create_complex_wp_post_order();
		$order2 = OrderHelper::create_complex_wp_post_order();

		assert( $order1 > 0 );
		assert( $order2 > 0 );

		$refund_args = array(
			'amount'   => 10,
			'reason'   => 'test',
			'order_id' => $order1,
		);
		$refund1     = wc_create_refund( $refund_args );
		assert( $refund1->get_id() > 0 );

		$refund_args['order_id'] = $order2;
		$refund2                 = wc_create_refund( $refund_args );
		assert( $refund2->get_id() > 0 );

		$order_ids = array( $order1, $order2, $refund1->get_id(), $refund2->get_id() );

		$orders_with_diff_types = WC_Order_Factory::get_orders( $order_ids );
		$this->assertEquals( 4, count( $orders_with_diff_types ) );

		// Assert IDs.
		$this->assertEquals( $order1, $orders_with_diff_types[0]->get_id() );
		$this->assertEquals( $order2, $orders_with_diff_types[1]->get_id() );
		$this->assertEquals( $refund1->get_id(), $orders_with_diff_types[2]->get_id() );
		$this->assertEquals( $refund2->get_id(), $orders_with_diff_types[3]->get_id() );

		// Assert class types.
		$this->assertInstanceOf( WC_Order::class, $orders_with_diff_types[0] );
		$this->assertInstanceOf( WC_Order::class, $orders_with_diff_types[1] );
		$this->assertInstanceOf( WC_Order_Refund::class, $orders_with_diff_types[2] );
		$this->assertInstanceOf( WC_Order_Refund::class, $orders_with_diff_types[3] );
	}

	/**
	 * @testDox Test that cache does not interfere with order sorting.
	 */
	public function test_cache_dont_interfere_with_orders() {
		OrderHelper::toggle_cot_feature_and_usage( $this->cot_state );
		$order1 = OrderHelper::create_order();
		$order2 = OrderHelper::create_order();

		wp_cache_flush();
		$cache = wc_get_container()->get( OrderCache::class );
		$cache->set( $order2, $order2->get_id() );

		$orders = WC_Order_Factory::get_orders( array( $order1->get_id(), $order2->get_id() ) );
		$this->assertEquals( 2, count( $orders ) );
		$this->assertEquals( $order1->get_id(), $orders[0]->get_id() );
		$this->assertEquals( $order2->get_id(), $orders[1]->get_id() );
		OrderHelper::toggle_cot_feature_and_usage( false );
	}

	/**
	 * @testDox Test that an WC_Order_Factory::get_order() does not return an order that did not properly load, $id = 0.
	 */
	public function test_get_order_doesnt_return_invalid_cached_order() {
		global $wpdb;

		OrderHelper::toggle_cot_feature_and_usage( true );
		$this->setup_mock_cache();

		$order = OrderHelper::create_order();

		// Retrieve the order to prime the cache.
		$retrieved_order = WC_Order_Factory::get_order( $order->get_id() );
		$this->assertEquals( $order->get_id(), $retrieved_order->get_id() );

		// Delete the order from the DB to mimic situations like SQL replication lag where the cache has propagated,
		// but SQL data hasn't yet caught up.
		$wpdb->delete( OrdersTableDataStore::get_orders_table_name(), array( 'ID' => $retrieved_order->get_id() ) );

		/**
		 * Retrieving the order again should either:
		 * - return false because the order was not able to fully load.
		 * - return a fully loaded order. Our direct DB write purposefully does not trigger a cache refresh so a fully
		 *      loaded order should also be an expected result with cache enabled.
		 */
		$retrieved_order = WC_Order_Factory::get_order( $order->get_id() );
		if ( is_object( $retrieved_order ) ) {
			$this->assertEquals( $order->get_id(), $retrieved_order->get_id() );
		} else {
			$this->assertFalse( $retrieved_order );
		}
	}

}
