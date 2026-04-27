<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

/**
 * Appends Back-in-Stock Notifications usage data to the WC Tracker snapshot.
 *
 * Data is minimised on purpose: no PII, no per-product breakdown, no email-address
 * counts. Only feature-activation and aggregate adoption-signal counts are emitted,
 * and the shape is kept stable regardless of whether the feature is enabled so that
 * downstream consumers can rely on the schema.
 *
 * @internal This class and its TRACKER_KEY constant are not part of the public
 *           extension API; the snapshot contract is owned by WC Core's tracker.
 */
class Tracker {

	/**
	 * Tracker array key under which BIS data is emitted.
	 */
	public const TRACKER_KEY = 'back_in_stock_notifications';

	/**
	 * Constructor.
	 *
	 * Registers the tracker filter with a low priority so feature-agnostic
	 * data is assembled before we append ours.
	 */
	public function __construct() {
		add_filter( 'woocommerce_tracker_data', array( $this, 'append_tracker_data' ), 20 );
	}

	/**
	 * Append BIS tracker data to the snapshot.
	 *
	 * @param array $data The tracker data.
	 * @return array The tracker data with the BIS block appended.
	 */
	public function append_tracker_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$data[ self::TRACKER_KEY ] = $this->get_tracker_data();
		return $data;
	}

	/**
	 * Build the BIS tracker data block.
	 *
	 * Always returns the same set of keys regardless of feature state so that the
	 * schema is stable for downstream consumers.
	 *
	 * @return array<string, int|string>
	 */
	public function get_tracker_data(): array {
		$enabled = Constants::is_true( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' );

		return array(
			'enabled'                  => $enabled ? 'yes' : 'no',
			'allow_signups'            => Config::allows_signups() ? 'yes' : 'no',
			'verification_required'    => Config::requires_double_opt_in() ? 'yes' : 'no',
			'signups_total'            => $enabled ? $this->count_signups() : 0,
			'notifications_sent_total' => $enabled ? $this->count_sent() : 0,
		);
	}

	/**
	 * Count all BIS signup rows via the data store.
	 *
	 * Wrapped in a try/catch so a BIS-level DB error (e.g. missing table on a
	 * partially-migrated install) degrades to 0 instead of taking down the
	 * whole woocommerce_tracker_data snapshot.
	 *
	 * @return int
	 */
	private function count_signups(): int {
		try {
			return NotificationQuery::count_notifications();
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'BIS tracker signups count failed: %s', $e->getMessage() ),
				array( 'source' => 'wc-customer-stock-notifications' )
			);
			return 0;
		}
	}

	/**
	 * Count BIS notifications that have been dispatched via the data store.
	 *
	 * @return int
	 */
	private function count_sent(): int {
		try {
			return NotificationQuery::count_notifications(
				array(
					'status' => NotificationStatus::SENT,
				)
			);
		} catch ( \Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'BIS tracker sent count failed: %s', $e->getMessage() ),
				array( 'source' => 'wc-customer-stock-notifications' )
			);
			return 0;
		}
	}
}
