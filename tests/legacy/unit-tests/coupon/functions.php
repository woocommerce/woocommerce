<?php
/**
 * Class Functions.
 * @package WooCommerce\Tests\Coupon
 * @since 2.2
 */
class WC_Tests_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_coupon_types().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_coupon_types() {

		$coupon_types = array(
			'percent'       => __( 'Percentage discount', 'woocommerce' ),
			'fixed_cart'    => __( 'Fixed cart discount', 'woocommerce' ),
			'fixed_product' => __( 'Fixed product discount', 'woocommerce' ),
		);

		$this->assertEquals( $coupon_types, wc_get_coupon_types() );
	}

	/**
	 * Test wc_get_coupon_type().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_coupon_type() {

		$this->assertEquals( 'Fixed cart discount', wc_get_coupon_type( 'fixed_cart' ) );
		$this->assertEmpty( wc_get_coupon_type( 'bogus_type' ) );
	}

	/**
	 * Test coupons_enabled method.
	 *
	 * @since 2.5.0
	 */
	public function test_wc_coupons_enabled() {
		$this->assertEquals( apply_filters( 'woocommerce_coupons_enabled', get_option( 'woocommerce_enable_coupons' ) === 'yes' ), wc_coupons_enabled() );
	}

	/**
	 * Test wc_get_coupon_code_by_id().
	 *
	 * @since 3.0.0
	 */
	public function test_wc_get_coupon_code_by_id() {
		// Create coupon.
		$code   = 'testcoupon';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		$this->assertEquals( $code, wc_get_coupon_code_by_id( $coupon->get_id() ) );

		// Delete coupon.
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );

		$this->assertEmpty( wc_get_coupon_code_by_id( 0 ) );
	}

	/**
	 * Test wc_get_coupon_id_by_code().
	 *
	 * @since 3.0.0
	 */
	public function test_wc_get_coupon_id_by_code() {
		// Create coupon.
		$code   = 'testcoupon';
		$coupon = WC_Helper_Coupon::create_coupon( $code );

		$this->assertEquals( $coupon->get_id(), wc_get_coupon_id_by_code( $coupon->get_code() ) );

		// Delete coupon.
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );

		$this->assertEquals( 0, wc_get_coupon_id_by_code( '' ) );

		$this->assertEmpty( wc_get_coupon_id_by_code( 0 ) );
	}

}
