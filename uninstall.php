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

// Pages
wp_delete_post( woocommerce_get_page_id('shop'), true );
wp_delete_post( woocommerce_get_page_id('cart'), true );
wp_delete_post( woocommerce_get_page_id('checkout'), true );
wp_delete_post( woocommerce_get_page_id('order_tracking'), true );
wp_delete_post( woocommerce_get_page_id('myaccount'), true );
wp_delete_post( woocommerce_get_page_id('edit_address'), true );
wp_delete_post( woocommerce_get_page_id('view_order'), true );
wp_delete_post( woocommerce_get_page_id('change_password'), true );
wp_delete_post( woocommerce_get_page_id('pay'), true );
wp_delete_post( woocommerce_get_page_id('thanks'), true );

// Tables
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."woocommerce_attribute_taxonomies");
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."woocommerce_downloadable_product_permissions");
$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."woocommerce_termmeta");

// Delete options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_%';");