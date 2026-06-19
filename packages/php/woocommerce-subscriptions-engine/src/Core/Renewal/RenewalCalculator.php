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
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;

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
}
