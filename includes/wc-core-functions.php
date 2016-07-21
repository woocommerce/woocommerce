<?php
/**
 * WooCommerce Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions (available in both admin and frontend).
include( 'wc-conditional-functions.php' );
include( 'wc-coupon-functions.php' );
include( 'wc-user-functions.php' );
include( 'wc-deprecated-functions.php' );
include( 'wc-formatting-functions.php' );
include( 'wc-order-functions.php' );
include( 'wc-page-functions.php' );
include( 'wc-product-functions.php' );
include( 'wc-account-functions.php' );
include( 'wc-term-functions.php' );
include( 'wc-attribute-functions.php' );
include( 'wc-rest-functions.php' );

/**
 * Filters on data used in admin and frontend.
 */
add_filter( 'woocommerce_coupon_code', 'html_entity_decode' );
add_filter( 'woocommerce_coupon_code', 'sanitize_text_field' );
add_filter( 'woocommerce_coupon_code', 'strtolower' ); // Coupons case-insensitive by default
add_filter( 'woocommerce_stock_amount', 'intval' ); // Stock amounts are integers by default
add_filter( 'woocommerce_shipping_rate_label', 'sanitize_text_field' ); // Shipping rate label

/**
 * Short Description (excerpt).
 */
add_filter( 'woocommerce_short_description', 'wptexturize' );
add_filter( 'woocommerce_short_description', 'convert_smilies' );
add_filter( 'woocommerce_short_description', 'convert_chars' );
add_filter( 'woocommerce_short_description', 'wpautop' );
add_filter( 'woocommerce_short_description', 'shortcode_unautop' );
add_filter( 'woocommerce_short_description', 'prepend_attachment' );
add_filter( 'woocommerce_short_description', 'do_shortcode', 11 ); // AFTER wpautop()

/**
 * Create a new order programmatically.
 *
 * Returns a new order object on success which can then be used to add additional data.
 *
 * @param  array $args
 *
 * @return WC_Order|WP_Error WC_Order on success, WP_Error on failure.
 */
