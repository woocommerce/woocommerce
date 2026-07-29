<?php
/**
 * Unit tests for the live Contract entity.
 *
 * Covers the live source-of-truth shape: stable identity plus the live schedule,
 * the latest/live snapshot references, and the live config values (the four totals
 * and the four stamps). The contract holds no in-memory cycle graph and no generic
 * cycle_count (counters are per-chain and derived from the cycle rows), and
 * origin_order_id may be null.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\InstrumentRef;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract
 */
class ContractTest extends TestCase {

	/**
	 * A complete, valid contract row.
	 *
	 * @return array<string, mixed>
	 */
	private function valid_row(): array {
		return array(
			'id'                => 10,
			'status'            => ContractStatus::ACTIVE,
			'customer_id'       => 1,
			'currency'          => 'USD',
			'selling_plan_id'   => 2,
			'origin_order_id'   => 3,
			'extension_slug'    => 'acme-subs',
			'start_gmt'         => '2026-01-01 00:00:00',
			'next_payment_gmt'  => '2026-02-01 00:00:00',
			'plan_snapshot_id'  => 11,
			'items_snapshot_id' => 22,
			'billing_total'     => '20.00',
			'discount_total'    => '0',
			'shipping_total'    => '5.00',
			'tax_total'         => '2.50',
			'last_payment_gmt'  => '2026-01-01 00:00:00',
			'last_attempt_gmt'  => '2026-01-01 00:00:00',
			'trial_end_gmt'     => null,
			'end_gmt'           => null,
			'schedule_source'   => Contract::SCHEDULE_SOURCE_PRIMITIVE,
		);
	}

