<?php
/**
 * Unit tests for the WC_Order_Item_Product class functionalities.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * WC_Order_Item_Product unit tests.
 */
class WC_Order_Item_Product_Test extends WC_Unit_Test_Case {

	/**
	 * Test that backorder meta is excluded from formatted meta data
	 * for completed orders.
	 */
	public function test_get_formatted_meta_data_excludes_backorder_on_completed() {
		// 1. Setup: Create product and set backorders allowed.
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->set_backorders( 'notify' ); // Allow backorders with notification.
		$product->save();

		// 2. Create Order and add product item.
		$order = WC_Helper_Order::create_order();
		$item  = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->set_quantity( 1 );
		$item->set_order_id( $order->get_id() );

		// 3. Add backorder meta.
		$backorder_meta_key = 'Backordered';
		$item->add_meta_data( $backorder_meta_key, 1, true ); // Value typically is the backordered quantity.
		$item->save();
		$order->add_item( $item );
		$order->save();

		// 4. Assert: Check meta exists before completion.
		$item_id                = $item->get_id();
		$order_item             = $order->get_item( $item_id ); // Re-fetch item from order.
		$formatted_meta_before  = $order_item->get_formatted_meta_data();
		$found_backorder_before = false;
		foreach ( $formatted_meta_before as $meta ) {
			if ( $meta->key === $backorder_meta_key ) {
				$found_backorder_before = true;
				break;
			}
		}
		$this->assertTrue( $found_backorder_before, 'Backorder meta should exist before order completion.' );

		// 5. Complete the order.
		$order->update_status( OrderStatus::COMPLETED );
		$order->save();

		// 6. Assert: Check meta is excluded after completion.
		$order_item_after_complete = $order->get_item( $item_id ); // Re-fetch item again after status change.
		$formatted_meta_after      = $order_item_after_complete->get_formatted_meta_data();
		$found_backorder_after     = false;
		foreach ( $formatted_meta_after as $meta ) {
			if ( $meta->key === $backorder_meta_key ) {
				$found_backorder_after = true;
				break;
			}
		}
		$this->assertFalse( $found_backorder_after, 'Backorder meta should be excluded after order completion.' );

		// Clean up.
		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Order::delete_order( $order->get_id() );
	}
}
