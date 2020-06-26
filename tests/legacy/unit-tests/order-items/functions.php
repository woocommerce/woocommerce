<?php
/**
 * Order Item Function Tests
 * @package WooCommerce\Tests\Order_Items
 * @since 3.0.0
 */
class WC_Tests_Order_Item_Functions extends WC_Unit_Test_Case {

	/**
	 * test_wc_order_item_meta_functions
	 *
	 * wc_add_order_item_meta, wc_update_order_item_meta, and
	 * wc_delete_order_item_meta all make direct updates through
	 * a datastore. These tests make sure cache is properly busted and
	 * accessing those values via CRUD returns the correct value.
	 *
	 * @since 3.0.0
	 */
	function test_wc_order_item_meta_functions() {
		$meta_value  = 'cat';
		$meta_value2 = 'dog';

		$order  = new WC_Order();
		$item_1 = new WC_Order_Item_Product();
		$item_1->set_props(
			array(
				'product'  => WC_Helper_Product::create_simple_product(),
				'quantity' => 4,
			)
		);
		$order->add_item( $item_1 );
		$order->save();

		$item    = current( $order->get_items() );
		$item_id = $item->get_id();

		// Test that the initial key doesn't exist.
		$item = new WC_Order_Item_Product( $item_id );
		$this->assertEmpty( $item->get_meta( '_test_key' ) );
		$this->assertEmpty( wc_get_order_item_meta( $item_id, '_test_key' ) );

		// Test making sure cache is properly busted when adding meta.
		wc_add_order_item_meta( $item_id, '_test_key', $meta_value );
		$item      = new WC_Order_Item_Product( $item_id );
		$item_meta = $item->get_meta( '_test_key' );
		$this->assertEquals( $meta_value, $item_meta );
		$this->assertEquals( $meta_value, wc_get_order_item_meta( $item_id, '_test_key' ) );

		// Test making sure cache is properly busted when updating meta.
		wc_update_order_item_meta( $item_id, '_test_key', $meta_value2 );
		$item      = new WC_Order_Item_Product( $item_id );
		$item_meta = $item->get_meta( '_test_key' );
		$this->assertEquals( $meta_value2, $item_meta );
		$this->assertEquals( $meta_value2, wc_get_order_item_meta( $item_id, '_test_key' ) );

		// Test making sure cache is properly busted when deleting meta.
		wc_delete_order_item_meta( $item_id, '_test_key' );
		$item      = new WC_Order_Item_Product( $item_id );
		$item_meta = $item->get_meta( '_test_key' );
		$this->assertEmpty( $item->get_meta( '_test_key' ) );
		$this->assertEmpty( wc_get_order_item_meta( $item_id, '_test_key' ) );
	}

}