	private function make_contract(): Contract {
		return Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => 2,
				'origin_order_id'  => 3,
				'extension_slug'   => 'acme-subs',
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2026-02-01 00:00:00',
			)
		);
	}

	/**
	 * @testdox create() builds an active contract from its identity and live config.
	 */
	public function test_create_builds_an_active_contract(): void {
		$contract = $this->make_contract();

		$this->assertNull( $contract->get_id() );
		$this->assertSame( ContractStatus::ACTIVE, $contract->get_status() );
		$this->assertSame( 1, $contract->get_customer_id() );
		$this->assertSame( 'USD', $contract->get_currency() );
		$this->assertSame( 2, $contract->get_selling_plan_id() );
		$this->assertSame( 3, $contract->get_origin_order_id() );
		$this->assertSame( 'acme-subs', $contract->get_extension_slug() );
		$this->assertSame( '2026-01-01 00:00:00', $contract->get_start_gmt() );
		$this->assertSame( '2026-02-01 00:00:00', $contract->get_next_payment_gmt() );
		$this->assertSame( Contract::SCHEDULE_SOURCE_PRIMITIVE, $contract->get_schedule_source() );
	}

	/**
	 * @testdox create() defaults the live config to empty values.
	 */
	public function test_create_defaults_live_config(): void {
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 2,
				'start_gmt'       => '2026-01-01 00:00:00',
			)
		);

		$this->assertNull( $contract->get_next_payment_gmt() );
		$this->assertNull( $contract->get_extension_slug() );
		$this->assertNull( $contract->get_origin_order_id() );
		$this->assertNull( $contract->get_plan_snapshot_id() );
		$this->assertNull( $contract->get_items_snapshot_id() );
		$this->assertSame( '0.00000000', $contract->get_billing_total() );
		$this->assertSame( '0.00000000', $contract->get_discount_total() );
		$this->assertSame( '0.00000000', $contract->get_shipping_total() );
		$this->assertSame( '0.00000000', $contract->get_tax_total() );
		$this->assertNull( $contract->get_last_payment_gmt() );
		$this->assertNull( $contract->get_last_attempt_gmt() );
		$this->assertNull( $contract->get_trial_end_gmt() );
		$this->assertNull( $contract->get_end_gmt() );
	}

	/**
	 * @testdox create() normalizes the live totals to the storage scale.
	 */
	public function test_create_normalizes_live_totals(): void {
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 2,
				'origin_order_id' => 3,
				'start_gmt'       => '2026-01-01 00:00:00',
				'billing_total'   => '20.00',
				'discount_total'  => '1.5',
				'shipping_total'  => '5',
				'tax_total'       => '2.345',
			)
		);

		$this->assertSame( '20.00000000', $contract->get_billing_total() );
		$this->assertSame( '1.50000000', $contract->get_discount_total() );
		$this->assertSame( '5.00000000', $contract->get_shipping_total() );
		$this->assertSame( '2.34500000', $contract->get_tax_total() );
	}

	/**
	 * @testdox create() allows a null origin_order_id (a manual/admin contract).
	 */
	public function test_create_allows_a_null_origin_order_id(): void {
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 2,
				'start_gmt'       => '2026-01-01 00:00:00',
			)
		);

		$this->assertNull( $contract->get_origin_order_id() );
	}

	/**
	 * @testdox create() rejects an invalid status.
	 */
	public function test_create_rejects_an_invalid_status(): void {
		$this->expectException( DomainException::class );

		Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 2,
				'origin_order_id' => 3,
				'start_gmt'       => '2026-01-01 00:00:00',
				'status'          => 'nonsense',
			)
		);
	}

	/**
	 * @testdox create() rejects an invalid schedule source.
	 */
	public function test_create_rejects_an_invalid_schedule_source(): void {
		$this->expectException( DomainException::class );

		Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 2,
				'origin_order_id' => 3,
				'start_gmt'       => '2026-01-01 00:00:00',
				'schedule_source' => 'nonsense',
			)
		);
	}

	/**
	 * @testdox The payment instrument round-trips through an InstrumentRef.
	 */
	public function test_payment_instrument_round_trips(): void {
		$contract = $this->make_contract();

		$contract->set_payment_instrument( new InstrumentRef( 99, 'dummy', 'Dummy Gateway' ) );

		$instrument = $contract->get_payment_instrument();
		$this->assertSame( 99, $instrument->get_token_id() );
		$this->assertSame( 'dummy', $instrument->get_gateway() );
		$this->assertSame( 'Dummy Gateway', $instrument->get_title() );
	}

	/**
	 * @testdox The live schedule is replaceable.
	 */
	public function test_next_payment_schedule_is_replaceable(): void {
		$contract = $this->make_contract();

		$contract->set_next_payment_gmt( '2026-03-01 00:00:00' );
		$this->assertSame( '2026-03-01 00:00:00', $contract->get_next_payment_gmt() );

		$contract->set_next_payment_gmt( null );
		$this->assertNull( $contract->get_next_payment_gmt() );
	}

	/**
	 * @testdox The live snapshot references and stamps are settable over the contract's life.
	 */
	public function test_live_snapshot_refs_and_stamps_are_settable(): void {
		$contract = $this->make_contract();

		$contract->set_plan_snapshot_id( 11 );
		$contract->set_items_snapshot_id( 22 );
		$contract->set_billing_total( '49.00' );
		$contract->set_discount_total( '1.00' );
		$contract->set_shipping_total( '5.00' );
		$contract->set_tax_total( '2.50' );
		$contract->set_last_payment_gmt( '2026-02-01 00:00:00' );
		$contract->set_last_attempt_gmt( '2026-02-01 00:00:00' );
		$contract->set_trial_end_gmt( '2026-01-15 00:00:00' );
		$contract->set_end_gmt( '2027-01-01 00:00:00' );

		$this->assertSame( 11, $contract->get_plan_snapshot_id() );
		$this->assertSame( 22, $contract->get_items_snapshot_id() );
		$this->assertSame( '49.00000000', $contract->get_billing_total() );
		$this->assertSame( '1.00000000', $contract->get_discount_total() );
		$this->assertSame( '5.00000000', $contract->get_shipping_total() );
		$this->assertSame( '2.50000000', $contract->get_tax_total() );
		$this->assertSame( '2026-02-01 00:00:00', $contract->get_last_payment_gmt() );
		$this->assertSame( '2026-02-01 00:00:00', $contract->get_last_attempt_gmt() );
		$this->assertSame( '2026-01-15 00:00:00', $contract->get_trial_end_gmt() );
		$this->assertSame( '2027-01-01 00:00:00', $contract->get_end_gmt() );
	}

	/**
	 * @testdox The live snapshot reference can be re-pointed (unlike a cycle's frozen ref).
	 */
	public function test_live_snapshot_ref_can_be_repointed(): void {
		$contract = $this->make_contract();

		// The contract holds the latest/live snapshot ref and re-points it when the
		// plan changes; this is the intentional contrast with a cycle's write-once ref.
		$contract->set_plan_snapshot_id( 11 );
		$contract->set_plan_snapshot_id( 99 );

		$this->assertSame( 99, $contract->get_plan_snapshot_id() );
	}

	/**
	 * @testdox from_storage() hydrates the identity, schedule, refs, and live config.
	 */
	public function test_from_storage_hydrates_the_live_state(): void {
		$contract = Contract::from_storage( $this->valid_row() );

		$this->assertSame( 10, $contract->get_id() );
		$this->assertSame( ContractStatus::ACTIVE, $contract->get_status() );
		$this->assertSame( 1, $contract->get_customer_id() );
		$this->assertSame( 2, $contract->get_selling_plan_id() );
		$this->assertSame( 3, $contract->get_origin_order_id() );
		$this->assertSame( 'acme-subs', $contract->get_extension_slug() );
		$this->assertSame( '2026-02-01 00:00:00', $contract->get_next_payment_gmt() );
		$this->assertSame( 11, $contract->get_plan_snapshot_id() );
		$this->assertSame( 22, $contract->get_items_snapshot_id() );
		$this->assertSame( '20.00000000', $contract->get_billing_total() );
		$this->assertSame( '0.00000000', $contract->get_discount_total() );
		$this->assertSame( '5.00000000', $contract->get_shipping_total() );
		$this->assertSame( '2.50000000', $contract->get_tax_total() );
		$this->assertSame( '2026-01-01 00:00:00', $contract->get_last_payment_gmt() );
		$this->assertSame( '2026-01-01 00:00:00', $contract->get_last_attempt_gmt() );
		$this->assertNull( $contract->get_trial_end_gmt() );
		$this->assertNull( $contract->get_end_gmt() );
		$this->assertSame( Contract::SCHEDULE_SOURCE_PRIMITIVE, $contract->get_schedule_source() );
	}

	/**
	 * @testdox from_storage() hydrates a manual/admin contract with a null origin order.
	 */
	public function test_from_storage_hydrates_a_null_origin_order(): void {
		$row                    = $this->valid_row();
		$row['origin_order_id'] = null;

		$contract = Contract::from_storage( $row );

		$this->assertNull( $contract->get_origin_order_id() );
	}

	/**
	 * @testdox from_storage() hydrates the plan snapshot, items, addresses, and meta children.
	 */
	public function test_from_storage_hydrates_children(): void {
		$snapshot  = PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ) );
		$items     = array( array( 'product_id' => 42 ) );
		$addresses = array( 'billing' => array( 'first_name' => 'Ada' ) );
		$meta      = array( 'flag' => 'on' );

		$contract = Contract::from_storage( $this->valid_row(), $snapshot, $items, $addresses, $meta );

		$this->assertSame( $snapshot, $contract->get_plan_snapshot() );
		$this->assertSame( $items, $contract->get_items() );
		$this->assertSame( $addresses, $contract->get_addresses() );
		$this->assertSame( $meta, $contract->get_meta() );
	}

	/**
	 * @testdox from_storage() leaves the plan snapshot null when none is passed.
	 */
	public function test_from_storage_defaults_to_no_plan_snapshot(): void {
		$this->assertNull( Contract::from_storage( $this->valid_row() )->get_plan_snapshot() );
	}

	/**
	 * @testdox to_storage() carries the full live column set.
	 */
	public function test_to_storage_carries_the_live_column_set(): void {
		$row = $this->make_contract()->to_storage();

		// Assert the key SET, not the insertion order: the row's column order is
		// not load-bearing, so canonicalize to avoid a brittle ordering coupling.
		$this->assertEqualsCanonicalizing(
			array(
				'status',
				'customer_id',
				'currency',
				'selling_plan_id',
				'origin_order_id',
				'extension_slug',
				'payment_method',
				'payment_method_title',
				'payment_token_id',
				'start_gmt',
				'next_payment_gmt',
				'plan_snapshot_id',
				'items_snapshot_id',
				'billing_total',
				'discount_total',
				'shipping_total',
				'tax_total',
				'last_payment_gmt',
				'last_attempt_gmt',
				'trial_end_gmt',
				'end_gmt',
				'schedule_source',
			),
			array_keys( $row )
		);
	}

	/**
	 * @testdox to_storage() does not carry a generic cycle_count column.
	 */
	public function test_to_storage_has_no_generic_cycle_count(): void {
		$row = $this->make_contract()->to_storage();

		$this->assertArrayNotHasKey( 'cycle_count', $row, 'to_storage() must not carry a generic cycle_count; counters are per-chain and derived.' );
	}

	/**
	 * @testdox the hydrated plan snapshot is null until set, then returns what was set.
	 */
	public function test_plan_snapshot_hydration_round_trips(): void {
		$contract = $this->make_contract();
		$this->assertNull( $contract->get_plan_snapshot() );

		$snapshot = PlanSnapshot::from_array(
			array(
				'selling_plan_id' => 2,
				'billing_policy'  => array(
					'period'   => 'month',
					'interval' => 1,
				),
			)
		);
		$contract->set_plan_snapshot( $snapshot );

		$this->assertSame( $snapshot, $contract->get_plan_snapshot() );
	}

	/**
	 * @testdox the hydrated plan snapshot is not a stored column.
	 */
	public function test_plan_snapshot_is_not_in_to_storage(): void {
		$contract = $this->make_contract();
		$contract->set_plan_snapshot( PlanSnapshot::from_array( array( 'selling_plan_id' => 2 ) ) );

		$this->assertArrayNotHasKey( 'plan_snapshot', $contract->to_storage(), 'plan_snapshot is a hydrated read-only field, not a stored column.' );
	}
}
