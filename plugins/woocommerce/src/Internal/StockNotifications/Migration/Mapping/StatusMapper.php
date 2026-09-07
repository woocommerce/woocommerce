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
 * Pure and side-effect free: no `$wpdb`, no WordPress functions beyond the status enum.
 */
class StatusMapper {

	/**
	 * Map a legacy row to a Core `status` value.
	 *
	 * Rules are evaluated top-down; the first match wins.
	 *
	 * Rule 1 separates the three populations that legacy stores as `is_verified = 'no'`.
	 * An unverified row that is still switched on is a live subscriber, because the
	 * delivery query filters on `is_active` and never reads `is_verified`. An unverified
	 * row that is switched off is a cancelled subscription when the activity log has a
	 * cancelling event for it, since deactivating under double opt-in clears the verified
	 * flag; with no such event it is a signup that never completed verification.
	 *
	 * Rule 2 keys on the delivery clocks rather than on `is_active`, since legacy will not
	 * re-send to a row that has already been delivered for its current cycle until the
	 * product goes out of stock and re-arms `subscribe_date`.
	 *
	 * The legacy `awaiting_verification` meta is deliberately not consulted: it is deleted
	 * on verify, on cancel-pending and on deactivate, and is absent altogether on rows
	 * created before the store turned double opt-in on. The columns and the activity log
	 * are the source of truth.
	 *
	 * @param array      $legacy_row   Row from `woocommerce_bis_notifications`.
	 * @param array|null $cancellation Mined entry for this row from `CancellationSourceMiner::mine()`,
	 *                                 or null when the row is absent from the lookup.
	 * @return string One of the `NotificationStatus` constants.
	 */
	public static function map( array $legacy_row, ?array $cancellation ): string {
		$is_verified = (string) ( $legacy_row['is_verified'] ?? '' );
		$is_active   = (string) ( $legacy_row['is_active'] ?? '' );

		if ( 'no' === $is_verified && 'on' !== $is_active && ! CancellationSourceMiner::has_cancelling_event( $cancellation ) ) {
			return NotificationStatus::PENDING;
		}

		$last_notified_date = (int) ( $legacy_row['last_notified_date'] ?? 0 );
		$subscribe_date     = (int) ( $legacy_row['subscribe_date'] ?? 0 );

		if ( $last_notified_date > 0 && $last_notified_date >= $subscribe_date ) {
			return NotificationStatus::SENT;
		}

		if ( 'on' === $is_active ) {
			return NotificationStatus::ACTIVE;
		}

		return NotificationStatus::CANCELLED;
	}
}
