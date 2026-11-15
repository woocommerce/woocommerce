<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Enums\OrderStatus;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for product sales counts after order item edits.
 */
class ProductSalesTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Updates sales counts when a recorded order's items are edited, added or removed.
	 */
	public function test_item_edits_update_sales(): void {
		$product = WC_Helper_Product::create_simple_product();
		$other   = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->update_status( OrderStatus::ON_HOLD );
		$this->assertSame( 1, wc_get_product( $product->get_id() )->get_total_sales() );

		$item = new WC_Order_Item_Product( $item_id );
		$item->set_quantity( 5 );
		$item->save();
		$this->assertSame( 5, wc_get_product( $product->get_id() )->get_total_sales(), 'Quantity increases should increase sales.' );

		$item->set_quantity( 2 );
		$item->save();
		$item->save();
		$this->assertSame( 2, wc_get_product( $product->get_id() )->get_total_sales(), 'Repeated saves should not count sales twice.' );

		$other_item_id = $order->add_product( $other, 3 );
		$this->assertSame( 3, wc_get_product( $other->get_id() )->get_total_sales(), 'New items in recorded orders should count as sales.' );

		wc_delete_order_item( $other_item_id );
		$this->assertSame( 0, wc_get_product( $other->get_id() )->get_total_sales(), 'Removing an item should reverse its sales.' );

		$item->delete();
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'CRUD deletion should also reverse sales.' );
	}

	/**
	 * @testdox Does not count edits to an order whose sales have not been recorded.
	 */
	public function test_unrecorded_order_edits_do_not_count_sales(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$item    = new WC_Order_Item_Product( $item_id );
		$item->set_quantity( 5 );
		$item->save();
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'Pending orders must not record sales on item saves.' );

		$order = new WC_Order( $order->get_id() );
		$order->update_status( OrderStatus::ON_HOLD );
		$this->assertSame( 5, wc_get_product( $product->get_id() )->get_total_sales(), 'The status transition should count the final quantity once.' );
	}

	/**
	 * @testdox Reverses the edited quantities on cancellation, trash and deletion.
	 */
	public function test_edited_sales_follow_order_status_changes(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->update_status( OrderStatus::ON_HOLD );

		$order = new WC_Order( $order->get_id() );
		$items = $order->get_items();
		$item  = $items[ $item_id ];
		$item->set_quantity( 5 );
		$order->set_status( OrderStatus::CANCELLED );
		$order->save();
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'Editing and cancelling together should reverse the correct quantity.' );

		$order = new WC_Order( $order->get_id() );
		$order->update_status( OrderStatus::ON_HOLD );
		$this->assertSame( 5, wc_get_product( $product->get_id() )->get_total_sales() );

		$order_id = $order->get_id();
		$order->delete( false );
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'Trashing should reverse the edited sales.' );
		$order = wc_get_order( $order_id );
		$order->untrash();
		$this->assertSame( 5, wc_get_product( $product->get_id() )->get_total_sales() );
		wc_get_order( $order->get_id() )->delete( true );
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'Order deletion should not reverse item sales twice.' );
	}

	/**
	 * @testdox Transfers sales to the replacement product when an item changes product.
	 */
	public function test_changing_item_product_updates_both_products(): void {
		$product = WC_Helper_Product::create_simple_product();
		$other   = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 2 );
		$order->update_status( OrderStatus::ON_HOLD );

		$item = new WC_Order_Item_Product( $item_id );
		$item->set_product( $other );
		$item->set_quantity( 3 );
		$item->save();

		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'The original product should no longer count the item.' );
		$this->assertSame( 3, wc_get_product( $other->get_id() )->get_total_sales(), 'The replacement product should count the new quantity.' );
	}

	/**
	 * @testdox Updates sales through the admin item-save path without counting recalculations twice.
	 */
	public function test_admin_item_save_updates_sales(): void {
		global $wpdb;
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->update_status( OrderStatus::ON_HOLD );
		$items = array(
			'order_item_id'  => array( $item_id ),
			'order_item_qty' => array( $item_id => '5' ),
		);

		wc_save_order_items( $order->get_id(), $items );
		wc_save_order_items( $order->get_id(), $items );
		$this->assertSame( 5, wc_get_product( $product->get_id() )->get_total_sales(), 'Repeated admin saves should only count the quantity difference.' );
		$this->assertSame(
			5,
			(int) $wpdb->get_var( $wpdb->prepare( "SELECT total_sales FROM {$wpdb->wc_product_meta_lookup} WHERE product_id = %d", $product->get_id() ) ),
			'The product lookup table should also reflect the edited quantity.'
		);

		$items['order_item_qty'][ $item_id ] = '0';
		wc_save_order_items( $order->get_id(), $items );
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'Setting quantity to zero in admin should reverse the sales.' );
	}

	/**
	 * @testdox Counts variation sales against the parent product, just like initial sales recording.
	 */
	public function test_variation_edits_update_parent_sales(): void {
		$parent    = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $parent->get_children()[0] );
		$order     = wc_create_order();
		$item_id   = $order->add_product( $variation, 1 );
		$order->update_status( OrderStatus::ON_HOLD );

		$item = new WC_Order_Item_Product( $item_id );
		$item->set_quantity( 4 );
		$item->save();

		$this->assertSame( 4, wc_get_product( $parent->get_id() )->get_total_sales(), 'Variation quantity changes should update parent sales.' );
		$this->assertSame( 0, (int) wc_get_product( $variation->get_id() )->get_total_sales(), 'Variation sales should not be recorded separately.' );
	}

	/**
	 * @testdox Leaves sales unchanged when refund line items are saved or deleted.
	 */
	public function test_refund_items_do_not_change_sales(): void {
		$product = WC_Helper_Product::create_simple_product();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 5 );
		$order->calculate_totals();
		$order->update_status( OrderStatus::ON_HOLD );
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => 1,
				'line_items' => array(
					$item_id => array(
						'qty'          => 1,
						'refund_total' => 1,
					),
				),
			)
		);
		$this->assertNotWPError( $refund );
		$items = $refund->get_items();
		$item  = reset( $items );
		$item->set_quantity( -2 );
		$item->save();
		$item->delete();

		$this->assertSame( 5, wc_get_product( $product->get_id() )->get_total_sales(), 'Refund item writes should preserve the existing sales-count behavior.' );
	}

	/**
	 * @testdox Preserves sales from other orders when editing and removing an item.
	 */
	public function test_edits_preserve_other_sales(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_total_sales( 10 );
		$product->save();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 1 );
		$order->update_status( OrderStatus::ON_HOLD );
		$item = new WC_Order_Item_Product( $item_id );
		$item->set_quantity( 5 );
		$item->save();
		$this->assertSame( 15, wc_get_product( $product->get_id() )->get_total_sales(), 'Only the edited order contribution should change.' );

		$order = new WC_Order( $order->get_id() );
		$order->remove_item( $item_id );
		$order->save();
		$this->assertSame( 10, wc_get_product( $product->get_id() )->get_total_sales(), 'Removing the edited item should preserve unrelated sales.' );
	}

	/**
	 * @testdox Adjusts sales correctly when an item is moved between recorded and unrecorded orders.
	 */
	public function test_moving_item_between_orders(): void {
		$product = WC_Helper_Product::create_simple_product();
		$pending = wc_create_order();
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, 2 );
		$order->update_status( OrderStatus::ON_HOLD );

		$item = new WC_Order_Item_Product( $item_id );
		$item->set_order_id( $pending->get_id() );
		$item->set_quantity( 3 );
		$item->save();
		$this->assertSame( 0, wc_get_product( $product->get_id() )->get_total_sales(), 'Moving to an unrecorded order should reverse the previously counted quantity.' );

		$item->set_order_id( $order->get_id() );
		$item->save();
		$this->assertSame( 3, wc_get_product( $product->get_id() )->get_total_sales(), 'Moving back to a recorded order should count the current quantity.' );
	}
}
