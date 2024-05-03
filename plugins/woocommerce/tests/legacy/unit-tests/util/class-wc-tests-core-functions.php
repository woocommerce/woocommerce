<?php
/**
 * Unit tests for the core functions.
 *
 * @package WooCommerce\Tests\Util
 * @since 2.2
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Utilities\LoggingUtil;

/**
 * Core function unit tests.
 */
class WC_Tests_Core_Functions extends WC_Unit_Test_Case {

	/**
	 * Set up test
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->wc = WC();
	}

	/**
	 * Test get_woocommerce_currency().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currency() {

		$this->assertEquals( 'USD', get_woocommerce_currency() );
	}

	/**
	 * Test get_woocommerce_currencies().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currencies() {
		static $currencies;

		$expected_currencies = array(
			'AED' => 'United Arab Emirates dirham',
			'AFN' => 'Afghan afghani',
			'ALL' => 'Albanian lek',
			'AMD' => 'Armenian dram',
			'ANG' => 'Netherlands Antillean guilder',
			'AOA' => 'Angolan kwanza',
			'ARS' => 'Argentine peso',
			'AUD' => 'Australian dollar',
			'AWG' => 'Aruban florin',
			'AZN' => 'Azerbaijani manat',
			'BAM' => 'Bosnia and Herzegovina convertible mark',
			'BBD' => 'Barbadian dollar',
			'BDT' => 'Bangladeshi taka',
			'BGN' => 'Bulgarian lev',
			'BHD' => 'Bahraini dinar',
			'BIF' => 'Burundian franc',
			'BMD' => 'Bermudian dollar',
			'BND' => 'Brunei dollar',
			'BOB' => 'Bolivian boliviano',
			'BRL' => 'Brazilian real',
			'BSD' => 'Bahamian dollar',
			'BTC' => 'Bitcoin',
			'BTN' => 'Bhutanese ngultrum',
			'BWP' => 'Botswana pula',
			'BYR' => 'Belarusian ruble (old)',
			'BYN' => 'Belarusian ruble',
			'BZD' => 'Belize dollar',
			'CAD' => 'Canadian dollar',
			'CDF' => 'Congolese franc',
			'CHF' => 'Swiss franc',
			'CLP' => 'Chilean peso',
			'CNY' => 'Chinese yuan',
			'COP' => 'Colombian peso',
			'CRC' => 'Costa Rican col&oacute;n',
			'CUC' => 'Cuban convertible peso',
			'CUP' => 'Cuban peso',
			'CVE' => 'Cape Verdean escudo',
			'CZK' => 'Czech koruna',
			'DJF' => 'Djiboutian franc',
			'DKK' => 'Danish krone',
			'DOP' => 'Dominican peso',
			'DZD' => 'Algerian dinar',
			'EGP' => 'Egyptian pound',
			'ERN' => 'Eritrean nakfa',
			'ETB' => 'Ethiopian birr',
			'EUR' => 'Euro',
			'FJD' => 'Fijian dollar',
			'FKP' => 'Falkland Islands pound',
			'GBP' => 'Pound sterling',
			'GEL' => 'Georgian lari',
			'GGP' => 'Guernsey pound',
			'GHS' => 'Ghana cedi',
			'GIP' => 'Gibraltar pound',
			'GMD' => 'Gambian dalasi',
			'GNF' => 'Guinean franc',
			'GTQ' => 'Guatemalan quetzal',
			'GYD' => 'Guyanese dollar',
			'HKD' => 'Hong Kong dollar',
			'HNL' => 'Honduran lempira',
			'HRK' => 'Croatian kuna',
			'HTG' => 'Haitian gourde',
			'HUF' => 'Hungarian forint',
			'IDR' => 'Indonesian rupiah',
			'ILS' => 'Israeli new shekel',
			'IMP' => 'Manx pound',
			'INR' => 'Indian rupee',
			'IQD' => 'Iraqi dinar',
			'IRR' => 'Iranian rial',
			'IRT' => 'Iranian toman',
			'ISK' => 'Icelandic kr&oacute;na',
			'JEP' => 'Jersey pound',
			'JMD' => 'Jamaican dollar',
			'JOD' => 'Jordanian dinar',
			'JPY' => 'Japanese yen',
			'KES' => 'Kenyan shilling',
			'KGS' => 'Kyrgyzstani som',
			'KHR' => 'Cambodian riel',
			'KMF' => 'Comorian franc',
			'KPW' => 'North Korean won',
			'KRW' => 'South Korean won',
			'KWD' => 'Kuwaiti dinar',
			'KYD' => 'Cayman Islands dollar',
			'KZT' => 'Kazakhstani tenge',
			'LAK' => 'Lao kip',
			'LBP' => 'Lebanese pound',
			'LKR' => 'Sri Lankan rupee',
			'LRD' => 'Liberian dollar',
			'LSL' => 'Lesotho loti',
			'LYD' => 'Libyan dinar',
			'MAD' => 'Moroccan dirham',
			'MDL' => 'Moldovan leu',
			'MGA' => 'Malagasy ariary',
			'MKD' => 'Macedonian denar',
			'MMK' => 'Burmese kyat',
			'MNT' => 'Mongolian t&ouml;gr&ouml;g',
			'MOP' => 'Macanese pataca',
			'MRU' => 'Mauritanian ouguiya',
			'MUR' => 'Mauritian rupee',
			'MVR' => 'Maldivian rufiyaa',
			'MWK' => 'Malawian kwacha',
			'MXN' => 'Mexican peso',
			'MYR' => 'Malaysian ringgit',
			'MZN' => 'Mozambican metical',
			'NAD' => 'Namibian dollar',
			'NGN' => 'Nigerian naira',
			'NIO' => 'Nicaraguan c&oacute;rdoba',
			'NOK' => 'Norwegian krone',
			'NPR' => 'Nepalese rupee',
			'NZD' => 'New Zealand dollar',
			'OMR' => 'Omani rial',
			'PAB' => 'Panamanian balboa',
			'PEN' => 'Sol',
			'PGK' => 'Papua New Guinean kina',
			'PHP' => 'Philippine peso',
			'PKR' => 'Pakistani rupee',
			'PLN' => 'Polish z&#x142;oty',
			'PRB' => 'Transnistrian ruble',
			'PYG' => 'Paraguayan guaran&iacute;',
			'QAR' => 'Qatari riyal',
			'RON' => 'Romanian leu',
			'RSD' => 'Serbian dinar',
			'RUB' => 'Russian ruble',
			'RWF' => 'Rwandan franc',
			'SAR' => 'Saudi riyal',
			'SBD' => 'Solomon Islands dollar',
			'SCR' => 'Seychellois rupee',
			'SDG' => 'Sudanese pound',
			'SEK' => 'Swedish krona',
			'SGD' => 'Singapore dollar',
			'SHP' => 'Saint Helena pound',
			'SLL' => 'Sierra Leonean leone',
			'SOS' => 'Somali shilling',
			'SRD' => 'Surinamese dollar',
			'SSP' => 'South Sudanese pound',
			'STN' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra',
			'SYP' => 'Syrian pound',
			'SZL' => 'Swazi lilangeni',
			'THB' => 'Thai baht',
			'TJS' => 'Tajikistani somoni',
			'TMT' => 'Turkmenistan manat',
			'TND' => 'Tunisian dinar',
			'TOP' => 'Tongan pa&#x2bb;anga',
			'TRY' => 'Turkish lira',
			'TTD' => 'Trinidad and Tobago dollar',
			'TWD' => 'New Taiwan dollar',
			'TZS' => 'Tanzanian shilling',
			'UAH' => 'Ukrainian hryvnia',
			'UGX' => 'Ugandan shilling',
			'USD' => 'United States (US) dollar',
			'UYU' => 'Uruguayan peso',
			'UZS' => 'Uzbekistani som',
			'VEF' => 'Venezuelan bol&iacute;var (2008–2018)',
			'VES' => 'Venezuelan bol&iacute;var',
			'VND' => 'Vietnamese &#x111;&#x1ed3;ng',
			'VUV' => 'Vanuatu vatu',
			'WST' => 'Samoan t&#x101;l&#x101;',
			'XAF' => 'Central African CFA franc',
			'XCD' => 'East Caribbean dollar',
			'XOF' => 'West African CFA franc',
			'XPF' => 'CFP franc',
			'YER' => 'Yemeni rial',
			'ZAR' => 'South African rand',
			'ZMW' => 'Zambian kwacha',
		);

		$this->assertEquals( $expected_currencies, get_woocommerce_currencies() );

		// Unset cached currencies and test again.
		unset( $currencies );
		$this->assertEquals( $expected_currencies, get_woocommerce_currencies() );
	}

	/**
	 * Test get_woocommerce_currency_symbol().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_currency_symbol() {

		// Default currency.
		$this->assertEquals( '&#36;', get_woocommerce_currency_symbol() );

		// Given specific currency.
		$this->assertEquals( '&pound;', get_woocommerce_currency_symbol( 'GBP' ) );

		// Each case.
		foreach ( array_keys( get_woocommerce_currencies() ) as $currency_code ) {
			$this->assertIsString( get_woocommerce_currency_symbol( $currency_code ) );
		}
	}

	/**
	 * Test wc_get_theme_support()
	 *
	 * @return void
	 */
	public function test_wc_get_theme_support() {
		$this->assertEquals( 'default', wc_get_theme_support( '', 'default' ) );

		$theme_support_options = array(
			'thumbnail_image_width' => 150,
			'single_image_width'    => 300,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 2,
				'max_rows'        => 8,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 5,
			),
		);
		add_theme_support( 'woocommerce', $theme_support_options );

