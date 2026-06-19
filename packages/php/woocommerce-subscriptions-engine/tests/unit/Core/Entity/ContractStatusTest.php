<?php
/**
 * Unit tests for the ContractStatus state machine.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use DomainException;
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
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::ON_HOLD ) );
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ON_HOLD, ContractStatus::ACTIVE ) );
	}

	public function test_active_reaches_every_other_status(): void {
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::ON_HOLD ) );
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::PENDING_CANCELLATION ) );
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::CANCELLED ) );
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::EXPIRED ) );
	}

	public function test_on_hold_cannot_expire(): void {
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ON_HOLD, ContractStatus::PENDING_CANCELLATION ) );
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::ON_HOLD, ContractStatus::CANCELLED ) );
		$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::ON_HOLD, ContractStatus::EXPIRED ) );
	}

	public function test_cancelled_and_expired_are_terminal(): void {
		$this->assertTrue( ContractStatus::is_terminal( ContractStatus::CANCELLED ) );
		$this->assertTrue( ContractStatus::is_terminal( ContractStatus::EXPIRED ) );

		foreach ( ContractStatus::all() as $target ) {
			$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::CANCELLED, $target ) );
			$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::EXPIRED, $target ) );
		}
	}

	public function test_pending_cancellation_only_reaches_active_or_cancelled(): void {
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::PENDING_CANCELLATION, ContractStatus::ACTIVE ) );
		$this->assertTrue( ContractStatus::is_transition_allowed( ContractStatus::PENDING_CANCELLATION, ContractStatus::CANCELLED ) );
		$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::PENDING_CANCELLATION, ContractStatus::ON_HOLD ) );
		$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::PENDING_CANCELLATION, ContractStatus::EXPIRED ) );
	}

	public function test_unknown_statuses_never_transition(): void {
		$this->assertFalse( ContractStatus::is_transition_allowed( 'nonsense', ContractStatus::ACTIVE ) );
		$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, 'nonsense' ) );
	}

	public function test_same_status_is_not_an_allowed_transition(): void {
		// set_status() short-circuits no-ops; the table itself reports a
		// same-status move as not allowed.
		$this->assertFalse( ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::ACTIVE ) );
	}

	public function test_can_transition_aliases_is_transition_allowed(): void {
		$this->assertSame(
			ContractStatus::is_transition_allowed( ContractStatus::ACTIVE, ContractStatus::ON_HOLD ),
			ContractStatus::can_transition( ContractStatus::ACTIVE, ContractStatus::ON_HOLD )
		);
		$this->assertSame(
			ContractStatus::is_transition_allowed( ContractStatus::CANCELLED, ContractStatus::ACTIVE ),
			ContractStatus::can_transition( ContractStatus::CANCELLED, ContractStatus::ACTIVE )
		);
	}

	public function test_assert_transition_allowed_passes_for_a_legal_move(): void {
		ContractStatus::assert_transition_allowed( ContractStatus::ACTIVE, ContractStatus::CANCELLED );

		// No exception thrown.
		$this->addToAssertionCount( 1 );
	}

	public function test_assert_transition_allowed_throws_for_an_illegal_move(): void {
		$this->expectException( DomainException::class );

		ContractStatus::assert_transition_allowed( ContractStatus::CANCELLED, ContractStatus::ACTIVE );
	}

	public function test_assert_transition_allowed_throws_out_of_a_terminal_status(): void {
		$this->expectException( DomainException::class );

		ContractStatus::assert_transition_allowed( ContractStatus::EXPIRED, ContractStatus::ON_HOLD );
	}
}
