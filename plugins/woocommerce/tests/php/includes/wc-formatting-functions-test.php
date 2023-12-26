<?php
/**
 * Formatting functions tests
 *
 * @package WooCommerce\Tests\Formatting.
 */

/**
 * Class WC_Formatting_Functions_Test
 */
class WC_Formatting_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test wc_sanitize_coupon_code() function.
	 */
	public function test_wc_sanitize_coupon_code() {
		$this->assertEquals( 'DUMMYCOUPON', wc_sanitize_coupon_code( 'DUMMYCOUPON' ) );
		$this->assertEquals( 'a&amp;a', wc_sanitize_coupon_code( 'a&a' ) );
	}

	/**
	 * Test wc_format_postcode() function.
	 */
	public function test_wc_format_postcode() {
		$this->assertEquals( 'D02 AF30', wc_format_postcode( 'D02AF30', 'IE' ), 'Test formatting of IE postcodes.' );
		$this->assertEquals( '1000-205', wc_format_postcode( '1000205', 'PT' ), 'Test formatting of PT postcodes.' );
	}
}
