<?php
/**
 * Telemetry for the customer stock notifications feature.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Tracker snapshot for the customer stock notifications feature.
 */
class Telemetry {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_tracker_data', array( $this, 'add_snapshot_to_tracker_data' ), 10, 1 );
	}

	/**
	 * Append the stock notifications snapshot to WC_Tracker's payload.
	 *
	 * Params are untyped and coerced in the body because any third-party callback
	 * can pass a different value along the filter chain.
	 *
	 * @internal
	 *
	 * @param mixed $data The aggregated tracker data.
	 * @return mixed
	 */
	public function add_snapshot_to_tracker_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$data['customer_stock_notifications'] = self::collect_snapshot();

		return $data;
	}

	/**
	 * Collect the stock notifications snapshot fields.
	 *
	 * @return array<string, mixed>
	 */
	public static function collect_snapshot(): array {
		return array(
			'settings'      => self::get_settings(),
			'notifications' => self::get_notification_counts(),
		);
	}

	/**
	 * The feature's settings, as merchants configured them.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_settings(): array {
		return array(
			'allow_signups'                       => Config::allows_signups() ? 'yes' : 'no',
			'require_double_opt_in'               => Config::requires_double_opt_in() ? 'yes' : 'no',
			'require_account'                     => Config::requires_account() ? 'yes' : 'no',
			'unverified_deletions_days_threshold' => Config::get_unverified_deletion_days_threshold(),
			'hide_out_of_stock_items'             => get_option( 'woocommerce_hide_out_of_stock_items', 'no' ),
		);
	}

	/**
	 * The number of notifications in total and per status.
	 *
	 * @return array<string, int>
	 */
	private static function get_notification_counts(): array {
		$counts_by_status = wc_get_container()->get( StockNotificationsDataStore::class )->count_by_status();

		$counts = array( 'total' => array_sum( $counts_by_status ) );
		foreach ( NotificationStatus::get_valid_statuses() as $status ) {
			$counts[ $status ] = $counts_by_status[ $status ] ?? 0;
		}

		return $counts;
	}
}
