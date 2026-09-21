<?php
/**
 * Unit tests for the CycleStatus state machine.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus
 */
class CycleStatusTest extends TestCase {

	public function test_all_returns_exactly_the_known_statuses(): void {
		$this->assertSame(
			array(
				CycleStatus::PENDING,
				CycleStatus::PROCESSING,
				CycleStatus::BILLED,
				CycleStatus::FAILED,
				CycleStatus::CANCELLED,
			),
			CycleStatus::all()
		);
	}

	public function test_known_statuses_are_valid(): void {
		$this->assertTrue( CycleStatus::is_valid( CycleStatus::PENDING ) );
		$this->assertTrue( CycleStatus::is_valid( CycleStatus::PROCESSING ) );
		$this->assertTrue( CycleStatus::is_valid( CycleStatus::BILLED ) );
		$this->assertTrue( CycleStatus::is_valid( CycleStatus::FAILED ) );
		$this->assertTrue( CycleStatus::is_valid( CycleStatus::CANCELLED ) );
		$this->assertFalse( CycleStatus::is_valid( 'nonsense' ) );
	}

	public function test_pending_reaches_processing_billed_failed_and_cancelled(): void {
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PENDING, CycleStatus::PROCESSING ) );
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PENDING, CycleStatus::BILLED ) );
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PENDING, CycleStatus::FAILED ) );
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PENDING, CycleStatus::CANCELLED ) );
	}

	public function test_processing_settles_to_billed_failed_or_cancelled_but_not_back_to_pending(): void {
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PROCESSING, CycleStatus::BILLED ) );
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PROCESSING, CycleStatus::FAILED ) );
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::PROCESSING, CycleStatus::CANCELLED ) );
		$this->assertFalse( CycleStatus::is_transition_allowed( CycleStatus::PROCESSING, CycleStatus::PENDING ) );
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::PROCESSING ) );
	}

	public function test_failed_can_be_retried_to_pending_or_cancelled(): void {
		// A failed cycle can be retried (re-queued to pending by an admin trigger) or cancelled,
		// but not settled directly to billed without re-attempting the charge.
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::FAILED, CycleStatus::PENDING ) );
		$this->assertTrue( CycleStatus::is_transition_allowed( CycleStatus::FAILED, CycleStatus::CANCELLED ) );
		$this->assertFalse( CycleStatus::is_transition_allowed( CycleStatus::FAILED, CycleStatus::BILLED ) );
	}

	public function test_billed_and_cancelled_are_terminal(): void {
		$this->assertTrue( CycleStatus::is_terminal( CycleStatus::BILLED ) );
		$this->assertTrue( CycleStatus::is_terminal( CycleStatus::CANCELLED ) );

		foreach ( CycleStatus::all() as $target ) {
			$this->assertFalse( CycleStatus::is_transition_allowed( CycleStatus::BILLED, $target ) );
			$this->assertFalse( CycleStatus::is_transition_allowed( CycleStatus::CANCELLED, $target ) );
		}
	}

	public function test_pending_processing_and_failed_are_not_terminal(): void {
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::PENDING ) );
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::PROCESSING ) );
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::FAILED ) );
	}

	public function test_unknown_statuses_never_transition(): void {
		$this->assertFalse( CycleStatus::is_transition_allowed( 'nonsense', CycleStatus::BILLED ) );
		$this->assertFalse( CycleStatus::is_transition_allowed( CycleStatus::PENDING, 'nonsense' ) );
	}

	public function test_same_status_is_not_an_allowed_transition(): void {
		$this->assertFalse( CycleStatus::is_transition_allowed( CycleStatus::PENDING, CycleStatus::PENDING ) );
	}

	public function test_can_transition_aliases_is_transition_allowed(): void {
		$this->assertSame(
			CycleStatus::is_transition_allowed( CycleStatus::PENDING, CycleStatus::BILLED ),
			CycleStatus::can_transition( CycleStatus::PENDING, CycleStatus::BILLED )
		);
		$this->assertSame(
			CycleStatus::is_transition_allowed( CycleStatus::BILLED, CycleStatus::PENDING ),
			CycleStatus::can_transition( CycleStatus::BILLED, CycleStatus::PENDING )
		);
	}

	public function test_assert_transition_allowed_passes_for_a_legal_move(): void {
		CycleStatus::assert_transition_allowed( CycleStatus::PENDING, CycleStatus::BILLED );

		// No exception thrown.
		$this->addToAssertionCount( 1 );
	}

	public function test_assert_transition_allowed_throws_for_an_illegal_move(): void {
		$this->expectException( DomainException::class );

		CycleStatus::assert_transition_allowed( CycleStatus::FAILED, CycleStatus::BILLED );
	}

	public function test_assert_transition_allowed_throws_out_of_a_terminal_status(): void {
		$this->expectException( DomainException::class );

		CycleStatus::assert_transition_allowed( CycleStatus::BILLED, CycleStatus::PENDING );
	}

	public function test_named_factories_carry_their_status_value(): void {
		$this->assertSame( CycleStatus::PENDING, CycleStatus::pending()->get_value() );
		$this->assertSame( CycleStatus::PROCESSING, CycleStatus::processing()->get_value() );
		$this->assertSame( CycleStatus::BILLED, CycleStatus::billed()->get_value() );
		$this->assertSame( CycleStatus::FAILED, CycleStatus::failed()->get_value() );
		$this->assertSame( CycleStatus::CANCELLED, CycleStatus::cancelled()->get_value() );
	}

	public function test_from_builds_a_known_status(): void {
		$this->assertSame( CycleStatus::PENDING, CycleStatus::from( CycleStatus::PENDING )->get_value() );
	}

	public function test_from_rejects_an_unknown_status(): void {
		$this->expectException( DomainException::class );

		CycleStatus::from( 'nonsense' );
	}

	public function test_equals_compares_by_value(): void {
		$this->assertTrue( CycleStatus::pending()->equals( CycleStatus::pending() ) );
		$this->assertFalse( CycleStatus::pending()->equals( CycleStatus::billed() ) );
	}

	public function test_can_transition_to_mirrors_the_static_table(): void {
		$this->assertTrue( CycleStatus::pending()->can_transition_to( CycleStatus::billed() ) );
		$this->assertFalse( CycleStatus::failed()->can_transition_to( CycleStatus::billed() ) );
	}

	public function test_transition_to_returns_the_target_for_a_legal_move(): void {
		$next = CycleStatus::pending()->transition_to( CycleStatus::billed() );

		$this->assertTrue( $next->equals( CycleStatus::billed() ) );
	}

	public function test_transition_to_throws_for_an_illegal_move(): void {
		$this->expectException( DomainException::class );

		CycleStatus::failed()->transition_to( CycleStatus::billed() );
	}
}
