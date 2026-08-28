<?php
/**
 * CancellationSourceMiner class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;

defined( 'ABSPATH' ) || exit;

/**
 * Mines `woocommerce_bis_activity` for the cancellation source of a batch of legacy
 * Back In Stock Notifications rows.
 *
 * One query per batch, never per row: `mine()` takes every legacy row in the batch and
 * resolves the whole set with a single `notification_id IN (...)` lookup.
 */
class CancellationSourceMiner {

	/**
	 * Legacy activity types that can end a notification.
	 *
	 * @var string[]
	 */
	private const CANCELLING_TYPES = array( 'unsubscribed', 'deactivated' );

	/**
	 * Whether a mined entry records an actual cancelling event.
	 *
	 * `mine()` returns an entry for every row it was given, falling back to a `SYSTEM`
	 * source with a null date when the activity log holds no cancelling event. Callers
	 * that need to know whether the row was actually cancelled — rather than which source
	 * to attribute a cancellation to — must ask this rather than test for a null entry.
	 *
	 * @param array|null $cancellation Entry from `mine()`, or null when the row is absent.
	 * @return bool True when a cancelling event was found for the row.
	 */
	public static function has_cancelling_event( ?array $cancellation ): bool {
		return null !== $cancellation && null !== ( $cancellation['date'] ?? null );
	}

	/**
	 * Mine the cancellation source and date for a batch of legacy rows.
	 *
	 * For each row, takes the latest `unsubscribed`/`deactivated` activity event:
	 *
	 * - `unsubscribed` always resolves to `USER` — both legacy call sites are customer
	 *   initiated.
	 * - `deactivated` with an activity `user_id` other than `0` and other than the row's
	 *   own `user_id` resolves to `ADMIN`: someone else deactivated the subscription.
	 * - `deactivated` with activity `user_id = 0` resolves to `SYSTEM` — this is
	 *   `deactivate( 0 )` from the delivery path.
	 * - `deactivated` with activity `user_id` equal to the row's own `user_id` resolves
	 *   to `USER` — this is `deactivate()` called with no arguments, which uses the
	 *   customer's own id.
	 * - No matching activity row resolves to `SYSTEM`. Legacy's `add_event()` writes
	 *   nothing when the user id is set but the email is empty, so coverage of every
	 *   cancelled row is not guaranteed.
	 *
	 * @param array $legacy_rows List of rows from `woocommerce_bis_notifications`, each
	 *                           with at least `id` and `user_id` keys.
	 * @return array<int,array{source:string,date:?int}> Lookup keyed by legacy
	 *                                                    notification id.
	 */
	public function mine( array $legacy_rows ): array {
		$notification_user_ids = array();

		foreach ( $legacy_rows as $legacy_row ) {
			$notification_id = (int) ( $legacy_row['id'] ?? 0 );

			if ( $notification_id > 0 ) {
				$notification_user_ids[ $notification_id ] = (int) ( $legacy_row['user_id'] ?? 0 );
			}
		}

		if ( empty( $notification_user_ids ) ) {
			return array();
		}

		$result = array();

		foreach ( $notification_user_ids as $notification_id => $notification_user_id ) {
			$result[ $notification_id ] = array(
				'source' => NotificationCancellationSource::SYSTEM,
				'date'   => null,
			);
		}

		foreach ( $this->fetch_latest_events( array_keys( $notification_user_ids ) ) as $notification_id => $event ) {
			$result[ $notification_id ] = array(
				'source' => $this->resolve_source( $event, $notification_user_ids[ $notification_id ] ),
				'date'   => (int) $event['date'],
			);
		}

		return $result;
	}

	/**
	 * Fetch the latest `unsubscribed`/`deactivated` activity row per notification id.
	 *
	 * @param int[] $notification_ids Legacy notification ids to look up.
	 * @return array<int,array{type:string,user_id:int,date:int}> Latest event per id.
	 */
	private function fetch_latest_events( array $notification_ids ): array {
		global $wpdb;

		$table             = $wpdb->prefix . 'woocommerce_bis_activity';
		$id_placeholders   = implode( ', ', array_fill( 0, count( $notification_ids ), '%d' ) );
		$type_placeholders = implode( ', ', array_fill( 0, count( self::CANCELLING_TYPES ), '%s' ) );

		// $table is built from $wpdb->prefix, not user input; $id_placeholders/$type_placeholders are
		// locally built %d/%s placeholder lists, bound via $wpdb->prepare() below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$sql = $wpdb->prepare(
			"SELECT notification_id, type, user_id, date FROM {$table}
			WHERE notification_id IN ( $id_placeholders )
			AND type IN ( $type_placeholders )
			ORDER BY notification_id ASC, date DESC, id DESC",
			array_merge( $notification_ids, self::CANCELLING_TYPES )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.

		$latest = array();

		foreach ( (array) $rows as $row ) {
			$notification_id = (int) $row['notification_id'];

			// The ORDER BY puts the latest event first for each notification id, so
			// only the first row seen per id needs to be kept.
			if ( isset( $latest[ $notification_id ] ) ) {
				continue;
			}

			$latest[ $notification_id ] = array(
				'type'    => (string) $row['type'],
				'user_id' => (int) $row['user_id'],
				'date'    => (int) $row['date'],
			);
		}

		return $latest;
	}

	/**
	 * Resolve a single event to a `NotificationCancellationSource` value.
	 *
	 * @param array{type:string,user_id:int,date:int} $event                 Latest cancelling event for the row.
	 * @param int                                     $notification_user_id The row's own `user_id`.
	 * @return string One of the `NotificationCancellationSource` constants.
	 */
	private function resolve_source( array $event, int $notification_user_id ): string {
		if ( 'unsubscribed' === $event['type'] ) {
			return NotificationCancellationSource::USER;
		}

		if ( 0 === $event['user_id'] ) {
			return NotificationCancellationSource::SYSTEM;
		}

		if ( $event['user_id'] !== $notification_user_id ) {
			return NotificationCancellationSource::ADMIN;
		}

		return NotificationCancellationSource::USER;
	}
}
