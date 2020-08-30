<?php
/**
 * Class WC_Abstract_Order file.
 *
 * @package WooCommerce\Tests\Abstracts
 */

/**
 * Class WC_Abstract_Order.
 */
class WC_Abstract_Order_Test extends WC_Unit_Test_Case {

	/**
	 * Test when rounding is different when doing per line and in subtotal.
	 */
	public function test_order_calculate_26582() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '15.0000',
			'tax_rate_name'     => 'tax',
			'tax_rate_priority' => '1',
			'tax_rate_order'    => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_regular_price( 99.48 );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_regular_price( 108.68 );
		$product2->save();

		$order = new WC_Order();
		$order->add_product( $product1, 6 );
		$order->add_product( $product2, 6 );
		$order->save();

		$this->order_calculate_rounding_line( $order );
		$this->order_calculate_rounding_subtotal( $order );
	}

	/**
	 * Helper method to test rounding per line for `test_order_calculate_26582`.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function order_calculate_rounding_line( $order ) {
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		$order->calculate_totals( true );

		$this->assertEquals( 1086.06, $order->get_subtotal() );
		$this->assertEquals( 162.90, $order->get_total_tax() );
		$this->assertEquals( 1248.96, $order->get_total() );
	}

	/**
	 * Helper method to test rounding at subtotal for `test_order_calculate_26582`.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function order_calculate_rounding_subtotal( $order ) {
		update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );

		$order->calculate_totals( true );

		$this->assertEquals( 1086.05, $order->get_subtotal() );
		$this->assertEquals( 162.91, $order->get_total_tax() );
		$this->assertEquals( 1248.96, $order->get_total() );
	}

}
