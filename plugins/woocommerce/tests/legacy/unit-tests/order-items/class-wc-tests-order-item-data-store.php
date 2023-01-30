<?php
/**
 * Unit tests for the WC_Order_Item_Data_Store class.
 *
 * @package WooCommerce\Tests\Order_Items
 * @since 4.0.0
 */

/**
 * Order Item data store unit tests.
 *
 * @since 4.0.0
 */
class WC_Tests_Order_Item_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Tests that the cache is cleared when an order item is added.
	 */
	public function test_cache_cleared_on_item_addition() {
		$data_store = WC_Data_Store::load( 'order-item' );
		$order      = WC_Helper_Order::create_order();

		// Set something to the cache that should be cleared.
		wp_cache_set( 'order-items-' . $order->get_id(), 'test', 'orders' );

		$data_store->add_order_item(
			$order->get_id(),
			array(
				'order_item_name' => 'Test Item',
				'order_item_type' => 'line_item',
			)
		);

		$cached = wp_cache_get( 'order-items-' . $order->get_id(), 'orders' );
		$this->assertNotEquals( 'test', $cached );
	}
	/**
	 * Tests that the cache is cleared when an order item is updated.
	 */
	public function test_cache_cleared_on_item_update() {
		$data_store = WC_Data_Store::load( 'order-item' );
		$order      = WC_Helper_Order::create_order();
		$items      = $order->get_items();
		$order_item = reset( $items );

		// Set something to the cache that should be cleared.
		wp_cache_set( 'item-' . $order_item->get_id(), 'test_item', 'order-items' );
		wp_cache_set( 'order-items-' . $order->get_id(), 'test', 'orders' );

		$data_store->update_order_item( $order_item->get_id(), array( 'order_item_name' => 'Test Item' ) );

		$cached = wp_cache_get( 'item-' . $order_item->get_id(), 'order-items' );
		$this->assertNotEquals( 'test_item', $cached );
		$cached = wp_cache_get( 'order-items-' . $order->get_id(), 'orders' );
		$this->assertNotEquals( 'test', $cached );
	}

	/**
	 * Tests that the cache is cleared when an order item is deleted.
	 */
	public function test_cache_cleared_on_item_deletion() {
		$data_store = WC_Data_Store::load( 'order-item' );
		$order      = WC_Helper_Order::create_order();
		$items      = $order->get_items();
		$order_item = reset( $items );

		// Set something to the cache that should be cleared.
		wp_cache_set( 'item-' . $order_item->get_id(), 'test_item', 'order-items' );
		wp_cache_set( 'order-items-' . $order->get_id(), 'test', 'orders' );

		$data_store->delete_order_item( $order_item->get_id() );

		$cached = wp_cache_get( 'item-' . $order_item->get_id(), 'order-items' );
		$this->assertNotEquals( 'test_item', $cached );
		$cached = wp_cache_get( 'order-items-' . $order->get_id(), 'orders' );
		$this->assertNotEquals( 'test', $cached );
	}
}