		$this->assertEquals( $theme_support_options, wc_get_theme_support() );
		$this->assertEquals( $theme_support_options['product_grid'], wc_get_theme_support( 'product_grid' ) );
	}

	/**
	 * Test get_woocommerce_api_url().
	 *
	 * @since 2.2
	 */
	public function test_get_woocommerce_api_url() {

		$base_uri = get_home_url();

		// Base URI.
		$this->assertEquals( "$base_uri/wc-api/v3/", get_woocommerce_api_url( null ) );

		// Path.
		$this->assertEquals( "$base_uri/wc-api/v3/orders", get_woocommerce_api_url( 'orders' ) );
	}

	/**
	 * Test wc_get_log_file_path().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_log_file_path() {
		$this->setExpectedDeprecated( 'wc_get_log_file_path' );

		$log_dir   = trailingslashit( WC_LOG_DIR );
		$hash_name = sanitize_file_name( wp_hash( 'unit-tests' ) );
		$file_id   = LoggingUtil::generate_log_file_id( 'unit-tests', null, time() );
		$hash      = LoggingUtil::generate_log_file_hash( $file_id );

		$this->assertEquals( $log_dir . $file_id . '-' . $hash . '.log', wc_get_log_file_path( 'unit-tests' ) );
	}

	/**
	 * Test wc_get_logger().
	 *
	 * @since 3.0.0
	 */
	public function test_wc_get_logger() {
		// This filter should have no effect because the class does not implement WC_Logger_Interface.
		add_filter( 'woocommerce_logging_class', array( $this, 'return_bad_logger' ) );

		$this->setExpectedIncorrectUsage( 'wc_get_logger' );
		$log_a = wc_get_logger();
		$log_b = wc_get_logger();

		$this->assertInstanceOf( 'WC_Logger', $log_a );
		$this->assertInstanceOf( 'WC_Logger', $log_b );
		$this->assertSame( $log_a, $log_b, '`wc_get_logger()` should return the same instance' );
	}

	/**
	 * Test wc_get_logger() to check if can return instance when given in filter.
	 */
	public function test_wc_get_logger_for_instance() {
		add_filter( 'woocommerce_logging_class', array( $this, 'return_valid_logger_instance' ) );

		$logger = wc_get_logger();

		$this->assertInstanceOf( 'WC_Logger_Interface', $logger, '`wc_get_logger()` should return valid Dummy_WC_Logger instance' );
	}

	/**
	 * Return valid logger instance that implements WC_Logger_Interface.
	 *
	 * @return WC_Logger_Interface
	 */
	public function return_valid_logger_instance() {
		if ( ! class_exists( 'Dummy_WC_Logger' ) ) {
			include_once __DIR__ . '/dummy-wc-logger.php';
		}
		return new Dummy_WC_Logger();
	}

	/**
	 * Return class which does not implement WC_Logger_Interface
	 *
	 * This is a helper function to test woocommerce_logging_class filter and wc_get_logger.
	 *
	 * @return string Class name
	 */
	public function return_bad_logger() {
		return __CLASS__;
	}

	/**
	 * Test wc_get_base_location().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_base_location() {
		$default = wc_get_base_location();

		$this->assertEquals( 'US', $default['country'] );
		$this->assertEquals( 'CA', $default['state'] );
	}

	/**
	 * Test wc_format_country_state_string().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_format_country_state_string() {
		// Test with correct values.
		$this->assertEquals(
			array(
				'country' => 'US',
				'state'   => 'CA',
			),
			wc_format_country_state_string( 'US:CA' )
		);

		// Test what happens when we pass an incorrect value.
		$this->assertEquals(
			array(
				'country' => 'US-CA',
				'state'   => '',
			),
			wc_format_country_state_string( 'US-CA' )
		);
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

	/**
	 * Test wc_print_r()
	 *
	 * @since 3.0.0
	 */
	public function test_wc_print_r() {
		$arr = array(
			1,
			2,
			'a',
			'b',
			'c' => 'd',
		);

		// This filter will sequentially remove handlers, allowing us to test as though our
		// functions were accumulatively blocked, adding one on each call.
		add_filter( 'woocommerce_print_r_alternatives', array( $this, 'filter_wc_print_r_alternatives' ) );

		$this->expectOutputString(
			print_r( $arr, true ),
			$return_value = wc_print_r( $arr )
		);
		$this->assertTrue( $return_value );
		ob_clean();

		$this->expectOutputString(
			var_export( $arr, true ),
			$return_value = wc_print_r( $arr )
		);
		$this->assertTrue( $return_value );
		ob_clean();

		$this->expectOutputString(
			json_encode( $arr ),
			$return_value = wc_print_r( $arr )
		);
		$this->assertTrue( $return_value );
		ob_clean();

		$this->expectOutputString(
			serialize( $arr ),
			$return_value = wc_print_r( $arr )
		);
		$this->assertTrue( $return_value );
		ob_clean();

		$this->expectOutputString(
			'',
			$return_value = wc_print_r( $arr )
		);
		$this->assertFalse( $return_value );
		ob_clean();

		// Reset filter to include all handlers.
		$this->filter_wc_print_r_alternatives( array(), true );

		$this->assertEquals( print_r( $arr, true ), wc_print_r( $arr, true ) );
		$this->assertEquals( var_export( $arr, true ), wc_print_r( $arr, true ) );
		$this->assertEquals( wp_json_encode( $arr ), wc_print_r( $arr, true ) );
		$this->assertEquals( serialize( $arr ), wc_print_r( $arr, true ) );
		$this->assertFalse( wc_print_r( $arr, true ) );

		remove_filter( 'woocommerce_print_r_alternatives', array( $this, 'filter_wc_print_r_alternatives' ) );
	}

	/**
	 * Filter function to help test wc_print_r alternatives via filter.
	 *
	 * On the first run, all alternatives are returned.
	 * On each successive run, an alternative is excluded from the beginning.
	 * Eventually, no handlers are returned.
	 *
	 * @param array $alternatives Input array of alternatives.
	 * @param bool  $reset Optional. Default false. True to reset excluded alternatives.
	 * @return array|bool Alternatives. True on reset.
	 */
	public function filter_wc_print_r_alternatives( $alternatives, $reset = false ) {
		static $skip = 0;
		if ( $reset ) {
			$skip = 0;
			return true;
		}
		return array_slice( $alternatives, $skip++ );
	}

	/**
	 * Test wc_get_wildcard_postcodes
	 */
	public function test_wc_get_wildcard_postcodes() {
		$postcode  = 'cb23 6as';
		$country   = 'GB';
		$wildcards = array( 'cb23 6as', 'CB23 6AS', 'CB23 6AS*', 'CB23 6A*', 'CB23 6*', 'CB23 *', 'CB23*', 'CB2*', 'CB*', 'C*', '*' );
		$this->assertEquals( $wildcards, wc_get_wildcard_postcodes( $postcode, $country ) );

		$postcode  = 'GIJóN';
		$country   = '';
		$wildcards = array( 'GIJóN', 'GIJÓN', 'GIJÓN*', 'GIJÓ*', 'GIJ*', 'GI*', 'G*', '*' );
		$this->assertEquals( $wildcards, wc_get_wildcard_postcodes( $postcode, $country ) );
	}

	/**
	 * Tests wc_maybe_define_constant().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_maybe_define_constant() {
		$this->assertFalse( Constants::is_defined( 'WC_TESTING_DEFINE_FUNCTION' ) );

		// Check if defined.
		wc_maybe_define_constant( 'WC_TESTING_DEFINE_FUNCTION', true );
		$this->assertTrue( Constants::is_defined( 'WC_TESTING_DEFINE_FUNCTION' ) );

		// Check value.
		wc_maybe_define_constant( 'WC_TESTING_DEFINE_FUNCTION', false );
		$this->assertTrue( WC_TESTING_DEFINE_FUNCTION );
	}

	/**
	 * Tests wc_create_order() and wc_update_order() currency handling.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_create_update_order_currency() {
		$old_currency = get_woocommerce_currency();
		$new_currency = 'BGN';

		update_option( 'woocommerce_currency', $new_currency );

		// New order should be created using shop currency.
		$order = wc_create_order(
			array(
				'status'      => 'pending',
				'customer_id' => 1,
				'created_via' => 'unit tests',
				'cart_hash'   => '',
			)
		);
		$this->assertEquals( $new_currency, $order->get_currency() );

		update_option( 'woocommerce_currency', $old_currency );

		// Currency should not change when order is updated.
		$order = wc_update_order(
			array(
				'customer_id' => 2,
				'order_id'    => $order->get_id(),
			)
		);
		$this->assertEquals( $new_currency, $order->get_currency() );

		$order = wc_update_order(
			array(
				'customer_id' => 2,
			)
		);
		$this->assertInstanceOf( 'WP_Error', $order );
	}

	/**
	 * Test the wc_is_active_theme function.
	 *
	 * @return void
	 */
	public function test_wc_is_active_theme() {
		$current_theme = get_template();
		$this->assertTrue( wc_is_active_theme( $current_theme ) );
		$this->assertFalse( wc_is_active_theme( 'somegiberish' ) );
		$this->assertTrue( wc_is_active_theme( array( $current_theme, 'somegiberish' ) ) );
	}

	/**
	 * Test the wc_get_template_part function.
	 *
	 * @return void
	 */
	public function test_wc_get_template_part() {
		$this->assertEmpty( wc_get_template_part( 'nothinghere' ) );
	}

	/**
	 * Tests the wc_tokenize_path function.
	 */
	public function test_wc_tokenize_path() {
		$path = wc_tokenize_path( ABSPATH . 'test', array() );
		$this->assertEquals( ABSPATH . 'test', $path );

		$path = wc_tokenize_path(
			ABSPATH . 'test',
			array(
				'ABSPATH' => ABSPATH,
			)
		);
		$this->assertEquals( '{{ABSPATH}}test', $path );

		$path = wc_tokenize_path(
			ABSPATH . 'test',
			array(
				'WP_CONTENT_DIR' => WP_CONTENT_DIR,
			)
		);
		$this->assertEquals( ABSPATH . 'test', $path );

		$path = wc_tokenize_path(
			WP_CONTENT_DIR . 'test',
			array(
				'ABSPATH'        => ABSPATH,
				'WP_CONTENT_DIR' => WP_CONTENT_DIR,
			)
		);
		$this->assertEquals( '{{WP_CONTENT_DIR}}test', $path );
	}

	/**
	 * Tests the wc_untokenize_path function.
	 */
	public function test_wc_untokenize_path() {
		$path = wc_untokenize_path( '{{ABSPATH}}test', array() );
		$this->assertEquals( '{{ABSPATH}}test', $path );

		$path = wc_untokenize_path(
			'{{ABSPATH}}test',
			array(
				'ABSPATH' => ABSPATH,
			)
		);
		$this->assertEquals( ABSPATH . 'test', $path );

		$path = wc_untokenize_path(
			'{{ABSPATH}}test',
			array(
				'WP_CONTENT_DIR' => WP_CONTENT_DIR,
			)
		);
		$this->assertEquals( '{{ABSPATH}}test', $path );

		$path = wc_untokenize_path(
			'{{WP_CONTENT_DIR}}test',
			array(
				'WP_CONTENT_DIR' => WP_CONTENT_DIR,
				'ABSPATH'        => ABSPATH,
			)
		);
		$this->assertEquals( WP_CONTENT_DIR . 'test', $path );
	}

	/**
	 * Tests the wc_get_path_define_tokens function.
	 */
	public function test_wc_get_path_define_tokens() {
		$defines = wc_get_path_define_tokens();
		$this->assertArrayHasKey( 'ABSPATH', $defines );
		$this->assertEquals( ABSPATH, $defines['ABSPATH'] );
	}

	/**
	 * Test wc_get_template.
	 *
	 * @expectedIncorrectUsage wc_get_template
	 */
	public function test_wc_get_template_invalid_action_args() {
		ob_start();
		wc_get_template(
			'global/wrapper-start.php',
			array(
				'action_args' => 'this is bad',
			)
		);
		$template = ob_get_clean();
	}

	/**
	 * Test wc_get_template.
	 */
	public function test_wc_get_template() {
		ob_start();
		wc_get_template( 'global/wrapper-start.php' );
		$template = ob_get_clean();
		$this->assertNotEmpty( $template );

		ob_start();
		wc_get_template(
			'global/wrapper-start.php',
			array(
				'template' => 'x',
				'located'  => 'x',
			)
		);
		$template = ob_get_clean();
		$this->assertNotEmpty( $template );
	}

	/**
	 * This test ensures that the absolute path to template files is replaced with a token. We do this so
	 * that the path can be made relative to each installation, and the cache can be shared.
	 */
	public function test_wc_get_template_cleans_absolute_path() {
		add_filter( 'woocommerce_locate_template', array( $this, 'force_template_path' ), 10, 2 );

		ob_start();
		try {
			wc_get_template( 'global/wrapper-start.php' );
		} catch ( \Exception $exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Since the file doesn't really exist this is going to throw an exception (which is fine for our test).
		}
		ob_end_clean();

		remove_filter( 'woocommerce_locate_template', array( $this, 'force_template_path' ) );

		$file_path = wp_cache_get( sanitize_key( 'template-global/wrapper-start.php---' . WC_VERSION ), 'woocommerce' );

		$this->assertEquals( '{{ABSPATH}}global/wrapper-start.php', $file_path );
	}

	/**
	 * Test wc_get_image_size function.
	 *
	 * @return void
	 */
	public function test_wc_get_image_size() {
		$this->assertArrayHasKey( 'width', wc_get_image_size( array( 100, 100, 1 ) ) );
		$this->assertArrayHasKey( 'height', wc_get_image_size( 'shop_single' ) );
		update_option( 'woocommerce_thumbnail_cropping', 'uncropped' );
		$this->assertArrayHasKey( 'crop', wc_get_image_size( 'shop_thumbnail' ) );
		update_option( 'woocommerce_thumbnail_cropping', 'custom' );
		$this->assertArrayHasKey( 'crop', wc_get_image_size( 'shop_thumbnail' ) );
	}

	/**
	 * Test wc_enqueue_js and wc_print_js functions.
	 *
	 * @return void
	 */
	public function test_wc_enqueue_js_wc_print_js() {
		$js = 'alert( "test" );';

		ob_start();
		wc_print_js();
		$printed_js = ob_get_clean();
		$this->assertStringNotContainsString( $js, $printed_js );

		wc_enqueue_js( $js );

		ob_start();
		wc_print_js();
		$printed_js = ob_get_clean();
		$this->assertStringContainsString( $js, $printed_js );
	}

	/**
	 * Test wc_get_log_file_name function.
	 *
	 * @return void
	 */
	public function test_wc_get_log_file_name() {
		$this->setExpectedDeprecated( 'wc_get_log_file_name' );
		$this->assertNotEmpty( wc_get_log_file_name( 'test' ) );
	}

	/**
	 * Test wc_get_page_children function.
	 *
	 * @return void
	 */
	public function test_wc_get_page_children() {
		$page_id = wp_insert_post(
			array(
				'post_title'  => 'Parent Page',
				'post_type'   => 'page',
				'post_name'   => 'parent-page',
				'post_status' => 'publish',
				'post_author' => 1,
				'menu_order'  => 0,
			)
		);

		$child_page_id = wp_insert_post(
			array(
				'post_parent' => $page_id,
				'post_title'  => 'Parent Page',
				'post_type'   => 'page',
				'post_name'   => 'parent-page',
				'post_status' => 'publish',
				'post_author' => 1,
				'menu_order'  => 0,
			)
		);
		$children      = wc_get_page_children( $page_id );
		$this->assertEquals( $child_page_id, $children[0] );

		wp_delete_post( $page_id, true );
		wp_delete_post( $child_page_id, true );
	}

	/**
	 * Test hash_equals function.
	 *
	 * @return void
	 */
	public function test_hash_equals() {
		$this->assertTrue( hash_equals( 'abc', 'abc' ) ); // @codingStandardsIgnoreLine.
		$this->assertFalse( hash_equals( 'abcd', 'abc' ) ); // @codingStandardsIgnoreLine.
	}

	/**
	 * Test wc_rand_hash function.
	 *
	 * @return void
	 */
	public function test_wc_rand_hash() {
		$this->assertNotEquals( wc_rand_hash(), wc_rand_hash() );
	}

	/**
	 * Test wc_transaction_query function.
	 */
	public function test_wc_transaction_query() {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'options',
			array(
				'option_name'  => 'transaction_test',
				'option_value' => '1',
			),
			array(
				'%s',
				'%s',
			)
		);
		wc_transaction_query( 'start', true );
		$wpdb->update(
			$wpdb->prefix . 'options',
			array(
				'option_value' => '0',
			),
			array(
				'option_name' => 'transaction_test',
			)
		);
		$col = $wpdb->get_col( "SElECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'transaction_test'" );
		$this->assertEquals( '0', $col[0] );

		wc_transaction_query( 'rollback', true );
		$col = $wpdb->get_col( "SElECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'transaction_test'" );
		$this->assertEquals( '1', $col[0] );

		wc_transaction_query( 'start', true );
		$wpdb->update(
			$wpdb->prefix . 'options',
			array(
				'option_value' => '0',
			),
			array(
				'option_name' => 'transaction_test',
			)
		);
		wc_transaction_query( 'commit', true );
		$col = $wpdb->get_col( "SElECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'transaction_test'" );
		$this->assertEquals( '0', $col[0] );

		$wpdb->delete(
			$wpdb->prefix . 'options',
			array(
				'option_name' => 'transaction_test',
			)
		);
	}

	/**
	 * Test: wc_selected
	 */
	public function test_wc_selected() {
		$test_cases = array(
			// both value and options int.
			array( 0, 0, true ),
			array( 0, 1, false ),
			array( 1, 0, false ),

			// value string, options int.
			array( '0', 0, true ),
			array( '0', 1, false ),
			array( '1', 0, false ),

			// value int, options string.
			array( 0, '0', true ),
			array( 0, '1', false ),
			array( 1, '0', false ),

			// both value and options str.
			array( '0', '0', true ),
			array( '0', '1', false ),
			array( '1', '0', false ),

			// both value and options int.
			array( 0, array( 0, 1, 2 ), true ),
			array( 0, array( 1, 1, 1 ), false ),

			// value string, options int.
			array( '0', array( 0, 1, 2 ), true ),
			array( '0', array( 1, 1, 1 ), false ),

			// value int, options string.
			array( 0, array( '0', '1', '2' ), true ),
			array( 0, array( '1', '1', '1' ), false ),

			// both value and options str.
			array( '0', array( '0', '1', '2' ), true ),
			array( '0', array( '1', '1', '1' ), false ),
		);

		foreach ( $test_cases as $test_case ) {
			list( $value, $options, $result ) = $test_case;
			$actual_result                    = $result ? " selected='selected'" : '';
			$this->assertEquals( wc_selected( $value, $options ), $actual_result );
		}
	}

	/**
	 * Test wc_array_cartesian.
	 *
	 * @return void
	 */
	public function test_wc_array_cartesian() {
		$array = array(
			'attr1' => array(
				'Attr1 Value 1',
				'Attr1 Value 2',
			),
			'attr2' => array(
				'Attr2 Value 1',
				'Attr2 Value 2',
			),
		);

		$expected_combinations = array(
			array(
				'attr2' => 'Attr2 Value 1',
				'attr1' => 'Attr1 Value 1',
			),
			array(
				'attr2' => 'Attr2 Value 2',
				'attr1' => 'Attr1 Value 1',
			),
			array(
				'attr2' => 'Attr2 Value 1',
				'attr1' => 'Attr1 Value 2',
			),
			array(
				'attr2' => 'Attr2 Value 2',
				'attr1' => 'Attr1 Value 2',
			),
		);

		$this->assertEquals( $expected_combinations, array_reverse( wc_array_cartesian( $array ) ) );
	}

	/**
	 * Test wc_get_credit_card_type_label.
	 *
	 * @return void
	 */
	public function test_wc_get_credit_card_type_label() {
		$this->assertEquals( 'Visa', wc_get_credit_card_type_label( 'visa' ) );
		$this->assertEquals( 'JCB', wc_get_credit_card_type_label( 'jCb' ) );
		$this->assertEquals( 'MasterCard', wc_get_credit_card_type_label( 'Mastercard' ) );
		$this->assertEquals( 'American Express', wc_get_credit_card_type_label( 'american_express' ) );
		$this->assertEquals( 'American Express', wc_get_credit_card_type_label( 'american-express' ) );
		$this->assertEquals( '', wc_get_credit_card_type_label( '' ) );
		$this->assertEquals( 'Random name', wc_get_credit_card_type_label( 'random-name' ) );
	}

	/**
	 * Test wc_get_permalink_structure.
	 *
	 * @return void
	 */
	public function test_wc_get_permalink_structure() {
		$expected_structure = array(
			'product_base'           => 'product',
			'category_base'          => 'product-category',
			'tag_base'               => 'product-tag',
			'attribute_base'         => '',
			'use_verbose_page_rules' => '',
			'product_rewrite_slug'   => 'product',
			'category_rewrite_slug'  => 'product-category',
			'tag_rewrite_slug'       => 'product-tag',
			'attribute_rewrite_slug' => '',
		);
		$this->assertEquals( $expected_structure, wc_get_permalink_structure() );
	}

	/**
	 * Test wc_decimal_to_fraction.
	 *
	 * @return void
	 */
	public function test_wc_decimal_to_fraction() {
		$this->assertEquals( array( 7, 2 ), wc_decimal_to_fraction( '3.5' ) );
	}

	/**
	 * Test wc_get_user_agent function.
	 *
	 * @return void
	 */
	public function test_wc_get_user_agent() {
		$example_user_agent         = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36';
		$_SERVER['HTTP_USER_AGENT'] = $example_user_agent;
		$this->assertEquals( $example_user_agent, wc_get_user_agent() );
	}

	/**
	 * Test the wc_checkout_fields_uasort_comparison function.
	 *
	 * @return void
	 */
	public function test_wc_checkout_fields_uasort_comparison() {
		$fields = array(
			'billing_first_name' => array(
				'priority' => 10,
			),
			'billing_last_name'  => array(
				'priority' => 20,
			),
			'billing_email'      => array(
				'priority' => 1,
			),
		);
		uasort( $fields, 'wc_checkout_fields_uasort_comparison' );
		$this->assertSame( 0, array_search( 'billing_email', array_keys( $fields ) ) );
	}

	/**
	 * Test wc_ascii_uasort_comparison function
	 *
	 * @return void
	 */
	public function test_wc_ascii_uasort_comparison() {
		$unsorted_values = array(
			'Benin',
			'Bélgica',
		);

		// First test a normal asort which does not work right for accented characters.
		$sorted_values = $unsorted_values;
		asort( $sorted_values );
		$this->assertSame( array( 'Benin', 'Bélgica' ), array_values( $sorted_values ) );

		$sorted_values = $unsorted_values;
		// Now test the new wc_ascii_uasort_comparison function which sorts the strings correctly.
		uasort( $sorted_values, 'wc_ascii_uasort_comparison' );
		$this->assertSame( array( 'Bélgica', 'Benin' ), array_values( $sorted_values ) );
	}

	/**
	 * Test wc_load_cart function.
	 *
	 * @return void
	 */
	public function test_wc_load_cart() {
		$original_cart     = $this->wc->cart;
		$original_customer = $this->wc->customer;
		$original_session  = $this->wc->session;

		$this->assertInstanceOf( 'WC_Cart', $this->wc->cart );
		$this->assertInstanceOf( 'WC_Customer', $this->wc->customer );
		$this->assertInstanceOf( 'WC_Session', $this->wc->session );

		$this->wc->cart     = null;
		$this->wc->customer = null;
		$this->wc->session  = null;
		$this->assertNull( $this->wc->cart );
		$this->assertNull( $this->wc->customer );
		$this->assertNull( $this->wc->session );

		wc_load_cart();
		$this->assertInstanceOf( 'WC_Cart', $this->wc->cart );
		$this->assertInstanceOf( 'WC_Customer', $this->wc->customer );
		$this->assertInstanceOf( 'WC_Session', $this->wc->session );

		// Restore original cart, customer and session instances. Important since global state (including filters and
		// actions) is reset between tests, and we want to avoid a scenario in which wc()->cart and cart-related
		// callbacks are separate instances of the same class - which can lead to strange effects.
		$this->wc->cart     = $original_cart;
		$this->wc->customer = $original_customer;
		$this->wc->session  = $original_session;
	}

	/**
	 * Allows us to force the template path. Since the ABSPATH is to /tmp/wordpress in tests, we need to do this
	 * in order to keep the paths consistent for testing purposes.
	 *
	 * @param string $template The path to the template file.
	 * @param string $template_name The name of the template file.
	 * @return string The path to be used instead.
	 */
	public function force_template_path( $template, $template_name ) {
		return ABSPATH . $template_name;
	}
}
