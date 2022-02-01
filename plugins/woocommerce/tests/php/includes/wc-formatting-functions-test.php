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
		// Generic postcode.
		$this->assertEquals( '02111', wc_format_postcode( ' 02111	', 'US' ) );

		// US 9-digit postcode.
		$this->assertEquals( '02111-9999', wc_format_postcode( ' 021119999	', 'US' ) );

		// UK postcode.
		$this->assertEquals( 'PCRN 1ZZ', wc_format_postcode( 'pcrn1zz', 'GB' ) );

		// IE postcode.
		$this->assertEquals( 'D02 AF30', wc_format_postcode( 'D02AF30', 'IE' ) );

		// PT postcode.
		$this->assertEquals( '1000-205', wc_format_postcode( '1000205', 'PT' ) );

		// BR/PL postcode.
		$this->assertEquals( '99999-999', wc_format_postcode( '99999999', 'BR' ) );

		// JP postcode.
		$this->assertEquals( '999-9999', wc_format_postcode( '9999999', 'JP' ) );

		// Test empty NL postcode.
		$this->assertEquals( '', wc_format_postcode( '', 'NL' ) );
	}
}
