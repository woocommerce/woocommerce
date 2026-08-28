<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Mapping;

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
	 * @param array  $legacy_row Test case value.
	 * @param array  $legacy_meta Test case value.
	 * @param string $expected Test case value.
	 */
	public function test_map( array $legacy_row, array $legacy_meta, string $expected ): void {
		$this->assertSame( $expected, StatusMapper::map( $legacy_row, $legacy_meta ) );
	}

	/**
	 * Truth table for StatusMapper::map(). Rules are evaluated top-down, first match wins:
	 * 1. is_verified='no' OR meta awaiting_verification='yes' -> pending.
	 * 2. last_notified_date > 0 AND last_notified_date >= subscribe_date -> sent.
	 * 3. is_active='on' -> active.
	 * 4. else -> cancelled.
	 *
	 * @return array
	 */
	public function provider_legacy_rows(): array {
		return array(
			// Rule 1: unverified column, regardless of the other flags.
			'rule 1 - is_verified no wins over sent-looking dates'    => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 100,
					'subscribe_date'     => 50,
					'is_active'          => 'on',
				),
				array(),
				NotificationStatus::PENDING,
			),
			'rule 1 - is_verified no wins over active-looking flags'  => array(
				array(
					'is_verified'        => 'no',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'on',
				),
				array(),
				NotificationStatus::PENDING,
			),
			// Rule 1: awaiting_verification meta, independent of is_verified.
			'rule 1 - awaiting_verification meta wins over active'    => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'on',
				),
				array( 'awaiting_verification' => 'yes' ),
				NotificationStatus::PENDING,
			),
			'rule 1 - awaiting_verification meta wins over sent'      => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 200,
					'subscribe_date'     => 100,
					'is_active'          => 'off',
				),
				array( 'awaiting_verification' => 'yes' ),
				NotificationStatus::PENDING,
			),
			// Rule 2: delivered-and-not-re-armed, both halves of "is_active".
			'rule 2 - delivered, not re-armed, is_active on maps to sent (not active)' => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 100,
					'is_active'          => 'on',
				),
				array(),
				NotificationStatus::SENT,
			),
			'rule 2 - delivered, not re-armed, is_active off maps to sent'            => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 50,
					'is_active'          => 'off',
				),
				array(),
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
				array(),
				NotificationStatus::SENT,
			),
			// Rule 2: equality boundary (last_notified_date == subscribe_date) still counts as sent.
			'rule 2 - last_notified_date equal to subscribe_date maps to sent'        => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 300,
					'subscribe_date'     => 300,
					'is_active'          => 'off',
				),
				array(),
				NotificationStatus::SENT,
			),
			// Rule 3: re-armed (subscribe_date advanced past last_notified_date) and active.
			'rule 3 - re-armed and is_active on maps to active'                       => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 200,
					'is_active'          => 'on',
				),
				array(),
				NotificationStatus::ACTIVE,
			),
			'rule 3 - never notified and is_active on maps to active'                 => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'on',
				),
				array(),
				NotificationStatus::ACTIVE,
			),
			// Rule 4: re-armed but not active, and never notified and not active.
			'rule 4 - re-armed and is_active off maps to cancelled'                   => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 100,
					'subscribe_date'     => 200,
					'is_active'          => 'off',
				),
				array(),
				NotificationStatus::CANCELLED,
			),
			'rule 4 - never notified and is_active off maps to cancelled'             => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
					'is_active'          => 'off',
				),
				array(),
				NotificationStatus::CANCELLED,
			),
			'rule 4 - missing is_active column maps to cancelled'                     => array(
				array(
					'is_verified'        => 'yes',
					'last_notified_date' => 0,
					'subscribe_date'     => 0,
				),
				array(),
				NotificationStatus::CANCELLED,
			),
		);
	}
}
