<?php

/**
 * Order Item Fee Tests.
 * @package WooCommerce\Tests\Order_Items
 * @since 3.2.0
 */
class WC_Tests_Order_Item_Fee extends WC_Unit_Test_Case {

	/**
	 * Test set_amount and get_amount methods of WC_Order_Item_Fee.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_amount() {
		$fee = new WC_Order_Item_Fee;
		$fee->set_amount( '20.00' );
		$this->assertEquals( '20.00', $fee->get_amount() );
	}

	/**
	 * Test set_tax_class and get_tax_class methods of WC_Order_Item_Fee.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_tax_class() {
		$old_classes = get_option( 'woocommerce_tax_classes' );
		update_option( 'woocommerce_tax_classes', 'testclass' );

		$fee = new WC_Order_Item_Fee;
		$fee->set_tax_class( 'testclass' );
		$this->assertEquals( 'testclass', $fee->get_tax_class() );

		// Clean up.
		update_option( 'woocommerce_tax_classes', $old_classes );
	}

	/**
	 * Test set_total and get_total methods of WC_Order_Item_Fee.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_total() {
		$fee = new WC_Order_Item_Fee;
		$fee->set_total( '15.01' );
		$this->assertEquals( '15.01', $fee->get_total() );
	}

	/**
	 * Test set_total_tax and get_total_tax methods of WC_Order_Item_Fee.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_total_tax() {
		$fee = new WC_Order_Item_Fee;
		$fee->set_total_tax( '5.01' );
		$this->assertEquals( '5.01', $fee->get_total_tax() );
	}

	/**
	 * Test set_taxes and get_taxes methods of WC_Order_Item_Fee.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_taxes() {
		$taxes = array(
			'total' => array( '10', '2.4' ),
		);

		$fee = new WC_Order_Item_Fee;
		$fee->set_taxes( $taxes );
		$this->assertEquals( $taxes, $fee->get_taxes() );
		$this->assertEquals( '12.4', $fee->get_total_tax() );
	}

	/**
	 * Test calculate_taxes method of WC_Order_Item_Fee.
	 *
	 * @since 3.2.0
	 */
	public function test_calculate_taxes() {
		global $wpdb;
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );
		$order = WC_Helper_Order::create_order();

		// Positive fee.
		$fee = new WC_Order_Item_Fee;
		$fee->set_amount( '5.00' );
		$fee->set_total( '5.00' );
		$fee->set_order_id( $order->get_id() );

		$fee->calculate_taxes( array(
			'country' => 'US',
			'state' => 'OR',
			'postcode' => 97266,
			'city' => 'Portland',
		) );

		$taxes = $fee->get_taxes();
		$total_taxes = array_values( $taxes['total'] );
		$expected = array( '0.5' );
		$this->assertEquals( $expected, $total_taxes );
		$this->assertEquals( '0.5', $fee->get_total_tax() );

		// Negative fee.
		$fee = new WC_Order_Item_Fee;
		$fee->set_amount( '-5.00' );
		$fee->set_total( '-5.00' );
		$fee->set_order_id( $order->get_id() );

		$fee->calculate_taxes( array(
			'country' => 'US',
			'state' => 'OR',
			'postcode' => 97266,
			'city' => 'Portland',
		) );

		$taxes = $fee->get_taxes();
		$total_taxes = array_values( $taxes['total'] );
		$expected = array( '-0.5' );
		$this->assertEquals( $expected, $total_taxes );
		$this->assertEquals( '-0.5', $fee->get_total_tax() );

		// Clean up.
		WC_Helper_Order::delete_order( $order->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
	}
}
