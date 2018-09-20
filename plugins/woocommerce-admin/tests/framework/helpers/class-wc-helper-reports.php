<?php

/**
 * Class WC_Helper_Reports.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Reports {

	/**
	 * Delete everything in the lookup tables.
	 */
	public static function reset_stats_dbs() {
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Orders_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Products_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
	}
}
