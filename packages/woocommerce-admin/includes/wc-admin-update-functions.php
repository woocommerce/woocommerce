<?php
/**
 * WooCommerce Admin Updates
 *
 * Functions for updating data, used by the background updater.
 *
 * @package WooCommerce/Admin
 */

use \Automattic\WooCommerce\Admin\Install as Installer;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;

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

/**
 * Rename "gross_total" to "total_sales".
 * See: https://github.com/woocommerce/woocommerce-admin/issues/3175
 */
function wc_admin_update_0230_rename_gross_total() {
	global $wpdb;

	// We first need to drop the new `total_sales` column, since dbDelta() will have created it.
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_order_stats DROP COLUMN `total_sales`" );
	// Then we can rename the existing `gross_total` column.
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_order_stats CHANGE COLUMN `gross_total` `total_sales` double DEFAULT 0 NOT NULL" );
}

/**
 * Update DB Version.
 */
function wc_admin_update_0230_db_version() {
	Installer::update_db_version( '0.23.0' );
}

/**
 * Remove the note unsnoozing scheduled action.
 */
function wc_admin_update_0251_remove_unsnooze_action() {
	as_unschedule_action( WC_Admin_Notes::UNSNOOZE_HOOK, null, 'wc-admin-data' );
	as_unschedule_action( WC_Admin_Notes::UNSNOOZE_HOOK, null, 'wc-admin-notes' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_0251_db_version() {
	Installer::update_db_version( '0.25.1' );
}

/**
 * Remove Facebook Extension note.
 */
function wc_admin_update_110_remove_facebook_note() {
	WC_Admin_Notes::delete_notes_with_name( 'wc-admin-facebook-extension' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_110_db_version() {
	Installer::update_db_version( '1.1.0' );
}
