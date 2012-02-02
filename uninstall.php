<?php
/**
 * WooCommerce Uninstall
 * 
 * Uninstalling WooCommerce deletes user roles, options, tables, and pages.
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 * @since		1.4
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

// Tables
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."woocommerce_attribute_taxonomies");
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."woocommerce_downloadable_product_permissions");
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."woocommerce_termmeta");

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_%';");