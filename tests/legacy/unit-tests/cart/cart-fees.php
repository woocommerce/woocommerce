<?php

/**
 * Class WC_Cart_Fees.
 * @package WooCommerce\Tests\Cart
 * @since 3.2.0
 */
class WC_Tests_WC_Cart_Fees extends WC_Unit_Test_Case {

	/**
	 * Test the set/get/remove methods of WC_Cart_Fees.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_remove_fees() {

		$cart_fees = new WC_Cart_Fees( wc()->cart );

		// Test add_fee.
		$args = array(
			'name'   => 'testfee',
			'amount' => 10,
		);
		$cart_fees->add_fee( $args );
		$applied_fees = $cart_fees->get_fees();
		$this->assertEquals( 'testfee', $applied_fees['testfee']->name );
		$this->assertEquals( 10, $applied_fees['testfee']->amount );
		$this->assertEquals( 1, count( $applied_fees ) );

		// Test remove_all_fees.
		$cart_fees->remove_all_fees();
		$this->assertEquals( array(), $cart_fees->get_fees() );

		// Test set_fees.
		$args = array(
			array(
				'name'   => 'newfee',
				'amount' => -5,
			),
			array(
				'name'      => 'newfee2',
				'amount'    => 10,
				'tax_class' => 'Reduced rate',
				'taxable'   => true,
			),
		);
		$cart_fees->set_fees( $args );
		$applied_fees = $cart_fees->get_fees();
		$this->assertEquals( -5, $applied_fees['newfee']->amount );
		$this->assertEquals( 'Reduced rate', $applied_fees['newfee2']->tax_class );
		$this->assertEquals( 2, count( $applied_fees ) );

		// Clean up.
		WC()->cart->empty_cart();
	}
}
