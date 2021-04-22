<?php

/**
 * Class WC_Order_Data_Store_CPT_Test.
 */
class WC_Order_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * Test that refund cache are invalidated correctly when refund is deleted.
	 */
	public function test_refund_cache_invalidation() {
		$order = WC_Helper_Order::create_order();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'reason'   => 'testing',
				'amount'   => 1,
			)
		);

		$this->assertNotWPError( $refund );

		// Prime cache.
		$fetched_order = wc_get_orders(
			array(
				'post__in' => array( $order->get_id() ),
				'type'     => 'shop_order',
			)
		)[0];

		$refund_cache_key = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'refunds' . $order->get_id();
		$cached_refunds = wp_cache_get( $refund_cache_key, 'orders' );

		$this->assertEquals( $cached_refunds[0]->get_id(), $fetched_order->get_refunds()[0]->get_id() );

		$refund->delete( true );

		// Cache should be cleared now.
		$cached_refunds = wp_cache_get( $refund_cache_key, 'orders' );
		$this->assertEquals( false, $cached_refunds );
	}

}
