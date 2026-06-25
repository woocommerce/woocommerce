<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

/**
 * Data store for tax rates.
 */
class TaxRateDataStore {

	/**
	 * Fetch multiple tax rate rows in a single query, keyed by tax_rate_id.
	 *
	 * @since 11.0.0
	 *
	 * @param int[] $ids Tax rate IDs to fetch.
	 * @return array<int,object>
	 */
	public function get_rate_objects_for_ids( array $ids ): array {
		$tax_rate_objects = array();
		$ids              = array_values( array_filter( array_map( 'absint', array_unique( $ids ) ) ) );

		if ( ! empty( $ids ) ) {
			global $wpdb;

			$list = implode( ', ', $ids );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id IN ( $list )" );
			foreach ( $rows as $row ) {
				$tax_rate_objects[ (int) $row->tax_rate_id ] = $row;
			}
		}

		return $tax_rate_objects;
	}
}
