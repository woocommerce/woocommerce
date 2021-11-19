<?php
/**
 * Order functions tests
 *
 * @package WooCommerce\Tests\Order.
 */

/**
 * Class WC_Order_Functions_Test
 */
class WC_Order_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test that wc_restock_refunded_items() preserves order item stock metadata.
	 */
	public function test_wc_restock_refunded_items_stock_metadata() {
		// Create a product, with stock management enabled.
		$product = WC_Helper_Product::create_simple_product(
			true,
			array(
				'manage_stock'   => true,
				'stock_quantity' => 10,
			)
		);

		// Place an order for the product, qty 2.
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 2 );
		WC()->cart->calculate_totals();

		$checkout = WC_Checkout::instance();
		$order    = new WC_Order();
		$checkout->set_data_from_cart( $order );
		$order->set_status( 'wc-processing' );
		$order->save();

		// Get the line item.
		$items     = $order->get_items();
		$line_item = reset( $items );

		// Force a restock of one item.
		$refunded_items = array();
		$refunded_items[ $line_item->get_id() ] = array(
			'qty' => 1,
		);
		wc_restock_refunded_items( $order, $refunded_items );

		// Verify metadata.
		$this->assertEquals( 1, (int) $line_item->get_meta( '_reduced_stock', true ) );
		$this->assertEquals( 1, (int) $line_item->get_meta( '_restock_refunded_items', true ) );

		// Force another restock of one item.
		wc_restock_refunded_items( $order, $refunded_items );

		// Verify metadata.
		$this->assertEquals( 0, (int) $line_item->get_meta( '_reduced_stock', true ) );
		$this->assertEquals( 2, (int) $line_item->get_meta( '_restock_refunded_items', true ) );
	}
}
