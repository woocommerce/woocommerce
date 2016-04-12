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

WC_Admin_Notices::add_notice( 'legacy_shipping' );

/**
 * Migrate term meta to WordPress tables
 */
if ( get_option( 'db_version' ) >= 34370 && $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_termmeta';" ) ) {
	if ( $wpdb->query( "INSERT INTO {$wpdb->termmeta} ( term_id, meta_key, meta_value ) SELECT woocommerce_term_id, meta_key, meta_value FROM {$wpdb->prefix}woocommerce_termmeta;" ) ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_termmeta" );
	}
}

/**
 * Old (table rate) shipping zones to new core shipping zones migration.
 * zone_enabled and zone_type are no longer used, but it's safe to leave them be.
 */
if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}woocommerce_shipping_zones` LIKE 'zone_enabled';" ) ) {
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_shipping_zones CHANGE `zone_type` `zone_type` VARCHAR(40) NOT NULL DEFAULT '';" );
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}woocommerce_shipping_zones CHANGE `zone_enabled` `zone_enabled` INT(1) NOT NULL DEFAULT 1;" );
}

/**
 * Shipping zones in WC 2.6.0 use a table named woocommerce_shipping_zone_methods.
 * Migrate the old data out of woocommerce_shipping_zone_shipping_methods into the new table and port over any known options (used by table rates and flat rate boxes).
 */
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}woocommerce_shipping_zone_shipping_methods';" ) ) {
	$old_methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods;" );
	if ( $old_methods ) {
		$max_new_id = $wpdb->get_var( "SELECT MAX(instance_id) FROM {$wpdb->prefix}woocommerce_shipping_zone_methods" );
		$max_old_id = $wpdb->get_var( "SELECT MAX(shipping_method_id) FROM {$wpdb->prefix}woocommerce_shipping_zone_shipping_methods" );

		// Avoid ID conflicts
		$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}woocommerce_shipping_zone_methods AUTO_INCREMENT = %d;", max( $max_new_id, $max_old_id ) + 1 ) );

		// Move data
		foreach ( $old_methods as $old_method ) {
			$wpdb->insert( $wpdb->prefix . 'woocommerce_shipping_zone_methods', array(
				'zone_id'      => $old_method->zone_id,
				'method_id'    => $old_method->shipping_method_type,
				'method_order' => $old_method->shipping_method_order
			) );

			$new_instance_id = $wpdb->insert_id;

			// Move main settings
			$older_settings_key = 'woocommerce_' . $old_method->shipping_method_type . '-' . $old_method->shipping_method_id . '_settings';
			$old_settings_key   = 'woocommerce_' . $old_method->shipping_method_type . '_' . $old_method->shipping_method_id . '_settings';
			add_option( 'woocommerce_' . $old_method->shipping_method_type . '_' . $new_instance_id . '_settings', get_option( $old_settings_key, get_option( $older_settings_key ) ) );

			if ( 'table_rate' === $old_method->shipping_method_type ) {
				// Move priority settings
				add_option( 'woocommerce_table_rate_default_priority_' . $new_instance_id, get_option( 'woocommerce_table_rate_default_priority_' . $old_method->shipping_method_id ) );
				add_option( 'woocommerce_table_rate_priorities_' . $new_instance_id, get_option( 'woocommerce_table_rate_priorities_' . $old_method->shipping_method_id ) );

				// Move rates
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_table_rates',
					array(
						'shipping_method_id' => $new_instance_id
					),
					array(
						'shipping_method_id' => $old_method->shipping_method_id
					)
				);
			}

			if ( 'flat_rate_boxes' === $old_method->shipping_method_type ) {
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_shipping_flat_rate_boxes',
					array(
						'shipping_method_id' => $new_instance_id
					),
					array(
						'shipping_method_id' => $old_method->shipping_method_id
					)
				);
			}
		}
	}
}

/**
 * woocommerce_calc_shipping option has been removed in 2.6
 */
if ( 'no' === get_option( 'woocommerce_calc_shipping' ) ) {
	update_option( 'woocommerce_ship_to_countries', 'disabled' );
}

/**
 * Refund item qty should be negative
 */
$wpdb->query( "
UPDATE {$wpdb->prefix}woocommerce_order_itemmeta as item_meta
LEFT JOIN {$wpdb->prefix}woocommerce_order_items as items ON item_meta.order_item_id = items.order_item_id
LEFT JOIN {$wpdb->posts} as posts ON items.order_id = posts.ID
SET item_meta.meta_value = item_meta.meta_value * -1
WHERE item_meta.meta_value > 0 AND item_meta.meta_key = '_qty' AND posts.post_type = 'shop_order_refund'
" );
