<?php

/**
 * Test for the discount class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discount extends WC_Unit_Test_Case {

	/**
	 * Test get and set ID.
	 */
	public function test_get_set_id() {
		$discount = new WC_Discount;
		$discount->set_id( 'discount-5' );
		$this->assertEquals( 'discount-5', $discount->get_id() );
	}

	/**
	 * Test get and set ID.
	 */
	public function test_get_set_amount() {
		$discount = new WC_Discount;
		$discount->set_amount( '10' );
		$this->assertEquals( '10', $discount->get_amount() );
		$this->assertEquals( 'fixed_cart', $discount->get_discount_type() );

		$discount = new WC_Discount;
		$discount->set_amount( '10%' );
		$this->assertEquals( '10', $discount->get_amount() );
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

	/**
	 * Test get and set taxes.
	 */
	public function test_get_set_taxes() {

	}

	/**
	 * Test calculate negative taxes.
	 */
	public function test_calculate_negative_taxes() {

	}
}
