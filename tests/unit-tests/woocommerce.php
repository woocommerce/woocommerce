<?php

class WC_Tests_Core_Functions extends WP_UnitTestCase {


	public function test_get_woocommerce_currency() {

		$this->assertEquals( 'GBP', get_woocommerce_currency() );
	}

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
			'KIP'	=> __( 'Lao Kip', 'woocommerce' ),
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
			'USD' => __( 'US Dollars', 'woocommerce' ),
			'VND' => __( 'Vietnamese Dong', 'woocommerce' ),
			'EGP' => __( 'Egyptian Pound', 'woocommerce' ),
		);

		$this->assertEquals( $expected_currencies, get_woocommerce_currencies() );
	}

	public function test_get_woocommerce_currency_symbol() {

		$this->assertEquals( '&pound;', get_woocommerce_currency_symbol() );
		$this->assertEquals( '&#36;', get_woocommerce_currency_symbol( 'USD' ) );
	}

	public function test_get_v2_woocommerce_api_url() {

		$this->assertEquals( 'http://example.org/wc-api/v2/', get_woocommerce_api_url( '' ) );
	}

	public function test_get_v1_woocommerce_api_url() {

		define( 'WC_API_REQUEST_VERSION', 1 );

		$this->assertEquals( 'http://example.org/wc-api/v1/', get_woocommerce_api_url( '' ) );
	}

	public function test_wc_get_core_supported_themes() {

		$expected_themes = array( 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' );

		$this->assertEquals( $expected_themes, wc_get_core_supported_themes() );
	}
}

