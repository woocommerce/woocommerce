<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Tax;

/**
 * Data store for tax rates.
 */
class TaxRateDataStore {
	/**
	 * Request-level cache of fetched tax rate rows, keyed by tax_rate_id.
	 *
	 * @var array<int,object>
	 */
	private array $rate_objects_cache = array();

	/**
	 * Fetch multiple tax rate rows in a single query, keyed by tax_rate_id.
	 *
	 * @since 11.0.0
	 *
	 * @param int[] $ids Tax rate IDs to fetch.
	 * @return array<int,object>
	 */
	public function get_rate_objects_for_ids( array $ids ): array {
		global $wpdb;

		$ids          = array_filter( array_map( 'absint', array_unique( $ids ) ) );
		$uncached_ids = array_diff( $ids, array_keys( $this->rate_objects_cache ) );
		if ( ! empty( $uncached_ids ) ) {
			$list = implode( ', ', $uncached_ids );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id IN ( $list )" );
			foreach ( $rows as $row ) {
				$this->rate_objects_cache[ (int) $row->tax_rate_id ] = $row;
			}
		}

		$result = array();
		foreach ( $ids as $id ) {
			if ( isset( $this->rate_objects_cache[ $id ] ) ) {
				$result[ $id ] = $this->rate_objects_cache[ $id ];
			}
		}

		return $result;
	}
}