function wc_create_order( $args = array() ) {
	$default_args = array(
		'status'        => '',
		'customer_id'   => null,
		'customer_note' => null,
		'order_id'      => 0,
		'created_via'   => '',
		'cart_hash'     => '',
		'parent'        => 0,
	);

	$args       = wp_parse_args( $args, $default_args );
	$order_data = array();

	if ( $args['order_id'] > 0 ) {
		$updating         = true;
		$order_data['ID'] = $args['order_id'];
	} else {
		$updating                    = false;
		$order_data['post_type']     = 'shop_order';
		$order_data['post_status']   = 'wc-' . apply_filters( 'woocommerce_default_order_status', 'pending' );
		$order_data['ping_status']   = 'closed';
		$order_data['post_author']   = 1;
		$order_data['post_password'] = uniqid( 'order_' );
		$order_data['post_title']    = sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
		$order_data['post_parent']   = absint( $args['parent'] );
	}

	if ( $args['status'] ) {
		if ( ! in_array( 'wc-' . $args['status'], array_keys( wc_get_order_statuses() ) ) ) {
			return new WP_Error( 'woocommerce_invalid_order_status', __( 'Invalid order status', 'woocommerce' ) );
		}
		$order_data['post_status']  = 'wc-' . $args['status'];
	}

	if ( ! is_null( $args['customer_note'] ) ) {
		$order_data['post_excerpt'] = $args['customer_note'];
	}

	if ( $updating ) {
		$order_id = wp_update_post( $order_data );
	} else {
		$order_id = wp_insert_post( apply_filters( 'woocommerce_new_order_data', $order_data ), true );
	}

	if ( is_wp_error( $order_id ) ) {
		return $order_id;
	}

	if ( ! $updating ) {
		update_post_meta( $order_id, '_order_key', 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
		update_post_meta( $order_id, '_order_currency', get_woocommerce_currency() );
		update_post_meta( $order_id, '_prices_include_tax', get_option( 'woocommerce_prices_include_tax' ) );
		update_post_meta( $order_id, '_customer_ip_address', WC_Geolocation::get_ip_address() );
		update_post_meta( $order_id, '_customer_user_agent', isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
		update_post_meta( $order_id, '_customer_user', 0 );
		update_post_meta( $order_id, '_created_via', sanitize_text_field( $args['created_via'] ) );
		update_post_meta( $order_id, '_cart_hash', sanitize_text_field( $args['cart_hash'] ) );
	}

	if ( is_numeric( $args['customer_id'] ) ) {
		update_post_meta( $order_id, '_customer_user', $args['customer_id'] );
	}

	update_post_meta( $order_id, '_order_version', WC_VERSION );

	return wc_get_order( $order_id );
}

/**
 * Update an order. Uses wc_create_order.
 *
 * @param  array $args
 * @return string | WC_Order
 */
function wc_update_order( $args ) {
	if ( ! $args['order_id'] ) {
		return new WP_Error( __( 'Invalid order ID', 'woocommerce' ) );
	}
	return wc_create_order( $args );
}

/**
 * Get template part (for templates like the shop-loop).
 *
 * WC_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @access public
 * @param mixed $slug
 * @param string $name (default: '')
 */
function wc_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/woocommerce/slug-name.php
	if ( $name && ! WC_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}-{$name}.php", WC()->template_path() . "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php
	if ( ! $template && $name && file_exists( WC()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = WC()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/woocommerce/slug.php
	if ( ! $template && ! WC_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", WC()->template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'wc_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @access public
 * @param string $template_name
 * @param array $args (default: array())
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 */
function wc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	$located = wc_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
		return;
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'wc_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'woocommerce_before_template_part', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'woocommerce_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Like wc_get_template, but returns the HTML instead of outputting.
 * @see wc_get_template
 * @since 2.5.0
 * @param string $template_name
 */
function wc_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	wc_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 *		yourtheme		/	$template_path	/	$template_name
 *		yourtheme		/	$template_name
 *		$default_path	/	$template_name
 *
 * @access public
 * @param string $template_name
 * @param string $template_path (default: '')
 * @param string $default_path (default: '')
 * @return string
 */
function wc_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = WC()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = WC()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name
		)
	);

	// Get default template/
	if ( ! $template || WC_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'woocommerce_locate_template', $template, $template_name, $template_path );
}

/**
 * Get Base Currency Code.
 *
 * @return string
 */
function get_woocommerce_currency() {
	return apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
}

/**
 * Get full list of currency codes.
 *
 * @return array
 */
function get_woocommerce_currencies() {
	return array_unique(
		apply_filters( 'woocommerce_currencies',
			array(
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
			)
		)
	);
}

/**
 * Get Currency symbol.
 *
 * @param string $currency (default: '')
 * @return string
 */
function get_woocommerce_currency_symbol( $currency = '' ) {
	if ( ! $currency ) {
		$currency = get_woocommerce_currency();
	}

	$symbols = apply_filters( 'woocommerce_currency_symbols', array(
		'AED' => '&#x62f;.&#x625;',
		'AFN' => '&#x60b;',
		'ALL' => 'L',
		'AMD' => 'AMD',
		'ANG' => '&fnof;',
		'AOA' => 'Kz',
		'ARS' => '&#36;',
		'AUD' => '&#36;',
		'AWG' => '&fnof;',
		'AZN' => 'AZN',
		'BAM' => 'KM',
		'BBD' => '&#36;',
		'BDT' => '&#2547;&nbsp;',
		'BGN' => '&#1083;&#1074;.',
		'BHD' => '.&#x62f;.&#x628;',
		'BIF' => 'Fr',
		'BMD' => '&#36;',
		'BND' => '&#36;',
		'BOB' => 'Bs.',
		'BRL' => '&#82;&#36;',
		'BSD' => '&#36;',
		'BTC' => '&#3647;',
		'BTN' => 'Nu.',
		'BWP' => 'P',
		'BYR' => 'Br',
		'BZD' => '&#36;',
		'CAD' => '&#36;',
		'CDF' => 'Fr',
		'CHF' => '&#67;&#72;&#70;',
		'CLP' => '&#36;',
		'CNY' => '&yen;',
		'COP' => '&#36;',
		'CRC' => '&#x20a1;',
		'CUC' => '&#36;',
		'CUP' => '&#36;',
		'CVE' => '&#36;',
		'CZK' => '&#75;&#269;',
		'DJF' => 'Fr',
		'DKK' => 'DKK',
		'DOP' => 'RD&#36;',
		'DZD' => '&#x62f;.&#x62c;',
		'EGP' => 'EGP',
		'ERN' => 'Nfk',
		'ETB' => 'Br',
		'EUR' => '&euro;',
		'FJD' => '&#36;',
		'FKP' => '&pound;',
		'GBP' => '&pound;',
		'GEL' => '&#x10da;',
		'GGP' => '&pound;',
		'GHS' => '&#x20b5;',
		'GIP' => '&pound;',
		'GMD' => 'D',
		'GNF' => 'Fr',
		'GTQ' => 'Q',
		'GYD' => '&#36;',
		'HKD' => '&#36;',
		'HNL' => 'L',
		'HRK' => 'Kn',
		'HTG' => 'G',
		'HUF' => '&#70;&#116;',
		'IDR' => 'Rp',
		'ILS' => '&#8362;',
		'IMP' => '&pound;',
		'INR' => '&#8377;',
		'IQD' => '&#x639;.&#x62f;',
		'IRR' => '&#xfdfc;',
		'ISK' => 'Kr.',
		'JEP' => '&pound;',
		'JMD' => '&#36;',
		'JOD' => '&#x62f;.&#x627;',
		'JPY' => '&yen;',
		'KES' => 'KSh',
		'KGS' => '&#x43b;&#x432;',
		'KHR' => '&#x17db;',
		'KMF' => 'Fr',
		'KPW' => '&#x20a9;',
		'KRW' => '&#8361;',
		'KWD' => '&#x62f;.&#x643;',
		'KYD' => '&#36;',
		'KZT' => 'KZT',
		'LAK' => '&#8365;',
		'LBP' => '&#x644;.&#x644;',
		'LKR' => '&#xdbb;&#xdd4;',
		'LRD' => '&#36;',
		'LSL' => 'L',
		'LYD' => '&#x644;.&#x62f;',
		'MAD' => '&#x62f;. &#x645;.',
		'MAD' => '&#x62f;.&#x645;.',
		'MDL' => 'L',
		'MGA' => 'Ar',
		'MKD' => '&#x434;&#x435;&#x43d;',
		'MMK' => 'Ks',
		'MNT' => '&#x20ae;',
		'MOP' => 'P',
		'MRO' => 'UM',
		'MUR' => '&#x20a8;',
		'MVR' => '.&#x783;',
		'MWK' => 'MK',
		'MXN' => '&#36;',
		'MYR' => '&#82;&#77;',
		'MZN' => 'MT',
		'NAD' => '&#36;',
		'NGN' => '&#8358;',
		'NIO' => 'C&#36;',
		'NOK' => '&#107;&#114;',
		'NPR' => '&#8360;',
		'NZD' => '&#36;',
		'OMR' => '&#x631;.&#x639;.',
		'PAB' => 'B/.',
		'PEN' => 'S/.',
		'PGK' => 'K',
		'PHP' => '&#8369;',
		'PKR' => '&#8360;',
		'PLN' => '&#122;&#322;',
		'PRB' => '&#x440;.',
		'PYG' => '&#8370;',
		'QAR' => '&#x631;.&#x642;',
		'RMB' => '&yen;',
		'RON' => 'lei',
		'RSD' => '&#x434;&#x438;&#x43d;.',
		'RUB' => '&#8381;',
		'RWF' => 'Fr',
		'SAR' => '&#x631;.&#x633;',
		'SBD' => '&#36;',
		'SCR' => '&#x20a8;',
		'SDG' => '&#x62c;.&#x633;.',
		'SEK' => '&#107;&#114;',
		'SGD' => '&#36;',
		'SHP' => '&pound;',
		'SLL' => 'Le',
		'SOS' => 'Sh',
		'SRD' => '&#36;',
		'SSP' => '&pound;',
		'STD' => 'Db',
		'SYP' => '&#x644;.&#x633;',
		'SZL' => 'L',
		'THB' => '&#3647;',
		'TJS' => '&#x405;&#x41c;',
		'TMT' => 'm',
		'TND' => '&#x62f;.&#x62a;',
		'TOP' => 'T&#36;',
		'TRY' => '&#8378;',
		'TTD' => '&#36;',
		'TWD' => '&#78;&#84;&#36;',
		'TZS' => 'Sh',
		'UAH' => '&#8372;',
		'UGX' => 'UGX',
		'USD' => '&#36;',
		'UYU' => '&#36;',
		'UZS' => 'UZS',
		'VEF' => 'Bs F',
		'VND' => '&#8363;',
		'VUV' => 'Vt',
		'WST' => 'T',
		'XAF' => 'Fr',
		'XCD' => '&#36;',
		'XOF' => 'Fr',
		'XPF' => 'Fr',
		'YER' => '&#xfdfc;',
		'ZAR' => '&#82;',
		'ZMW' => 'ZK',
	) );

	$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

	return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
}

/**
 * Send HTML emails from WooCommerce.
 *
 * @param mixed $to
 * @param mixed $subject
 * @param mixed $message
 * @param string $headers (default: "Content-Type: text/html\r\n")
 * @param string $attachments (default: "")
 */
function wc_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	$mailer = WC()->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Get an image size.
 *
 * Variable is filtered by woocommerce_get_image_size_{image_size}.
 *
 * @param mixed $image_size
 * @return array
 */
function wc_get_image_size( $image_size ) {
	if ( is_array( $image_size ) ) {
		$width  = isset( $image_size[0] ) ? $image_size[0] : '300';
		$height = isset( $image_size[1] ) ? $image_size[1] : '300';
		$crop   = isset( $image_size[2] ) ? $image_size[2] : 1;

		$size = array(
			'width'  => $width,
			'height' => $height,
			'crop'   => $crop
		);

		$image_size = $width . '_' . $height;

	} elseif ( in_array( $image_size, array( 'shop_thumbnail', 'shop_catalog', 'shop_single' ) ) ) {
		$size           = get_option( $image_size . '_image_size', array() );
		$size['width']  = isset( $size['width'] ) ? $size['width'] : '300';
		$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
		$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 0;

	} else {
		$size = array(
			'width'  => '300',
			'height' => '300',
			'crop'   => 1
		);
	}

	return apply_filters( 'woocommerce_get_image_size_' . $image_size, $size );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code
 */
function wc_enqueue_js( $code ) {
	global $wc_queued_js;

	if ( empty( $wc_queued_js ) ) {
		$wc_queued_js = '';
	}

	$wc_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function wc_print_js() {
	global $wc_queued_js;

	if ( ! empty( $wc_queued_js ) ) {
		// Sanitize.
		$wc_queued_js = wp_check_invalid_utf8( $wc_queued_js );
		$wc_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $wc_queued_js );
		$wc_queued_js = str_replace( "\r", '', $wc_queued_js );

		$js = "<!-- WooCommerce JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $wc_queued_js });\n</script>\n";

		/**
		 * woocommerce_queued_js filter.
		 *
		 * @since 2.6.0
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'woocommerce_queued_js', $js );

		unset( $wc_queued_js );
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @param  string  $name   Name of the cookie being set.
 * @param  string  $value  Value of the cookie.
 * @param  integer $expire Expiry of the cookie.
 * @param  string  $secure Whether the cookie should be served only over https.
 */
function wc_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	}
}

/**
 * Get the URL to the WooCommerce REST API.
 *
 * @since 2.1
 * @param string $path an endpoint to include in the URL.
 * @return string the URL.
 */
function get_woocommerce_api_url( $path ) {
	$version  = defined( 'WC_API_REQUEST_VERSION' ) ? WC_API_REQUEST_VERSION : substr( WC_API::VERSION, 0, 1 );

	$url = get_home_url( null, "wc-api/v{$version}/", is_ssl() ? 'https' : 'http' );

	if ( ! empty( $path ) && is_string( $path ) ) {
		$url .= ltrim( $path, '/' );
	}

	return $url;
}

/**
 * Get a log file path.
 *
 * @since 2.2
 * @param string $handle name.
 * @return string the log file path.
 */
function wc_get_log_file_path( $handle ) {
	return trailingslashit( WC_LOG_DIR ) . $handle . '-' . sanitize_file_name( wp_hash( $handle ) ) . '.log';
}

/**
 * Init for our rewrite rule fixes.
 */
function wc_fix_rewrite_rules_init() {
	$permalinks = get_option( 'woocommerce_permalinks' );

	if ( ! empty( $permalinks['use_verbose_page_rules'] ) ) {
		$GLOBALS['wp_rewrite']->use_verbose_page_rules = true;
	}
}
add_action( 'init', 'wc_fix_rewrite_rules_init' );

/**
 * Various rewrite rule fixes.
 *
 * @since 2.2
 * @param array $rules
 * @return array
 */
function wc_fix_rewrite_rules( $rules ) {
	global $wp_rewrite;

	$permalinks        = get_option( 'woocommerce_permalinks' );
	$product_permalink = empty( $permalinks['product_base'] ) ? _x( 'product', 'slug', 'woocommerce' ) : $permalinks['product_base'];

	// Fix the rewrite rules when the product permalink have %product_cat% flag.
	if ( preg_match( '`/(.+)(/%product_cat%)`' , $product_permalink, $matches ) ) {
		foreach ( $rules as $rule => $rewrite ) {

			if ( preg_match( '`^' . preg_quote( $matches[1], '`' ) . '/\(`', $rule ) && preg_match( '/^(index\.php\?product_cat)(?!(.*product))/', $rewrite ) ) {
				unset( $rules[ $rule ] );
			}
		}
	}

	// If the shop page is used as the base, we need to enable verbose rewrite rules or sub pages will 404.
	if ( ! empty( $permalinks['use_verbose_page_rules'] ) ) {
		$page_rewrite_rules = $wp_rewrite->page_rewrite_rules();
		$rules              = array_merge( $page_rewrite_rules, $rules );
	}

	return $rules;
}
add_filter( 'rewrite_rules_array', 'wc_fix_rewrite_rules' );

/**
 * Prevent product attachment links from breaking when using complex rewrite structures.
 *
 * @param  string $link
 * @param  id $post_id
 * @return string
 */
function wc_fix_product_attachment_link( $link, $post_id ) {
	global $wp_rewrite;

	$post = get_post( $post_id );
	if ( 'product' === get_post_type( $post->post_parent ) ) {
		$permalinks        = get_option( 'woocommerce_permalinks' );
		$product_permalink = empty( $permalinks['product_base'] ) ? _x( 'product', 'slug', 'woocommerce' ) : $permalinks['product_base'];
		if ( preg_match( '/\/(.+)(\/%product_cat%)$/' , $product_permalink, $matches ) ) {
			$link = home_url( '/?attachment_id=' . $post->ID );
		}
	}
	return $link;
}
add_filter( 'attachment_link', 'wc_fix_product_attachment_link', 10, 2 );

/**
 * Protect downloads from ms-files.php in multisite.
 *
 * @param mixed $rewrite
 * @return string
 */
function wc_ms_protect_download_rewite_rules( $rewrite ) {
	if ( ! is_multisite() || 'redirect' == get_option( 'woocommerce_file_download_method' ) ) {
		return $rewrite;
	}

	$rule  = "\n# WooCommerce Rules - Protect Files from ms-files.php\n\n";
	$rule .= "<IfModule mod_rewrite.c>\n";
	$rule .= "RewriteEngine On\n";
	$rule .= "RewriteCond %{QUERY_STRING} file=woocommerce_uploads/ [NC]\n";
	$rule .= "RewriteRule /ms-files.php$ - [F]\n";
	$rule .= "</IfModule>\n\n";

	return $rule . $rewrite;
}
add_filter( 'mod_rewrite_rules', 'wc_ms_protect_download_rewite_rules' );

/**
 * WooCommerce Core Supported Themes.
 *
 * @since 2.2
 * @return string[]
 */
function wc_get_core_supported_themes() {
	return array( 'twentysixteen', 'twentyfifteen', 'twentyfourteen', 'twentythirteen', 'twentyeleven', 'twentytwelve', 'twentyten' );
}

/**
 * Wrapper function to execute the `woocommerce_deliver_webhook_async` cron.
 * hook, see WC_Webhook::process().
 *
 * @since 2.2
 * @param int $webhook_id webhook ID to deliver.
 * @param mixed $arg hook argument.
 */
function wc_deliver_webhook_async( $webhook_id, $arg ) {

	$webhook = new WC_Webhook( $webhook_id );

	$webhook->deliver( $arg );
}
add_action( 'woocommerce_deliver_webhook_async', 'wc_deliver_webhook_async', 10, 2 );

/**
 * Enables template debug mode.
 */
function wc_template_debug_mode() {
	if ( ! defined( 'WC_TEMPLATE_DEBUG_MODE' ) ) {
		$status_options = get_option( 'woocommerce_status_options', array() );
		if ( ! empty( $status_options['template_debug_mode'] ) && current_user_can( 'manage_options' ) ) {
			define( 'WC_TEMPLATE_DEBUG_MODE', true );
		} else {
			define( 'WC_TEMPLATE_DEBUG_MODE', false );
		}
	}
}
add_action( 'after_setup_theme', 'wc_template_debug_mode', 20 );

/**
 * Formats a string in the format COUNTRY:STATE into an array.
 *
 * @since 2.3.0
 * @param  string $country_string
 * @return array
 */
function wc_format_country_state_string( $country_string ) {
	if ( strstr( $country_string, ':' ) ) {
		list( $country, $state ) = explode( ':', $country_string );
	} else {
		$country = $country_string;
		$state   = '';
	}
	return array(
		'country' => $country,
		'state'   => $state
	);
}

/**
 * Get the store's base location.
 *
 * @todo should the woocommerce_default_country option be renamed to contain 'base'?
 * @since 2.3.0
 * @return array
 */
function wc_get_base_location() {
	$default = apply_filters( 'woocommerce_get_base_location', get_option( 'woocommerce_default_country' ) );

	return wc_format_country_state_string( $default );
}

/**
 * Get the customer's default location.
 *
 * Filtered, and set to base location or left blank. If cache-busting,
 * this should only be used when 'location' is set in the querystring.
 *
 * @todo should the woocommerce_default_country option be renamed to contain 'base'?
 * @todo deprecate woocommerce_customer_default_location and support an array filter only to cover all cases.
 * @since 2.3.0
 * @return array
 */
function wc_get_customer_default_location() {
	$location = array();

	switch ( get_option( 'woocommerce_default_customer_address' ) ) {
		case 'geolocation_ajax' :
		case 'geolocation' :
			// Exclude common bots from geolocation by user agent.
			$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';

			if ( ! strstr( $ua, 'bot' ) && ! strstr( $ua, 'spider' ) && ! strstr( $ua, 'crawl' ) ) {
				$location = WC_Geolocation::geolocate_ip( '', true, false );
			}

			// Base fallback.
			if ( empty( $location['country'] ) ) {
				$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
			}
		break;
		case 'base' :
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) ) );
		break;
		default :
			$location = wc_format_country_state_string( apply_filters( 'woocommerce_customer_default_location', '' ) );
		break;
	}

	return apply_filters( 'woocommerce_customer_default_location_array', $location );
}

