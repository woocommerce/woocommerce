<?php
/**
 * Unit tests for the Contract_Status state machine.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract_Status;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract_Status
 */
class Contract_Status_Test extends TestCase {

	public function test_known_statuses_are_valid(): void {
		$this->assertTrue( Contract_Status::is_valid( Contract_Status::ACTIVE ) );
		$this->assertFalse( Contract_Status::is_valid( 'nonsense' ) );
	}

	public function test_active_can_move_to_hold_and_back(): void {
		$this->assertTrue( Contract_Status::can_transition( Contract_Status::ACTIVE, Contract_Status::ON_HOLD ) );
		$this->assertTrue( Contract_Status::can_transition( Contract_Status::ON_HOLD, Contract_Status::ACTIVE ) );
	}

	public function test_cancelled_and_expired_are_terminal(): void {
		$this->assertTrue( Contract_Status::is_terminal( Contract_Status::CANCELLED ) );
		$this->assertTrue( Contract_Status::is_terminal( Contract_Status::EXPIRED ) );

		foreach ( Contract_Status::all() as $target ) {
			$this->assertFalse( Contract_Status::can_transition( Contract_Status::CANCELLED, $target ) );
			$this->assertFalse( Contract_Status::can_transition( Contract_Status::EXPIRED, $target ) );
		}
	}

	public function test_pending_cancellation_only_reaches_active_or_cancelled(): void {
		$this->assertTrue( Contract_Status::can_transition( Contract_Status::PENDING_CANCELLATION, Contract_Status::ACTIVE ) );
		$this->assertTrue( Contract_Status::can_transition( Contract_Status::PENDING_CANCELLATION, Contract_Status::CANCELLED ) );
		$this->assertFalse( Contract_Status::can_transition( Contract_Status::PENDING_CANCELLATION, Contract_Status::ON_HOLD ) );
	}

	public function test_unknown_statuses_never_transition(): void {
		$this->assertFalse( Contract_Status::can_transition( 'nonsense', Contract_Status::ACTIVE ) );
		$this->assertFalse( Contract_Status::can_transition( Contract_Status::ACTIVE, 'nonsense' ) );
	}
}
