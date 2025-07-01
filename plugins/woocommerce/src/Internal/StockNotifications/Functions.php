<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

defined( 'ABSPATH' ) || exit;

/**
 * Functions class for stock notifications.
 */
class Functions {
	/**
	 * Returns verification codes expiration time threshold (in seconds).
	 *
	 * @return int
	 */
	public static function get_verification_expiration_time_threshold() {
		return (int) apply_filters( 'woocommerce_bis_verification_expiration_time_threshold', HOUR_IN_SECONDS );
	}

	/**
	 * Time period required to keep unverified notifications in the system (in seconds). @see WC_BIS_Sync_Tasks::do_wc_bis_daily()
	 *
	 * @since 1.2.0
	 *
	 * @return int
	 */
	public static function get_delete_unverified_time_threshold() {
		$delete_after_days = absint( get_option( 'wc_customer_stock_notifications_delete_unverified_days_threshold', 0 ) );
		if ( $delete_after_days > 0 ) {
			$delete_after_days = $delete_after_days * DAY_IN_SECONDS;
		}

		return $delete_after_days;
	}
}
