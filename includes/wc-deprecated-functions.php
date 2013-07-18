<?php
/**
 * Deprecated functions
 *
 * Where functions come to die.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function woocommerce_show_messages() {
	_deprecated_function( 'woocommerce_show_messages', '2.1', 'wc_print_messages' );
	wc_show_messages();
}

function woocommerce_weekend_area_js() {
	_deprecated_function( 'woocommerce_weekend_area_js', '2.1', '' );
}
function woocommerce_tooltip_js() {
	_deprecated_function( 'woocommerce_tooltip_js', '2.1', '' );
}
function woocommerce_datepicker_js() {
	_deprecated_function( 'woocommerce_datepicker_js', '2.1', '' );
}

/**
 * Handle renamed filters
 */
global $wc_map_deprecated_filters;

$wc_map_deprecated_filters = array(
	'woocommerce_cart_item_class'        => 'woocommerce_cart_table_item_class',
	'woocommerce_cart_item_product_id'   => 'hook_woocommerce_in_cart_product_id',
	'woocommerce_cart_item_thumbnail'    => 'hook_woocommerce_in_cart_product_thumbnail',
	'woocommerce_cart_item_price'        => 'woocommerce_cart_item_price_html',
	'woocommerce_cart_item_name'         => 'woocommerce_in_cart_product_title',
	'woocommerce_order_item_class'       => 'woocommerce_order_table_item_class',
	'woocommerce_order_item_name' => 'woocommerce_order_table_product_title'
);

foreach ( $wc_map_deprecated_filters as $new => $old )
	add_filter( $new, 'woocommerce_deprecated_filter_mapping' );

function woocommerce_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $wc_map_deprecated_filters;

	$filter = current_filter();

	if ( isset( $wc_map_deprecated_filters[ $filter ] ) )
		if ( has_filter( $wc_map_deprecated_filters[ $filter ] ) ) {
			$data = apply_filters( $wc_map_deprecated_filters[ $filter ], $arg_1, $arg_2, $arg_3 );
			_deprecated_function( 'The ' . $wc_map_deprecated_filters[ $filter ] . ' filter', '2.1', $filter );
		}

	return $data;
}