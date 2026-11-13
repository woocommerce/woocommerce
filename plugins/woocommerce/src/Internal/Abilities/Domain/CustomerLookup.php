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
 * customers-query reads through the get_data() of the Analytics data store, so its counts match the Customers report.
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

		if ( null === $row->user_id ) {
			return null;
		}

		return get_userdata( (int) $row->user_id ) ? (int) $row->user_id : false;
	}
}
