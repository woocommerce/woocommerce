<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusMapper;
use WC_Unit_Test_Case;

/**
 * Tests for StatusMapper.
 */
class StatusMapperTests extends WC_Unit_Test_Case {

	/**
	 * @testdox Should map a legacy row to the expected Core status across the full flag truth table.
	 * @dataProvider provider_legacy_rows
	 *
	 * @param array      $legacy_row Test case value.
	 * @param array|null $cancellation Test case value.
	 * @param string     $expected Test case value.
	 */
	public function test_map( array $legacy_row, ?array $cancellation, string $expected ): void {
		$this->assertSame( $expected, StatusMapper::map( $legacy_row, $cancellation ) );
	}

	/**
	 * Truth table for StatusMapper::map(). Rules are evaluated top-down, first match wins:
	 * 1. is_verified='no' AND is_active!='on' AND no cancelling event -> pending.
	 * 2. last_notified_date > 0 AND last_notified_date >= subscribe_date -> sent.
	 * 3. is_active='on' -> active.
	 * 4. else -> cancelled.
	 *
	 * @return array
	 */
	public function provider_legacy_rows(): array {
		return array(
			// Rule 1: the three populations legacy stores as is_verified='no'.
			'rule 1 - unverified, switched off, no mined entry maps to pending' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::PENDING,
			),
			'rule 1 - unverified, switched off, mined entry without an event maps to pending' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'off',
				),
				self::no_event(),
				NotificationStatus::PENDING,
			),
			// A shopper who cancelled their own pending verification writes a
			// `verification_cancelled` event, which the miner does not treat as cancelling,
			// so the row still reaches Core as pending.
			'rule 1 - shopper-cancelled pending verification stays pending' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 0,
					'subscribe_date'     => 1600000000,
					'is_active'          => 'off',
				),
				self::no_event(),
				NotificationStatus::PENDING,
			),
			// Rule 1 is evaluated before the delivery clocks, so a switched-off unverified row
			// with no cancelling event is pending even if it was delivered to in the past.
			'rule 1 - unverified and switched off wins over sent-looking dates' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 100,
					'subscribe_date'     => 50,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::PENDING,
			),
			'rule 1 - unverified with a deactivated event maps to cancelled' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 0,
					'subscribe_date'     => 1600000000,
					'is_active'          => 'off',
				),
				self::event( NotificationCancellationSource::USER, 1600009999 ),
				NotificationStatus::CANCELLED,
			),
			// An unverified row that is still switched on is a live subscriber: legacy's
			// delivery query filters on is_active and never reads is_verified.
			'rule 1 - unverified but still switched on maps to active' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'on',
				),
				null,
				NotificationStatus::ACTIVE,
			),
			'rule 1 - unverified, switched on and already delivered maps to sent' => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 100,
					'subscribe_date'     => 50,
					'is_active'          => 'on',
				),
				null,
				NotificationStatus::SENT,
			),
			// Rule 2: delivered-and-not-re-armed, both halves of "is_active".
			'rule 2 - delivered, not re-armed, is_active on maps to sent (not active)' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 100,
					'is_active'          => 'on',
				),
				null,
				NotificationStatus::SENT,
			),
			'rule 2 - delivered, not re-armed, is_active off maps to sent' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 50,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::SENT,
			),
			// Rule 2: the admin "send now" case. subscribe_date is 0 (never re-armed) and
			// last_notified_date is set; a strict `>` plus `subscribe_date != 0` legacy-style
			// test would wrongly drop this into cancelled since 0 fails "!= 0".
			'rule 2 - admin send-now with unset subscribe_date maps to sent, not cancelled' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 500,
					'subscribe_date'     => 0,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::SENT,
			),
			// Rule 2: equality boundary (last_notified_date == subscribe_date) still counts as sent.
			'rule 2 - last_notified_date equal to subscribe_date maps to sent' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 300,
					'subscribe_date'     => 300,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::SENT,
			),
			// Rule 3: re-armed (subscribe_date advanced past last_notified_date) and active.
			'rule 3 - re-armed and is_active on maps to active' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 200,
					'is_active'          => 'on',
				),
				null,
				NotificationStatus::ACTIVE,
			),
			'rule 3 - never notified and is_active on maps to active' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'on',
				),
				null,
				NotificationStatus::ACTIVE,
			),
			// Rule 4: re-armed but not active, and never notified and not active.
			'rule 4 - re-armed and is_active off maps to cancelled' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 200,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::CANCELLED,
			),
			'rule 4 - never notified and is_active off maps to cancelled' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'off',
				),
				null,
				NotificationStatus::CANCELLED,
			),
			'rule 4 - missing is_active column maps to cancelled' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
				),
				null,
				NotificationStatus::CANCELLED,
			),
			// A verified row is cancelled on the flags alone; the mined entry only supplies
			// the source, which this mapper does not read.
			'rule 4 - verified and switched off with a mined event maps to cancelled' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'off',
				),
				self::event( NotificationCancellationSource::ADMIN, 1600009999 ),
				NotificationStatus::CANCELLED,
			),
		);
	}

	/**
	 * A mined entry for a row the activity log holds no cancelling event for.
	 *
	 * @return array
	 */
	private static function no_event(): array {
		return array(
			'source' => NotificationCancellationSource::SYSTEM,
			'date'   => null,
		);
	}

	/**
	 * A mined entry carrying a cancelling event.
	 *
	 * @param string $source Cancellation source.
	 * @param int    $date   Event timestamp.
	 * @return array
	 */
	private static function event( string $source, int $date ): array {
		return array(
			'source' => $source,
			'date'   => $date,
		);
	}
}
