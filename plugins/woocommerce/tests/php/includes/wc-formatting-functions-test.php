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
}
