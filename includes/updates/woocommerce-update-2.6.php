<?php
/**
 * Update WC to 2.6.0
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Updates
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

/**
 * Old (table rate) shipping zones to new core shipping zones migration.
 *
 * zone_enabled and zone_type are no longer used, but it's safe to leave them be.
 */
if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_shipping_zones` LIKE 'zone_enabled';" ) ) {
    $wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_shipping_zones CHANGE `zone_type` `zone_type` VARCHAR(40) NOT NULL DEFAULT '';" );
    $wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_shipping_zones CHANGE `zone_enabled` `zone_enabled` INT(1) NOT NULL DEFAULT 1;" );
}

/**
 * Core uses woocommerce_shipping_zone_methods instead of woocommerce_shipping_zone_shipping_methods. Migrate the data.
 */
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_shipping_zone_shipping_methods';" ) ) {
    $old_methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods;" );
    if ( $old_methods ) {
        foreach ( $old_methods as $old_method ) {
            $wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array(
                'zone_id'      => $old_method->zone_id,
                'method_id'    => $old_method->shipping_method_type,
                'method_order' => $old_method->shipping_method_order
            ) );
            $old_settings_key = 'woocommerce_' . $old_method->shipping_method_type . '-' . $old_method->shipping_method_id . '_settings';
            add_option( 'woocommerce_' . $old_method->shipping_method_type . '_' . $wpdb->insert_id . '_settings', get_option( $old_settings_key ) );
        }
    }
}
