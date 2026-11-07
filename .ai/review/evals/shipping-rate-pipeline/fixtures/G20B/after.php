<?php
function make_export_rate( $args ) {
    $args = wp_parse_args( $args, array( 'id' => 'flat_rate:1', 'label' => 'Delivery', 'cost' => '0', 'price_decimals' => false ) );
    $total_cost = is_array( $args['cost'] ) ? array_sum( $args['cost'] ) : $args['cost'];
    $total_cost = wc_format_decimal( $total_cost, $args['price_decimals'] );
    return new WC_Shipping_Rate( $args['id'], $args['label'], $total_cost, array(), 'flat_rate', 1 );
}
function shipping_export_amount( $subtotal, $args ) {
    return $subtotal + make_export_rate( $args )->get_cost();
}
$amount = shipping_export_amount( 10, array( 'cost' => '' ) );

// Reduced WC_Shipping_Rate storage surface; cost setter/getter match the core class.
class WC_Shipping_Rate {
    private $cost;
    public function __construct( $id = '', $label = '', $cost = 0, $taxes = array(), $method_id = '', $instance_id = 0 ) {
        $this->set_cost( $cost );
    }
    public function set_cost( $cost ) { $this->cost = $cost; }
    public function get_cost() { return apply_filters( 'woocommerce_shipping_rate_cost', $this->cost, $this ); }
}

// Core decimal formatter.
function wc_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	$number = $number ?? '';

	if ( '' === $number ) {
		return '';
	}

	$locale   = localeconv();
	$decimals = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'] );

	// Remove locale from string.
	if ( ! is_float( $number ) ) {
		$number = str_replace( $decimals, '.', $number );

		// Convert multiple dots to just one.
		$number = preg_replace( '/\.(?![^.]+$)|[^0-9.-]/', '', wc_clean( $number ) );
	}

	if ( false !== $dp ) {
		$dp     = intval( '' === $dp ? wc_get_price_decimals() : $dp );
		$number = number_format( floatval( $number ), $dp, '.', '' );
	} elseif ( is_float( $number ) ) {
		// DP is false - don't use number format, just return a string using whatever is given. Remove scientific notation using sprintf.
		$number = str_replace( $decimals, '.', sprintf( '%.' . wc_get_rounding_precision() . 'f', $number ) );
		// We already had a float, so trailing zeros are not needed.
		$trim_zeros = true;
	}

	if ( $trim_zeros && strstr( $number, '.' ) ) {
		$number = rtrim( rtrim( $number, '0' ), '.' );
	}

	return $number;
}
