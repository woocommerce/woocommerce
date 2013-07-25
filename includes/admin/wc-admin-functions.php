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
function wc_get_screen_ids() {
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

/**
 * Create a page and store the ID in an option.
 *
 * @access public
 * @param mixed $slug Slug for the new page
 * @param mixed $option Option name to store the page's ID
 * @param string $page_title (default: '') Title for the new page
 * @param string $page_content (default: '') Content for the new page
 * @param int $post_parent (default: 0) Parent for the new page
 * @return int page ID
 */
function wc_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
    global $wpdb;

    $option_value = get_option( $option );

    if ( $option_value > 0 && get_post( $option_value ) )
        return;

    $page_found = null;

    if ( strlen( $page_content ) > 0 ) {
        // Search for an existing page with the specified page content (typically a shortcode)
        $page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type='page' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
    } else {
        // Search for an existing page with the specified page slug
        $page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_type='page' AND post_name = %s LIMIT 1;", $slug ) );
    }

    if ( $page_found ) {
        if ( ! $option_value )
            update_option( $option, $page_found );
        return;
    }

    $page_data = array(
        'post_status'       => 'publish',
        'post_type'         => 'page',
        'post_author'       => 1,
        'post_name'         => $slug,
        'post_title'        => $page_title,
        'post_content'      => $page_content,
        'post_parent'       => $post_parent,
        'comment_status'    => 'closed'
    );
    $page_id = wp_insert_post( $page_data );

    if ( $option )
        update_option( $option, $page_id );

    return $page_id;
}