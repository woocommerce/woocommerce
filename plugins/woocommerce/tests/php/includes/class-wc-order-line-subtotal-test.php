<?php
declare( strict_types = 1 );

/**
 * Tests preservation of explicit order line subtotals.
 */
class WC_Order_Line_Subtotal_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox Reloading an item keeps its explicit subtotal below its final total.
	 */
	public function test_saved_item_keeps_explicit_subtotal(): void {
		$order   = wc_create_order();
		$product = WC_Helper_Product::create_simple_product();
		$item_id = $order->add_product( $product );
		$item    = $order->get_item( $item_id );
		$item->set_total( 20 );
		$item->set_subtotal( 10 );
		$item->save();

		$reloaded = wc_get_order( $order->get_id() )->get_item( $item_id );
		$this->assertSame( '10', $reloaded->get_subtotal(), 'The stored subtotal must survive a reload.' );
		$this->assertSame( '20', $reloaded->get_total(), 'The final amount must survive a reload.' );
	}

	/**
	 * @testdox Checkout keeps a cart line's explicit subtotal below its final total.
	 */
	public function test_checkout_keeps_explicit_subtotal(): void {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product();
		$key     = WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		WC()->cart->cart_contents[ $key ]['line_total']    = 20;
		WC()->cart->cart_contents[ $key ]['line_subtotal'] = 10;

		$order_id = WC()->checkout()->create_order( array( 'payment_method' => '' ) );
		$order    = wc_get_order( $order_id );
		$item     = current( $order->get_items() );
		$this->assertSame( '10', $item->get_subtotal(), 'Checkout must keep the cart subtotal.' );
		$this->assertSame( '20', $item->get_total(), 'Checkout must keep the cart final amount.' );
	}
}