// This function can be removed when WP 3.9.2 or greater is required.
if ( ! function_exists( 'hash_equals' ) ) :
	/**
	 * Compare two strings in constant time.
	 *
	 * This function was added in PHP 5.6.
	 * It can leak the length of a string.
	 *
	 * @since 3.9.2
	 *
	 * @param string $a Expected string.
	 * @param string $b Actual string.
	 * @return bool Whether strings are equal.
	 */
	function hash_equals( $a, $b ) {
		$a_length = strlen( $a );
		if ( $a_length !== strlen( $b ) ) {
			return false;
		}
		$result = 0;

		// Do not attempt to "optimize" this.
		for ( $i = 0; $i < $a_length; $i++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return $result === 0;
	}
endif;

/**
 * Generate a rand hash.
 *
 * @since  2.4.0
 * @return string
 */
function wc_rand_hash() {
	if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return bin2hex( openssl_random_pseudo_bytes( 20 ) );
	} else {
		return sha1( wp_rand() );
	}
}

/**
 * WC API - Hash.
 *
 * @since  2.4.0
 * @param  string $data
 * @return string
 */
function wc_api_hash( $data ) {
	return hash_hmac( 'sha256', $data, 'wc-api' );
}

/**
 * Find all possible combinations of values from the input array and return in a logical order.
 * @since 2.5.0
 * @param array $input
 * @return array
 */
