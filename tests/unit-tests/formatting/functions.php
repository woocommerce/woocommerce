<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Formatting
 * @since 2.2
 *
 * @todo Split formatting class into smaller classes
 */
class WC_Tests_Formatting_Functions extends WC_Unit_Test_Case {

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
	 * Data provider for test_wc_get_dimension().
	 *
	 * @since 2.2.0
	 */
	public function data_provider_wc_get_dimension() {

		// save default
		$default_unit = get_option( 'woocommerce_dimension_unit' );

		$cm = array(
			array( 10, wc_get_dimension( 10, 'cm' ) ),
			array( 3.937, wc_get_dimension( 10, 'in' ) ),
			array( 0.10936133, wc_get_dimension( 10, 'yd' ) ),
			array( 100, wc_get_dimension( 10, 'mm' ) ),
			array( 0.1, wc_get_dimension( 10, 'm' ) ),
		);

		update_option( 'woocommerce_dimension_unit', 'in' );
		$in = array(
			array( 25.4, wc_get_dimension( 10, 'cm' ) ),
			array( 10, wc_get_dimension( 10, 'in' ) ),
			array( 0.2777777782, wc_get_dimension( 10, 'yd' ) ),
			array( 254, wc_get_dimension( 10, 'mm' ) ),
			array( 0.254, wc_get_dimension( 10, 'm' ) ),
		);

		update_option( 'woocommerce_dimension_unit', 'm' );
		$m = array(
			array( 1000, wc_get_dimension( 10, 'cm' ) ),
			array( 393.7, wc_get_dimension( 10, 'in' ) ),
			array( 10.936133, wc_get_dimension( 10, 'yd' ) ),
			array( 10000, wc_get_dimension( 10, 'mm' ) ),
			array( 10, wc_get_dimension( 10, 'm' ) ),
		);

		update_option( 'woocommerce_dimension_unit', 'mm' );
		$mm = array(
			array( 1, wc_get_dimension( 10, 'cm' ) ),
			array( 0.3937, wc_get_dimension( 10, 'in' ) ),
			array( 0.010936133, wc_get_dimension( 10, 'yd' ) ),
			array( 10, wc_get_dimension( 10, 'mm' ) ),
			array( 0.01, wc_get_dimension( 10, 'm' ) ),
		);

		update_option( 'woocommerce_dimension_unit', 'yd' );
		$yd = array(
			array( 914.4, wc_get_dimension( 10, 'cm' ) ),
			array( 359.99928, wc_get_dimension( 10, 'in' ) ),
			array( 10, wc_get_dimension( 10, 'yd' ) ),
			array( 9144, wc_get_dimension( 10, 'mm' ) ),
			array( 9.144, wc_get_dimension( 10, 'm' ) ),
		);

		$n = array(
			array( 0, wc_get_dimension( -10, 'mm' ) ),
		);

		$custom = array(
			array( 25.4, wc_get_dimension( 10, 'cm', 'in' ) ),
			array( 914.4, wc_get_dimension( 10, 'cm', 'yd' ) ),
			array( 393.7, wc_get_dimension( 10, 'in', 'm' ) ),
			array( 0.010936133, wc_get_dimension( 10, 'yd', 'mm' ) )
		);

		// restore default
		update_option( 'woocommerce_dimension_unit', $default_unit );

		return array_merge( $cm, $in, $m, $mm, $yd, $n, $custom );

	}

	/**
	 * Test wc_get_dimension().
	 *
	 * @dataProvider data_provider_wc_get_dimension
	 *
	 * @since 2.2
	 */
	public function test_wc_get_dimension( $assert, $value ) {

		$this->assertEquals( $assert, $value );

	}

	/**
	 * Test wc_get_weight().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_weight() {

		// save default
		$default_unit = get_option( 'woocommerce_weight_unit' );

		// kg (default unit)
		$this->assertEquals( 10, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 10000, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 22.0462, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 352.74, wc_get_weight( 10, 'oz' ) );

		// g
		update_option( 'woocommerce_weight_unit', 'g' );
		$this->assertEquals( 0.01, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 0.0220462, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 0.35274, wc_get_weight( 10, 'oz' ) );

		// lbs
		update_option( 'woocommerce_weight_unit', 'lbs' );
		$this->assertEquals( 4.53592, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 4535.92, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 160.00004208, wc_get_weight( 10, 'oz' ) );

		// oz
		update_option( 'woocommerce_weight_unit', 'oz' );
		$this->assertEquals( 0.283495, wc_get_weight( 10, 'kg' ) );
		$this->assertEquals( 283.495, wc_get_weight( 10, 'g' ) );
		$this->assertEquals( 0.6249987469, wc_get_weight( 10, 'lbs' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'oz' ) );

		// custom from unit
		$this->assertEquals( 0.283495, wc_get_weight( 10, 'kg', 'oz' ) );
		$this->assertEquals( 0.01, wc_get_weight( 10, 'kg', 'g' ) );
		$this->assertEquals( 4.53592, wc_get_weight( 10, 'kg', 'lbs' ) );
		$this->assertEquals( 10, wc_get_weight( 10, 'kg', 'kg' ) );

		// negative
		$this->assertEquals( 0, wc_get_weight( -10, 'g' ) );

		// restore default
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

		// given string
		$this->assertEquals( '9.99', wc_format_decimal( '9.99' ) );

		// float
		$this->assertEquals( '9.99', wc_format_decimal( 9.99 ) );

		// dp = false, no rounding
		$this->assertEquals( '9.9999', wc_format_decimal( 9.9999 ) );

		// dp = use default (2)
		$this->assertEquals( '9.99', wc_format_decimal( 9.9911, '' ) );

		// dp = use default (2) and round
		$this->assertEquals( '10.00', wc_format_decimal( 9.9999, '' ) );

		// dp = use custom
		$this->assertEquals( '9.991', wc_format_decimal( 9.9912, 3 ) );

		// trim zeros
		$this->assertEquals( '9', wc_format_decimal( 9.00, false, true ) );

		// trim zeros and round
		$this->assertEquals( '10', wc_format_decimal( 9.9999, '', true ) );
	}

	/**
	 * Test wc_float_to_string().
	 *
	 * @since 2.2
	 */
	public function test_wc_float_to_string() {

		// given string, return string
		$this->assertEquals( '1.99', wc_float_to_string( '1.99' ) );

		$this->assertEquals( '1.17', wc_float_to_string( 1.17 ) );
	}

