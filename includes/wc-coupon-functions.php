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
		'fixed_cart'      => __( 'Cart Discount', 'woocommerce' ),
		'percent'         => __( 'Cart % Discount', 'woocommerce' ),
		'fixed_product'   => __( 'Product Discount', 'woocommerce' ),
		'percent_product' => __( 'Product % Discount', 'woocommerce' ),
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
 *
 * @param int $id Coupon ID.
 *
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
