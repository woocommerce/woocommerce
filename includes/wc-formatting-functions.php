<?php
/**
 * WooCommerce Formatting
 *
 * Functions for formatting data.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Sanitize taxonomy names. Slug format (no spaces, lowercase).
 *
 * Doesn't use sanitize_title as this destroys utf chars.
 *
 * @access public
 * @param mixed $taxonomy
 * @return void
 */
function woocommerce_sanitize_taxonomy_name( $taxonomy ) {
	$filtered = strtolower( remove_accents( stripslashes( strip_tags( $taxonomy ) ) ) );
	$filtered = preg_replace( '/&.+?;/', '', $filtered ); // Kill entities
	$filtered = str_replace( array( '.', '\'', '"' ), '', $filtered ); // Kill quotes and full stops.
	$filtered = str_replace( array( ' ', '_' ), '-', $filtered ); // Replace spaces and underscores.

	return apply_filters( 'sanitize_taxonomy_name', $filtered, $taxonomy );
}

/**
 * Gets the filename part of a download URL
 *
 * @access public
 * @param string $file_url
 * @return string
 */
function woocommerce_get_filename_from_url( $file_url ) {
	$parts = parse_url( $file_url );
	return basename( $parts['path'] );
}

/**
 * woocommerce_get_dimension function.
 *
 * Normalise dimensions, unify to cm then convert to wanted unit value
 *
 * Usage: woocommerce_get_dimension(55, 'in');
 *
 * @access public
 * @param mixed $dim
 * @param mixed $to_unit 'in', 'm', 'cm', 'm'
 * @return float
 */
function woocommerce_get_dimension( $dim, $to_unit ) {

	$from_unit 	= strtolower( get_option( 'woocommerce_dimension_unit' ) );
	$to_unit	= strtolower( $to_unit );

	// Unify all units to cm first
	if ( $from_unit !== $to_unit ) {

		switch ( $from_unit ) {
			case 'in':
				$dim *= 2.54;
			break;
			case 'm':
				$dim *= 100;
			break;
			case 'mm':
				$dim *= 0.1;
			break;
			case 'yd':
				$dim *= 0.010936133;
			break;
		}

		// Output desired unit
		switch ( $to_unit ) {
			case 'in':
				$dim *= 0.3937;
			break;
			case 'm':
				$dim *= 0.01;
			break;
			case 'mm':
				$dim *= 10;
			break;
			case 'yd':
				$dim *= 91.44;
			break;
		}
	}
	return ( $dim < 0 ) ? 0 : $dim;
}

/**
 * woocommerce_get_weight function.
 *
 * Normalise weights, unify to cm then convert to wanted unit value
 *
 * Usage: woocommerce_get_weight(55, 'kg');
 *
 * @access public
 * @param mixed $weight
 * @param mixed $to_unit 'g', 'kg', 'lbs'
 * @return float
 */
function woocommerce_get_weight( $weight, $to_unit ) {

	$from_unit 	= strtolower( get_option('woocommerce_weight_unit') );
	$to_unit	= strtolower( $to_unit );

	//Unify all units to kg first
	if ( $from_unit !== $to_unit ) {

		switch ( $from_unit ) {
			case 'g':
				$weight *= 0.001;
			break;
			case 'lbs':
				$weight *= 0.4536;
			break;
			case 'oz':
				$weight *= 0.0283;
			break;
		}

		// Output desired unit
		switch ( $to_unit ) {
			case 'g':
				$weight *= 1000;
			break;
			case 'lbs':
				$weight *= 2.2046;
			break;
			case 'oz':
				$weight *= 35.274;
			break;
		}
	}
	return ( $weight < 0 ) ? 0 : $weight;
}

/**
 * Trim trailing zeros off prices.
 *
 * @access public
 * @param mixed $price
 * @return string
 */
function woocommerce_trim_zeros( $price ) {
	return preg_replace( '/' . preg_quote( get_option( 'woocommerce_price_decimal_sep' ), '/' ) . '0++$/', '', $price );
}

/**
 * Round a tax amount
 *
 * @access public
 * @param mixed $price
 * @return string
 */
function woocommerce_round_tax_total( $tax ) {
	$dp                   = (int) get_option( 'woocommerce_price_num_decimals' );
	return round( $tax, $dp, WOOCOMMERCE_TAX_ROUNDING_MODE );
}

