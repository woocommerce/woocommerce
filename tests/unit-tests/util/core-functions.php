<?php

/**
 * Class Core_Functions.
 * @package WooCommerce\Tests\Util
 * @since 2.2
 */
class WC_Tests_Core_Functions extends WC_Unit_Test_Case {

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
			'AED' => __( 'United Arab Emirates dirham', 'woocommerce' ),
			'AFN' => __( 'Afghan afghani', 'woocommerce' ),
			'ALL' => __( 'Albanian lek', 'woocommerce' ),
			'AMD' => __( 'Armenian dram', 'woocommerce' ),
			'ANG' => __( 'Netherlands Antillean guilder', 'woocommerce' ),
			'AOA' => __( 'Angolan kwanza', 'woocommerce' ),
			'ARS' => __( 'Argentine peso', 'woocommerce' ),
			'AUD' => __( 'Australian dollar', 'woocommerce' ),
			'AWG' => __( 'Aruban florin', 'woocommerce' ),
			'AZN' => __( 'Azerbaijani manat', 'woocommerce' ),
			'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'woocommerce' ),
			'BBD' => __( 'Barbadian dollar', 'woocommerce' ),
			'BDT' => __( 'Bangladeshi taka', 'woocommerce' ),
			'BGN' => __( 'Bulgarian lev', 'woocommerce' ),
			'BHD' => __( 'Bahraini dinar', 'woocommerce' ),
			'BIF' => __( 'Burundian franc', 'woocommerce' ),
			'BMD' => __( 'Bermudian dollar', 'woocommerce' ),
			'BND' => __( 'Brunei dollar', 'woocommerce' ),
			'BOB' => __( 'Bolivian boliviano', 'woocommerce' ),
			'BRL' => __( 'Brazilian real', 'woocommerce' ),
			'BSD' => __( 'Bahamian dollar', 'woocommerce' ),
			'BTC' => __( 'Bitcoin', 'woocommerce' ),
			'BTN' => __( 'Bhutanese ngultrum', 'woocommerce' ),
			'BWP' => __( 'Botswana pula', 'woocommerce' ),
			'BYR' => __( 'Belarusian ruble', 'woocommerce' ),
			'BZD' => __( 'Belize dollar', 'woocommerce' ),
			'CAD' => __( 'Canadian dollar', 'woocommerce' ),
			'CDF' => __( 'Congolese franc', 'woocommerce' ),
			'CHF' => __( 'Swiss franc', 'woocommerce' ),
			'CLP' => __( 'Chilean peso', 'woocommerce' ),
			'CNY' => __( 'Chinese yuan', 'woocommerce' ),
			'COP' => __( 'Colombian peso', 'woocommerce' ),
			'CRC' => __( 'Costa Rican col&oacute;n', 'woocommerce' ),
			'CUC' => __( 'Cuban convertible peso', 'woocommerce' ),
			'CUP' => __( 'Cuban peso', 'woocommerce' ),
			'CVE' => __( 'Cape Verdean escudo', 'woocommerce' ),
			'CZK' => __( 'Czech koruna', 'woocommerce' ),
			'DJF' => __( 'Djiboutian franc', 'woocommerce' ),
			'DKK' => __( 'Danish krone', 'woocommerce' ),
			'DOP' => __( 'Dominican peso', 'woocommerce' ),
			'DZD' => __( 'Algerian dinar', 'woocommerce' ),
			'EGP' => __( 'Egyptian pound', 'woocommerce' ),
			'ERN' => __( 'Eritrean nakfa', 'woocommerce' ),
			'ETB' => __( 'Ethiopian birr', 'woocommerce' ),
			'EUR' => __( 'Euro', 'woocommerce' ),
			'FJD' => __( 'Fijian dollar', 'woocommerce' ),
			'FKP' => __( 'Falkland Islands pound', 'woocommerce' ),
			'GBP' => __( 'Pound sterling', 'woocommerce' ),
			'GEL' => __( 'Georgian lari', 'woocommerce' ),
			'GGP' => __( 'Guernsey pound', 'woocommerce' ),
			'GHS' => __( 'Ghana cedi', 'woocommerce' ),
			'GIP' => __( 'Gibraltar pound', 'woocommerce' ),
			'GMD' => __( 'Gambian dalasi', 'woocommerce' ),
			'GNF' => __( 'Guinean franc', 'woocommerce' ),
			'GTQ' => __( 'Guatemalan quetzal', 'woocommerce' ),
			'GYD' => __( 'Guyanese dollar', 'woocommerce' ),
			'HKD' => __( 'Hong Kong dollar', 'woocommerce' ),
			'HNL' => __( 'Honduran lempira', 'woocommerce' ),
			'HRK' => __( 'Croatian kuna', 'woocommerce' ),
			'HTG' => __( 'Haitian gourde', 'woocommerce' ),
			'HUF' => __( 'Hungarian forint', 'woocommerce' ),
			'IDR' => __( 'Indonesian rupiah', 'woocommerce' ),
			'ILS' => __( 'Israeli new shekel', 'woocommerce' ),
			'IMP' => __( 'Manx pound', 'woocommerce' ),
			'INR' => __( 'Indian rupee', 'woocommerce' ),
			'IQD' => __( 'Iraqi dinar', 'woocommerce' ),
			'IRR' => __( 'Iranian rial', 'woocommerce' ),
			'ISK' => __( 'Icelandic kr&oacute;na', 'woocommerce' ),
			'JEP' => __( 'Jersey pound', 'woocommerce' ),
			'JMD' => __( 'Jamaican dollar', 'woocommerce' ),
			'JOD' => __( 'Jordanian dinar', 'woocommerce' ),
			'JPY' => __( 'Japanese yen', 'woocommerce' ),
			'KES' => __( 'Kenyan shilling', 'woocommerce' ),
			'KGS' => __( 'Kyrgyzstani som', 'woocommerce' ),
			'KHR' => __( 'Cambodian riel', 'woocommerce' ),
			'KMF' => __( 'Comorian franc', 'woocommerce' ),
			'KPW' => __( 'North Korean won', 'woocommerce' ),
			'KRW' => __( 'South Korean won', 'woocommerce' ),
			'KWD' => __( 'Kuwaiti dinar', 'woocommerce' ),
			'KYD' => __( 'Cayman Islands dollar', 'woocommerce' ),
			'KZT' => __( 'Kazakhstani tenge', 'woocommerce' ),
			'LAK' => __( 'Lao kip', 'woocommerce' ),
			'LBP' => __( 'Lebanese pound', 'woocommerce' ),
			'LKR' => __( 'Sri Lankan rupee', 'woocommerce' ),
			'LRD' => __( 'Liberian dollar', 'woocommerce' ),
			'LSL' => __( 'Lesotho loti', 'woocommerce' ),
			'LYD' => __( 'Libyan dinar', 'woocommerce' ),
			'MAD' => __( 'Moroccan dirham', 'woocommerce' ),
			'MDL' => __( 'Moldovan leu', 'woocommerce' ),
			'MGA' => __( 'Malagasy ariary', 'woocommerce' ),
			'MKD' => __( 'Macedonian denar', 'woocommerce' ),
			'MMK' => __( 'Burmese kyat', 'woocommerce' ),
			'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'woocommerce' ),
			'MOP' => __( 'Macanese pataca', 'woocommerce' ),
			'MRO' => __( 'Mauritanian ouguiya', 'woocommerce' ),
			'MUR' => __( 'Mauritian rupee', 'woocommerce' ),
			'MVR' => __( 'Maldivian rufiyaa', 'woocommerce' ),
			'MWK' => __( 'Malawian kwacha', 'woocommerce' ),
			'MXN' => __( 'Mexican peso', 'woocommerce' ),
			'MYR' => __( 'Malaysian ringgit', 'woocommerce' ),
			'MZN' => __( 'Mozambican metical', 'woocommerce' ),
			'NAD' => __( 'Namibian dollar', 'woocommerce' ),
			'NGN' => __( 'Nigerian naira', 'woocommerce' ),
			'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'woocommerce' ),
			'NOK' => __( 'Norwegian krone', 'woocommerce' ),
			'NPR' => __( 'Nepalese rupee', 'woocommerce' ),
			'NZD' => __( 'New Zealand dollar', 'woocommerce' ),
			'OMR' => __( 'Omani rial', 'woocommerce' ),
			'PAB' => __( 'Panamanian balboa', 'woocommerce' ),
			'PEN' => __( 'Peruvian nuevo sol', 'woocommerce' ),
			'PGK' => __( 'Papua New Guinean kina', 'woocommerce' ),
			'PHP' => __( 'Philippine peso', 'woocommerce' ),
			'PKR' => __( 'Pakistani rupee', 'woocommerce' ),
			'PLN' => __( 'Polish z&#x142;oty', 'woocommerce' ),
			'PRB' => __( 'Transnistrian ruble', 'woocommerce' ),
			'PYG' => __( 'Paraguayan guaran&iacute;', 'woocommerce' ),
			'QAR' => __( 'Qatari riyal', 'woocommerce' ),
			'RON' => __( 'Romanian leu', 'woocommerce' ),
			'RSD' => __( 'Serbian dinar', 'woocommerce' ),
			'RUB' => __( 'Russian ruble', 'woocommerce' ),
			'RWF' => __( 'Rwandan franc', 'woocommerce' ),
			'SAR' => __( 'Saudi riyal', 'woocommerce' ),
			'SBD' => __( 'Solomon Islands dollar', 'woocommerce' ),
			'SCR' => __( 'Seychellois rupee', 'woocommerce' ),
			'SDG' => __( 'Sudanese pound', 'woocommerce' ),
			'SEK' => __( 'Swedish krona', 'woocommerce' ),
			'SGD' => __( 'Singapore dollar', 'woocommerce' ),
			'SHP' => __( 'Saint Helena pound', 'woocommerce' ),
			'SLL' => __( 'Sierra Leonean leone', 'woocommerce' ),
			'SOS' => __( 'Somali shilling', 'woocommerce' ),
			'SRD' => __( 'Surinamese dollar', 'woocommerce' ),
			'SSP' => __( 'South Sudanese pound', 'woocommerce' ),
			'STD' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'woocommerce' ),
			'SYP' => __( 'Syrian pound', 'woocommerce' ),
			'SZL' => __( 'Swazi lilangeni', 'woocommerce' ),
			'THB' => __( 'Thai baht', 'woocommerce' ),
			'TJS' => __( 'Tajikistani somoni', 'woocommerce' ),
			'TMT' => __( 'Turkmenistan manat', 'woocommerce' ),
			'TND' => __( 'Tunisian dinar', 'woocommerce' ),
			'TOP' => __( 'Tongan pa&#x2bb;anga', 'woocommerce' ),
			'TRY' => __( 'Turkish lira', 'woocommerce' ),
			'TTD' => __( 'Trinidad and Tobago dollar', 'woocommerce' ),
			'TWD' => __( 'New Taiwan dollar', 'woocommerce' ),
			'TZS' => __( 'Tanzanian shilling', 'woocommerce' ),
			'UAH' => __( 'Ukrainian hryvnia', 'woocommerce' ),
			'UGX' => __( 'Ugandan shilling', 'woocommerce' ),
			'USD' => __( 'United States dollar', 'woocommerce' ),
			'UYU' => __( 'Uruguayan peso', 'woocommerce' ),
			'UZS' => __( 'Uzbekistani som', 'woocommerce' ),
			'VEF' => __( 'Venezuelan bol&iacute;var', 'woocommerce' ),
			'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'woocommerce' ),
			'VUV' => __( 'Vanuatu vatu', 'woocommerce' ),
			'WST' => __( 'Samoan t&#x101;l&#x101;', 'woocommerce' ),
			'XAF' => __( 'Central African CFA franc', 'woocommerce' ),
			'XCD' => __( 'East Caribbean dollar', 'woocommerce' ),
			'XOF' => __( 'West African CFA franc', 'woocommerce' ),
			'XPF' => __( 'CFP franc', 'woocommerce' ),
			'YER' => __( 'Yemeni rial', 'woocommerce' ),
			'ZAR' => __( 'South African rand', 'woocommerce' ),
			'ZMW' => __( 'Zambian kwacha', 'woocommerce' ),
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

	/**
	 * Test wc_format_country_state_string().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_format_country_state_string() {
		// Test with correct values.
		$this->assertEquals( array( 'country' => 'US', 'state' => 'CA' ), wc_format_country_state_string( 'US:CA' ) );
		// Test what happens when we pass an incorrect value.
		$this->assertEquals( array( 'country' => 'US-CA', 'state' => '' ), wc_format_country_state_string( 'US-CA' ) );
	}

	/**
	 * Test wc_get_shipping_method_count()
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_shipping_method_count() {
		// Without legacy methods.
		$this->assertEquals( 0, wc_get_shipping_method_count( false ) );

		// With legacy methods.
		$this->assertEquals( 0, wc_get_shipping_method_count( true ) );
	}
}
