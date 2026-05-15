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
	 * @see WC_Tests_Formatting_Functions::test_wc_format_postcode for US, GB, BR, JP, NL, LV
	 */
	public function data_provider_test_wc_format_postcode(): array {
		$ie = array(
			array( 'D02 AF30', 'D02AF30', 'IE' ),
		);

		$pt = array(
			array( '1000-205', '1000205', 'PT' ),
		);

		$dk = array(
			array( '1234', '1234', 'DK' ),
			array( 'DK-1234', 'DK-1234', 'DK' ),
			array( 'DK-1234', 'dk-1234', 'DK' ),
		);

		$se = array(
			array( '113 52', '11352', 'SE' ),
		);

		$sk = array(
			array( '811 02', '81102', 'SK' ),
			array( 'SK-811 02', 'SK-81102', 'SK' ),
			array( 'SK-811 02', 'sk-81102', 'SK' ),
		);

		$cz = array(
			array( '115 03', '11503', 'CZ' ),
			array( 'CZ-115 03', 'CZ-11503', 'CZ' ),
			array( 'CZ-115 03', 'cz-11503', 'CZ' ),
		);

		return array_merge( $ie, $pt, $dk, $se, $sk, $cz );
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
		$this->assertSame( $assert, wc_format_postcode( $postcode, $country ), "Test formatting of $postcode postcodes." );
	}

	/**
	 * Test wc_is_stock_amount_integer function.
	 *
	 * @testdox wc_is_stock_amount_integer should return true when stock amounts are integers and false when they are floats.
	 */
	public function test_wc_is_stock_amount_integer() {
		// Remove all filters from woocommerce_stock_amount.
		remove_all_filters( 'woocommerce_stock_amount' );

		// Test with floatval applied to the filter.
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$this->assertFalse( wc_is_stock_amount_integer(), 'Should return false when floatval is applied to stock amount filter.' );

		// Remove floatval filter and add intval filter.
		remove_all_filters( 'woocommerce_stock_amount' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
		$this->assertTrue( wc_is_stock_amount_integer(), 'Should return true when intval is applied to stock amount filter.' );
	}

	/**
	 * Data provider for `test_wc_normalize_product_description_list_whitespace`.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function provide_list_whitespace_samples(): array {
		return array(
			'collapses single newline between list items' => array(
				'<ul><li>Feature one</li>' . "\n" . '<li>Feature two</li></ul>',
				'<ul><li>Feature one</li><li>Feature two</li></ul>',
			),
			'collapses double newline between list items' => array(
				'<ul><li>Feature one</li>' . "\n\n" . '<li>Feature two</li></ul>',
				'<ul><li>Feature one</li><li>Feature two</li></ul>',
			),
			'collapses whitespace inside an ordered list' => array(
				"<ol>\n<li>one</li>\n<li>two</li>\n</ol>",
				'<ol><li>one</li><li>two</li></ol>',
			),
			'preserves whitespace inside the visible content of an <li>' => array(
				"<ul><li>Feature   one</li>\n<li>Feature\ntwo</li></ul>",
				"<ul><li>Feature   one</li><li>Feature\ntwo</li></ul>",
			),
			'leaves descriptions without list items untouched' => array(
				"<p>Hello</p>\n\n<p>World</p>",
				"<p>Hello</p>\n\n<p>World</p>",
			),
			'preserves attributes on the list container'  => array(
				"<ul class=\"x\">\n<li>a</li>\n<li>b</li>\n</ul>",
				'<ul class="x"><li>a</li><li>b</li></ul>',
			),
			'handles empty input'                         => array(
				'',
				'',
			),
		);
	}

	/**
	 * @dataProvider provide_list_whitespace_samples
	 *
	 * @testdox wc_normalize_product_description_list_whitespace collapses structural list whitespace introduced by block round-trips.
	 *
	 * @param string $input    Raw input HTML.
	 * @param string $expected Normalized output HTML.
	 */
	public function test_wc_normalize_product_description_list_whitespace( string $input, string $expected ): void {
		$this->assertSame( $expected, wc_normalize_product_description_list_whitespace( $input ) );
	}

	/**
	 * Non-string inputs should yield an empty string and never trigger a PHP warning.
	 *
	 * @testdox wc_normalize_product_description_list_whitespace returns an empty string for non-string inputs.
	 */
	public function test_wc_normalize_product_description_list_whitespace_non_string_input(): void {
		// @phpstan-ignore-next-line - intentionally passing a non-string to verify defensive handling.
		$this->assertSame( '', wc_normalize_product_description_list_whitespace( null ) );
	}
}