function wc_array_cartesian( $input ) {
	$input   = array_filter( $input );
	$results = array();
	$indexes = array();
	$index   = 0;

	// Generate indexes from keys and values so we have a logical sort order
	foreach ( $input as $key => $values ) {
		foreach ( $values as $value ) {
			$indexes[ $key ][ $value ] = $index++;
		}
	}

	// Loop over the 2D array of indexes and generate all combinations
	foreach ( $indexes as $key => $values ) {
		// When result is empty, fill with the values of the first looped array
		if ( empty( $results ) ) {
			foreach ( $values as $value ) {
				$results[] = array( $key => $value );
			}

		// Second and subsequent input sub-array merging.
		} else {
			foreach ( $results as $result_key => $result ) {
				foreach ( $values as $value ) {
					// If the key is not set, we can set it
					if ( ! isset( $results[ $result_key ][ $key ] ) ) {
						$results[ $result_key ][ $key ] = $value;
					// If the key is set, we can add a new combination to the results array
					} else {
						$new_combination         = $results[ $result_key ];
						$new_combination[ $key ] = $value;
						$results[]               = $new_combination;
					}
				}
			}
		}
	}

	// Sort the indexes
	arsort( $results );

	// Convert indexes back to values
	foreach ( $results as $result_key => $result ) {
		$converted_values = array();

		// Sort the values
		arsort( $results[ $result_key ] );

		// Convert the values
		foreach ( $results[ $result_key ] as $key => $value ) {
			$converted_values[ $key ] = array_search( $value, $indexes[ $key ] );
		}

		$results[ $result_key ] = $converted_values;
	}

	return $results;
}

