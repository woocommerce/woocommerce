<?php
/**
 * WooCommerce Coupons Functions
 *
 * Functions for coupon specific things.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get coupon types.
 *
 * @return array
 */
function wc_get_coupon_types() {
	return (array) apply_filters( 'woocommerce_coupon_discount_types', array(
		'fixed_cart'      => __( 'Cart discount', 'woocommerce' ),
		'percent'         => __( 'Cart % discount', 'woocommerce' ),
		'fixed_product'   => __( 'Product discount', 'woocommerce' ),
		'percent_product' => __( 'Product % discount', 'woocommerce' ),
	) );
}

/**
 * Get a coupon type's name.
 *
 * @param string $type (default: '')
 * @return string
 */
function wc_get_coupon_type( $type = '' ) {
	$types = wc_get_coupon_types();
	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

/**
 * Coupon types that apply to individual products. Controls which validation rules will apply.
 *
 * @since  2.5.0
 * @return array
 */
function wc_get_product_coupon_types() {
	return (array) apply_filters( 'woocommerce_product_coupon_types', array( 'fixed_product', 'percent_product' ) );
}

/**
 * Coupon types that apply to the cart as a whole. Controls which validation rules will apply.
 *
 * @since  2.5.0
 * @return array
 */
function wc_get_cart_coupon_types() {
	return (array) apply_filters( 'woocommerce_cart_coupon_types', array( 'fixed_cart', 'percent' ) );
}

/**
 * Check if coupons are enabled.
 * Filterable.
 *
 * @since  2.5.0
 *
 * @return array
 */
function wc_coupons_enabled() {
	return apply_filters( 'woocommerce_coupons_enabled', 'yes' === get_option( 'woocommerce_enable_coupons' ) );
}

/**
 * Get coupon code by ID.
 *
 * @since 2.7.0
 * @param int $id Coupon ID.
 * @return string
 */
function wc_get_coupon_code_by_id( $id ) {
	global $wpdb;

	$code = $wpdb->get_var( $wpdb->prepare( "
		SELECT post_title
		FROM $wpdb->posts
		WHERE ID = %d
		AND post_type = 'shop_coupon'
		AND post_status = 'publish';
	", $id ) );

	return (string) $code;
}

/**
 * Get coupon code by ID.
 *
 * @since 2.7.0
 * @param string $code
 * @param int $exclude Used to exclude an ID from the check if you're checking existance.
 * @return int
 */
function wc_get_coupon_id_by_code( $code, $exclude = 0 ) {
	global $wpdb;

	$ids = wp_cache_get( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $code, 'coupons' );

	if ( false === $ids ) {
		$sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC;", $code );
		$ids = $wpdb->get_col( $sql );

		if ( $ids ) {
			wp_cache_set( WC_Cache_Helper::get_cache_prefix( 'coupons' ) . 'coupon_id_from_code_' . $code, $ids, 'coupons' );
		}
	}

	$ids = array_diff( array_filter( array_map( 'absint', (array) $ids ) ), array( $exclude ) );

	return apply_filters( 'woocommerce_get_coupon_id_from_code', absint( current( $ids ) ), $code, $exclude );
}
