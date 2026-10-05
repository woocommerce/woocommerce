<?php
declare( strict_types = 1 );

/**
 * Tests preservation of explicit order line subtotals.
 */
class WC_Order_Line_Subtotal_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox Reloading an item keeps its saved subtotal.
	 *
	 * @param int $subtotal Saved subtotal.
	 *
	 * @testWith [10]
	 *           [0]
	 *           [30]
	 */
	public function test_saved_item_keeps_explicit_subtotal( int $subtotal ): void {
		$order   = wc_create_order();
		$product = WC_Helper_Product::create_simple_product();
		$item_id = $order->add_product( $product );
		$item    = $order->get_item( $item_id );
		$item->set_total( 20 );
		$item->set_subtotal( $subtotal );
		$item->save();

		$sut = new WC_Order_Item_Product( $item_id );
		$this->assertSame( (string) $subtotal, $sut->get_subtotal( 'edit' ), 'The stored subtotal must survive a reload.' );
		$this->assertSame( '20', $sut->get_total( 'edit' ), 'The final amount must survive a reload.' );
	}

	/**
	 * @testdox Reloading an item with no saved subtotal uses its total.
	 */
	public function test_saved_item_with_missing_subtotal_uses_total(): void {
		$item = new WC_Order_Item_Product();
		$item->set_total( 20 );
		$item->save();
		delete_metadata( 'order_item', $item->get_id(), '_line_subtotal' );

		$sut = new WC_Order_Item_Product( $item->get_id() );
		$this->assertSame( '20', $sut->get_subtotal( 'edit' ), 'A missing subtotal must still use the final amount.' );
	}

	/**
	 * @testdox Checkout keeps a cart line's explicit subtotal.
	 *
	 * @param int $subtotal Cart line subtotal.
	 *
	 * @testWith [10]
	 *           [0]
	 *           [30]
	 */
	public function test_checkout_keeps_explicit_subtotal( int $subtotal ): void {
		update_option( 'woocommerce_calc_taxes', 'no' );
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product();
		$key     = WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		WC()->cart->cart_contents[ $key ]['line_total']    = 20;
		WC()->cart->cart_contents[ $key ]['line_subtotal'] = $subtotal;

		$order_id = WC()->checkout()->create_order( array( 'payment_method' => '' ) );
		$order    = wc_get_order( $order_id );
		$sut      = current( $order->get_items() );
		$this->assertSame( (string) $subtotal, $sut->get_subtotal( 'edit' ), 'Checkout must keep the cart subtotal.' );
		$this->assertSame( '20', $sut->get_total( 'edit' ), 'Checkout must keep the cart final amount.' );
		$this->assertSame( (string) $subtotal, get_metadata( 'order_item', $sut->get_id(), '_line_subtotal', true ), 'Checkout must save the cart subtotal.' );
	}
}