/**
 * Run a MySQL transaction query, if supported.
 * @param string $type start (default), commit, rollback
 * @since 2.5.0
 */
function wc_transaction_query( $type = 'start' ) {
	global $wpdb;

	$wpdb->hide_errors();

	if ( ! defined( 'WC_USE_TRANSACTIONS' ) ) {
		define( 'WC_USE_TRANSACTIONS', true );
	}

	if ( WC_USE_TRANSACTIONS ) {
		switch ( $type ) {
			case 'commit' :
				$wpdb->query( 'COMMIT' );
				break;
			case 'rollback' :
				$wpdb->query( 'ROLLBACK' );
				break;
			default :
				$wpdb->query( 'START TRANSACTION' );
			break;
		}
	}
}

/**
 * Gets the url to the cart page.
 *
 * @since  2.5.0
 *
 * @return string Url to cart page
 */
function wc_get_cart_url() {
	return apply_filters( 'woocommerce_get_cart_url', wc_get_page_permalink( 'cart' ) );
}

/**
 * Gets the url to the checkout page.
 *
 * @since  2.5.0
 *
 * @return string Url to checkout page
 */
function wc_get_checkout_url() {
	$checkout_url = wc_get_page_permalink( 'checkout' );
	if ( $checkout_url ) {
		// Force SSL if needed
		if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
			$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
		}
	}

	return apply_filters( 'woocommerce_get_checkout_url', $checkout_url );
}

