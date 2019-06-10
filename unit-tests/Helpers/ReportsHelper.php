<?php
/**
 * Helper code for wc-admin unit tests.
 *
 * @package WooCommerce\Tests\Framework\Helpers
 */

namespace WooCommerce\RestApi\UnitTests\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class ReportsHelper.
 */
class ReportsHelper {

	/**
	 * Delete everything in the lookup tables.
	 */
	public static function reset_stats_dbs() {
		global $wpdb;
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Orders_Stats_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Products_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Coupons_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . WC_Admin_Reports_Customers_Data_Store::TABLE_NAME ); // @codingStandardsIgnoreLine.
	}
}
