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
	 * Data provider for test_wc_sanitize_coupon_code.
	 *
	 * @return array[]
	 */
	public function data_provider_test_wc_sanitize_coupon_code(): array {
		return array(
			array( 'DUMMYCOUPON', 'DUMMYCOUPON' ),
			array( 'a&amp;a', 'a&a' ),
			array( "test's", "test's" ),
		);
	}

	/**
	 * Test wc_sanitize_coupon_code() function.
	 *
	 * @dataProvider data_provider_test_wc_sanitize_coupon_code
	 *
	 * @param string $assert Expected result.
	 * @param string $input Input for wc_sanitize_coupon_code().
	 */
	public function test_wc_sanitize_coupon_code( string $assert, string $input ) {
		$this->assertSame( $assert, wc_sanitize_coupon_code( $input ) );
	}

	/**
	 * Data provider for test_wc_format_postcode.
	 *
	 * @return array[]
	 */
	public function data_provider_test_wc_format_postcode(): array {
		return array(
			array( 'D02 AF30', 'D02AF30', 'IE' ),
			array( '1000-205', '1000205', 'PT' ),
		);
	}

	/**
	 * Test wc_format_postcode() function.
	 *
	 * @dataProvider data_provider_test_wc_format_postcode
	 *
	 * @param string $assert Expected result.
	 * @param string $postcode Postcode input for wc_format_postcode().
	 * @param string $country Country input for wc_format_postcode().
	 */
	public function test_wc_format_postcode( string $assert, string $postcode, string $country ) {
		$this->assertSame( $assert, wc_format_postcode( $postcode, $country ) );
	}
}
