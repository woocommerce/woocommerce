<?php
/**
* Test WC coupon functions
*
* @since 2.2
*/
class WC_Tests_Coupon_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_coupon_types()
	 *
	 * @since 2.2
	 */
	public function test_wc_get_coupon_types() {

		$coupon_types = array(
			'fixed_cart'      => __( 'Cart Discount', 'woocommerce' ),
			'percent'         => __( 'Cart % Discount', 'woocommerce' ),
			'fixed_product'   => __( 'Product Discount', 'woocommerce' ),
			'percent_product' => __( 'Product % Discount', 'woocommerce' )
		);

		$this->assertEquals( $coupon_types, wc_get_coupon_types() );
	}

	/**
	 * Test wc_get_coupon_type()
	 *
	 * @since 2.2
	 */
	public function test_wc_get_coupon_type() {

		$this->assertEquals( 'Cart Discount', wc_get_coupon_type( 'fixed_cart' ) );
		$this->assertEmpty( wc_get_coupon_type( 'bogus_type' ) );
	}

}