/**
 * Format decimal numbers - format to a defined number of dp and remove trailing zeros.
 *
 * Remove's user locale settings, converting to a string which uses '.' for decimals, with no thousand separators.
 *
 * @param  float|string $number
 * @param  mixed $dp number of decimal points to use, blank to use woocommerce_price_num_decimals, or false to avoid all rounding.
 * @param  boolean $trim_zeros from end of string
 * @return string
 */
function woocommerce_format_decimal( $number, $dp = '', $trim_zeros = false ) {
	$number = floatval( $number );

	// DP is false - don't use number format, just remove locale settings from the number
	if ( $dp === false ) {
		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		// Only allow numbers and the decimal point
		if ( strstr( $number, $decimal ) )
			$number = preg_replace( "/[^0-9\\" . $decimal . "]/", "", strval( $number ) );
		else
			$number = preg_replace( "/[^0-9.]/", "", strval( $number ) );

		// Replace decimal point with .
		$number = str_replace( $decimal, '.', $number );
	} else {
		$dp     = ( $dp == "" ) ? intval( get_option( 'woocommerce_price_num_decimals' ) ) : intval( $dp );
		$number = number_format( $number, $dp, '.', '' );
	}

	if ( $trim_zeros && strstr( $number, '.' ) )
		$number = rtrim( rtrim( $number, '0' ), '.' );

	return $number;
}

/**
 * Formal total costs - format to the number of decimal places for the base currency.
 *
 * @access public
 * @param mixed $number
 * @return string
 */
function woocommerce_format_total( $number ) {
	return woocommerce_format_decimal( $number, '', false );
}

/**
 * Clean variables
 *
 * @access public
 * @param string $var
 * @return string
 */
function woocommerce_clean( $var ) {
	return sanitize_text_field( $var );
}

/**
 * Merge two arrays
 *
 * @access public
 * @param array $a1
 * @param array $a2
 * @return array
 */
function woocommerce_array_overlay( $a1, $a2 ) {
    foreach( $a1 as $k => $v ) {
        if ( ! array_key_exists( $k, $a2 ) )
        	continue;
        if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
            $a1[ $k ] = woocommerce_array_overlay( $v, $a2[ $k ] );
        } else {
            $a1[ $k ] = $a2[ $k ];
        }
    }
    return $a1;
}

/**
 * Get the price format depending on the currency position
 *
 * @return string
 */
function get_woocommerce_price_format() {
	$currency_pos = get_option( 'woocommerce_currency_pos' );

	switch ( $currency_pos ) {
		case 'left' :
			$format = '%1$s%2$s';
		break;
		case 'right' :
			$format = '%2$s%1$s';
		break;
		case 'left_space' :
			$format = '%1$s&nbsp;%2$s';
		break;
		case 'right_space' :
			$format = '%2$s&nbsp;%1$s';
		break;
	}

	return apply_filters( 'woocommerce_price_format', $format, $currency_pos );
}

/**
 * Format the price with a currency symbol.
 *
 * @access public
 * @param float $price
 * @param array $args (default: array())
 * @return string
 */
function woocommerce_price( $price, $args = array() ) {
	global $woocommerce;

	extract( shortcode_atts( array(
		'ex_tax_label' 	=> '0'
	), $args ) );

	$return          = '';
	$num_decimals    = (int) get_option( 'woocommerce_price_num_decimals' );
	$currency_pos    = get_option( 'woocommerce_currency_pos' );
	$currency_symbol = get_woocommerce_currency_symbol();
	$decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
	$thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );

	$price           = apply_filters( 'raw_woocommerce_price', (double) $price );
	$price           = number_format( $price, $num_decimals, $decimal_sep, $thousands_sep );

	if ( apply_filters( 'woocommerce_price_trim_zeros', true ) && $num_decimals > 0 )
		$price = woocommerce_trim_zeros( $price );

	$return = '<span class="amount">' . sprintf( get_woocommerce_price_format(), $currency_symbol, $price ) . '</span>';

	if ( $ex_tax_label && get_option( 'woocommerce_calc_taxes' ) == 'yes' )
		$return .= ' <small>' . $woocommerce->countries->ex_tax_or_vat() . '</small>';

	return $return;
}