	/**
	 * Test wc_format_localized_price().
	 *
	 * @since 2.2
	 */
	public function test_wc_format_localized_price() {

		// save default
		$decimal_sep = get_option( 'woocommerce_price_decimal_sep' );
		update_option( 'woocommerce_price_decimal_sep', ',' );

		$this->assertEquals( '1,17', wc_format_localized_price( '1.17' ) );

		// restore default
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
	 * Test wc_clean() - note this is a basic type test as WP core already.
	 * has coverage for sanitized_text_field().
	 *
	 * @since 2.2
	 */
	public function test_wc_clean() {

		$this->assertEquals( 'cleaned', wc_clean( '<script>alert();</script>cleaned' ) );
	}

	/**
	 * Test wc_sanitize_tooltip() - note this is a basic type test as WP core already.
	 * has coverage for wp_kses().
	 *
	 * @since 2.4
	 */
	public function test_wc_sanitize_tooltip() {

		$this->assertEquals( 'alert();cleaned', wc_sanitize_tooltip( '<script>alert();</script>cleaned' ) );
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
			)
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

		// save default
		$currency_pos = get_option( 'woocommerce_currency_pos' );

		// default format (left)
		$this->assertEquals( '%1$s%2$s', get_woocommerce_price_format() );

		// right
		update_option( 'woocommerce_currency_pos', 'right' );
		$this->assertEquals( '%2$s%1$s', get_woocommerce_price_format() );

		// left space
		update_option( 'woocommerce_currency_pos', 'left_space' );
		$this->assertEquals( '%1$s&nbsp;%2$s', get_woocommerce_price_format() );

		// right space
		update_option( 'woocommerce_currency_pos', 'right_space' );
		$this->assertEquals( '%2$s&nbsp;%1$s', get_woocommerce_price_format() );

		// restore default
		update_option( 'woocommerce_currency_pos', $currency_pos );
	}

	/**
	 * Test wc_get_price_thousand_separator().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_price_thousand_separator() {
		$separator = get_option( 'woocommerce_price_thousand_sep' );

		// defualt value
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

		// defualt value
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

		// defualt value
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

		// common prices
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>1.00</span>', wc_price( 1 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>1.10</span>', wc_price( 1.1 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>1.17</span>', wc_price( 1.17 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>1,111.17</span>', wc_price( 1111.17 ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>0.00</span>', wc_price( 0 ) );

		// different currency
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&#36;</span>1,111.17</span>', wc_price( 1111.17, array( 'currency' => 'USD' ) ) );

		// negative price
		$this->assertEquals( '<span class="woocommerce-Price-amount amount">-<span class="woocommerce-Price-currencySymbol">&pound;</span>1.17</span>', wc_price( -1.17 ) );

		// bogus prices
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>0.00</span>', wc_price( null ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>0.00</span>', wc_price( 'Q' ) );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>0.00</span>', wc_price( 'ಠ_ಠ' ) );

		// trim zeros
		add_filter( 'woocommerce_price_trim_zeros', '__return_true' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>1</span>', wc_price( 1.00 ) );
		remove_filter( 'woocommerce_price_trim_zeros', '__return_true' );

		// ex tax label
		$calc_taxes = get_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->assertEquals( '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">&pound;</span>1,111.17</span> <small class="woocommerce-Price-taxLabel tax_label">(ex. VAT)</small>', wc_price( '1111.17', array( 'ex_tax_label' => true ) ) );
		update_option( 'woocommerce_calc_taxes', $calc_taxes );
	}

	/**
	 * Test wc_let_to_num().
	 *
	 * @since 2.2
	 */
	public function test_wc_let_to_num() {

		$sizes = array(
			'10K' => 10240,
			'10M' => 10485760,
			'10G' => 10737418240,
			'10T' => 10995116277760,
			'10P' => 11258999068426240,
		);

		foreach ( $sizes as $notation => $size ) {
			$this->assertEquals( $size, wc_let_to_num( $notation ) );
		}
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
	 * Test wc_timezone_string().
	 *
	 * @since 2.2
	 */
	public function test_wc_timezone_string() {

		// test when timezone string exists
		update_option( 'timezone_string', 'America/New_York' );
		$this->assertEquals( 'America/New_York', wc_timezone_string() );

		// restore default
		update_option( 'timezone_string', '' );

		// test with missing UTC offset
		delete_option( 'gmt_offset' );
		$this->assertEquals( 'UTC', wc_timezone_string() );

		// test with manually set UTC offset
		update_option( 'gmt_offset', -4 );
		$this->assertEquals( 'America/Halifax', wc_timezone_string() );

		// test with invalid offset
		update_option( 'gmt_offset', 99 );
		$this->assertEquals( 'UTC', wc_timezone_string() );

		// restore default
		update_option( 'gmt_offset', '0' );
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

		// generic postcode
		$this->assertEquals( '02111', wc_format_postcode( ' 02111	', 'US' ) );

		// UK postcode
		$this->assertEquals( 'PCRN 1ZZ', wc_format_postcode( 'pcrn1zz', 'GB' ) );
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

}
