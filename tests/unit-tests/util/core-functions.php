<?php

namespace WooCommerce\Tests\Util;

/**
 * Class Core_Functions.
 * @package WooCommerce\Tests\Util
 * @since 2.2
 */
class Core_Functions extends \WC_Unit_Test_Case {

	/**
	 * Test get_woocommerce_currency().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currency() {

		$this->assertEquals( 'GBP', get_woocommerce_currency() );
	}

	/**
	 * Test get_woocommerce_currencies().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currencies() {

		$expected_currencies = array(
			'AED' => __( 'United Arab Emirates Dirham', 'woocommerce' ),
			'ARS' => __( 'Argentine Peso', 'woocommerce' ),
			'AUD' => __( 'Australian Dollars', 'woocommerce' ),
			'BDT' => __( 'Bangladeshi Taka', 'woocommerce' ),
			'BGN' => __( 'Bulgarian Lev', 'woocommerce' ),
			'BRL' => __( 'Brazilian Real', 'woocommerce' ),
			'CAD' => __( 'Canadian Dollars', 'woocommerce' ),
			'CHF' => __( 'Swiss Franc', 'woocommerce' ),
			'CLP' => __( 'Chilean Peso', 'woocommerce' ),
			'CNY' => __( 'Chinese Yuan', 'woocommerce' ),
			'COP' => __( 'Colombian Peso', 'woocommerce' ),
			'CZK' => __( 'Czech Koruna', 'woocommerce' ),
			'DKK' => __( 'Danish Krone', 'woocommerce' ),
			'DOP' => __( 'Dominican Peso', 'woocommerce' ),
			'EGP' => __( 'Egyptian Pound', 'woocommerce' ),
			'EUR' => __( 'Euros', 'woocommerce' ),
			'GBP' => __( 'Pounds Sterling', 'woocommerce' ),
			'HKD' => __( 'Hong Kong Dollar', 'woocommerce' ),
			'HRK' => __( 'Croatia kuna', 'woocommerce' ),
			'HUF' => __( 'Hungarian Forint', 'woocommerce' ),
			'IDR' => __( 'Indonesia Rupiah', 'woocommerce' ),
			'ILS' => __( 'Israeli Shekel', 'woocommerce' ),
			'INR' => __( 'Indian Rupee', 'woocommerce' ),
			'ISK' => __( 'Icelandic krona', 'woocommerce' ),
			'JPY' => __( 'Japanese Yen', 'woocommerce' ),
			'KIP' => __( 'Lao Kip', 'woocommerce' ),
			'KRW' => __( 'South Korean Won', 'woocommerce' ),
			'MXN' => __( 'Mexican Peso', 'woocommerce' ),
			'MYR' => __( 'Malaysian Ringgits', 'woocommerce' ),
			'NGN' => __( 'Nigerian Naira', 'woocommerce' ),
			'NOK' => __( 'Norwegian Krone', 'woocommerce' ),
			'NPR' => __( 'Nepali Rupee', 'woocommerce' ),
			'NZD' => __( 'New Zealand Dollar', 'woocommerce' ),
			'PHP' => __( 'Philippine Pesos', 'woocommerce' ),
			'PKR' => __( 'Pakistani Rupee', 'woocommerce' ),
			'PLN' => __( 'Polish Zloty', 'woocommerce' ),
			'PYG' => __( 'Paraguayan Guaraní', 'woocommerce' ),
			'RON' => __( 'Romanian Leu', 'woocommerce' ),
			'RUB' => __( 'Russian Ruble', 'woocommerce' ),
			'SEK' => __( 'Swedish Krona', 'woocommerce' ),
			'SGD' => __( 'Singapore Dollar', 'woocommerce' ),
			'THB' => __( 'Thai Baht', 'woocommerce' ),
			'TRY' => __( 'Turkish Lira', 'woocommerce' ),
			'TWD' => __( 'Taiwan New Dollars', 'woocommerce' ),
			'UAH' => __( 'Ukrainian Hryvnia', 'woocommerce' ),
			'USD' => __( 'US Dollars', 'woocommerce' ),
			'VND' => __( 'Vietnamese Dong', 'woocommerce' ),
			'ZAR' => __( 'South African rand', 'woocommerce' ),
		);


		$this->assertEquals( $expected_currencies, get_woocommerce_currencies() );
	}

	/**
	 * Test get_woocommerce_currency_symbol().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currency_symbol() {

		// default currency
		$this->assertEquals( '&pound;', get_woocommerce_currency_symbol() );

		// given specific currency
		$this->assertEquals( '&#36;', get_woocommerce_currency_symbol( 'USD' ) );

		// each case
		foreach ( array_keys( get_woocommerce_currencies() ) as $currency_code ) {
			$this->assertInternalType( 'string', get_woocommerce_currency_symbol( $currency_code ) );
		}
	}

	/**
	 * Test get_woocommerce_api_url().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_api_url() {

		$base_uri = get_home_url();

		// base uri
		$this->assertEquals( "$base_uri/wc-api/v3/", get_woocommerce_api_url( null ) );

		// path
		$this->assertEquals( "$base_uri/wc-api/v3/orders", get_woocommerce_api_url( 'orders' ) );
	}

	/**
	 * Test wc_get_log_file_path().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_log_file_path() {
		$log_dir   = trailingslashit( WC_LOG_DIR );
		$hash_name = sanitize_file_name( wp_hash( 'unit-tests' ) );

		$this->assertEquals( $log_dir . 'unit-tests-' . $hash_name . '.log', wc_get_log_file_path( 'unit-tests' ) );
	}

	/**
	 * Test wc_get_core_supported_themes().
	 *
	 * @since 2.2
	 */
	public function test_wc_get_core_supported_themes() {

		$expected_themes = array( 'twentysixteen', 'twentyfifteen', 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' );

		$this->assertEquals( $expected_themes, wc_get_core_supported_themes() );
	}

	/**
	 * Test wc_get_base_location().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_base_location() {
		$default = wc_get_base_location();

		$this->assertEquals( 'GB', $default['country'] );
		$this->assertEquals( '', $default['state'] );
	}

}

