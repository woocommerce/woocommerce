<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

/**
 * Tests that checkout keeps the cart's price tax setting.
 */
class WC_Effective_Price_Tax_Setting_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox Both checkout paths keep the price tax setting used by the cart.
	 * @testWith ["classic", "no", true, "109.09", "10.91", "120.00"]
	 *           ["store-api", "no", true, "109.09", "10.91", "120.00"]
	 *           ["classic", "yes", false, "120.00", "12.00", "132.00"]
	 *           ["store-api", "yes", false, "120.00", "12.00", "132.00"]
	 * @param string $path Checkout path.
	 * @param string $option Stored price tax setting.
	 * @param bool   $effective_setting Price tax setting after filtering.
	 * @param string $expected_subtotal Cart line subtotal.
	 * @param string $expected_tax Cart line tax.
	 * @param string $expected_total Cart and order total.
	 */
	public function test_checkout_keeps_cart_price_tax_setting( string $path, string $option, bool $effective_setting, string $expected_subtotal, string $expected_tax, string $expected_total ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', $option );
		update_option( 'woocommerce_price_num_decimals', 2 );
		add_filter( 'woocommerce_prices_include_tax', $effective_setting ? '__return_true' : '__return_false' );
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'DE',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
			)
		);
		WC()->customer->set_billing_country( 'DE' );
		WC()->customer->set_shipping_country( 'DE' );
		WC()->customer->set_is_vat_exempt( false );
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 120 ) );
		$product->set_virtual( true );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();
		$cart_items = WC()->cart->get_cart();
		$cart_item  = current( $cart_items );

		$this->assertSame( $effective_setting, wc_prices_include_tax(), 'The cart must use the filtered setting.' );
		$this->assertSame( $expected_subtotal, wc_format_decimal( $cart_item['line_subtotal'], 2 ), 'The cart must price the item using that setting.' );
		$this->assertSame( $expected_tax, wc_format_decimal( $cart_item['line_subtotal_tax'], 2 ), 'The cart must calculate tax using that setting.' );
		$this->assertSame( $expected_total, wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ), 'The cart total must include the expected tax.' );

		if ( 'store-api' === $path ) {
			$order = ( new OrderController() )->create_order_from_cart();
		} else {
			$order = wc_get_order( WC()->checkout()->create_order( array( 'payment_method' => '' ) ) );
		}

		$this->assertSame( $effective_setting, $order->get_prices_include_tax(), 'The saved order must use the cart price tax setting.' );
		$this->assertSame( $expected_total, wc_format_decimal( $order->get_total(), 2 ), 'Checkout must keep the cart total.' );
	}
}
