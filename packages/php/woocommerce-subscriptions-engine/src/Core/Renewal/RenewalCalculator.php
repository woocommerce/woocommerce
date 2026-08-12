<?php
/**
 * RenewalCalculator - pure renewal date math and the next-cycle builder.
 *
 * Answers the questions the renewal money-path needs with no knowledge of
 * WordPress, Action Scheduler, orders, or the wall clock: given a plan's
 * {@see BillingPolicy} and where a contract is in its chain,
 *
 *   1. has the contract reached its hard end (max_cycles)?
 *   2. when is the next bill date, computed from an explicit anchor?
 *   3. what is the next {@see Cycle} the contract advances into?
 *
 * `$now`/anchors are always passed in by the integration layer so the math is
 * deterministic and unit-testable. All cadence math delegates to
 * {@see BillingPolicy} so there is a single cadence path in the package.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;

defined( 'ABSPATH' ) || exit;

/**
 * Pure renewal calculator.
 *
 * Stateless: every method is static and derives its answer solely from its
 * arguments. The integration layer (`Integration\Renewal\RenewalEngine`) reads
 * the contract row, resolves its billing policy, calls in here for the next
 * cycle, then persists it and talks to Action Scheduler / the gateway.
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
	 * Compute the next bill date one cadence past `$current_period_start`.
	 *
	 * Delegates to {@see BillingPolicy::compute_next_renewal_from()} so calendar
	 * semantics (month-end roll-over, DST) stay in one place. The result is in
	 * UTC; the caller formats it to the `Y-m-d H:i:s` GMT string the rows store.
	 *
	 * @param BillingPolicy     $policy               The plan's billing policy.
	 * @param DateTimeImmutable $current_period_start The anchor the next cycle is measured from.
	 * @return DateTimeImmutable The next bill date, in UTC.
	 */
	public static function next_bill_date( BillingPolicy $policy, DateTimeImmutable $current_period_start ): DateTimeImmutable {
		return $policy->compute_next_renewal_from( $current_period_start );
	}

	/**
	 * Build the next cycle a contract advances into, one cadence forward from the
	 * anchor the caller supplies.
	 *
	 * The period runs from `period_start` one cadence forward (calendar-aware, via
	 * the billing policy) - a real one-cadence period, never zero-duration. The
	 * amount, currency, count, and snapshot references are the contract's live
	 * values, passed in by the caller, so the cycle reflects the contract rather
	 * than a possibly-stale earlier cycle. The returned cycle is `pending`, ready
	 * for the integration layer to claim (insert) and then charge.
	 *
	 * @param BillingPolicy        $policy The billing policy whose cadence the period runs on.
	 * @param array<string, mixed> $values Cycle inputs: `contract_id`, `sequence_no`, `count`,
	 *                                     `period_start` (GMT `Y-m-d H:i:s` anchor), `expected_total`,
	 *                                     `currency`, `extension_slug`, `plan_snapshot_id`,
	 *                                     `items_snapshot_id`.
	 * @return Cycle The pending next cycle.
	 */
	public static function compute_next_cycle( BillingPolicy $policy, array $values ): Cycle {
		$anchor = $values['period_start'] ?? null;
		$start  = new DateTimeImmutable( is_string( $anchor ) ? $anchor : '', new DateTimeZone( 'UTC' ) );
		$end    = $policy->compute_next_renewal_from( $start );

		// Cycle::create() coerces and validates each attribute, so the contract's values pass
		// through as given; only the period boundaries are computed here.
		return Cycle::create(
			array(
				'contract_id'       => $values['contract_id'] ?? null,
				'sequence_no'       => $values['sequence_no'] ?? null,
				'count'             => $values['count'] ?? null,
				'status'            => CycleStatus::pending(),
				'starts_at_gmt'     => $start->format( 'Y-m-d H:i:s' ),
				'ends_at_gmt'       => $end->format( 'Y-m-d H:i:s' ),
				'expected_total'    => $values['expected_total'] ?? null,
				'currency'          => $values['currency'] ?? null,
				'extension_slug'    => $values['extension_slug'] ?? null,
				'plan_snapshot_id'  => $values['plan_snapshot_id'] ?? null,
				'items_snapshot_id' => $values['items_snapshot_id'] ?? null,
			)
		);
	}
}
