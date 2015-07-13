<?php
/**
 * WooCommerce Uninstall
 *
 * Uninstalling WooCommerce deletes user roles, pages, tables, and options.
 *
 * @author      WooThemes
 * @category    Core
 * @package     WooCommerce/Uninstaller
 * @version     2.3.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$status_options = get_option( 'woocommerce_status_options', array() );

if ( ! empty( $status_options['uninstall_data'] ) ) {

	global $wpdb;

	// Roles + caps
	include_once( 'includes/class-wc-install.php' );
	WC_Install::remove_roles();

	// Pages
	wp_trash_post( get_option( 'woocommerce_shop_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_cart_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_checkout_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_myaccount_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_edit_address_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_view_order_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_change_password_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_logout_page_id' ) );

	// Tables
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_api_keys" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_attribute_taxonomies" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_downloadable_product_permissions" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_termmeta" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_tax_rates" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_tax_rate_locations" );

	// Delete options
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce\_%';");

	// Delete posts + data
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'product', 'product_variation', 'shop_coupon', 'shop_order', 'shop_order_refund' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_items" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_order_itemmeta" );
}
