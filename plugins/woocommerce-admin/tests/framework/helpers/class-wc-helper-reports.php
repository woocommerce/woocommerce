<?php
/**
 * Helper code for wc-admin unit tests.
 *
 * @package WooCommerce\Tests\Framework\Helpers
 */

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
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::TABLE_NAME ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM $wpdb->prefix" . \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::TABLE_NAME ); // @codingStandardsIgnoreLine.
	}
}
