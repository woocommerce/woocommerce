<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;

/**
 * Tests that checkout stores the effective price tax setting.
 */
class WC_Effective_Price_Tax_Setting_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox Both checkout paths save the filtered price tax setting.
	 * @testWith ["classic"]
	 *           ["store-api"]
	 * @param string $path Checkout path.
	 */
	public function test_checkout_uses_effective_price_tax_setting( string $path ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		add_filter( 'woocommerce_prices_include_tax', '__return_true' );
		WC()->cart->empty_cart();
		$product = WC_Helper_Product::create_simple_product( true, array( 'regular_price' => 12 ) );
		$product->set_virtual( true );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		if ( 'store-api' === $path ) {
			$order = ( new OrderController() )->create_order_from_cart();
		} else {
			$order = wc_get_order( WC()->checkout()->create_order( array( 'payment_method' => '' ) ) );
		}

		$this->assertTrue( wc_prices_include_tax(), 'The filter must make prices tax-inclusive.' );
		$this->assertTrue( $order->get_prices_include_tax(), 'The order must store the effective setting.' );
	}
}
