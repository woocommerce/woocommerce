<?php
/**
 * Integration tests for the customer-scoped contract reads on ContractRepository:
 * find_by_customer_id (owner scoping, ordering, status filter, paging) and
 * find_for_customer (the ownership-filtered full read behind the asymmetric
 * not-found rule).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository
 */
class ContractCustomerReadTest extends EngineIntegrationTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var ContractRepository
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ContractRepository();
	}

	/**
	 * Insert a contract for a customer at a given status.
	 *
	 * @param int    $customer_id Owning customer.
	 * @param string $status      Contract status.
	 */
	private function seed( int $customer_id, string $status ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => $customer_id,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2026-02-01 00:00:00',
				'billing_total'    => '10.00',
			)
		);

		return $this->sut->insert( $contract );
	}

	/**
	 * Map a result set to its contract ids.
	 *
	 * @param array<int, Contract> $contracts Contracts.
	 * @return array<int, int>
	 */
	private function ids( array $contracts ): array {
		return array_map(
			static function ( Contract $contract ): int {
				return (int) $contract->get_id();
			},
			$contracts
		);
	}

	public function test_returns_only_the_requested_customers_contracts(): void {
		$mine_a = $this->seed( 10, ContractStatus::ACTIVE );
		$mine_b = $this->seed( 10, ContractStatus::ON_HOLD );
		$theirs = $this->seed( 20, ContractStatus::ACTIVE );

		$ids = $this->ids( $this->sut->find_by_customer_id( 10 ) );

		$this->assertContains( $mine_a, $ids );
		$this->assertContains( $mine_b, $ids );
		$this->assertNotContains( $theirs, $ids );
		$this->assertCount( 2, $ids );
	}

	public function test_orders_newest_first(): void {
		$older = $this->seed( 11, ContractStatus::ACTIVE );
		$newer = $this->seed( 11, ContractStatus::ACTIVE );

		$results = $this->sut->find_by_customer_id( 11 );

		$this->assertSame( $newer, (int) $results[0]->get_id() );
		$this->assertSame( $older, (int) $results[1]->get_id() );
	}

	public function test_honours_a_status_filter(): void {
		$this->seed( 12, ContractStatus::ACTIVE );
		$held = $this->seed( 12, ContractStatus::ON_HOLD );

		$results = $this->sut->find_by_customer_id( 12, array( 'status' => ContractStatus::ON_HOLD ) );

		$this->assertCount( 1, $results );
		$this->assertSame( $held, (int) $results[0]->get_id() );
		$this->assertSame( ContractStatus::ON_HOLD, $results[0]->get_status() );
	}

	public function test_paging_with_limit_and_offset(): void {
		$ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$ids[] = $this->seed( 13, ContractStatus::ACTIVE );
		}
		rsort( $ids ); // Newest-first order the query returns.

		$page_one = $this->sut->find_by_customer_id(
			13,
			array(
				'limit'  => 2,
				'offset' => 0,
			)
		);
		$page_two = $this->sut->find_by_customer_id(
			13,
			array(
				'limit'  => 2,
				'offset' => 2,
			)
		);

		$this->assertSame( array( $ids[0], $ids[1] ), $this->ids( $page_one ) );
		$this->assertSame( array( $ids[2], $ids[3] ), $this->ids( $page_two ) );
	}

	public function test_empty_for_a_customer_with_no_contracts(): void {
		$this->assertSame( array(), $this->sut->find_by_customer_id( 999 ) );
	}

	public function test_find_for_customer_returns_the_owned_contract_hydrated(): void {
		$id = $this->seed( 14, ContractStatus::ACTIVE );

		$contract = $this->sut->find_for_customer( $id, 14 );

		$this->assertInstanceOf( Contract::class, $contract );
		$this->assertSame( $id, $contract->get_id() );
		$this->assertSame( 14, $contract->get_customer_id() );
	}

	public function test_find_for_customer_is_null_for_a_foreign_owned_contract(): void {
		$id = $this->seed( 14, ContractStatus::ACTIVE );

		$this->assertNull( $this->sut->find_for_customer( $id, 15 ) );
	}

	public function test_find_for_customer_is_null_for_an_unknown_contract(): void {
		// Indistinguishable from "owned by someone else" - the asymmetric not-found rule.
		$this->assertNull( $this->sut->find_for_customer( 123456, 14 ) );
	}
}
