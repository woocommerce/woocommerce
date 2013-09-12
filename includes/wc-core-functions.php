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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Include core functions
include( 'wc-cart-functions.php' );
include( 'wc-conditional-functions.php' );
include( 'wc-coupon-functions.php' );
include( 'wc-customer-functions.php' );
include( 'wc-deprecated-functions.php' );
include( 'wc-formatting-functions.php' );
include( 'wc-message-functions.php' );
include( 'wc-order-functions.php' );
include( 'wc-page-functions.php' );
include( 'wc-product-functions.php' );
include( 'wc-term-functions.php' );
include( 'wc-attribute-functions.php' );

/**
 * Filters on data used in admin and frontend
 */
add_filter( 'woocommerce_coupon_code', 'sanitize_text_field' );
add_filter( 'woocommerce_coupon_code', 'strtolower' ); // Coupons case-insensitive by default
add_filter( 'woocommerce_stock_amount', 'intval' ); // Stock amounts are integers by default

/**
 * Short Description (excerpt)
 */
add_filter( 'woocommerce_short_description', 'wptexturize' );
add_filter( 'woocommerce_short_description', 'convert_smilies' );
add_filter( 'woocommerce_short_description', 'convert_chars' );
add_filter( 'woocommerce_short_description', 'wpautop' );
add_filter( 'woocommerce_short_description', 'shortcode_unautop' );
add_filter( 'woocommerce_short_description', 'prepend_attachment' );
add_filter( 'woocommerce_short_description', 'do_shortcode', 11 ); // AFTER wpautop()

/**
 * Get Base Currency Code.
 * @return string
 */
function get_woocommerce_currency() {
	return apply_filters( 'woocommerce_currency', get_option('woocommerce_currency') );
}

/**
 * Get full list of currency codes.
 * @return array
 */
function get_woocommerce_currencies() {
	return array_unique(
		apply_filters( 'woocommerce_currencies',
			array(
				'AUD' => __( 'Australian Dollars', 'woocommerce' ),
				'BRL' => __( 'Brazilian Real', 'woocommerce' ),
				'BGN' => __( 'Bulgarian Lev', 'woocommerce' ),
				'CAD' => __( 'Canadian Dollars', 'woocommerce' ),
				'CNY' => __( 'Chinese Yuan', 'woocommerce' ),
				'CZK' => __( 'Czech Koruna', 'woocommerce' ),
				'DKK' => __( 'Danish Krone', 'woocommerce' ),
				'EUR' => __( 'Euros', 'woocommerce' ),
				'HKD' => __( 'Hong Kong Dollar', 'woocommerce' ),
				'HUF' => __( 'Hungarian Forint', 'woocommerce' ),
				'IDR' => __( 'Indonesia Rupiah', 'woocommerce' ),
				'INR' => __( 'Indian Rupee', 'woocommerce' ),
				'ILS' => __( 'Israeli Shekel', 'woocommerce' ),
				'JPY' => __( 'Japanese Yen', 'woocommerce' ),
				'KRW' => __( 'South Korean Won', 'woocommerce' ),
				'MYR' => __( 'Malaysian Ringgits', 'woocommerce' ),
				'MXN' => __( 'Mexican Peso', 'woocommerce' ),
				'NOK' => __( 'Norwegian Krone', 'woocommerce' ),
				'NZD' => __( 'New Zealand Dollar', 'woocommerce' ),
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
			)
		)
	);
}

/**
 * Get Currency symbol.
 * @param string $currency (default: '')
 * @return string
 */
function get_woocommerce_currency_symbol( $currency = '' ) {
	if ( ! $currency )
		$currency = get_woocommerce_currency();

	switch ( $currency ) {
		case 'BRL' :
			$currency_symbol = '&#82;&#36;';
			break;
		case 'BGN' :
			$currency_symbol = '&#1083;&#1074;.';
			break;
		case 'AUD' :
		case 'CAD' :
		case 'MXN' :
		case 'NZD' :
		case 'HKD' :
		case 'SGD' :
		case 'USD' :
			$currency_symbol = '&#36;';
			break;
		case 'EUR' :
			$currency_symbol = '&euro;';
			break;
		case 'CNY' :
		case 'RMB' :
		case 'JPY' :
			$currency_symbol = '&yen;';
			break;
		case 'RUB' :
			$currency_symbol = '&#1088;&#1091;&#1073;.';
			break;
		case 'KRW' : $currency_symbol = '&#8361;'; break;
		case 'TRY' : $currency_symbol = '&#84;&#76;'; break;
		case 'NOK' : $currency_symbol = '&#107;&#114;'; break;
		case 'ZAR' : $currency_symbol = '&#82;'; break;
		case 'CZK' : $currency_symbol = '&#75;&#269;'; break;
		case 'MYR' : $currency_symbol = '&#82;&#77;'; break;
		case 'DKK' : $currency_symbol = '&#107;&#114;'; break;
		case 'HUF' : $currency_symbol = '&#70;&#116;'; break;
		case 'IDR' : $currency_symbol = 'Rp'; break;
		case 'INR' : $currency_symbol = 'Rs.'; break;
		case 'ILS' : $currency_symbol = '&#8362;'; break;
		case 'PHP' : $currency_symbol = '&#8369;'; break;
		case 'PLN' : $currency_symbol = '&#122;&#322;'; break;
		case 'SEK' : $currency_symbol = '&#107;&#114;'; break;
		case 'CHF' : $currency_symbol = '&#67;&#72;&#70;'; break;
		case 'TWD' : $currency_symbol = '&#78;&#84;&#36;'; break;
		case 'THB' : $currency_symbol = '&#3647;'; break;
		case 'GBP' : $currency_symbol = '&pound;'; break;
		case 'RON' : $currency_symbol = 'lei'; break;
		case 'VND' : $currency_symbol = '&#8363;'; break;
		default    : $currency_symbol = ''; break;
	}

	return apply_filters( 'woocommerce_currency_symbol', $currency_symbol, $currency );
}

/**
 * Send HTML emails from WooCommerce
 *
 * @param mixed $to
 * @param mixed $subject
 * @param mixed $message
 * @param string $headers (default: "Content-Type: text/html\r\n")
 * @param string $attachments (default: "")
 */
function woocommerce_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	global $woocommerce;

	$mailer = $woocommerce->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Get an image size.
 *
 * Variable is filtered by woocommerce_get_image_size_{image_size}
 *
 * @param string $image_size
 * @return array
 */
function wc_get_image_size( $image_size ) {
	if ( in_array( $image_size, array( 'shop_thumbnail', 'shop_catalog', 'shop_single' ) ) ) {
		$size           = get_option( $image_size . '_image_size', array() );
		$size['width']  = isset( $size['width'] ) ? $size['width'] : '300';
		$size['height'] = isset( $size['height'] ) ? $size['height'] : '300';
		$size['crop']   = isset( $size['crop'] ) ? $size['crop'] : 1;
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

	if ( empty( $wc_queued_js ) )
		$wc_queued_js = "";

	$wc_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function wc_print_js() {
	global $wc_queued_js;

	if ( ! empty( $wc_queued_js ) ) {

		echo "<!-- WooCommerce JavaScript-->\n<script type=\"text/javascript\">\njQuery(document).ready(function($) {";

		// Sanitize
		$wc_queued_js = wp_check_invalid_utf8( $wc_queued_js );
		$wc_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $wc_queued_js );
		$wc_queued_js = str_replace( "\r", '', $wc_queued_js );

		echo $wc_queued_js . "});\n</script>\n";

		unset( $wc_queued_js );
	}
}