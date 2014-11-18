<?php

/**
 * Test WooCommerce core functions
 *
 * @since 2.2
 */
class WC_Tests_Core_Functions extends WC_Unit_Test_Case {

	/**
	 * Test get_woocommerce_currency()
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currency() {

		$this->assertEquals( 'GBP', get_woocommerce_currency() );
	}

	/**
	 * Test get_woocommerce_currencies()
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currencies() {

		$expected_currencies = array(
			'AED' => __( 'United Arab Emirates Dirham', 'woocommerce' ),
			'AUD' => __( 'Australian Dollars', 'woocommerce' ),
			'BDT' => __( 'Bangladeshi Taka', 'woocommerce' ),
			'BRL' => __( 'Brazilian Real', 'woocommerce' ),
			'BGN' => __( 'Bulgarian Lev', 'woocommerce' ),
			'CAD' => __( 'Canadian Dollars', 'woocommerce' ),
			'CLP' => __( 'Chilean Peso', 'woocommerce' ),
			'CNY' => __( 'Chinese Yuan', 'woocommerce' ),
			'COP' => __( 'Colombian Peso', 'woocommerce' ),
			'CZK' => __( 'Czech Koruna', 'woocommerce' ),
			'DKK' => __( 'Danish Krone', 'woocommerce' ),
			'DOP' => __( 'Dominican Peso', 'woocommerce' ),
			'EUR' => __( 'Euros', 'woocommerce' ),
			'HKD' => __( 'Hong Kong Dollar', 'woocommerce' ),
			'HRK' => __( 'Croatia kuna', 'woocommerce' ),
			'HUF' => __( 'Hungarian Forint', 'woocommerce' ),
			'ISK' => __( 'Icelandic krona', 'woocommerce' ),
			'IDR' => __( 'Indonesia Rupiah', 'woocommerce' ),
			'INR' => __( 'Indian Rupee', 'woocommerce' ),
			'NPR' => __( 'Nepali Rupee', 'woocommerce' ),
			'ILS' => __( 'Israeli Shekel', 'woocommerce' ),
			'JPY' => __( 'Japanese Yen', 'woocommerce' ),
			'KIP' => __( 'Lao Kip', 'woocommerce' ),
			'KRW' => __( 'South Korean Won', 'woocommerce' ),
			'MYR' => __( 'Malaysian Ringgits', 'woocommerce' ),
			'MXN' => __( 'Mexican Peso', 'woocommerce' ),
			'NGN' => __( 'Nigerian Naira', 'woocommerce' ),
			'NOK' => __( 'Norwegian Krone', 'woocommerce' ),
			'NZD' => __( 'New Zealand Dollar', 'woocommerce' ),
			'PYG' => __( 'Paraguayan Guaraní', 'woocommerce' ),
			'PHP' => __( 'Philippine Pesos', 'woocommerce' ),
			'PLN' => __( 'Polish Zloty', 'woocommerce' ),
			'GBP' => __( 'Pounds Sterling', 'woocommerce' ),
			'RON' => __( 'Romanian Leu', 'woocommerce' ),
			'RUB' => __( 'Russian Ruble', 'woocommerce' ),
			'SGD' => __( 'Singapore Dollar', 'woocommerce' ),
			'ZAR' => __( 'South African rand', 'woocommerce' ),
			'SEK' => __( 'Swedish Krona', 'woocommerce' ),
			'CHF' => __( 'Swiss Franc', 'woocommerce' ),
			'TWD' => __( 'Taiwan New Dollars', 'woocommerce' ),
			'THB' => __( 'Thai Baht', 'woocommerce' ),
			'TRY' => __( 'Turkish Lira', 'woocommerce' ),
			'UAH' => __( 'Ukrainian Hryvnia', 'woocommerce' ),
			'USD' => __( 'US Dollars', 'woocommerce' ),
			'VND' => __( 'Vietnamese Dong', 'woocommerce' ),
			'EGP' => __( 'Egyptian Pound', 'woocommerce' )
		);

		$this->assertEquals( $expected_currencies, get_woocommerce_currencies() );
	}

	/**
	 * Test get_woocommerce_currency_symbol()
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
	 * Test get_woocommerce_api_url()
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_api_url() {

		$base_uri = get_home_url();

		// base uri
		$this->assertEquals( "$base_uri/wc-api/v2/", get_woocommerce_api_url( null ) );

		// path
		$this->assertEquals( "$base_uri/wc-api/v2/orders", get_woocommerce_api_url( 'orders' ) );
	}

	/**
	 * Test wc_get_core_supported_themes()
	 *
	 * @since 2.2
	 */
	public function test_wc_get_core_supported_themes() {

		$expected_themes = array( 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' );

		$this->assertEquals( $expected_themes, wc_get_core_supported_themes() );
	}

	/**
	 * Test wc_get_default_location()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_default_location() {
		$default = wc_get_default_location();

		$this->assertEquals( 'GB', $default['country'] );
		$this->assertEquals( '', $default['state'] );
	}

}

