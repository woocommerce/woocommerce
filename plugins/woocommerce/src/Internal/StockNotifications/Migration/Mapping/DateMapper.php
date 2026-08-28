<?php
/**
 * DateMapper class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Maps legacy Back In Stock Notifications epoch columns to Core GMT datetime strings.
 *
 * Pure and side-effect free: no `$wpdb`, no options, no hooks. Legacy INT columns are
 * already UTC `time()` values, so every conversion here uses `gmdate()` unconditionally.
 * The migration timestamp is injected at construction rather than read from `time()`,
 * so a whole batch shares one stable "now" and the class stays testable without it.
 */
class DateMapper {

	/**
	 * Datetime format used for every Core GMT column.
	 *
	 * @var string
	 */
	private const FORMAT = 'Y-m-d H:i:s';

	/**
	 * Earliest `create_date` accepted as plausible; anything before this is corrupt.
	 * 1420070400 is 2015-01-01 00:00:00 UTC.
	 *
	 * @var int
	 */
	private const MIN_CREATE_DATE = 1420070400;

	/**
	 * Seconds in a day, used for the `create_date` upper bound. A local literal, not the
	 * WordPress `DAY_IN_SECONDS` constant, so this class stays testable without WP loaded.
	 *
	 * @var int
	 */
	private const DAY_IN_SECONDS = 86400;

	/**
	 * Migration run timestamp, shared by every row in the batch.
	 *
	 * @var int
	 */
	private int $migration_timestamp;

	/**
	 * Constructor.
	 *
	 * @param int $migration_timestamp Epoch seconds to use as "now" for this run.
	 */
	public function __construct( int $migration_timestamp ) {
		$this->migration_timestamp = $migration_timestamp;
	}

	/**
	 * Map `date_created_gmt`.
	 *
	 * Prefers `create_date`, rejecting it as corrupt when it falls outside
	 * 2015..now+1day, then falls back to `subscribe_date`, then the migration time.
	 *
	 * @param array $legacy_row Row from `woocommerce_bis_notifications`.
	 * @return string GMT datetime string.
	 */
	public function date_created_gmt( array $legacy_row ): string {
		$create_date = (int) ( $legacy_row['create_date'] ?? 0 );

		if ( $this->is_plausible_create_date( $create_date ) ) {
			return gmdate( self::FORMAT, $create_date );
		}

		$subscribe_date = (int) ( $legacy_row['subscribe_date'] ?? 0 );

		if ( $subscribe_date > 0 ) {
			return gmdate( self::FORMAT, $subscribe_date );
		}

		return gmdate( self::FORMAT, $this->migration_timestamp );
	}

	/**
	 * Map `date_modified_gmt`.
	 *
	 * Always the migration timestamp: legacy has no per-row "last modified" column.
	 *
	 * @return string GMT datetime string.
	 */
	public function date_modified_gmt(): string {
		return gmdate( self::FORMAT, $this->migration_timestamp );
	}

	/**
	 * Map `date_confirmed_gmt`.
	 *
	 * `subscribe_date` is legacy's activation date. `subscribe_date = 0` is normal —
	 * legacy leaves it unset when the signup happened while the product was in stock —
	 * so it falls back to the mapped creation date in that case, which screens out a
	 * corrupt `create_date` instead of confirming the row in 1970.
	 *
	 * A row that never completed double opt-in has nothing to confirm, so `pending` maps
	 * to null rather than to a date the shopper never reached.
	 *
	 * @param array  $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param string $status     Mapped Core status for this row.
	 * @return string|null GMT datetime string, or null when the row is still pending.
	 */
	public function date_confirmed_gmt( array $legacy_row, string $status ): ?string {
		if ( NotificationStatus::PENDING === $status ) {
			return null;
		}

		$subscribe_date = (int) ( $legacy_row['subscribe_date'] ?? 0 );

		if ( $subscribe_date > 0 ) {
			return gmdate( self::FORMAT, $subscribe_date );
		}

		return $this->date_created_gmt( $legacy_row );
	}

	/**
	 * Map `date_last_attempt_gmt`.
	 *
	 * @param array $legacy_row Row from `woocommerce_bis_notifications`.
	 * @return string|null GMT datetime string, or null when `last_notified_date` is 0.
	 */
	public function date_last_attempt_gmt( array $legacy_row ): ?string {
		$last_notified_date = (int) ( $legacy_row['last_notified_date'] ?? 0 );

		if ( $last_notified_date <= 0 ) {
			return null;
		}

		return gmdate( self::FORMAT, $last_notified_date );
	}

	/**
	 * Map `date_notified_gmt`.
	 *
	 * Only populated when the row's mapped status is `sent`; every other status leaves
	 * this null even when `last_notified_date` is set.
	 *
	 * @param array  $legacy_row Row from `woocommerce_bis_notifications`.
	 * @param string $status     Status already resolved by `StatusMapper::map()`.
	 * @return string|null GMT datetime string, or null.
	 */
	public function date_notified_gmt( array $legacy_row, string $status ): ?string {
		if ( NotificationStatus::SENT !== $status ) {
			return null;
		}

		$last_notified_date = (int) ( $legacy_row['last_notified_date'] ?? 0 );

		if ( $last_notified_date <= 0 ) {
			return null;
		}

		return gmdate( self::FORMAT, $last_notified_date );
	}

	/**
	 * Map `date_cancelled_gmt`.
	 *
	 * Only populated when the row's mapped status is `cancelled`. Prefers the latest
	 * `unsubscribed`/`deactivated` activity date mined from `woocommerce_bis_activity`,
	 * then falls back to `last_notified_date`, then to the mapped creation date.
	 *
	 * @param array    $legacy_row            Row from `woocommerce_bis_notifications`.
	 * @param string   $status                Status already resolved by `StatusMapper::map()`.
	 * @param int|null $latest_activity_date  Latest cancelling activity epoch, if any.
	 * @return string|null GMT datetime string, or null.
	 */
	public function date_cancelled_gmt( array $legacy_row, string $status, ?int $latest_activity_date = null ): ?string {
		if ( NotificationStatus::CANCELLED !== $status ) {
			return null;
		}

		if ( null !== $latest_activity_date && $latest_activity_date > 0 ) {
			return gmdate( self::FORMAT, $latest_activity_date );
		}

		$last_notified_date = (int) ( $legacy_row['last_notified_date'] ?? 0 );

		if ( $last_notified_date > 0 ) {
			return gmdate( self::FORMAT, $last_notified_date );
		}

		return $this->date_created_gmt( $legacy_row );
	}

	/**
	 * Whether a `create_date` value is plausible rather than corrupt.
	 *
	 * @param int $create_date Candidate epoch value.
	 * @return bool
	 */
	private function is_plausible_create_date( int $create_date ): bool {
		$max = $this->migration_timestamp + self::DAY_IN_SECONDS;

		return $create_date >= self::MIN_CREATE_DATE && $create_date <= $max;
	}
}