/**
 * Register a shipping method.
 *
 * @since 1.5.7
 * @param string|object $shipping_method class name (string) or a class object.
 */
function woocommerce_register_shipping_method( $shipping_method ) {
	WC()->shipping->register_shipping_method( $shipping_method );
}

if ( ! function_exists( 'wc_get_shipping_zone' ) ) {
	/**
	 * Get the shipping zone matching a given package from the cart.
	 *
	 * @since  2.6.0
	 * @uses   WC_Shipping_Zones::get_zone_matching_package
	 * @param  array $package
	 * @return WC_Shipping_Zone
	 */
	function wc_get_shipping_zone( $package ) {
		return WC_Shipping_Zones::get_zone_matching_package( $package );
	}
}

/**
 * Get a nice name for credit card providers.
 *
 * @since  2.6.0
 * @param  string $type Provider Slug/Type
 * @return string
 */
function wc_get_credit_card_type_label( $type ) {
	// Normalize
	$type = strtolower( $type );
	$type = str_replace( '-', ' ', $type );
	$type = str_replace( '_', ' ', $type );

	$labels = apply_filters( 'wocommerce_credit_card_type_labels', array(
		'mastercard'       => __( 'MasterCard', 'woocommerce' ),
		'visa'             => __( 'Visa', 'woocommerce' ),
		'discover'         => __( 'Discover', 'woocommerce' ),
		'american express' => __( 'American Express', 'woocommerce' ),
		'diners'           => __( 'Diners', 'woocommerce' ),
		'jcb'              => __( 'JCB', 'woocommerce' ),
	) );

	return apply_filters( 'woocommerce_get_credit_card_type_label', ( array_key_exists( $type, $labels ) ? $labels[ $type ] : ucfirst( $type ) ) );
}

