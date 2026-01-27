<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Customers;

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Internal API for searching users/customers: no backward compatibility obligation.
 */
final class SearchService {
	/**
	 * Searches users having the billing email (when applicable lookup orders as well) as specified and returns their id.
	 *
	 * @param string[] $emails Emails to search for.
	 *
	 * @return int[]
	 */
	public function find_user_ids_by_billing_email_for_coupons_usage_lookup( array $emails ): array {
		$emails = array_unique( array_map( 'strtolower', array_map( 'sanitize_email', $emails ) ) );

		$include_user_ids = array();
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			global $wpdb;

			// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$placeholders     = implode( ', ', array_fill( 0, count( $emails ), '%s' ) );
			$include_user_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT customer_id FROM %i WHERE billing_email IN ($placeholders)",
					OrdersTableDataStore::get_orders_table_name(),
					...$emails
				)
			);
			// phpcs:enable

			if ( array() === $include_user_ids ) {
				return array();
			}
		}

		$users_query = new \WP_User_Query(
			array(
				'fields'     => 'ID',
				'include'    => $include_user_ids,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'billing_email',
						'value'   => $emails,
						'compare' => 'IN',
					),
				),
			)
		);
		return array_map( 'intval', array_unique( $users_query->get_results() ) );
	}
}
