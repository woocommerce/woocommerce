<?php

/**
 * Order Item Coupon Tests.
 * @package WooCommerce\Tests\Order_Items
 * @since 3.2.0
 */
class WC_Tests_Order_Item_Coupon extends WC_Unit_Test_Case {

	/**
	 * Test the setter and getter methods.
	 *
	 * @since 3.2.0
	 */
	public function test_setters_getters() {
		$coupon = new WC_Order_Item_Coupon();

		$coupon->set_name( 'testcoupon' );
		$this->assertTrue( 'testcoupon' === $coupon->get_name() && $coupon->get_name() === $coupon->get_code() );
		$coupon->set_code( 'testcoupon2' );
		$this->assertTrue( 'testcoupon2' === $coupon->get_name() && $coupon->get_name() === $coupon->get_code() );

		$coupon->set_discount( '5.00' );
		$this->assertEquals( '5.00', $coupon->get_discount() );

		$coupon->set_discount_tax( '0.50' );
		$this->assertEquals( '0.50', $coupon->get_discount_tax() );
	}
}
