<?php
/**
 * Integration tests for the Hold contract operation: ACTIVE -> ON_HOLD, the
 * next-payment date preserved, and the held action fired.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Contracts;

use DomainException;
use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Hold
 */
class HoldTest extends EngineIntegrationTestCase {

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var Hold
	 */
	private $sut;

	public function set_up(): void {
		parent::set_up();
		$this->contracts = new ContractRepository();
		$this->sut       = new Hold( $this->contracts );
	}

	/**
	 * Seed a contract at a status with a future next-payment date.
	 *
	 * @param string $status Contract status.
	 */
	private function seed( string $status ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2099-01-01 00:00:00',
				'billing_total'    => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	public function test_hold_suspends_billing_by_moving_to_on_hold(): void {
		$id = $this->seed( ContractStatus::ACTIVE );

		$result = $this->sut->hold( $this->reload( $id ) );

		$this->assertTrue( $result );
		// No charge while held: the contract is on hold, and the batch due scan only bills active contracts.
		$this->assertSame( ContractStatus::ON_HOLD, $this->reload( $id )->get_status() );
	}

	public function test_hold_keeps_the_next_payment_for_a_later_reactivate(): void {
		$id = $this->seed( ContractStatus::ACTIVE );

		$this->sut->hold( $this->reload( $id ) );

		$this->assertSame( '2099-01-01 00:00:00', $this->reload( $id )->get_next_payment_gmt() );
	}

	public function test_hold_fires_the_held_action(): void {
		$id    = $this->seed( ContractStatus::ACTIVE );
		$fired = 0;
		add_action(
			Hold::CONTRACT_HELD_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->sut->hold( $this->reload( $id ) );

		$this->assertSame( 1, $fired );
	}

	public function test_hold_loses_the_race_to_a_concurrent_transition(): void {
		$id       = $this->seed( ContractStatus::ACTIVE );
		$contract = $this->reload( $id );

		// A concurrent request cancels the contract after our read: the compare-and-set
		// write must miss loudly instead of resurrecting the contract to on-hold.
		$concurrent = $this->reload( $id );
		$concurrent->set_status( ContractStatus::CANCELLED );
		$this->contracts->update( $concurrent );

		try {
			$this->sut->hold( $contract );
			$this->fail( 'Expected a DomainException when the conditional write misses.' );
		} catch ( DomainException $e ) {
			$this->assertSame( ContractStatus::CANCELLED, $this->reload( $id )->get_status(), 'The concurrent cancel is not clobbered.' );
		}
	}

	public function test_hold_rejects_a_cancelled_contract(): void {
		$id = $this->seed( ContractStatus::CANCELLED );

		$this->expectException( DomainException::class );
		$this->sut->hold( $this->reload( $id ) );
	}

	/**
	 * Reload a contract, asserting it still exists (narrows the nullable read).
	 *
	 * @param int $id Contract id.
	 */
	private function reload( int $id ): Contract {
		$contract = $this->contracts->find( $id );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract;
	}
}
