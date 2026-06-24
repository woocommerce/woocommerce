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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator
 */
class RenewalCalculatorTest extends TestCase {

	private function policy( ?int $max_cycles, string $period = 'month', int $interval = 1 ): BillingPolicy {
		return new BillingPolicy( $period, $interval, null, $max_cycles, null );
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
		// 2026-01-15 23:30 in a +05:00 zone is 2026-01-15 18:30 UTC; adding a
		// day lands on 2026-01-16 18:30 UTC.
		$anchor = new DateTimeImmutable( '2026-01-15 23:30:00', new DateTimeZone( '+05:00' ) );

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );

		$this->assertSame( '2026-01-16 18:30:00', $next->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'UTC', $next->getTimezone()->getName() );
	}

	/**
	 * Build a billed previous cycle whose period ends at `$ends_at`, on the given cadence.
	 *
	 * @param string $ends_at        The previous period end (and the next period start).
	 * @param string $expected_total The previous cycle's expected total (the flat recurring amount).
	 * @param string $period         Cadence period.
	 * @param int    $interval       Cadence interval.
	 */
	private function previous_cycle( string $ends_at, string $expected_total = '19.99', string $period = 'month', int $interval = 1 ): Cycle {
		return Cycle::create(
			array(
				'contract_id'    => 7,
				'sequence_no'    => 1,
				'count'          => 1,
				'status'         => CycleStatus::billed(),
				'starts_at_gmt'  => '2026-01-15 00:00:00',
				'ends_at_gmt'    => $ends_at,
				'expected_total' => $expected_total,
				'currency'       => 'USD',
				'plan_snapshot'  => PlanSnapshot::from_array(
					array(
						'selling_plan_id' => 7,
						'billing_policy'  => array(
							'period'   => $period,
							'interval' => $interval,
						),
					)
				),
			)
		);
	}

	/**
	 * @testdox compute_next_cycle starts the next period where the previous one ended.
	 */
	public function test_compute_next_cycle_period_starts_at_previous_period_end(): void {
		$previous = $this->previous_cycle( '2026-02-15 00:00:00' );
		$plan     = $previous->get_plan_snapshot();
		$this->assertInstanceOf( PlanSnapshot::class, $plan );
		$now = new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_next_cycle( $previous, $plan, $now );

		$this->assertSame( '2026-02-15 00:00:00', $spec->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $spec->get_ends_at_gmt() );
	}

	/**
	 * @testdox compute_next_cycle carries the flat recurring amount and currency forward.
	 */
	public function test_compute_next_cycle_carries_amount_and_currency_forward(): void {
		$previous = $this->previous_cycle( '2026-02-15 00:00:00', '24.50' );
		$plan     = $previous->get_plan_snapshot();
		$this->assertInstanceOf( PlanSnapshot::class, $plan );
		$now = new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_next_cycle( $previous, $plan, $now );

		$this->assertSame( '24.50000000', $spec->get_expected_total() );
		$this->assertSame( 'USD', $spec->get_currency() );
	}

	/**
	 * @testdox compute_next_cycle honours the plan cadence interval and period.
	 */
	public function test_compute_next_cycle_honours_cadence(): void {
		$previous = $this->previous_cycle( '2026-03-01 00:00:00', '19.99', 'week', 2 );
		$plan     = $previous->get_plan_snapshot();
		$this->assertInstanceOf( PlanSnapshot::class, $plan );
		$now = new DateTimeImmutable( '2026-03-01 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_next_cycle( $previous, $plan, $now );

		$this->assertSame( '2026-03-01 00:00:00', $spec->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $spec->get_ends_at_gmt() );
	}

	/**
	 * @testdox compute_next_cycle is calendar-aware for month-end roll-over.
	 */
	public function test_compute_next_cycle_is_calendar_aware(): void {
		// 31 Jan + 1 month rolls to 3 Mar (matching DateTimeImmutable::modify()).
		$previous = $this->previous_cycle( '2026-01-31 00:00:00' );
		$plan     = $previous->get_plan_snapshot();
		$this->assertInstanceOf( PlanSnapshot::class, $plan );
		$now = new DateTimeImmutable( '2026-01-31 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_next_cycle( $previous, $plan, $now );

		$this->assertSame( '2026-03-03 00:00:00', $spec->get_ends_at_gmt() );
	}

	/**
	 * Build a plan snapshot carrying just the cadence (the cadence source compute_first_cycle reads).
	 *
	 * @param string $period   Cadence period.
	 * @param int    $interval Cadence interval.
	 */
	private function plan_snapshot( string $period = 'month', int $interval = 1 ): PlanSnapshot {
		return PlanSnapshot::from_array(
			array(
				'selling_plan_id' => 7,
				'billing_policy'  => array(
					'period'   => $period,
					'interval' => $interval,
				),
			)
		);
	}

	/**
	 * @testdox compute_first_cycle runs one cadence forward from the period start.
	 */
	public function test_compute_first_cycle_runs_one_cadence_forward(): void {
		$start = new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_first_cycle( $this->plan_snapshot(), $start, '19.99', 'USD' );

		// Monthly cadence: the period ends one month after its start - a real one-cadence
		// period, never zero-duration.
		$this->assertSame( '2026-02-15 00:00:00', $spec->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $spec->get_ends_at_gmt() );
		$this->assertNotSame( $spec->get_starts_at_gmt(), $spec->get_ends_at_gmt() );
	}

	/**
	 * @testdox compute_first_cycle carries the supplied amount and currency.
	 */
	public function test_compute_first_cycle_carries_amount_and_currency(): void {
		$start = new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_first_cycle( $this->plan_snapshot(), $start, '24.50', 'EUR' );

		$this->assertSame( '24.50000000', $spec->get_expected_total() );
		$this->assertSame( 'EUR', $spec->get_currency() );
	}

	/**
	 * @testdox compute_first_cycle honours the plan cadence interval and period.
	 */
	public function test_compute_first_cycle_honours_cadence(): void {
		$start = new DateTimeImmutable( '2026-03-01 00:00:00', new DateTimeZone( 'UTC' ) );

		$spec = RenewalCalculator::compute_first_cycle( $this->plan_snapshot( 'week', 2 ), $start, '19.99', 'USD' );

		$this->assertSame( '2026-03-01 00:00:00', $spec->get_starts_at_gmt() );
		$this->assertSame( '2026-03-15 00:00:00', $spec->get_ends_at_gmt() );
	}

	/**
	 * @testdox compute_first_cycle is calendar-aware and normalizes a non-UTC start to UTC.
	 */
	public function test_compute_first_cycle_is_calendar_aware_and_utc_normalized(): void {
		// 2026-01-31 18:30 in a +05:00 zone is 2026-01-31 13:30 UTC; +1 month rolls to 3 Mar.
		$start = new DateTimeImmutable( '2026-01-31 18:30:00', new DateTimeZone( '+05:00' ) );

		$spec = RenewalCalculator::compute_first_cycle( $this->plan_snapshot(), $start, '19.99', 'USD' );

		$this->assertSame( '2026-01-31 13:30:00', $spec->get_starts_at_gmt() );
		$this->assertSame( '2026-03-03 13:30:00', $spec->get_ends_at_gmt() );
	}
}
