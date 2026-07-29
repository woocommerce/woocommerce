<?php
/**
 * Unit tests for RenewalCalculator.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator
 */
class RenewalCalculatorTest extends TestCase {

	private function policy( ?int $max_cycles, string $period = 'month', int $interval = 1 ): BillingPolicy {
		return new BillingPolicy( $period, $interval, null, $max_cycles, null );
	}

	/**
	 * The standard next-cycle inputs, overridable per test.
	 *
	 * @param array<string, mixed> $overrides Values to override.
	 * @return array<string, mixed>
	 */
	private function values( array $overrides = array() ): array {
		return array_merge(
			array(
				'contract_id'       => 7,
				'sequence_no'       => 2,
				'count'             => 2,
				'period_start'      => '2026-02-15 00:00:00',
				'expected_total'    => '19.99',
				'currency'          => 'USD',
				'extension_slug'    => 'lite',
				'plan_snapshot_id'  => 11,
				'items_snapshot_id' => 12,
			),
			$overrides
		);
	}

	public function test_open_ended_policy_never_reaches_max_cycles(): void {
		$policy = $this->policy( null );

		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 0 ) );
		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 99 ) );
	}

	public function test_not_terminal_below_max_cycles(): void {
		$policy = $this->policy( 3 );

		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 0 ) );
		$this->assertFalse( RenewalCalculator::has_reached_max_cycles( $policy, 2 ) );
	}

	public function test_terminal_at_max_cycles(): void {
		$policy = $this->policy( 3 );

		$this->assertTrue( RenewalCalculator::has_reached_max_cycles( $policy, 3 ) );
	}

	public function test_terminal_when_over_counted(): void {
		$policy = $this->policy( 3 );

		$this->assertTrue( RenewalCalculator::has_reached_max_cycles( $policy, 4 ) );
	}

	public function test_next_bill_date_adds_one_cadence_in_utc(): void {
		$policy = $this->policy( null, 'month', 1 );
		$anchor = new DateTimeImmutable( '2026-01-15 10:00:00', new DateTimeZone( 'UTC' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-02-15 10:00:00', $next->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $next->getTimezone()->getName() );
	}

	public function test_next_bill_date_honours_interval(): void {
		$policy = $this->policy( null, 'week', 2 );
		$anchor = new DateTimeImmutable( '2026-03-01 00:00:00', new DateTimeZone( 'UTC' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-03-15 00:00:00', $next->format( 'Y-m-d H:i:s' ) );
	}

	public function test_next_bill_date_normalizes_non_utc_anchor_to_utc(): void {
		$policy = $this->policy( null, 'day', 1 );
		// 2026-01-15 23:30 in a +05:00 zone is 2026-01-15 18:30 UTC; adding a day lands on 2026-01-16 18:30 UTC.
		$anchor = new DateTimeImmutable( '2026-01-15 23:30:00', new DateTimeZone( '+05:00' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-01-16 18:30:00', $next->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $next->getTimezone()->getName() );
	}

	/**
	 * @testdox compute_next_cycle runs the period one cadence forward from the anchor.
	 */
	public function test_compute_next_cycle_runs_one_cadence_forward(): void {
		$cycle = RenewalCalculator::compute_next_cycle( $this->policy( null, 'month', 1 ), $this->values() );

		// Monthly cadence: the period ends one month after its start - a real one-cadence
		// period, never zero-duration.
		$this->assertSame( '2026-02-15 00:00:00', $cycle->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $cycle->get_ends_at_gmt() );
		$this->assertNotSame( $cycle->get_starts_at_gmt(), $cycle->get_ends_at_gmt() );
	}

	/**
	 * @testdox compute_next_cycle honours the cadence interval and period.
	 */
	public function test_compute_next_cycle_honours_cadence(): void {
		$cycle = RenewalCalculator::compute_next_cycle(
			$this->policy( null, 'week', 2 ),
			$this->values( array( 'period_start' => '2026-03-01 00:00:00' ) )
		);

		$this->assertSame( '2026-03-01 00:00:00', $cycle->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $cycle->get_ends_at_gmt() );
	}

	/**
	 * @testdox compute_next_cycle returns a pending cycle carrying the passed count, amount, currency, and snapshots.
	 */
	public function test_compute_next_cycle_carries_passed_values(): void {
		$cycle = RenewalCalculator::compute_next_cycle(
			$this->policy( null, 'month', 1 ),
			$this->values(
				array(
					'count'          => 5,
					'sequence_no'    => 5,
					'expected_total' => '24.50',
					'currency'       => 'EUR',
				)
			)
		);

		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::pending() ) );
		$this->assertSame( 7, $cycle->get_contract_id() );
		$this->assertSame( 5, $cycle->get_count() );
		$this->assertSame( 5, $cycle->get_sequence_no() );
		$this->assertEquals( 24.50, (float) $cycle->get_expected_total() );
		$this->assertSame( 'EUR', $cycle->get_currency() );
		$this->assertSame( 11, $cycle->get_plan_snapshot_id() );
		$this->assertSame( 12, $cycle->get_items_snapshot_id() );
	}

	/**
	 * @testdox compute_next_cycle is calendar-aware for month-end roll-over.
	 */
	public function test_compute_next_cycle_is_calendar_aware(): void {
		$cycle = RenewalCalculator::compute_next_cycle(
			$this->policy( null, 'month', 1 ),
			$this->values( array( 'period_start' => '2026-01-31 00:00:00' ) )
		);

		// One month from 31 Jan rolls to 3 Mar (Feb has no 31st), via the billing policy.
		$this->assertSame( '2026-01-31 00:00:00', $cycle->get_starts_at_gmt() );
		$this->assertSame( '2026-03-03 00:00:00', $cycle->get_ends_at_gmt() );
	}
}
