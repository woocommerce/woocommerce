<?php
/**
 * Formatting functions
 *
 * @package WooCommerce\Tests\Formatting
 */

/**
 * Class Functions.
 *
 * @since 2.2
 */
class WC_Tests_Formatting_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_string_to_bool().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_string_to_bool() {
		$this->assertTrue( wc_string_to_bool( 1 ) );
		$this->assertTrue( wc_string_to_bool( 'yes' ) );
		$this->assertTrue( wc_string_to_bool( 'true' ) );
		$this->assertFalse( wc_string_to_bool( 0 ) );
		$this->assertFalse( wc_string_to_bool( 'no' ) );
		$this->assertFalse( wc_string_to_bool( 'false' ) );
	}

	/**
	 * Test wc_bool_to_string().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_bool_to_string() {
		$this->assertEquals( array( 'yes', 'no' ), array( wc_bool_to_string( true ), wc_bool_to_string( false )	) );
	}

	/**
	 * Test wc_string_to_array().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_string_to_array() {
		$this->assertEquals( array(
			'foo',
			'bar',
		), wc_string_to_array( 'foo|bar', '|' ) );
	}

	/**
	 * Test wc_sanitize_taxonomy_name().
	 *
	 * @since 2.2
	 */
	public function test_wc_sanitize_taxonomy_name() {
		$this->assertEquals( 'name-with-spaces', wc_sanitize_taxonomy_name( 'Name With Spaces' ) );
		$this->assertEquals( 'namewithtabs', wc_sanitize_taxonomy_name( 'Name	With	Tabs' ) );
		$this->assertEquals( 'specialchars', wc_sanitize_taxonomy_name( 'special!@#$%^&*()chars' ) );
		$this->assertEquals( 'look-of-ಠ_ಠ', wc_sanitize_taxonomy_name( 'Look Of ಠ_ಠ' ) );
	}

	/**
	 * Test wc_sanitize_permalink().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_sanitize_permalink() {
		$this->assertEquals( 'foo.com/bar', wc_sanitize_permalink( 'http://foo.com/bar' ) );
	}

	/**
	 * Test wc_get_filename_from_url().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_filename_from_url() {
		$this->assertEquals( 'woocommerce.pdf', wc_get_filename_from_url( 'https://woocommerce.com/woocommerce.pdf' ) );
		$this->assertEmpty( wc_get_filename_from_url( 'ftp://wc' ) );
		$this->assertEmpty( wc_get_filename_from_url( 'http://www.skyverge.com' ) );
		$this->assertEquals( 'woocommerce',  wc_get_filename_from_url( 'https://woocommerce.com/woocommerce' ) );
	}

	/**
	 * Test wc_get_dimension().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_dimension() {
		// Save default.
		$default_unit = get_option( 'woocommerce_dimension_unit' );

		// cm (default unit).
		$this->assertEquals( array( 10, 3.937, 0.10936133, 100, 0.1 ), array(
			wc_get_dimension( 10, 'cm' ),
			wc_get_dimension( 10, 'in' ),
			wc_get_dimension( 10, 'yd' ),
			wc_get_dimension( 10, 'mm' ),
			wc_get_dimension( 10, 'm' ),
		) );

		// in.
		update_option( 'woocommerce_dimension_unit', 'in' );
		$this->assertEquals( array( 25.4, 10, 0.2777777782, 254, 0.254 ), array(
			wc_get_dimension( 10, 'cm' ),
			wc_get_dimension( 10, 'in' ),
			wc_get_dimension( 10, 'yd' ),
			wc_get_dimension( 10, 'mm' ),
			wc_get_dimension( 10, 'm' ),
		) );

		// m.
		update_option( 'woocommerce_dimension_unit', 'm' );
		$this->assertEquals( array( 1000, 393.7, 10.936133, 10000, 10 ), array(
			wc_get_dimension( 10, 'cm' ),
			wc_get_dimension( 10, 'in' ),
			wc_get_dimension( 10, 'yd' ),
			wc_get_dimension( 10, 'mm' ),
			wc_get_dimension( 10, 'm' ),
		) );

		// mm.
		update_option( 'woocommerce_dimension_unit', 'mm' );
		$this->assertEquals( array( 1, 0.3937, 0.010936133, 10, 0.01 ), array(
			wc_get_dimension( 10, 'cm' ),
			wc_get_dimension( 10, 'in' ),
			wc_get_dimension( 10, 'yd' ),
			wc_get_dimension( 10, 'mm' ),
			wc_get_dimension( 10, 'm' ),
		) );

		// yd.
		update_option( 'woocommerce_dimension_unit', 'yd' );
		$this->assertEquals( array( 914.4, 359.99928, 10, 9144, 9.144 ), array(
			wc_get_dimension( 10, 'cm' ),
			wc_get_dimension( 10, 'in' ),
			wc_get_dimension( 10, 'yd' ),
			wc_get_dimension( 10, 'mm' ),
			wc_get_dimension( 10, 'm' ),
		) );

		// Negative.
		$this->assertEquals( 0, wc_get_dimension( -10, 'mm' ) );

		// Custom.
		$this->assertEquals( array( 25.4, 914.4, 393.7, 0.010936133 ), array(
			wc_get_dimension( 10, 'cm', 'in' ),
			wc_get_dimension( 10, 'cm', 'yd' ),
			wc_get_dimension( 10, 'in', 'm' ),
			wc_get_dimension( 10, 'yd', 'mm' ),
		) );

		// Restore default.
		update_option( 'woocommerce_dimension_unit', $default_unit );
	}

	/**
	 * Test wc_get_weight().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_weight() {
		// Save default.
		$default_unit = get_option( 'woocommerce_weight_unit' );

		// kg (default unit).
		$this->assertEquals( 10, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 10000, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 22.0462, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 352.74, wc_get_weight( 10, 'oz' ) );

		// g.
		update_option( 'woocommerce_weight_unit', 'g' );
		$this->assertEquals( 0.01, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 0.0220462, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 0.35274, wc_get_weight( 10, 'oz' ) );

		// lbs.
		update_option( 'woocommerce_weight_unit', 'lbs' );
		$this->assertEquals( 4.53592, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 4535.92, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 160.00004208, wc_get_weight( 10, 'oz' ) );

		// oz.
		update_option( 'woocommerce_weight_unit', 'oz' );
		$this->assertEquals( 0.283495, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 283.495, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 0.6249987469, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'oz' ) );

		// Custom from unit.
		$this->assertEquals( 0.283495, wc_get_weight( 10, 'kg', 'oz' ) );
		$this->assertEquals( 0.01, wc_get_weight( 10, 'kg', 'g' ) );
		$this->assertEquals( 4.53592, wc_get_weight( 10, 'kg', 'lbs' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'kg', 'kg' ) );

		// Negative.
		$this->assertEquals( 0, wc_get_weight( -10, 'g' ) );

		// Restore default.
		update_option( 'woocommerce_weight_unit', $default_unit );
	}

	/**
	 * Test wc_trim_zeros().
	 *
	 * @since 2.2
	 */
	public function test_wc_trim_zeros() {
		$this->assertEquals( '$1', wc_trim_zeros( '$1.00' ) );
		$this->assertEquals( '$1.10', wc_trim_zeros( '$1.10' ) );
	}

	/**
	 * Test wc_round_tax_total().
	 *
	 * Note the PHP 5.2 section of wc_round_tax_total() is excluded from test.
	 * coverage.
	 *
	 * @since 2.2
	 */
	public function test_wc_round_tax_total() {
		$this->assertEquals( 1.25, wc_round_tax_total( 1.246 ) );
		$this->assertEquals( 20, wc_round_tax_total( 19.9997 ) );
		$this->assertEquals( 19.99, wc_round_tax_total( 19.99 ) );
	}

	/**
	 * Test wc_format_refund_total().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_refund_total() {
		$this->assertEquals( -10, wc_format_refund_total( 10 ) );
		$this->assertEquals( 10, wc_format_refund_total( -10 ) );
	}

	/**
	 * Test wc_format_decimal().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_decimal() {
		// Given string.
		$this->assertEquals( '9.99', wc_format_decimal( '9.99' ) );

		// Float.
		$this->assertEquals( '9.99', wc_format_decimal( 9.99 ) );

		// DP false, no rounding.
		$this->assertEquals( '9.9999', wc_format_decimal( 9.9999 ) );

		// DP when empty string uses default as 2.
		$this->assertEquals( '9.99', wc_format_decimal( 9.9911, '' ) );

		// DP use default as 2 and round.
		$this->assertEquals( '10.00', wc_format_decimal( 9.9999, '' ) );

		// DP use custom.
		$this->assertEquals( '9.991', wc_format_decimal( 9.9912, 3 ) );

		// Trim zeros.
		$this->assertEquals( '9', wc_format_decimal( 9.00, false, true ) );

		// Trim zeros and round.
		$this->assertEquals( '10', wc_format_decimal( 9.9999, '', true ) );

		// Given string with thousands in german format.
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_price_thousand_sep', '.' );

		// Given string.
		$this->assertEquals( '9.99', wc_format_decimal( '9.99' ) );

		// Float.
		$this->assertEquals( '9.99', wc_format_decimal( 9.99 ) );

		// DP false, no rounding.
		$this->assertEquals( '9.9999', wc_format_decimal( 9.9999 ) );

		// DP when empty string uses default as 2.
		$this->assertEquals( '9.99', wc_format_decimal( 9.9911, '' ) );

		// DP use default as 2 and round.
		$this->assertEquals( '10.00', wc_format_decimal( 9.9999, '' ) );

		// DP use custom.
		$this->assertEquals( '9.991', wc_format_decimal( 9.9912, 3 ) );

		// Trim zeros.
		$this->assertEquals( '9', wc_format_decimal( 9.00, false, true ) );

		// Trim zeros and round.
		$this->assertEquals( '10', wc_format_decimal( 9.9999, '', true ) );

		update_option( 'woocommerce_price_num_decimals', '8' );

		// Floats.
		$this->assertEquals( '0.00001', wc_format_decimal( 0.00001 ) );
		$this->assertEquals( '0.22222222', wc_format_decimal( 0.22222222 ) );

		update_option( 'woocommerce_price_num_decimals', '2' );
		update_option( 'woocommerce_price_decimal_sep', '.' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
	}

	/**
	 * Test wc_float_to_string().
	 *
	 * @since 2.2
	 */
	public function test_wc_float_to_string() {
		// Given string, return string.
		$this->assertEquals( '1.99', wc_float_to_string( '1.99' ) );
		$this->assertEquals( '1.17', wc_float_to_string( 1.17 ) );
	}

	/**
	 * Test wc_format_localized_price().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_localized_price() {
		// Save default.
		$decimal_sep = get_option( 'woocommerce_price_decimal_sep' );
		update_option( 'woocommerce_price_decimal_sep', ',' );

		$this->assertEquals( '1,17', wc_format_localized_price( '1.17' ) );

		// Restore default.
		update_option( 'woocommerce_price_decimal_sep', $decimal_sep );
	}

	/**
	 * Test wc_format_localized_decimal().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_localized_decimal() {
		$this->assertEquals( '1.17', wc_format_localized_decimal( '1.17' ) );
	}

	/**
	 * Test wc_format_coupon_code().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_coupon_code() {
		$this->assertEquals( 'foo#bar', wc_format_coupon_code( 'FOO#bar<script>alert();</script>' ) );
	}

	/**
	 * Test wc_clean().
	 *
	 * @since 2.2
	 */
	public function test_wc_clean() {
		$this->assertEquals( 'cleaned', wc_clean( '<script>alert();</script>cleaned' ) );
		$this->assertEquals( array( 'cleaned', 'foo' ), wc_clean( array( '<script>alert();</script>cleaned', 'foo' ) ) );
	}

	/**
	 * Test wc_sanitize_textarea().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_sanitize_textarea() {
		$this->assertEquals( "foo\ncleaned\nbar", wc_sanitize_textarea( "foo\n<script>alert();</script>cleaned\nbar" ) );
	}

	/**
	 * Test wc_sanitize_tooltip().
	 *
	 * Note this is a basic type test as WP core already has coverage for wp_kses().
	 *
	 * @since 2.4
	 */
	public function test_wc_sanitize_tooltip() {
		$this->assertEquals( 'alert();cleaned&lt;p&gt;foo&lt;/p&gt;&lt;span&gt;bar&lt;/span&gt;', wc_sanitize_tooltip( '<script>alert();</script>cleaned<p>foo</p><span>bar</span>' ) );
	}

	/**
	 * Test wc_array_overlay().
	 *
	 * @since 2.2
	 */
	public function test_wc_array_overlay() {
		$a1 = array(
			'apple'      => 'banana',
			'pear'       => 'grape',
			'vegetables' => array(
				'cucumber' => 'asparagus',
			),
		);

		$a2 = array(
			'strawberry' => 'orange',
			'apple'      => 'kiwi',
			'vegetables' => array(
				'cucumber' => 'peas',
			),
		);

		$overlayed = array(
			'apple'      => 'kiwi',
			'pear'       => 'grape',
			'vegetables' => array(
				'cucumber' => 'peas',
			),
		);

		$this->assertEquals( $overlayed, wc_array_overlay( $a1, $a2 ) );
	}

	/**
	 * Test wc_stock_amount().
	 *
	 * @since 2.2
	 */
	public function test_wc_stock_amount() {
		$this->assertEquals( 10, wc_stock_amount( 10 ) );
		$this->assertEquals( 10, wc_stock_amount( '10' ) );
		$this->assertEquals( 3, wc_stock_amount( 3.43 ) );
	}

	/**
	 * Test wc_get_woocommerce_price_format().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_price_format() {
		// Save default.
		$currency_pos = get_option( 'woocommerce_currency_pos' );

		// Default format (left).
		$this->assertEquals( '%1$s&#x200e;%2$s', get_woocommerce_price_format() );

		// Right.
		update_option( 'woocommerce_currency_pos', 'right' );
		$this->assertEquals( '%2$s%1$s&#x200f;', get_woocommerce_price_format() );

		// Left space.
		update_option( 'woocommerce_currency_pos', 'left_space' );
		$this->assertEquals( '%1$s&#x200e;&nbsp;%2$s', get_woocommerce_price_format() );

		// Right space.
		update_option( 'woocommerce_currency_pos', 'right_space' );
		$this->assertEquals( '%2$s&nbsp;%1$s&#x200f;', get_woocommerce_price_format() );

		// Restore default.
		update_option( 'woocommerce_currency_pos', $currency_pos );
	}

	/**
	 * Test wc_get_price_thousand_separator().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_price_thousand_separator() {
		$separator = get_option( 'woocommerce_price_thousand_sep' );

		// Default value.
		$this->assertEquals( ',', wc_get_price_thousand_separator() );

		update_option( 'woocommerce_price_thousand_sep', '.' );
		$this->assertEquals( '.', wc_get_price_thousand_separator() );

		update_option( 'woocommerce_price_thousand_sep', '&lt;.&gt;' );
		$this->assertEquals( '&lt;.&gt;', wc_get_price_thousand_separator() );

		update_option( 'woocommerce_price_thousand_sep', $separator );
	}

	/**
	 * Test wc_get_price_decimal_separator().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_price_decimal_separator() {
		$separator = get_option( 'woocommerce_price_decimal_sep' );

		// Default value.
		$this->assertEquals( '.', wc_get_price_decimal_separator() );

		update_option( 'woocommerce_price_decimal_sep', ',' );
		$this->assertEquals( ',', wc_get_price_decimal_separator() );

		update_option( 'woocommerce_price_decimal_sep', '&lt;.&gt;' );
		$this->assertEquals( '&lt;.&gt;', wc_get_price_decimal_separator() );

		update_option( 'woocommerce_price_decimal_sep', $separator );
	}

	/**
	 * Test wc_get_price_decimals().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_price_decimals() {
		$decimals = get_option( 'woocommerce_price_num_decimals' );

		// Default value.
		$this->assertEquals( 2, wc_get_price_decimals() );

		update_option( 'woocommerce_price_num_decimals', '1' );
		$this->assertEquals( 1, wc_get_price_decimals() );

		update_option( 'woocommerce_price_num_decimals', '-2' );
		$this->assertEquals( 2, wc_get_price_decimals() );

		update_option( 'woocommerce_price_num_decimals', '2.50' );
		$this->assertEquals( 2, wc_get_price_decimals() );

		update_option( 'woocommerce_price_num_decimals', $decimals );
	}

	/**
	 * Test wc_price().
	 *
	 * @since 2.2
	 */
	public function test_wc_price() {
		// Common prices.
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1.00</span>', wc_price( 1 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1.10</span>', wc_price( 1.1 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1.17</span>', wc_price( 1.17 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1,111.17</span>', wc_price( 1111.17 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;0.00</span>', wc_price( 0 ) );

		// Different currency.
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>&#x200e;1,111.17</span>', wc_price( 1111.17, array( 'currency' => 'USD' ) ) );

		// Negative price.
		$this->assertEquals( '<span class="woocommerce-Price-amount amount">-<span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1.17</span>', wc_price( -1.17 ) );

		// Bogus prices.
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;0.00</span>', wc_price( null ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;0.00</span>', wc_price( 'Q' ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;0.00</span>', wc_price( 'ಠ_ಠ' ) );

		// Trim zeros.
		add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1</span>', wc_price( 1.00 ) );
		remove_filter( 'woocommerce_price_trim_zeros', '__return_true' );

		// Ex tax label.
		$calc_taxes = get_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;1,111.17</span> <small class="woocommerce-Price-taxLabel tax_label">(ex. VAT)</small>', wc_price( '1111.17', array( 'ex_tax_label' => true ) ) );
		update_option( 'woocommerce_calc_taxes', $calc_taxes );
	}

	/**
	 * Test wc_let_to_num().
	 *
	 * @since 2.2
	 */
	public function test_wc_let_to_num() {
		$this->assertEquals( array( 10240, 10485760, 10737418240, 10995116277760, 11258999068426240 ), array(
			wc_let_to_num( '10K' ),
			wc_let_to_num( '10M' ),
			wc_let_to_num( '10G' ),
			wc_let_to_num( '10T' ),
			wc_let_to_num( '10P' ),
		) );
	}

	/**
	 * Test wc_date_format().
	 *
	 * @since 2.2
	 */
	public function test_wc_date_format() {
		$this->assertEquals( get_option( 'date_format' ), wc_date_format() );
	}

	/**
	 * Test wc_time_format().
	 *
	 * @since 2.2
	 */
	public function test_wc_time_format() {
		$this->assertEquals( get_option( 'time_format' ), wc_time_format() );
	}

	/**
	 * Test wc_string_to_timestamp().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_string_to_timestamp() {
		$this->assertEquals( 1507075200, wc_string_to_timestamp( '2017-10-04' ) );
		$this->assertEquals( 1507075200, wc_string_to_timestamp( '2017-10-04', strtotime( '3000-10-04' ) ) );
	}

	/**
	 * Test wc_string_to_datetime().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_string_to_datetime() {
		$data = wc_string_to_datetime( '2014-10-04' );

		$this->assertInstanceOf( 'WC_DateTime', $data );
		$this->assertEquals( 1412380800, $data->getTimestamp() );
	}

	/**
	 * Test wc_timezone_string().
	 *
	 * @since 2.2
	 */
	public function test_wc_timezone_string() {
		// Test when timezone string exists.
		update_option( 'timezone_string', 'America/New_York' );
		$this->assertEquals( 'America/New_York', wc_timezone_string() );

		// Restore default.
		update_option( 'timezone_string', '' );

		// Test with missing UTC offset.
		delete_option( 'gmt_offset' );
		$this->assertEquals( 'UTC', wc_timezone_string() );

		// Test with manually set UTC offset.
		update_option( 'gmt_offset', -4 );
		$this->assertNotEquals( 'UTC', wc_timezone_string() );

		// Test with invalid offset.
		update_option( 'gmt_offset', 99 );
		$this->assertEquals( 'UTC', wc_timezone_string() );

		// Restore default.
		update_option( 'gmt_offset', '0' );
	}

	/**
	 * Test wc_timezone_offset().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_timezone_offset() {
		$this->assertEquals( 0.0, wc_timezone_offset() );
	}

	/**
	 * Test wc_rgb_from_hex().
	 *
	 * @since 2.2
	 */
	public function test_wc_rgb_from_hex() {
		$rgb = array(
			'R' => 0,
			'G' => 93,
			'B' => 171,
		);

		$this->assertEquals( $rgb, wc_rgb_from_hex( '005dab' ) );
		$this->assertEquals( $rgb, wc_rgb_from_hex( '#005dab' ) );
	}

	/**
	 * Test wc_hex_darker().
	 *
	 * @since 2.2
	 */
	public function test_wc_hex_darker() {
		$this->assertEquals( '#004178', wc_hex_darker( '005dab' ) );
		$this->assertEquals( '#004178', wc_hex_darker( '#005dab' ) );
	}

	/**
	 * Test wc_hex_lighter().
	 *
	 * @since 2.2
	 */
	public function test_wc_hex_lighter() {
		$this->assertEquals( '#4d8ec4', wc_hex_lighter( '005dab' ) );
		$this->assertEquals( '#4d8ec4', wc_hex_lighter( '#005dab' ) );
		$this->assertEquals( '#0c3a3b', wc_hex_lighter( '0a3839', 1 ) );
	}

	/**
	 * Test wc_light_or_dark().
	 *
	 * @since 2.2
	 */
	public function test_wc_light_or_dark() {
		$this->assertEquals( '#FFFFFF', wc_light_or_dark( '005dab' ) );
		$this->assertEquals( '#FFFFFF', wc_light_or_dark( '#005dab' ) );
	}

	/**
	 * Test wc_format_hex().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_hex() {
		$this->assertEquals( '#CCCCCC', wc_format_hex( 'CCC' ) );
		$this->assertEquals( '#CCCCCC', wc_format_hex( '#CCC' ) );
		$this->assertEquals( null, wc_format_hex( null ) );
	}

	/**
	 * Test wc_format_postcode().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_postcode() {
		// Generic postcode.
		$this->assertEquals( '02111', wc_format_postcode( ' 02111	', 'US' ) );

		// US 9-digit postcode.
		$this->assertEquals( '02111-9999', wc_format_postcode( ' 021119999	', 'US' ) );

		// UK postcode.
		$this->assertEquals( 'PCRN 1ZZ', wc_format_postcode( 'pcrn1zz', 'GB' ) );

		// BR/PL postcode.
		$this->assertEquals( '99999-999', wc_format_postcode( '99999999', 'BR' ) );

		// JP postcode.
		$this->assertEquals( '999-9999', wc_format_postcode( '9999999', 'JP' ) );
	}

	/**
	 * Test wc_normalize_postcode().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_normalize_postcode() {
		$this->assertEquals( '99999999', wc_normalize_postcode( '99999-999' ) );
	}

	/**
	 * Test wc_format_phone_number().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_phone_number() {
		$this->assertEquals( '1-610-385-0000', wc_format_phone_number( '1.610.385.0000' ) );
	}

	/**
	 * Test wc_trim_string().
	 *
	 * @since 2.2
	 */
	public function test_wc_trim_string() {
		$this->assertEquals( 'string', wc_trim_string( 'string' ) );
		$this->assertEquals( 's...',   wc_trim_string( 'string', 4 ) );
		$this->assertEquals( 'st.',    wc_trim_string( 'string', 3, '.' ) );
		$this->assertEquals( 'string¥', wc_trim_string( 'string¥', 7, '' ) );
	}

	/**
	 * Test wc_format_content().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_content() {
		$this->assertEquals( "<p>foo</p>\n", wc_format_content( 'foo' ) );
	}

	/**
	 * Test wc_sanitize_term_text_based().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_sanitize_term_text_based() {
		$this->assertEquals( 'foo', wc_sanitize_term_text_based( "<p>foo</p>\n" ) );
	}

	/**
	 * Test wc_make_numeric_postcode().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_make_numeric_postcode() {
		$this->assertEquals( '16050300', wc_make_numeric_postcode( 'PE30' ) );
	}

	/**
	 * Test wc_format_stock_for_display().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_stock_for_display() {
		$product = WC_Helper_Product::create_simple_product();

		$product->set_stock_quantity( '10' );
		$this->assertEquals( '10 in stock', wc_format_stock_for_display( $product ) );

		$product->set_stock_quantity( '1' );
		$default = get_option( 'woocommerce_stock_format' );
		update_option( 'woocommerce_stock_format', 'low_amount' );
		$this->assertEquals( 'Only 1 left in stock', wc_format_stock_for_display( $product ) );
		update_option( 'woocommerce_stock_format', $default );

		$product->set_stock_quantity( '-1' );
		$product->set_manage_stock( true );
		$product->set_backorders( 'notify' );
		$this->assertEquals( '-1 in stock (can be backordered)', wc_format_stock_for_display( $product ) );

		$product->delete( true );
	}

	/**
	 * Test wc_format_stock_quantity_for_display().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_stock_quantity_for_display() {
		$product = WC_Helper_Product::create_simple_product();

		$product->set_stock_quantity( '10' );
		$this->assertEquals( '10', wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product ) );

		$product->delete( true );
	}

	/**
	 * Test wc_format_sale_price().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_sale_price() {
		$this->assertEquals( '<del><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;10.00</span></del> <ins><span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;5.00</span></ins>', wc_format_sale_price( '10', '5' ) );
	}

	/**
	 * Test wc_format_price_range().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_price_range() {
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;10.00</span> &ndash; <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>&#x200e;5.00</span>', wc_format_price_range( '10', '5' ) );
	}

	/**
	 * Test wc_format_weight().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_weight() {
		$this->assertEquals( '10 kg', wc_format_weight( '10' ) );
	}

	/**
	 * Test wc_format_dimensions().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_dimensions() {
		$this->assertEquals( '10 x 10 x 10 cm', wc_format_dimensions( array( 10, 10, 10 ) ) );
	}

	/**
	 * Test wc_format_datetime().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_format_datetime() {
		$date = new WC_DateTime( '2017-10-05', new DateTimeZone( 'UTC' ) );
		$this->assertEquals( 'October 5, 2017', wc_format_datetime( $date ) );
		$this->assertEquals( '', wc_format_datetime( 'foo' ) );
	}

	/**
	 * Test wc_do_oembeds().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_do_oembeds() {
		// In this case should only return the URL back, since oEmbed will run other actions on frontend.
		$this->assertEquals( 'https://wordpress.tv/2015/10/19/mike-jolley-user-onboarding-for-wordpress-plugins/', wc_do_oembeds( 'https://wordpress.tv/2015/10/19/mike-jolley-user-onboarding-for-wordpress-plugins/' ) );
	}

	/**
	 * Test wc_get_string_before_colon().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_string_before_colon() {
		$this->assertEquals( 'foo', wc_get_string_before_colon( 'foo:1' ) );
	}


	/**
	 * Test wc_array_merge_recursive_numeric().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_array_merge_recursive_numeric() {
		$a = array(
			'A'   => 'bob',
			'sum' => 10,
			'C'   => array(
				'x',
				'y',
				'z' => 50,
			),
		);
		$b = array(
			'A'   => 'max',
			'sum' => 12,
			'C'   => array(
				'x',
				'y',
				'z' => 45,
			),
		);
		$c = array(
			'A'   => 'tom',
			'sum' => 8,
			'C'   => array(
				'x',
				'y',
				'z' => 50,
				'w' => 1,
			),
		);

		$this->assertEquals( array(
			'A'   => 'tom',
			'sum' => 30,
			'C'   => array(
				'x',
				'y',
				'z' => 145,
				'w' => 1,
			),
		), wc_array_merge_recursive_numeric( $a, $b, $c ) );
	}
}
