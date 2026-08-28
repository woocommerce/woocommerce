<?php
/**
 * StatusTransitions class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * The Core status transitions a shopper or the store can drive after a row was migrated.
 *
 * `verify` re-derives each migrated row's status from its legacy source. A row the shopper
 * has acted on since the run — verifying a pending signup through a legacy link, cancelling,
 * being notified — no longer matches what the legacy source derives, and reporting that as a
 * mismatch would flag correct behaviour as corruption. This table says which differences are
 * a row moving on and which are a real disagreement.
 *
 * Pure and side-effect free.
 */
final class StatusTransitions {

	/**
	 * Statuses reachable from each status once a row is live in Core.
	 *
	 * `cancelled` is terminal: nothing in Core moves a cancelled row back out.
	 *
	 * @var array<string, string[]>
	 */
	private const FORWARD = array(
		NotificationStatus::PENDING   => array( NotificationStatus::ACTIVE, NotificationStatus::CANCELLED, NotificationStatus::SENT ),
		NotificationStatus::ACTIVE    => array( NotificationStatus::CANCELLED, NotificationStatus::SENT ),
		NotificationStatus::SENT      => array( NotificationStatus::CANCELLED ),
		NotificationStatus::CANCELLED => array(),
	);

	/**
	 * Whether the stored status is reachable from the one re-derived from the legacy source.
	 *
	 * @param string $derived Status derived from the legacy row.
	 * @param string $stored  Status the Core row actually holds.
	 * @return bool True when the difference is a row that moved on, not a mismatch.
	 */
	public static function is_forward( string $derived, string $stored ): bool {
		return in_array( $stored, self::FORWARD[ $derived ] ?? array(), true );
	}
}
