<?php
/**
 * WooCommerce Admin Updates
 *
 * Functions for updating data, used by the background updater.
 *
 * @package WooCommerce\Admin
 */

use \Automattic\WooCommerce\Admin\Install as Installer;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes;
use \Automattic\WooCommerce\Admin\Notes\UnsecuredReportFiles;
use \Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes_Deactivate_Plugin;
use \Automattic\WooCommerce\Admin\ReportExporter;

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

/**
 * Remove Dismiss action from tracking opt-in admin note.
 */
function wc_admin_update_130_remove_dismiss_action_from_tracking_opt_in_note() {
	global $wpdb;

	$wpdb->query( "DELETE actions FROM {$wpdb->prefix}wc_admin_note_actions actions INNER JOIN {$wpdb->prefix}wc_admin_notes notes USING (note_id) WHERE actions.name = 'tracking-dismiss' AND notes.name = 'wc-admin-usage-tracking-opt-in'" );
}

/**
 * Update DB Version.
 */
function wc_admin_update_130_db_version() {
	Installer::update_db_version( '1.3.0' );
}

/**
 * Change the deactivate plugin note type to 'info'.
 */
function wc_admin_update_140_change_deactivate_plugin_note_type() {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wc_admin_notes SET type = 'info' WHERE name = %s", WC_Admin_Notes_Deactivate_Plugin::NOTE_NAME ) );
}

/**
 * Update DB Version.
 */
function wc_admin_update_140_db_version() {
	Installer::update_db_version( '1.4.0' );
}

/**
 * Remove Facebook Experts note.
 */
function wc_admin_update_160_remove_facebook_note() {
	WC_Admin_Notes::delete_notes_with_name( 'wc-admin-facebook-marketing-expert' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_160_db_version() {
	Installer::update_db_version( '1.6.0' );
}

/**
 * Delete the preexisting export files.
 */
function wc_admin_update_164_delete_report_downloads() {
	$upload_dir = wp_upload_dir();
	$base_dir   = trailingslashit( $upload_dir['basedir'] );

	$failed_files   = array();
	$exports_status = get_option( ReportExporter::EXPORT_STATUS_OPTION, array() );
	$has_failure    = false;

	if ( ! is_array( $exports_status ) ) {
		// This is essentially the same path as files failing deletion. Handle as such.
		return;
	}

	// Delete all export files based on the status option values.
	foreach ( $exports_status as $key => $progress ) {
		list( $report_type, $export_id ) = explode( ':', $key );

		if ( ! $export_id ) {
			continue;
		}

		$file   = "{$base_dir}wc-{$report_type}-report-export-{$export_id}.csv";
		$header = $file . '.headers';

		// phpcs:ignore
		if ( @file_exists( $file ) && false === @unlink( $file ) ) {
			array_push( $failed_files, $file );
		}

		// phpcs:ignore
		if ( @file_exists( $header ) && false === @unlink( $header ) ) {
			array_push( $failed_files, $header );
		}
	}

	// If the status option was missing or corrupt, there will be files left over.
	$potential_exports = glob( $base_dir . 'wc-*-report-export-*.csv' );
	$reports_pattern   = '(revenue|products|variations|orders|categories|coupons|taxes|stock|customers|downloads)';

	/**
	 * Look for files we can be reasonably sure were created by the report export.
	 *
	 * Export files we created will match the 'wc-*-report-export-*.csv' glob, with
	 * the first wildcard being one of the exportable report slugs, and the second
	 * being an integer with 11-14 digits (from microtime()'s output) that represents
	 * a time in the past.
	 */
	foreach ( $potential_exports as $potential_export ) {
		$matches = array();
		// See if the filename matches an unfiltered export pattern.
		if ( ! preg_match( "/wc-{$reports_pattern}-report-export-(?P<export_id>\d{11,14})\.csv\$/", $potential_export, $matches ) ) {
			$has_failure = true;
			continue;
		}

		// Validate the timestamp (anything in the past).
		$timestamp = (int) substr( $matches['export_id'], 0, 10 );

		if ( ! $timestamp || $timestamp > time() ) {
			$has_failure = true;
			continue;
		}

		// phpcs:ignore
		if ( false === @unlink( $potential_export ) ) {
			array_push( $failed_files, $potential_export );
		}
	}

	// Try deleting failed files once more.
	foreach ( $failed_files as $failed_file ) {
		// phpcs:ignore
		if ( false === @unlink( $failed_file ) ) {
			$has_failure = true;
		}
	}

	if ( $has_failure ) {
		UnsecuredReportFiles::possibly_add_note();
	}
}

/**
 * Update DB Version.
 */
function wc_admin_update_164_db_version() {
	Installer::update_db_version( '1.6.4' );
}
