<?php
/**
 * Class WC_Tests_Order file.
 *
 * @package WooCommerce|Tests|Order
 */

/**
 * Class WC_Tests_Order.
 */
class WC_Tests_Order extends WC_Unit_Test_Case {

	/**
	 * Test for total when round at subtotal is enabled.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/24695
	 */
	public function test_order_calculate_total_rounding_24695() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		$tax_rate    = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '7.0000',
			'tax_rate_name'     => 'CGST',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => 'tax_1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 2 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 2.5 );
		$product2->save();

		$order = new WC_Order();
		$order->add_product( $product1, 1 );
		$order->add_product( $product2, 4 );
		$order->save();

		$order->calculate_totals( true );

		$this->assertEquals( 12, $order->get_total() );
		$this->assertEquals( 0.79, $order->get_total_tax() );
	}

}
