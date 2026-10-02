<?php
/**
 * Unit tests for the registry-backed CycleStatus value object.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\StatusRegistry;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus
 */
class CycleStatusTest extends TestCase {

	protected function tearDown(): void {
		StatusRegistry::reset();
		parent::tearDown();
	}

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

	public function test_billed_and_cancelled_are_terminal(): void {
		$this->assertTrue( CycleStatus::is_terminal( CycleStatus::BILLED ) );
		$this->assertTrue( CycleStatus::is_terminal( CycleStatus::CANCELLED ) );
	}

	public function test_pending_processing_and_failed_are_not_terminal(): void {
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::PENDING ) );
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::PROCESSING ) );
		$this->assertFalse( CycleStatus::is_terminal( CycleStatus::FAILED ) );
	}

	public function test_unknown_and_extension_registered_statuses_are_not_terminal(): void {
		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );

		$this->assertFalse( CycleStatus::is_terminal( 'disputed' ) );
		$this->assertFalse( CycleStatus::is_terminal( 'legacy-x' ) );
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

	public function test_from_accepts_an_extension_registered_status(): void {
		StatusRegistry::register( StatusRegistry::KIND_CYCLE, 'disputed' );

		$this->assertSame( 'disputed', CycleStatus::from( 'disputed' )->get_value() );
	}

	public function test_from_rejects_a_status_registered_for_contracts_only(): void {
		StatusRegistry::register( StatusRegistry::KIND_CONTRACT, 'disputed' );

		$this->expectException( DomainException::class );

		CycleStatus::from( 'disputed' );
	}

	public function test_stored_builds_an_unregistered_value_without_validation(): void {
		$this->assertSame( 'legacy-x', CycleStatus::stored( 'legacy-x' )->get_value() );
	}

	public function test_equals_compares_by_value(): void {
		$this->assertTrue( CycleStatus::pending()->equals( CycleStatus::pending() ) );
		$this->assertFalse( CycleStatus::pending()->equals( CycleStatus::billed() ) );
	}

	public function test_the_retired_transition_api_is_gone(): void {
		foreach ( array( 'is_transition_allowed', 'can_transition', 'assert_transition_allowed', 'can_transition_to', 'transition_to' ) as $method ) {
			$this->assertFalse( method_exists( CycleStatus::class, $method ), "CycleStatus::{$method}() should not exist." );
		}
	}
}
