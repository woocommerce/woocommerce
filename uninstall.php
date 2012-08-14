<?php
/**
 * WooCommerce Uninstall
 *
 * Uninstalling WooCommerce deletes user roles, options, tables, and pages.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Uninstaller
 * @version     1.6.4
 */
if( !defined('WP_UNINSTALL_PLUGIN') ) exit();

global $wpdb, $wp_roles;

// Roles
remove_role( 'customer' );
remove_role( 'shop_manager' );

// Capabilities
$wp_roles->remove_cap( 'administrator', 'manage_woocommerce' );
$wp_roles->remove_cap( 'administrator', 'manage_woocommerce_orders' );
$wp_roles->remove_cap( 'administrator', 'manage_woocommerce_coupons' );
$wp_roles->remove_cap( 'administrator', 'manage_woocommerce_products' );
$wp_roles->remove_cap( 'administrator', 'view_woocommerce_reports' );

// Pages
wp_delete_post( get_option('woocommerce_shop_page_id'), true );
wp_delete_post( get_option('woocommerce_cart_page_id'), true );
wp_delete_post( get_option('woocommerce_checkout_page_id'), true );
wp_delete_post( get_option('woocommerce_order_tracking_page_id'), true );
wp_delete_post( get_option('woocommerce_myaccount_page_id'), true );
wp_delete_post( get_option('woocommerce_edit_address_page_id'), true );
wp_delete_post( get_option('woocommerce_view_order_page_id'), true );
wp_delete_post( get_option('woocommerce_change_password_page_id'), true );
wp_delete_post( get_option('woocommerce_pay_page_id'), true );
wp_delete_post( get_option('woocommerce_thanks_page_id'), true );

// mijireh checkout page
if ( $mijireh_page = get_page_by_path( 'mijireh-secure-checkout' ) )
	wp_delete_post( $mijireh_page->ID, true );

// Tables
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "woocommerce_downloadable_product_permissions" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "woocommerce_termmeta" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . "shareyourcart_tokens" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->base_prefix . "shareyourcart_coupons" );

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_%';");