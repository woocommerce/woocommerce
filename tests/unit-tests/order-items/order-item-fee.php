<?php

/**
 * Order Item Fee Tests.
 * @package WooCommerce\Tests\Order_Items
 * @since 3.2.0
 */
class WC_Tests_Order_Item_Fee extends WC_Unit_Test_Case {

	/**
	 * Test WC_Order_Item_Fee setters and getters.
	 *
	 * @since 3.2.0
	 */
	public function test_setters_getters() {
		$fee = new WC_Order_Item_Fee();

		$fee->set_amount( '20.00' );
		$this->assertEquals( '20.00', $fee->get_amount() );

		$fee->set_tax_class( 'reduced-rate' );
		$this->assertEquals( 'reduced-rate', $fee->get_tax_class() );

		$fee->set_total( '15.01' );
		$this->assertEquals( '15.01', $fee->get_total() );

		$fee->set_total_tax( '5.01' );
		$this->assertEquals( '5.01', $fee->get_total_tax() );

		$taxes = array(
			'total' => array( '10', '2.4' ),
		);
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
		$fee = new WC_Order_Item_Fee();
		$fee->set_amount( '5.00' );
		$fee->set_total( '5.00' );
		$fee->set_order_id( $order->get_id() );

		$fee->calculate_taxes(
			array(
				'country'  => 'US',
				'state'    => 'OR',
				'postcode' => 97266,
				'city'     => 'Portland',
			)
		);

		$taxes       = $fee->get_taxes();
		$total_taxes = array_values( $taxes['total'] );
		$expected    = array( '0.5' );
		$this->assertEquals( $expected, $total_taxes );
		$this->assertEquals( '0.5', $fee->get_total_tax() );

		// Negative fee.
		$fee = new WC_Order_Item_Fee();
		$fee->set_amount( '-5.00' );
		$fee->set_total( '-5.00' );
		$fee->set_order_id( $order->get_id() );

		$fee->calculate_taxes(
			array(
				'country'  => 'US',
				'state'    => 'OR',
				'postcode' => 97266,
				'city'     => 'Portland',
			)
		);

		$taxes       = $fee->get_taxes();
		$total_taxes = array_values( $taxes['total'] );
		$expected    = array( '-0.5' );
		$this->assertEquals( $expected, $total_taxes );
		$this->assertEquals( '-0.5', $fee->get_total_tax() );
	}
}
