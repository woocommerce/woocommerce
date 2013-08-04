<?php
/**
 * Update WC to 2.1.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb, $woocommerce;

// Pages no longer used
wp_delete_post( get_option('woocommerce_pay_page_id'), true );
wp_delete_post( get_option('woocommerce_thanks_page_id'), true );
wp_delete_post( get_option('woocommerce_view_order_page_id'), true );
wp_delete_post( get_option('woocommerce_change_password_page_id'), true );
wp_delete_post( get_option('woocommerce_edit_address_page_id'), true );
wp_delete_post( get_option('woocommerce_lost_password_page_id'), true );

// Update table primary keys - remove old key and then add new 'permission_id' row.
$wpdb->hide_errors();
$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions DROP PRIMARY KEY" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_downloadable_product_permissions ADD `permission_id` bigint(20) NOT NULL PRIMARY KEY AUTO_INCREMENT" );