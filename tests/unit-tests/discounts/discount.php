<?php

/**
 * Test for the discount class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discount extends WC_Unit_Test_Case {

	/**
	 * Test get and set ID.
	 */
	public function test_get_set_amount() {
		$discount = new WC_Discount;
		$discount->set_amount( '10' );
		$this->assertEquals( '10', $discount->get_amount() );
	}

	public function test_get_set_type() {
		$discount = new WC_Discount;

		$discount->set_discount_type( 'fixed' );
		$this->assertEquals( 'fixed', $discount->get_discount_type() );

		$discount->set_discount_type( 'percent' );
		$this->assertEquals( 'percent', $discount->get_discount_type() );
	}

	/**
	 * Test get and set discount total.
	 */
	public function test_get_set_discount_total() {
		$discount = new WC_Discount;
		$discount->set_discount_total( 1000 );
		$this->assertEquals( 1000, $discount->get_discount_total() );
	}
}
