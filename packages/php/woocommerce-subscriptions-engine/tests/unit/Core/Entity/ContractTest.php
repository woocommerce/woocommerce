<?php
/**
 * Unit tests for Contract hydration invariants.
 *
 * Confirms that Contract::from_storage() enforces the same boundary invariants
 * as the entity's setters, so a corrupted or migrated row cannot smuggle an
 * illegal value past hydration.
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
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract::from_storage
 */
class ContractTest extends TestCase {

	/**
	 * A complete, valid contract row.
	 *
	 * @param int $cycle_count Value for the cycle_count column.
	 * @return array<string, mixed>
	 */
	private function valid_row( int $cycle_count ): array {
		return array(
			'id'               => 10,
			'status'           => ContractStatus::ACTIVE,
			'customer_id'      => 1,
			'currency'         => 'USD',
			'selling_plan_id'  => 2,
			'origin_order_id'  => 3,
			'billing_total'    => '10.00',
			'start_gmt'        => '2026-01-01 00:00:00',
			'next_payment_gmt' => '2026-02-01 00:00:00',
			'cycle_count'      => $cycle_count,
		);
	}

	public function test_from_storage_hydrates_cycle_count(): void {
		$contract = Contract::from_storage( $this->valid_row( 3 ) );

		$this->assertSame( 3, $contract->get_cycle_count() );
	}

	public function test_from_storage_rejects_negative_cycle_count(): void {
		$this->expectException( DomainException::class );

		Contract::from_storage( $this->valid_row( -1 ) );
	}
}