/**
 * Outputs a "back" link so admin screens can easily jump back a page.
 *
 * @param string $label Title of the page to return to.
 * @param string $url   URL of the page to return to.
 */
function wc_back_link( $label, $url ) {
	echo '<small class="wc-admin-breadcrumb"><a href="' . esc_url( $url ) . '" title="' . esc_attr( $label ) . '">&#x2934;</a></small>';
}

/**
 * Display a WooCommerce help tip.
 *
 * @since  2.5.0
 *
 * @param  string $tip        Help tip text
 * @param  bool   $allow_html Allow sanitized HTML if true or escape
 * @return string
 */
function wc_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = wc_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="woocommerce-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Return a list of potential postcodes for wildcard searching.
 * @since 2.6.0
 * @param  string $postcode
 * @param  string $country to format postcode for matching.
 * @return string[]
 */
function wc_get_wildcard_postcodes( $postcode, $country = '' ) {
	$postcodes       = array( $postcode );
	$postcode        = wc_format_postcode( $postcode, $country );
	$postcodes[]     = $postcode;
	$postcode_length = strlen( $postcode );

	for ( $i = 0; $i < $postcode_length; $i ++ ) {
		$postcodes[] = substr( $postcode, 0, ( $i + 1 ) * -1 ) . '*';
	}

	return $postcodes;
}

