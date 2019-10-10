<?php
/**
 * WooCommerce Admin Updates
 *
 * Functions for updating data, used by the background updater.
 *
 * @package WooCommerce/Admin
 */

defined( 'ABSPATH' ) || exit;

use \Automattic\WooCommerce\Admin\Install as Installer;

/**
 * Update order stats `status` index length.
 * See: https://github.com/woocommerce/woocommerce-admin/issues/2969.
 */
function wc_admin_update_0201_order_status_index() {
	global $wpdb;

	// Max DB index length. See wp_get_db_schema().
	$max_index_length = 191;

	$index = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->prefix}wc_order_stats WHERE key_name = 'status'" );

	if ( property_exists( $index, 'Sub_part' ) ) {
		// The index was created with the right length. Time to bail.
		if ( $max_index_length === $index->Sub_part ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			return;
		}

		// We need to drop the index so it can be recreated.
		$wpdb->query( "DROP INDEX `status` ON {$wpdb->prefix}wc_order_stats" );
	}

	// Recreate the status index with a max length.
	$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}wc_order_stats ADD INDEX status (status(%d))", $max_index_length ) );
}

/**
 * Update DB Version.
 */
function wc_admin_update_0201_db_version() {
	Installer::update_db_version( '0.20.1' );
}
