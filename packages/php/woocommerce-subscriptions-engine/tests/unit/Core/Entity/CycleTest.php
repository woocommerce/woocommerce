<?php
/**
 * Unit tests for the Cycle entity.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle
 */
class CycleTest extends TestCase {

	/**
	 * Build a pending billing cycle with sensible defaults, overridable per test.
	 *
	 * @param array<string, mixed> $overrides Field overrides.
	 */
	private function make_pending( array $overrides = array() ): Cycle {
		return Cycle::create(
			array_merge(
				array(
					'contract_id'    => 7,
					'sequence_no'    => 1,
					'count'          => 1,
					'starts_at_gmt'  => '2026-02-01 00:00:00',
					'ends_at_gmt'    => '2026-03-01 00:00:00',
					'expected_total' => '20.00',
					'currency'       => 'USD',
				),
				$overrides
			)
		);
	}

	public function test_create_builds_a_pending_billing_cycle(): void {
		$cycle = $this->make_pending();

		$this->assertNull( $cycle->get_id() );
		$this->assertSame( 7, $cycle->get_contract_id() );
		$this->assertSame( 1, $cycle->get_sequence_no() );
		$this->assertSame( 1, $cycle->get_count() );
		$this->assertSame( Cycle::KIND_BILLING, $cycle->get_kind() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::pending() ) );
		$this->assertNull( $cycle->get_reason() );
		$this->assertSame( '2026-02-01 00:00:00', $cycle->get_starts_at_gmt() );
		$this->assertSame( '2026-03-01 00:00:00', $cycle->get_ends_at_gmt() );
		$this->assertSame( '20.00000000', $cycle->get_expected_total() );
		$this->assertSame( 'USD', $cycle->get_currency() );
		$this->assertNull( $cycle->get_order_id() );
		$this->assertNull( $cycle->get_extension_slug() );
		$this->assertNull( $cycle->get_plan_snapshot_id() );
		$this->assertNull( $cycle->get_items_snapshot_id() );
	}

	public function test_expected_total_preserves_a_large_realistic_amount(): void {
		// Money normalizes through a float (the same path as core's wc_format_decimal),
		// which is exact within double precision - so a realistic large amount with full
		// 8-decimal scale round-trips unchanged.
		$cycle = $this->make_pending( array( 'expected_total' => '12345.12345678' ) );

		$this->assertSame( '12345.12345678', $cycle->get_expected_total() );
	}

	public function test_create_can_build_a_billed_cycle_directly(): void {
		// The checkout signup cycle is created directly billed (the origin order is paid).
		$cycle = $this->make_pending( array( 'status' => CycleStatus::billed() ) );

		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	public function test_create_requires_a_contract_id(): void {
		$this->expectException( DomainException::class );

		Cycle::create(
			array(
				'sequence_no'    => 1,
				'starts_at_gmt'  => '2026-02-01 00:00:00',
				'ends_at_gmt'    => '2026-03-01 00:00:00',
				'expected_total' => '20.00',
				'currency'       => 'USD',
			)
		);
	}

	public function test_create_accepts_the_known_billing_kind(): void {
		$cycle = $this->make_pending( array( 'kind' => Cycle::KIND_BILLING ) );

		$this->assertSame( Cycle::KIND_BILLING, $cycle->get_kind() );
	}

	public function test_create_accepts_an_unknown_but_well_formed_kind(): void {
		// kind is known-but-extensible: a third party may introduce its own kind,
		// so an unrecognized non-empty kind is accepted rather than rejected.
		$cycle = $this->make_pending( array( 'kind' => 'shipping' ) );

		$this->assertSame( 'shipping', $cycle->get_kind() );
	}

	public function test_create_rejects_an_empty_kind(): void {
		$this->expectException( DomainException::class );

		$this->make_pending( array( 'kind' => '' ) );
	}

	public function test_create_rejects_a_non_positive_sequence_no(): void {
		$this->expectException( DomainException::class );

		$this->make_pending( array( 'sequence_no' => 0 ) );
	}

	public function test_create_rejects_a_non_positive_count(): void {
		$this->expectException( DomainException::class );

		$this->make_pending( array( 'count' => 0 ) );
	}

	public function test_create_allows_a_null_count_for_a_non_counting_cycle(): void {
		// A non-counting cycle (for example a future trial period) carries no
		// chargeable number, so count is null and no charge idempotency anchor applies.
		$cycle = $this->make_pending( array( 'count' => null ) );

		$this->assertNull( $cycle->get_count() );
	}

	public function test_create_rejects_an_invalid_status(): void {
		$this->expectException( DomainException::class );

		$this->make_pending( array( 'status' => 'nonsense' ) );
	}

	public function test_create_carries_order_id_and_owner(): void {
		$cycle = $this->make_pending(
			array(
				'order_id'       => 123,
				'extension_slug' => 'acme-subs',
			)
		);

		$this->assertSame( 123, $cycle->get_order_id() );
		$this->assertSame( 'acme-subs', $cycle->get_extension_slug() );
	}

	public function test_holds_snapshot_vos_when_provided(): void {
		$plan  = PlanSnapshot::from_array( array( 'selling_plan_id' => 7 ) );
		$items = ItemsSnapshot::from_items( array( array( 'product_id' => 42 ) ) );

		$cycle = $this->make_pending(
			array(
				'plan_snapshot'  => $plan,
				'items_snapshot' => $items,
			)
		);

		$this->assertSame( $plan, $cycle->get_plan_snapshot() );
		$this->assertSame( $items, $cycle->get_items_snapshot() );
	}

	public function test_status_changes_go_through_cycle_status(): void {
		$cycle = $this->make_pending();

		$cycle->set_status( CycleStatus::billed() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	public function test_status_change_rejects_an_illegal_transition(): void {
		$cycle = $this->make_pending( array( 'status' => CycleStatus::billed() ) );

		// billed is terminal, so it cannot move to failed.
		$this->expectException( DomainException::class );
		$cycle->set_status( CycleStatus::failed() );
	}

	public function test_setting_the_same_status_is_a_no_op(): void {
		$cycle = $this->make_pending();

		$cycle->set_status( CycleStatus::pending() );

		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::pending() ) );
	}

	public function test_period_boundaries_are_frozen_at_construction(): void {
		// The Cycle is an immutable record: there is no setter for the period
		// boundaries, they are fixed by create()/from_storage(). Asserting the
		// public surface via reflection means re-introducing a setter trips this.
		$reflection = new \ReflectionClass( Cycle::class );

		$this->assertFalse( $reflection->hasMethod( 'set_starts_at_gmt' ) );
		$this->assertFalse( $reflection->hasMethod( 'set_ends_at_gmt' ) );
	}

	public function test_plan_snapshot_id_is_stamped_write_once(): void {
		$cycle = $this->make_pending();

		$cycle->set_plan_snapshot_id( 11 );
		$cycle->set_items_snapshot_id( 22 );

		$this->assertSame( 11, $cycle->get_plan_snapshot_id() );
		$this->assertSame( 22, $cycle->get_items_snapshot_id() );
	}

	public function test_plan_snapshot_id_cannot_be_re_pointed(): void {
		$cycle = $this->make_pending();
		$cycle->set_plan_snapshot_id( 11 );

		$this->expectException( DomainException::class );
		$cycle->set_plan_snapshot_id( 99 );
	}

	public function test_items_snapshot_id_cannot_be_re_pointed(): void {
		$cycle = $this->make_pending();
		$cycle->set_items_snapshot_id( 22 );

		$this->expectException( DomainException::class );
		$cycle->set_items_snapshot_id( 99 );
	}

	public function test_plan_snapshot_vo_is_attached_write_once(): void {
		$cycle = $this->make_pending();

		$plan = PlanSnapshot::from_array( array( 'selling_plan_id' => 9 ) );
		$cycle->set_plan_snapshot( $plan );

		$this->assertSame( $plan, $cycle->get_plan_snapshot() );
	}

	public function test_plan_snapshot_vo_cannot_be_replaced(): void {
		$cycle = $this->make_pending( array( 'plan_snapshot' => PlanSnapshot::from_array( array( 'selling_plan_id' => 9 ) ) ) );

		$this->expectException( DomainException::class );
		$cycle->set_plan_snapshot( PlanSnapshot::from_array( array( 'selling_plan_id' => 10 ) ) );
	}

	public function test_items_snapshot_vo_cannot_be_replaced(): void {
		$cycle = $this->make_pending( array( 'items_snapshot' => ItemsSnapshot::from_items( array() ) ) );

		$this->expectException( DomainException::class );
		$cycle->set_items_snapshot( ItemsSnapshot::from_items( array( array( 'product_id' => 1 ) ) ) );
	}

	public function test_reason_can_be_annotated_on_a_pending_cycle(): void {
		$cycle = $this->make_pending();

		$cycle->set_reason( 'flagged for review' );

		$this->assertSame( 'flagged for review', $cycle->get_reason() );
	}

	public function test_reason_can_be_annotated_when_cancelling_a_pending_cycle(): void {
		$cycle = $this->make_pending();

		$cycle->set_status( CycleStatus::cancelled() );
		$cycle->set_reason( 'customer requested cancellation' );

		$this->assertSame( 'customer requested cancellation', $cycle->get_reason() );
	}

	public function test_reason_can_be_annotated_on_a_billed_cycle(): void {
		// `reason` is one of the few mutable fields: any cycle may carry one.
		$cycle = $this->make_pending( array( 'status' => CycleStatus::billed() ) );

		$cycle->set_reason( 'settled after retry' );

		$this->assertSame( 'settled after retry', $cycle->get_reason() );
	}

	public function test_reason_can_be_annotated_on_a_failed_cycle(): void {
		$cycle = $this->make_pending();
		$cycle->set_status( CycleStatus::failed() );

		$cycle->set_reason( 'gateway declined the charge' );

		$this->assertSame( 'gateway declined the charge', $cycle->get_reason() );
	}

	public function test_from_storage_hydrates_a_persisted_cycle(): void {
		$cycle = Cycle::from_storage(
			array(
				'id'                => 5,
				'contract_id'       => 7,
				'sequence_no'       => 2,
				'count'             => 2,
				'kind'              => Cycle::KIND_BILLING,
				'status'            => 'billed',
				'reason'            => null,
				'starts_at_gmt'     => '2026-03-01 00:00:00',
				'ends_at_gmt'       => '2026-04-01 00:00:00',
				'expected_total'    => '20.00',
				'currency'          => 'USD',
				'plan_snapshot_id'  => 11,
				'items_snapshot_id' => 22,
				'order_id'          => 123,
				'extension_slug'    => 'acme-subs',
			)
		);

		$this->assertSame( 5, $cycle->get_id() );
		$this->assertSame( 7, $cycle->get_contract_id() );
		$this->assertSame( 2, $cycle->get_sequence_no() );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
		$this->assertSame( 11, $cycle->get_plan_snapshot_id() );
		$this->assertSame( 22, $cycle->get_items_snapshot_id() );
		$this->assertSame( 123, $cycle->get_order_id() );
		$this->assertSame( 'acme-subs', $cycle->get_extension_slug() );
	}

	public function test_from_storage_cycle_plan_snapshot_ref_is_write_once(): void {
		// A cycle hydrated from storage already carries non-null snapshot refs through
		// the constructor; the write-once guard must still reject re-pointing them.
		$cycle = Cycle::from_storage(
			array(
				'id'                => 5,
				'contract_id'       => 7,
				'sequence_no'       => 2,
				'count'             => 2,
				'kind'              => Cycle::KIND_BILLING,
				'status'            => 'billed',
				'reason'            => null,
				'starts_at_gmt'     => '2026-03-01 00:00:00',
				'ends_at_gmt'       => '2026-04-01 00:00:00',
				'expected_total'    => '20.00',
				'currency'          => 'USD',
				'plan_snapshot_id'  => 11,
				'items_snapshot_id' => 22,
				'order_id'          => 123,
				'extension_slug'    => 'acme-subs',
			)
		);

		$this->expectException( DomainException::class );
		$cycle->set_plan_snapshot_id( 99 );
	}

	public function test_from_storage_cycle_items_snapshot_ref_is_write_once(): void {
		$cycle = Cycle::from_storage(
			array(
				'id'                => 5,
				'contract_id'       => 7,
				'sequence_no'       => 2,
				'count'             => 2,
				'kind'              => Cycle::KIND_BILLING,
				'status'            => 'billed',
				'reason'            => null,
				'starts_at_gmt'     => '2026-03-01 00:00:00',
				'ends_at_gmt'       => '2026-04-01 00:00:00',
				'expected_total'    => '20.00',
				'currency'          => 'USD',
				'plan_snapshot_id'  => 11,
				'items_snapshot_id' => 22,
				'order_id'          => 123,
				'extension_slug'    => 'acme-subs',
			)
		);

		$this->expectException( DomainException::class );
		$cycle->set_items_snapshot_id( 99 );
	}

	public function test_from_storage_hydrates_a_non_counting_cycle(): void {
		$cycle = Cycle::from_storage(
			array(
				'id'             => 6,
				'contract_id'    => 7,
				'sequence_no'    => 1,
				'count'          => null,
				'kind'           => Cycle::KIND_BILLING,
				'status'         => 'pending',
				'starts_at_gmt'  => '2026-02-01 00:00:00',
				'ends_at_gmt'    => '2026-03-01 00:00:00',
				'expected_total' => '0',
				'currency'       => 'USD',
			)
		);

		$this->assertNull( $cycle->get_count() );
	}

	public function test_to_storage_excludes_id_and_serializes_the_row(): void {
		$cycle = $this->make_pending(
			array(
				'order_id'         => 123,
				'extension_slug'   => 'acme-subs',
				'plan_snapshot_id' => 11,
			)
		);
		$cycle->set_items_snapshot_id( 22 );

		$row = $cycle->to_storage();

		$this->assertArrayNotHasKey( 'id', $row );
		$this->assertArrayNotHasKey( 'chain_id', $row );
		$this->assertSame( 7, $row['contract_id'] );
		$this->assertSame( 1, $row['sequence_no'] );
		$this->assertSame( 1, $row['count'] );
		$this->assertSame( Cycle::KIND_BILLING, $row['kind'] );
		$this->assertSame( 'pending', $row['status'] );
		$this->assertSame( '20.00000000', $row['expected_total'] );
		$this->assertSame( 11, $row['plan_snapshot_id'] );
		$this->assertSame( 22, $row['items_snapshot_id'] );
		$this->assertSame( 123, $row['order_id'] );
		$this->assertSame( 'acme-subs', $row['extension_slug'] );
	}

	public function test_to_storage_serializes_the_status_as_its_string_value(): void {
		$row = $this->make_pending()->to_storage();

		// The status column stores the plain string value; the typed CycleStatus is
		// an in-memory concern only.
		$this->assertSame( 'pending', $row['status'] );
	}

	public function test_sequence_no_can_be_reassigned_after_construction(): void {
		$cycle = $this->make_pending( array( 'sequence_no' => 1 ) );

		$cycle->set_sequence_no( 4 );

		$this->assertSame( 4, $cycle->get_sequence_no() );
	}

	public function test_set_sequence_no_rejects_a_non_positive_value(): void {
		$cycle = $this->make_pending();

		$this->expectException( DomainException::class );
		$cycle->set_sequence_no( 0 );
	}

	public function test_id_can_be_stamped_after_persistence(): void {
		$cycle = $this->make_pending();

		$cycle->set_id( 5 );

		$this->assertSame( 5, $cycle->get_id() );
	}
}
