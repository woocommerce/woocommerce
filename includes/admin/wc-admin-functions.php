<?php
/**
 * WooCommerce Admin Functions
 *
 * @author      WooThemes
 * @category    Core
 * @package     WooCommerce/Admin/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get all WooCommerce screen ids
 *
 * @return array
 */
function get_woocommerce_screen_ids() {
	$wc_screen_id = strtolower( __( 'WooCommerce', 'woocommerce' ) );

    return apply_filters( 'woocommerce_screen_ids', array(
    	'toplevel_page_' . $wc_screen_id,
    	$wc_screen_id . '_page_wc_reports',
    	$wc_screen_id . '_page_wc_status',
    	$wc_screen_id . '_page_woocommerce_settings',
    	$wc_screen_id . '_page_wc_status',
    	$wc_screen_id . '_page_woocommerce_customers',
    	'toplevel_page_woocommerce',
    	'woocommerce_page_woocommerce_settings',
    	'woocommerce_page_wc_status',
    	'product_page_woocommerce_attributes',
    	'edit-shop_order',
    	'shop_order',
    	'edit-product',
    	'product',
    	'edit-shop_coupon',
    	'shop_coupon',
    	'edit-product_cat',
    	'edit-product_tag',
    	'edit-product_shipping_class'
    ) );
}