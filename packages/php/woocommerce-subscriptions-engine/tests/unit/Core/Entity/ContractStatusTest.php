<?php
/**
 * Unit tests for the ContractStatus state machine.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus
 */
class ContractStatusTest extends TestCase {

	public function test_known_statuses_are_valid(): void {
		$this->assertTrue( ContractStatus::is_valid( ContractStatus::ACTIVE ) );
		$this->assertFalse( ContractStatus::is_valid( 'nonsense' ) );
	}

	public function test_active_can_move_to_hold_and_back(): void {
		$this->assertTrue( ContractStatus::can_transition( ContractStatus::ACTIVE, ContractStatus::ON_HOLD ) );
		$this->assertTrue( ContractStatus::can_transition( ContractStatus::ON_HOLD, ContractStatus::ACTIVE ) );
	}

	public function test_cancelled_and_expired_are_terminal(): void {
		$this->assertTrue( ContractStatus::is_terminal( ContractStatus::CANCELLED ) );
		$this->assertTrue( ContractStatus::is_terminal( ContractStatus::EXPIRED ) );

		foreach ( ContractStatus::all() as $target ) {
			$this->assertFalse( ContractStatus::can_transition( ContractStatus::CANCELLED, $target ) );
			$this->assertFalse( ContractStatus::can_transition( ContractStatus::EXPIRED, $target ) );
		}
	}

	public function test_pending_cancellation_only_reaches_active_or_cancelled(): void {
		$this->assertTrue( ContractStatus::can_transition( ContractStatus::PENDING_CANCELLATION, ContractStatus::ACTIVE ) );
		$this->assertTrue( ContractStatus::can_transition( ContractStatus::PENDING_CANCELLATION, ContractStatus::CANCELLED ) );
		$this->assertFalse( ContractStatus::can_transition( ContractStatus::PENDING_CANCELLATION, ContractStatus::ON_HOLD ) );
	}

	public function test_unknown_statuses_never_transition(): void {
		$this->assertFalse( ContractStatus::can_transition( 'nonsense', ContractStatus::ACTIVE ) );
		$this->assertFalse( ContractStatus::can_transition( ContractStatus::ACTIVE, 'nonsense' ) );
	}
}
