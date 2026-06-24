<?php
/**
 * RenewalCalculator - pure renewal date math and the cycle-advance decision.
 *
 * Answers two questions the renewal money-path needs, with no knowledge of
 * WordPress, Action Scheduler, orders, or the wall clock: given a plan's
 * {@see BillingPolicy} and where a contract is in its cycle count,
 *
 *   1. has the contract reached its hard end (max_cycles)? and
 *   2. if not, when is the next bill date, computed from an explicit `$now`?
 *
 * Core zone: WordPress-free by design. No WP/Woo symbols, and no time
 * functions - `$now` is always passed in by the integration layer so the math
 * is deterministic and unit-testable. All date math delegates to
 * {@see BillingPolicy} so there is a single cadence-math path in the package.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Pure renewal calculator.
 *
 * Stateless: every method is static and derives its answer solely from its
 * arguments. The integration layer (`Integration\Renewal\RenewalEngine`) reads
 * the contract row, calls into here for the decision, then writes the result
 * back and talks to Action Scheduler / the gateway.
 */
final class RenewalCalculator {

	/**
	 * Whether a contract that has paid `$cycle_count` cycles has reached the
	 * policy's hard end.
	 *
	 * `max_cycles` counts total billed cycles. A contract is terminal once its
	 * paid-cycle count is at or past `max_cycles`. Open-ended policies
	 * (`max_cycles === null`) never reach a hard end this way - they run until
	 * cancelled.
	 *
	 * The comparison is `>=`, not `===`: a contract that somehow over-counted
	 * (a replayed webhook that slipped the idempotency gate, a migrated row)
	 * should still be treated as terminal rather than billing forever past its
	 * cap.
	 *
	 * @param BillingPolicy $policy      The plan's billing policy.
	 * @param int           $cycle_count Count of successfully-billed cycles so far.
	 */
	public static function has_reached_max_cycles( BillingPolicy $policy, int $cycle_count ): bool {
		$max_cycles = $policy->get_max_cycles();

		if ( null === $max_cycles ) {
			return false;
		}

		return $cycle_count >= $max_cycles;
	}

	/**
	 * Compute the next bill date for a contract advancing past `$current_period_start`.
	 *
	 * Anchors on the moment the just-completed cycle was due (or, for the
	 * payment-anchored chain, the moment payment landed) and adds one cadence.
	 * Delegates to {@see BillingPolicy::compute_next_renewal_from()} so calendar
	 * semantics (month-end roll-over, DST) stay in one place. The result is in
	 * UTC; the caller formats it to the `Y-m-d H:i:s` GMT string the contract
	 * row stores.
	 *
	 * @param BillingPolicy     $policy               The plan's billing policy.
	 * @param DateTimeImmutable $current_period_start The anchor the next cycle is measured from.
	 * @return DateTimeImmutable The next bill date, in UTC.
	 */
	public static function next_bill_date( BillingPolicy $policy, DateTimeImmutable $current_period_start ): DateTimeImmutable {
		return $policy->compute_next_renewal_from( $current_period_start );
	}

	/**
	 * Compute the cycle the contract advances into after `$previous`.
	 *
	 * The next period starts where the previous one ended (`$previous`'s
	 * `ends_at_gmt`) and runs one cadence forward, taken from the plan snapshot's
	 * billing policy via {@see BillingPolicy::compute_next_renewal_from()} so the
	 * one cadence-math path applies. For the flat recurring case the amount and
	 * currency carry forward from `$previous` unchanged (proration / discount /
	 * tax recompute is out of scope here; the order still materializes tax and
	 * rounding).
	 *
	 * @param Cycle             $previous The chain's most-recent (just-completed) cycle.
	 * @param PlanSnapshot      $plan     The plan terms the cycle bills under (cadence source).
	 * @param DateTimeImmutable $now      The current moment. Reserved for the later RenewalService
	 *                                    extraction; the flat recurring period anchors on the
	 *                                    previous cycle's end, not the wall clock, so it is unused here.
	 * @return NextCycleSpec The computed next-cycle shape (period + expected_total + currency).
	 */
	public static function compute_next_cycle( Cycle $previous, PlanSnapshot $plan, DateTimeImmutable $now ): NextCycleSpec { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$policy = self::billing_policy_from_snapshot( $plan );

		$period_start = new DateTimeImmutable( $previous->get_ends_at_gmt(), new DateTimeZone( 'UTC' ) );
		$period_end   = $policy->compute_next_renewal_from( $period_start );

		return new NextCycleSpec(
			$period_start->format( 'Y-m-d H:i:s' ),
			$period_end->format( 'Y-m-d H:i:s' ),
			$previous->get_expected_total(),
			$previous->get_currency()
		);
	}

	/**
	 * Compute the first cycle for a chain with no previous cycle yet (a lean / manual
	 * contract). The period runs one cadence forward from `$period_start`, billing the
	 * supplied recurring amount and currency - a real one-cadence period, never
	 * zero-duration. The normal checkout path always has cycle 1, so this only covers a
	 * contract created without an origin cycle.
	 *
	 * @param PlanSnapshot      $plan           The plan terms the cycle bills under (cadence source).
	 * @param DateTimeImmutable $period_start   The period start (the contract's live next-bill anchor).
	 * @param string            $expected_total The recurring amount to bill.
	 * @param string            $currency       ISO-4217 currency code.
	 * @return NextCycleSpec The computed first-cycle shape.
	 */
	public static function compute_first_cycle( PlanSnapshot $plan, DateTimeImmutable $period_start, string $expected_total, string $currency ): NextCycleSpec {
		$start = $period_start->setTimezone( new DateTimeZone( 'UTC' ) );
		$end   = self::billing_policy_from_snapshot( $plan )->compute_next_renewal_from( $start );

		return new NextCycleSpec(
			$start->format( 'Y-m-d H:i:s' ),
			$end->format( 'Y-m-d H:i:s' ),
			$expected_total,
			$currency
		);
	}

	/**
	 * Reconstruct the plan's billing policy from a plan snapshot payload.
	 *
	 * @param PlanSnapshot $plan The plan snapshot whose cadence to read.
	 * @return BillingPolicy The plan's billing policy.
	 */
	private static function billing_policy_from_snapshot( PlanSnapshot $plan ): BillingPolicy {
		$payload        = $plan->to_array();
		$billing_policy = $payload['billing_policy'] ?? null;

		return BillingPolicy::from_array( is_array( $billing_policy ) ? $billing_policy : array() );
	}
}
