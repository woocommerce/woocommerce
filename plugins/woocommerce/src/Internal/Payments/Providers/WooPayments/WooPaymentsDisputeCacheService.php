<?php
/**
 * WooPaymentsDisputeCacheService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Invalidates preserved WooPayments dispute cache entries.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDisputeCacheService {

	private const DISPUTE_CACHE_KEYS = array(
		'wcpay_dispute_status_counts_cache',
		'wcpay_test_dispute_status_counts_cache',
		'wcpay_active_dispute_cache',
	);

	/**
	 * Delete all dispute-related cache entries.
	 *
	 * @since 11.0.0
	 */
	public function delete_dispute_caches(): void {
		foreach ( self::DISPUTE_CACHE_KEYS as $key ) {
			delete_option( $key );
		}
	}
}
