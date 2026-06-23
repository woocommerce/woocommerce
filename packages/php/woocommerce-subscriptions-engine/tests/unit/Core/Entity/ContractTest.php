<?php
/**
 * Unit tests for Contract construction invariants.
 *
 * Confirms that Contract::from_storage() enforces the same boundary invariants
 * as the entity's setters (a corrupted or migrated row cannot smuggle an illegal
 * value past hydration), and that Contract::create() rejects missing or invalid
 * required fields rather than coercing them to a silent default.
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
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract
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

	public function test_from_storage_rejects_non_integer_cycle_count(): void {
		$row                = $this->valid_row( 0 );
		$row['cycle_count'] = '1.5';

		$this->expectException( DomainException::class );

		Contract::from_storage( $row );
	}

	public function test_from_storage_rejects_invalid_schedule_source(): void {
		$row                    = $this->valid_row( 1 );
		$row['schedule_source'] = 'bogus';

		$this->expectException( DomainException::class );

		Contract::from_storage( $row );
	}

	/**
	 * A minimal, valid set of Contract::create() arguments.
	 *
	 * @return array<string, mixed>
	 */
	private function valid_create_args(): array {
		return array(
			'customer_id'     => 1,
			'currency'        => 'USD',
			'selling_plan_id' => 2,
			'origin_order_id' => 3,
			'start_gmt'       => '2026-01-01 00:00:00',
		);
	}

	public function test_create_succeeds_with_valid_required_fields(): void {
		$contract = Contract::create( $this->valid_create_args() );

		$this->assertSame( 1, $contract->get_customer_id() );
		$this->assertSame( 'USD', $contract->get_currency() );
		$this->assertSame( 2, $contract->get_selling_plan_id() );
		$this->assertSame( 3, $contract->get_origin_order_id() );
	}

	public function test_create_allows_guest_customer_id_zero(): void {
		$contract = Contract::create(
			array_merge( $this->valid_create_args(), array( 'customer_id' => 0 ) )
		);

		$this->assertSame( 0, $contract->get_customer_id() );
	}

	/**
	 * @dataProvider provide_invalid_create_args
	 * @param array<string, mixed> $overrides Field overrides that should make create() reject the args.
	 */
	public function test_create_rejects_invalid_required_fields( array $overrides ): void {
		$this->expectException( DomainException::class );

		Contract::create( array_merge( $this->valid_create_args(), $overrides ) );
	}

	/**
	 * @return array<string, array{0: array<string, mixed>}>
	 */
	public function provide_invalid_create_args(): array {
		return array(
			'missing customer_id'         => array( array( 'customer_id' => null ) ),
			'zero selling_plan_id'        => array( array( 'selling_plan_id' => 0 ) ),
			'non-numeric origin_order_id' => array( array( 'origin_order_id' => 'x' ) ),
			'missing currency'            => array( array( 'currency' => null ) ),
			'empty currency'              => array( array( 'currency' => '' ) ),
			'missing start_gmt'           => array( array( 'start_gmt' => null ) ),
		);
	}
}
