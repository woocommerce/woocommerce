<?php
/**
 * Integration tests for the Cancellation contract operation's period-end mode: ACTIVE ->
 * PENDING_CANCELLATION, the end date stamped, and the next-payment date left in place so
 * the contract lapses at period end. (The immediate-cancel mode is covered through the
 * facade suite.)
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Contracts;

use DomainException;
use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation
 */
class CancellationTest extends EngineIntegrationTestCase {

	/**
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * @var Cancellation
	 */
	private $sut;

	public function set_up(): void {
		parent::set_up();
		$this->contracts = new ContractRepository();
		$this->sut       = new Cancellation( $this->contracts );
	}

	/**
	 * Seed an active contract with a future next-payment date.
	 *
	 * @param string|null $end_gmt Optional pre-set end date.
	 */
	private function seed_active( ?string $end_gmt = null ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'status'           => ContractStatus::ACTIVE,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2099-01-01 00:00:00',
				'end_gmt'          => $end_gmt,
				'billing_total'    => '19.99',
			)
		);

		return $this->contracts->insert( $contract );
	}

	public function test_winds_down_to_pending_cancellation_and_stamps_the_end_date(): void {
		$id = $this->seed_active();

		$result = $this->sut->cancel_at_period_end( $this->reload( $id ) );

		$this->assertTrue( $result );
		$stored = $this->reload( $id );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $stored->get_status() );
		// The next-payment moment becomes the contract end (the "cancels on" date).
		$this->assertSame( '2099-01-01 00:00:00', $stored->get_end_gmt() );
	}

	public function test_leaves_the_next_payment_in_place_so_the_contract_lapses_at_the_date(): void {
		// The next-payment date is deliberately left in place; the due scan refuses to
		// charge a non-active contract, so no renewal fires while it winds down.
		$id = $this->seed_active();

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$this->assertSame( '2099-01-01 00:00:00', $this->reload( $id )->get_next_payment_gmt(), 'The contract lapses at the date; the next-payment date stays in place.' );
	}

	public function test_preserves_an_existing_end_date(): void {
		$id = $this->seed_active( '2026-09-09 00:00:00' );

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$this->assertSame( '2026-09-09 00:00:00', $this->reload( $id )->get_end_gmt() );
	}

	public function test_fires_the_pending_cancellation_action(): void {
		$id    = $this->seed_active();
		$fired = 0;
		add_action(
			Cancellation::CONTRACT_PENDING_CANCELLATION_ACTION,
			static function () use ( &$fired ): void {
				++$fired;
			}
		);

		$this->sut->cancel_at_period_end( $this->reload( $id ) );

		$this->assertSame( 1, $fired );
	}

	public function test_rejects_a_terminal_contract(): void {
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'status'          => ContractStatus::CANCELLED,
				'currency'        => 'USD',
				'selling_plan_id' => 1,
				'start_gmt'       => '2026-01-01 00:00:00',
				'billing_total'   => '19.99',
			)
		);
		$id       = $this->contracts->insert( $contract );

		$this->expectException( DomainException::class );
		$this->sut->cancel_at_period_end( $this->reload( $id ) );
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
