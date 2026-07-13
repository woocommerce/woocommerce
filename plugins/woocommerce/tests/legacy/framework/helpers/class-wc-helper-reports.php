<?php
/**
 * Helper code for wc-admin unit tests.
 *
 * @package WooCommerce\Admin\Tests\Framework\Helpers
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
		$wpdb->query( 'DELETE FROM ' . \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::get_db_table_name() ); // @codingStandardsIgnoreLine.
		$wpdb->query( 'DELETE FROM ' . \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::get_db_table_name() ); // @codingStandardsIgnoreLine.
		$wpdb->query( 'DELETE FROM ' . \Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::get_db_table_name() ); // @codingStandardsIgnoreLine.
		$wpdb->query( 'DELETE FROM ' . \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_db_table_name() ); // @codingStandardsIgnoreLine.
		$wpdb->query( "DELETE FROM {$wpdb->wc_category_lookup}" ); // @codingStandardsIgnoreLine.

		$category_lookup = \Automattic\WooCommerce\Internal\Admin\CategoryLookup::instance();
		$category_ids    = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		foreach ( $category_ids as $category_id ) {
			$category_lookup->on_create( $category_id );
		}
	}
}
