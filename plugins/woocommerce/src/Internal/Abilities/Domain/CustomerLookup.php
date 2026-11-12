<?php
/**
 * Customer lookup class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities\Domain;

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The customer abilities identify customers by their Analytics customer ID, which guests have too.
 * It extends the Analytics data store to count orders and spend with the same rules as the Customers report.
 */
final class CustomerLookup extends CustomersDataStore {

	/**
	 * Analytics keeps the lookup table in sync only while it is on, so the customer abilities refuse to run without it.
	 *
	 * @return \WP_Error|null
	 */
	public static function get_unavailable_error(): ?\WP_Error {
		if ( FeaturesUtil::feature_is_enabled( 'analytics' ) ) {
			return null;
		}

		return new \WP_Error(
			'woocommerce_ability_customer_analytics_disabled',
			__( 'Customer data comes from WooCommerce Analytics, which is turned off on this store. Ask the store owner to turn it on under WooCommerce > Settings > Advanced > Features.', 'woocommerce' ),
			array( 'status' => 503 )
		);
	}

	/**
	 * Find the WordPress user of a customer.
	 *
	 * @param int $customer_id Analytics customer ID.
	 * @return int|null|false The user ID, null for a guest, or false when no customer has the ID.
	 */
	public static function get_user_id( int $customer_id ) {
		global $wpdb;

		$table = self::get_db_table_name();
		$row   = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare( "SELECT user_id FROM {$table} WHERE customer_id = %d", $customer_id )
		);

		if ( null === $row ) {
			return false;
		}

		return null === $row->user_id ? null : (int) $row->user_id;
	}

	/**
	 * SQL that counts the orders of the joined order stats rows, without refunds.
	 *
	 * @param string $order_stats_table Order stats table name or alias.
	 * @return string
	 */
	public static function get_order_count_sql( string $order_stats_table ): string {
		return 'SUM( CASE WHEN ' . self::get_countable_customer_order_predicate( $order_stats_table ) . ' THEN 1 ELSE 0 END )';
	}

	/**
	 * SQL condition that leaves out the order statuses the store excludes from Analytics.
	 *
	 * @param string $order_stats_table Order stats table name or alias.
	 * @return string An `AND` condition, or an empty string.
	 */
	public static function get_counted_status_sql( string $order_stats_table ): string {
		$excluded = array_map( array( __CLASS__, 'normalize_order_status' ), self::get_excluded_report_order_statuses() );
		if ( empty( $excluded ) ) {
			return '';
		}

		return "AND {$order_stats_table}.status NOT IN ( '" . implode( "','", array_map( 'esc_sql', $excluded ) ) . "' )";
	}
}
