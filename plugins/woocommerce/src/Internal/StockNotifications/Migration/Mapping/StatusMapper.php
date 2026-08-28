<?php
/**
 * StatusMapper class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Maps a legacy Back In Stock Notifications row to a Core notification status.
 *
 * Pure and side-effect free: no `$wpdb`, no WordPress functions beyond the status
 * enum. `verify` re-derives expected status through this same mapper, so its rules must
 * match the ones the migrator applies at write time exactly.
 */
class StatusMapper {

	/**
	 * Map a legacy row to a Core `status` value.
	 *
	 * Rules are evaluated top-down; the first match wins. Rule 1 is listed for
	 * completeness only: the candidate predicate excludes unverified rows entirely, so
	 * no row reaching this method ever satisfies it. Rule 2 is load-bearing and keys on
	 * the delivery clocks rather than on `is_active`, since legacy will not re-send to a
	 * row that has already been delivered for its current cycle until the product goes
	 * out of stock and re-arms `subscribe_date`.
	 *
	 * @param array $legacy_row  Row from `woocommerce_bis_notifications`.
	 * @param array $legacy_meta Meta for this row, keyed by meta_key.
	 * @return string One of the `NotificationStatus` constants.
	 */
	public static function map( array $legacy_row, array $legacy_meta ): string {
		$is_verified           = (string) ( $legacy_row['is_verified'] ?? '' );
		$awaiting_verification = (string) ( $legacy_meta['awaiting_verification'] ?? '' );

		if ( 'no' === $is_verified || 'yes' === $awaiting_verification ) {
			return NotificationStatus::PENDING;
		}

		$last_notified_date = (int) ( $legacy_row['last_notified_date'] ?? 0 );
		$subscribe_date     = (int) ( $legacy_row['subscribe_date'] ?? 0 );

		if ( $last_notified_date > 0 && $last_notified_date >= $subscribe_date ) {
			return NotificationStatus::SENT;
		}

		$is_active = (string) ( $legacy_row['is_active'] ?? '' );

		if ( 'on' === $is_active ) {
			return NotificationStatus::ACTIVE;
		}

		return NotificationStatus::CANCELLED;
	}
}
