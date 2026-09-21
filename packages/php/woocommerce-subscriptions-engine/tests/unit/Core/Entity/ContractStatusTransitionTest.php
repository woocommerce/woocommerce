<?php
/**
 * Unit tests for status-transition enforcement on the Contract entity.
 *
 * Confirms that Contract::set_status() routes every status change through the
 * ContractStatus state machine, so illegal moves are rejected at the entity
 * boundary rather than being persisted.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract::set_status
 */
class ContractStatusTransitionTest extends TestCase {

	/**
	 * Build a minimal active contract for transition tests.
	 *
	 * @param string $status Starting status.
	 */
	private function make_contract( string $status = ContractStatus::ACTIVE ): Contract {
		return Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 1,
				'origin_order_id' => 1,
				'start_gmt'       => '2026-01-01 00:00:00',
				'status'          => $status,
				'billing_total'   => '10.00',
				'schedule_source' => Contract::SCHEDULE_SOURCE_PRIMITIVE,
				'items'           => array(),
				'addresses'       => array(),
				'meta'            => array(),
			)
		);
	}

	public function test_legal_transition_is_applied(): void {
		$contract = $this->make_contract( ContractStatus::ACTIVE );

		$contract->set_status( ContractStatus::ON_HOLD );

		$this->assertSame( ContractStatus::ON_HOLD, $contract->get_status() );
	}

	public function test_same_status_is_a_noop(): void {
		$contract = $this->make_contract( ContractStatus::ACTIVE );

		// Active is terminal-free but cannot transition to itself in the table;
		// set_status() must treat the no-op as a no-op, not an exception.
		$contract->set_status( ContractStatus::ACTIVE );

		$this->assertSame( ContractStatus::ACTIVE, $contract->get_status() );
	}

	public function test_illegal_transition_throws_and_leaves_status_unchanged(): void {
		$contract = $this->make_contract( ContractStatus::CANCELLED );

		try {
			$contract->set_status( ContractStatus::ACTIVE );
			$this->fail( 'Expected a DomainException for a transition out of a terminal status.' );
		} catch ( DomainException $e ) {
			$this->assertSame( ContractStatus::CANCELLED, $contract->get_status() );
		}
	}

	public function test_pending_cancellation_to_on_hold_is_rejected(): void {
		$contract = $this->make_contract( ContractStatus::PENDING_CANCELLATION );

		$this->expectException( DomainException::class );

		$contract->set_status( ContractStatus::ON_HOLD );
	}
}
