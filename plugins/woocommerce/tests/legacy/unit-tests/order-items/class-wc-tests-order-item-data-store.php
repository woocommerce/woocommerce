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
	 * Tests that the `item-{order_item_id}` cache entry has the same fields regardless of which
	 * data store path primed it (order data store `read_items()` vs. individual item data store
	 * `read()`).
	 *
	 * Regression test for https://github.com/woocommerce/woocommerce/issues/31656 where the order
	 * data store cached 4 fields (order_item_type, order_item_id, order_id, order_item_name) but
	 * the individual item data store only cached order_id and order_item_name.
	 */
	public function test_item_cache_shape_is_consistent_between_data_stores() {
		$order      = WC_Helper_Order::create_order();
		$items      = $order->get_items();
		$order_item = reset( $items );
		$item_id    = $order_item->get_id();

		// PATH A: order data store `read_items()` populates the `item-` cache when the order's
		// items collection is read. Trigger by clearing the cache and re-reading via a new order.
		wp_cache_delete( 'item-' . $item_id, 'order-items' );
		wp_cache_delete( 'order-items-' . $order->get_id(), 'orders' );
		$fresh_order = wc_get_order( $order->get_id() );
		$fresh_order->get_items();
		$cached_via_order_store = wp_cache_get( 'item-' . $item_id, 'order-items' );
		$this->assertNotFalse( $cached_via_order_store, 'Order data store should populate the item cache when reading order items.' );
		$order_store_fields = array_keys( (array) $cached_via_order_store );

		// PATH B: item data store `read()` populates the `item-` cache when an individual item
		// is read. Trigger by clearing the cache and re-instantiating the item.
		wp_cache_delete( 'item-' . $item_id, 'order-items' );
		wp_cache_delete( 'order-items-' . $order->get_id(), 'orders' );
		// Re-instantiating the item triggers the item data store's read() path.
		new WC_Order_Item_Product( $item_id );
		$cached_via_item_store = wp_cache_get( 'item-' . $item_id, 'order-items' );
		$this->assertNotFalse( $cached_via_item_store, 'Item data store should populate the item cache when reading an individual item.' );
		$item_store_fields = array_keys( (array) $cached_via_item_store );

		sort( $order_store_fields );
		sort( $item_store_fields );

		$this->assertSame(
			$order_store_fields,
			$item_store_fields,
			'The `item-{order_item_id}` cache entry should have the same fields regardless of which data store primed it.'
		);

		// Confirm the union includes the fields previously only set by the order data store.
		$this->assertContains( 'order_item_type', $item_store_fields );
		$this->assertContains( 'order_item_id', $item_store_fields );
		$this->assertContains( 'order_id', $item_store_fields );
		$this->assertContains( 'order_item_name', $item_store_fields );
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