/**
 * Used by shipping zones and taxes to compare a given $postcode to stored
 * postcodes to find matches for numerical ranges, and wildcards.
 * @since 2.6.0
 * @param string $postcode Postcode you want to match against stored postcodes
 * @param array  $objects Array of postcode objects from Database
 * @param string $object_id_key DB column name for the ID.
 * @param string $object_compare_key DB column name for the value.
 * @param string $country Country from which this postcode belongs. Allows for formatting.
 * @return array Array of matching object ID and matching values.
 */
function wc_postcode_location_matcher( $postcode, $objects, $object_id_key, $object_compare_key, $country = '' ) {
	$postcode           = wc_normalize_postcode( $postcode );
	$wildcard_postcodes = array_map( 'wc_clean', wc_get_wildcard_postcodes( $postcode, $country ) );
	$matches            = array();

	foreach ( $objects as $object ) {
		$object_id       = $object->$object_id_key;
		$compare_against = $object->$object_compare_key;

		// Handle postcodes containing ranges.
		if ( strstr( $compare_against, '...' ) ) {
			$range = array_map( 'trim', explode( '...', $compare_against ) );

			if ( 2 !== sizeof( $range ) ) {
				continue;
			}

			list( $min, $max ) = $range;

			// If the postcode is non-numeric, make it numeric.
			if ( ! is_numeric( $min ) || ! is_numeric( $max ) ) {
				$compare = wc_make_numeric_postcode( $postcode );
				$min     = str_pad( wc_make_numeric_postcode( $min ), strlen( $compare ), '0' );
				$max     = str_pad( wc_make_numeric_postcode( $max ), strlen( $compare ), '0' );
			} else {
				$compare = $postcode;
			}

			if ( $compare >= $min && $compare <= $max ) {
				$matches[ $object_id ]   = isset( $matches[ $object_id ] ) ? $matches[ $object_id ]: array();
				$matches[ $object_id ][] = $compare_against;
			}

		// Wildcard and standard comparison.
		} elseif ( in_array( $compare_against, $wildcard_postcodes ) ) {
			$matches[ $object_id ]   = isset( $matches[ $object_id ] ) ? $matches[ $object_id ]: array();
			$matches[ $object_id ][] = $compare_against;
		}
	}

	return $matches;
}

/**
 * Gets number of shipping methods currently enabled. Used to identify if
 * shipping is configured.
 *
 * @since  2.6.0
 * @param  bool $include_legacy Count legacy shipping methods too.
 * @return int
 */
function wc_get_shipping_method_count( $include_legacy = false ) {
	global $wpdb;

	$transient_name = 'wc_shipping_method_count_' . ( $include_legacy ? 1 : 0 ) . '_' . WC_Cache_Helper::get_transient_version( 'shipping' );
	$method_count   = get_transient( $transient_name );

	if ( false === $method_count ) {
		$method_count = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods" ) );

		if ( $include_legacy ) {
			// Count activated methods that don't support shipping zones.
			$methods = WC()->shipping->get_shipping_methods();

			foreach ( $methods as $method ) {
				if ( isset( $method->enabled ) && 'yes' === $method->enabled && ! $method->supports( 'shipping-zones' ) ) {
					$method_count++;
				}
			}
		}

		set_transient( $transient_name, $method_count, DAY_IN_SECONDS * 30 );
	}

	return absint( $method_count );
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 * @since 2.6.0
 */
function wc_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( $limit );
	}
}

/**
 * Used to sort products attributes with uasort.
 * @since 2.6.0
 */
function wc_product_attribute_uasort_comparison( $a, $b ) {
	if ( $a['position'] == $b['position'] ) {
		return 0;
	}
	return ( $a['position'] < $b['position'] ) ? -1 : 1;
}

/**
 * Get rounding precision for internal WC calculations.
 * Will increase the precision of wc_get_price_decimals by 2 decimals, unless WC_ROUNDING_PRECISION is set to a higher number.
 *
 * @since 2.6.3
 * @return int
 */
function wc_get_rounding_precision() {
	$precision = wc_get_price_decimals() + 2;
	if ( absint( WC_ROUNDING_PRECISION ) > $precision ) {
		$precision = absint( WC_ROUNDING_PRECISION );
	}
	return $precision;
}
