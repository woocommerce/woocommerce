<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StructuredData;

use WC_Cache_Helper;

/**
 * Provides short-lived batches of shipping-zone method data.
 */
class ShippingMethodBatch {
	/**
	 * Active method batches, including nested schema generation.
	 *
	 * @var array
	 */
	private static $batches = array();

	/**
	 * Load enabled methods for a batch of zones.
	 *
	 * @param int[] $zone_ids Zone IDs.
	 * @return void
	 */
	public static function begin( array $zone_ids ): void {
		global $wpdb;

		$zone_ids = array_unique( array_map( 'absint', $zone_ids ) );
		$methods  = array_fill_keys( $zone_ids, array() );
		if ( $zone_ids ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Zone IDs are integers.
			$rows = $wpdb->get_results( "SELECT zone_id, method_id, method_order, instance_id, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE is_enabled = 1 AND zone_id IN (" . implode( ',', $zone_ids ) . ')' );
			if ( ! is_array( $rows ) ) {
				$methods = array();
			} else {
				foreach ( $rows as $row ) {
					$zone_id = (int) $row->zone_id;
					unset( $row->zone_id );
					$methods[ $zone_id ][] = $row;
				}
			}
		}
		self::$batches[] = array(
			'blog_id' => get_current_blog_id(),
			'version' => WC_Cache_Helper::get_transient_version( 'shipping' ),
			'methods' => $methods,
		);
	}

	/**
	 * Return a zone's primed methods once, or null when the batch is unavailable.
	 *
	 * @param int $zone_id Zone ID.
	 * @return array|null Primed methods or null.
	 */
	public static function take( $zone_id ): ?array {
		$index = count( self::$batches ) - 1;
		if ( $index < 0 ) {
			return null;
		}
		$batch = &self::$batches[ $index ];
		if ( get_current_blog_id() !== $batch['blog_id'] || WC_Cache_Helper::get_transient_version( 'shipping' ) !== $batch['version'] || ! isset( $batch['methods'][ $zone_id ] ) ) {
			return null;
		}
		$methods = $batch['methods'][ $zone_id ];
		unset( $batch['methods'][ $zone_id ] );
		return $methods;
	}

	/**
	 * End the most recent batch.
	 *
	 * @return void
	 */
	public static function end(): void {
		array_pop( self::$batches );
	}
}
