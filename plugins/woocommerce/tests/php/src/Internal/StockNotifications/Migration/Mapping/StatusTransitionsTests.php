<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Mapping;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Migration\Mapping\StatusTransitions;
use WC_Unit_Test_Case;

/**
 * Tests for StatusTransitions.
 */
class StatusTransitionsTests extends WC_Unit_Test_Case {

	/**
	 * @testdox is_forward() should accept only the transitions a shopper or the store can drive.
	 * @dataProvider provider_transitions
	 *
	 * @param string $derived  Test case value.
	 * @param string $stored   Test case value.
	 * @param bool   $expected Test case value.
	 */
	public function test_is_forward( string $derived, string $stored, bool $expected ): void {
		$this->assertSame( $expected, StatusTransitions::is_forward( $derived, $stored ) );
	}

	/**
	 * Every ordered pair of statuses, plus an unknown status.
	 *
	 * @return array
	 */
	public function provider_transitions(): array {
		return array(
			'pending verified'          => array( NotificationStatus::PENDING, NotificationStatus::ACTIVE, true ),
			'pending cancelled'         => array( NotificationStatus::PENDING, NotificationStatus::CANCELLED, true ),
			'pending notified'          => array( NotificationStatus::PENDING, NotificationStatus::SENT, true ),
			'active cancelled'          => array( NotificationStatus::ACTIVE, NotificationStatus::CANCELLED, true ),
			'active notified'           => array( NotificationStatus::ACTIVE, NotificationStatus::SENT, true ),
			'sent cancelled'            => array( NotificationStatus::SENT, NotificationStatus::CANCELLED, true ),
			'active back to pending'    => array( NotificationStatus::ACTIVE, NotificationStatus::PENDING, false ),
			'sent back to pending'      => array( NotificationStatus::SENT, NotificationStatus::PENDING, false ),
			'sent back to active'       => array( NotificationStatus::SENT, NotificationStatus::ACTIVE, false ),
			'cancelled is terminal'     => array( NotificationStatus::CANCELLED, NotificationStatus::ACTIVE, false ),
			'cancelled stays cancelled' => array( NotificationStatus::CANCELLED, NotificationStatus::CANCELLED, false ),
			'no transition is forward'  => array( NotificationStatus::ACTIVE, NotificationStatus::ACTIVE, false ),
			'unknown derived status'    => array( 'not-a-status', NotificationStatus::ACTIVE, false ),
		);
	}
}
