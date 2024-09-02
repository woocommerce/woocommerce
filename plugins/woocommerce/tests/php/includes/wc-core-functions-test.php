<?php
/**
 * Core functions tests
 *
 * @package WooCommerce\Tests\Functions.
 */

/**
 * Class WC_Core_Functions_Test
 */
class WC_Core_Functions_Test extends \WC_Unit_Test_Case {

	/**
	 * Test wc_ascii_uasort_comparison() function.
	 */
	public function test_wc_ascii_uasort_comparison() {
		$unsorted_values = array(
			'ET' => 'Éthiopie',
			'ES' => 'Espagne',
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
		);

		$sorted_values = $unsorted_values;
		uasort( $sorted_values, 'wc_ascii_uasort_comparison' );

		$this->assertSame( array( 'Afghanistan', 'Åland Islands', 'Espagne', 'Éthiopie' ), array_values( $sorted_values ) );
	}

	/**
	 * Test wc_asort_by_locale() function.
	 */
	public function test_wc_asort_by_locale() {
		$unsorted_values = array(
			'ET' => 'Éthiopie',
			'ES' => 'Espagne',
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
		);

		$sorted_values = $unsorted_values;
		wc_asort_by_locale( $sorted_values );

		$this->assertSame( array( 'Afghanistan', 'Åland Islands', 'Espagne', 'Éthiopie' ), array_values( $sorted_values ) );
	}

	/**
	 * @testdDox wc_get_rounding_precision returns the value of wc_get_price_decimals()+2, but with a minimum of WC_ROUNDING_PRECISION (6)
	 *
	 * @testWith [0, 6]
	 *           [2, 6]
	 *           [4, 6]
	 *           [5, 7]
	 *           [6, 8]
	 *           [7, 9]
	 *
	 * @param int $decimals Value returned by wc_get_price_decimals().
	 * @param int $expected Expected value returned by the function.
	 */
	public function test_wc_get_rounding_precision( $decimals, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function() use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_get_rounding_precision();
		$this->assertEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * @testDox wc_add_number_precision moves the decimal point to the right as many places as wc_get_price_decimals() says, and (optionally) properly rounds the result.
	 *
	 * @testWith [2, 1.23456789, false, 123.456789]
	 *           [2, 1.23456789, true, 123.4568]
	 *           [4, 1.23456789, false, 12345.6789]
	 *           [4, 1.23456789, true, 12345.68]
	 *           [5, 1.23456789, false, 123456.789]
	 *           [5, 1.23456789, true, 123456.79]
	 *           [2, null, false, 0]
	 *           [2, null, true, 0]
	 *
	 * @param int   $decimals Value returned by wc_get_price_decimals().
	 * @param mixed $value Value to pass to the function.
	 * @param bool  $round Whether to round the result or not.
	 * @param float $expected Expected value returned by the function.
	 */
	public function test_wc_add_number_precision( $decimals, $value, $round, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function() use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_add_number_precision( $value, $round );
		$this->assertFloatEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * @testWith [2, 123.4567, 1.234567]
	 *           [5, 123.4567, 0.001234567]
	 *           [2, null, 0]
	 *
	 * @param int   $decimals Value returned by wc_get_price_decimals().
	 * @param mixed $value Value to pass to the function.
	 * @param float $expected Expected value returned by the function.
	 */
	public function test_wc_remove_number_precision( $decimals, $value, $expected ) {
		add_filter(
			'wc_get_price_decimals',
			function() use ( $decimals ) {
				return $decimals;
			}
		);

		$actual = wc_remove_number_precision( $value );
		$this->assertEquals( $expected, $actual );

		remove_all_filters( 'wc_get_price_decimals' );
	}

	/**
	 * Test wc_help_tip() function.
	 */
	public function test_wc_help_tip_strips_html() {
		$expected = '<span class="woocommerce-help-tip" tabindex="0" aria-label="Strong text regular text" data-tip="&lt;strong&gt;Strong text&lt;/strong&gt; regular text"></span>';
		$this->assertEquals( $expected, wc_help_tip( '<strong>Strong text</strong> regular text', false ) );
		$this->assertEquals( $expected, wc_help_tip( '<strong>Strong text</strong> regular text', true ) );
	}
}