/**
 * let_to_num function.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @access public
 * @param $size
 * @return int
 */
function woocommerce_let_to_num( $size ) {
    $l 		= substr( $size, -1 );
    $ret 	= substr( $size, 0, -1 );
    switch( strtoupper( $l ) ) {
	    case 'P':
	        $ret *= 1024;
	    case 'T':
	        $ret *= 1024;
	    case 'G':
	        $ret *= 1024;
	    case 'M':
	        $ret *= 1024;
	    case 'K':
	        $ret *= 1024;
    }
    return $ret;
}

/**
 * WooCommerce Date Format - Allows to change date format for everything WooCommerce
 *
 * @access public
 * @return string
 */
function woocommerce_date_format() {
	return apply_filters( 'woocommerce_date_format', get_option( 'date_format' ) );
}

/**
 * WooCommerce Time Format - Allows to change time format for everything WooCommerce
 *
 * @access public
 * @return string
 */
function woocommerce_time_format() {
	return apply_filters( 'woocommerce_time_format', get_option( 'time_format' ) );
}

if ( ! function_exists( 'woocommerce_rgb_from_hex' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @return string
	 */
	function woocommerce_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF"
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb['R'] = hexdec( $color{0}.$color{1} );
		$rgb['G'] = hexdec( $color{2}.$color{3} );
		$rgb['B'] = hexdec( $color{4}.$color{5} );
		return $rgb;
	}
}

if ( ! function_exists( 'woocommerce_hex_darker' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function woocommerce_hex_darker( $color, $factor = 30 ) {
		$base = woocommerce_rgb_from_hex( $color );
		$color = '#';

		foreach ($base as $k => $v) :
	        $amount = $v / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v - $amount;

	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
		endforeach;

		return $color;
	}
}

if ( ! function_exists( 'woocommerce_hex_lighter' ) ) {

	/**
	 * Hex darker/lighter/contrast functions for colours
	 *
	 * @access public
	 * @param mixed $color
	 * @param int $factor (default: 30)
	 * @return string
	 */
	function woocommerce_hex_lighter( $color, $factor = 30 ) {
		$base = woocommerce_rgb_from_hex( $color );
		$color = '#';

	    foreach ($base as $k => $v) :
	        $amount = 255 - $v;
	        $amount = $amount / 100;
	        $amount = round($amount * $factor);
	        $new_decimal = $v + $amount;

	        $new_hex_component = dechex($new_decimal);
	        if(strlen($new_hex_component) < 2) :
	        	$new_hex_component = "0".$new_hex_component;
	        endif;
	        $color .= $new_hex_component;
	   	endforeach;

	   	return $color;
	}
}

if ( ! function_exists( 'woocommerce_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark colour on a background colour
	 *
	 * @access public
	 * @param mixed $color
	 * @param string $dark (default: '#000000')
	 * @param string $light (default: '#FFFFFF')
	 * @return string
	 */
	function woocommerce_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
	    //return ( hexdec( $color ) > 0xffffff / 2 ) ? $dark : $light;
	    $hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );
		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155 ? $dark : $light;
	}
}

if ( ! function_exists( 'woocommerce_format_hex' ) ) {

	/**
	 * Format string as hex
	 *
	 * @access public
	 * @param string $hex
	 * @return string
	 */
	function woocommerce_format_hex( $hex ) {

	    $hex = trim( str_replace( '#', '', $hex ) );

	    if ( strlen( $hex ) == 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	    }

	    if ( $hex ) return '#' . $hex;
	}
}

/**
 * Format the postcode according to the country and length of the postcode
 *
 * @param   string	postcode
 * @param	string	country
 * @return  string	formatted postcode
 */
function wc_format_postcode( $postcode, $country ) {
	$postcode = strtoupper(trim($postcode));
	$postcode = trim(preg_replace('/[\s]/', '', $postcode));

	if ( in_array( $country, array('GB', 'CA') ) )
		$postcode = trim( substr_replace( $postcode, ' ', -3, 0 ) );

	return $postcode;
}

/**
 * format_phone function.
 *
 * @access public
 * @param mixed $tel
 * @return string
 */
function wc_format_phone_number( $tel ) {
	$tel = str_replace( '.', '-', $tel );
	return $tel;
}