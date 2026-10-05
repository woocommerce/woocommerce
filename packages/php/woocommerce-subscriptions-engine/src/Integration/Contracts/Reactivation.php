<?php
/**
 * Reactivation - resume a held subscription contract (resume billing).
 *
 * A focused contract-management operation (deliberately not a catch-all manager),
 * mirroring {@see Cancellation}: move the contract ON_HOLD -> ACTIVE, re-arm its
 * next-due moment forward from the hold anchor, and announce it. Writing the forward
 * next-payment date is the re-arm (the batch due scan, keyed on `next_payment_gmt` and a
 * registered owner, picks the contract up at that date), not the status change alone.
 * Lives under `Integration\Contracts` so contract lifecycle stays separate from the
 * renewal money-path. Its preconditions are its own, not a rule of the status primitive.
 *
 * Interim: moves out of the engine with the lifecycle flows (hold / reactivate /
 * cancel and their routes).
 *
 * `$now` is read at this integration boundary (or injected for tests) and the cadence
 * math is delegated to the clock-free {@see RenewalCalculator}, so the engine keeps a
 * single cadence path and Core takes no clock.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use RuntimeException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Reactivate a held contract.
 */
final class Reactivation {

	/**
	 * Action fired after a contract is reactivated, with `( $contract )`.
	 */
	public const CONTRACT_REACTIVATED_ACTION = 'woocommerce_subscriptions_engine_contract_reactivated';

	/**
	 * Bound on the forward roll so a pathological policy (or a very long-held contract)
	 * cannot loop unboundedly while moving a past-due date into the future.
	 */
	private const MAX_FORWARD_ROLLS = 1000;

	/**
	 * Log source, matching the package's shared logging channel.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Contract repository.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Plan repository, for the billing policy the forward recompute rolls on.
	 *
	 * @var PlanRepository
	 */
	private $plans;

	/**
	 * Construct.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 * @param PlanRepository|null     $plans     Plan repository; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null, ?PlanRepository $plans = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
		$this->plans     = $plans ?? new PlanRepository();
	}

	/**
	 * Reactivate `$contract`: move it to active, re-arm the next-payment date forward,
	 * and persist.
	 *
	 * The anchor the date is recomputed from is the stored `next_payment_gmt` when one is
	 * set (hold clears it, so a value means it was re-armed deliberately, or the contract
	 * was held before hold disarmed it), else the next-due moment stashed by {@see Hold}
	 * ({@see Hold::ANCHOR_META_KEY}); a malformed stashed value counts as absent. The
	 * anchor meta is removed. The date is recomputed through the single seam
	 * ({@see self::recompute_next_payment()}) so a contract that sat on hold past its due
	 * date does not fire an immediate, back-dated renewal the moment it resumes; with no
	 * anchor the contract simply stays unscheduled.
	 *
	 * @param Contract               $contract Contract to reactivate. Must have an id, and be ON_HOLD.
	 * @param DateTimeImmutable|null $now      The current moment; read from the wall clock (UTC) when omitted.
	 * @return bool True when the contract was reactivated and persisted.
	 * @throws RuntimeException If the contract has no id.
	 * @throws DomainException If the contract is not on hold, or its state changed concurrently.
	 */
	public function reactivate( Contract $contract, ?DateTimeImmutable $now = null ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new RuntimeException( 'Reactivation::reactivate(): cannot reactivate a contract that has no id.' );
		}

		// Only a held contract reactivates. In particular an already-ACTIVE contract must
		// not reach the recompute below: rolling a past-due active contract's next-payment
		// date forward would skip the charge the due scan owes it.
		if ( ContractStatus::ON_HOLD !== $contract->get_status() ) {
			throw new DomainException( 'Reactivation::reactivate(): only an on-hold contract can be reactivated.' );
		}

		// Read the clock at the integration boundary so the Core cadence math stays clock-free.
		$now = ( $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( 'UTC' ) );

		// A next-due moment set while held was re-armed deliberately (hold clears it), so
		// it wins; otherwise resume from the hold anchor, ignoring a malformed one.
		$anchor = $contract->get_next_payment_gmt() ?? self::well_formed_anchor( $contract->get_meta()[ Hold::ANCHOR_META_KEY ] ?? '' );

		$contract->set_next_payment_gmt( $this->recompute_next_payment( $contract, $anchor, $now, $this->billing_policy( $contract ) ) );
		$contract->set_meta( Hold::ANCHOR_META_KEY, null );
		$contract->set_status( ContractStatus::ACTIVE );

		// Compare-and-set on the ON_HOLD status read above: a concurrent transition
		// (another request, the renewal engine) makes this write miss loudly rather
		// than be clobbered.
		if ( ! $this->contracts->update_if_status( $contract, ContractStatus::ON_HOLD ) ) {
			throw new DomainException( 'Reactivation::reactivate(): the contract state changed concurrently; nothing was written.' );
		}

		/**
		 * Fires after a held contract is reactivated and its renewal re-armed.
		 *
		 * @param Contract $contract The reactivated contract.
		 */
		do_action( self::CONTRACT_REACTIVATED_ACTION, $contract );

		return true;
	}

	/**
	 * Recompute the next-payment date when a held contract reactivates.
	 *
	 * THE SINGLE SWAPPABLE RECOMPUTE SEAM. The exact behaviour here is a pending PRODUCT
	 * DECISION; every change to it is isolated to this one method so the rest of the
	 * lifecycle wiring stays stable when the policy is finalized.
	 *
	 * Default = "Model 1" (suspend without mutating the immutable current cycle;
	 * reactivate recomputes the next date FORWARD, with no catch-up / back-charge):
	 *
	 *  - A future anchor date is kept as-is - resuming before the date arrives changes
	 *    nothing.
	 *  - A past-due date (the contract sat on hold past it) is rolled forward by whole
	 *    billing cadences (via {@see RenewalCalculator::next_bill_date()}) until it is in
	 *    the future, so resuming does not immediately fire a back-dated renewal. With no
	 *    policy available to compute a cadence, the date is floored at `$now` (the due
	 *    scan then bills the resumed contract on its next pass rather than for the held
	 *    window).
	 *  - A contract with no anchor (no scheduled next payment when held) stays unscheduled.
	 *
	 * Models 2 (resume immediately and charge for the held period) and 3 (extend the end
	 * date by the held duration) are deliberately NOT implemented - do not add them here
	 * until the product decision lands.
	 *
	 * @param Contract           $contract The contract being reactivated (for the log line).
	 * @param string|null        $anchor   The GMT next-due moment to recompute from, or null.
	 * @param DateTimeImmutable  $now      The current moment (UTC; injected at the boundary).
	 * @param BillingPolicy|null $policy   The plan billing policy for the forward roll, or null.
	 * @return string|null The recomputed next-payment GMT string, or null when unscheduled.
	 */
	private function recompute_next_payment( Contract $contract, ?string $anchor, DateTimeImmutable $now, ?BillingPolicy $policy ): ?string {
		if ( null === $anchor ) {
			return null;
		}

		$next = new DateTimeImmutable( $anchor, new DateTimeZone( 'UTC' ) );

		// Still in the future: resuming before the date arrives keeps the schedule.
		if ( $next > $now ) {
			return $next->format( 'Y-m-d H:i:s' );
		}

		// Past due while held. With no cadence to roll by, floor at `$now` so the due scan
		// bills the resumed contract on its next pass, not for the held gap.
		if ( null === $policy ) {
			return $now->format( 'Y-m-d H:i:s' );
		}

		// Roll forward by whole cadences until the date is in the future.
		$rolls = self::MAX_FORWARD_ROLLS;
		while ( $next <= $now && $rolls-- > 0 ) {
			$next = RenewalCalculator::next_bill_date( $policy, $next );
		}

		if ( $next <= $now ) {
			// The cap ran out before the date cleared `$now` (a very long hold on a
			// fine-grained cadence): floor at `$now` like the no-policy branch above, so
			// the resume never lands a back-dated renewal. Logged because a capped roll
			// means the schedule anchor left the plan's cadence grid.
			wc_get_logger()->warning(
				sprintf( 'Reactivation: contract %d exhausted the forward-roll cap; next payment floored at now.', (int) $contract->get_id() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);

			return $now->format( 'Y-m-d H:i:s' );
		}

		return $next->format( 'Y-m-d H:i:s' );
	}

	/**
	 * The billing policy the forward roll steps by: the contract's own frozen plan
	 * terms first (the snapshot is what the contract actually bills under - the same
	 * source the renewal money-path resolves), falling back to the live selling plan
	 * for a contract with no snapshot, and null when neither resolves.
	 *
	 * @param Contract $contract The contract.
	 */
	private function billing_policy( Contract $contract ): ?BillingPolicy {
		$snapshot = $contract->get_plan_snapshot();
		if ( null !== $snapshot ) {
			$policy = $snapshot->get_billing_policy();
			if ( $policy instanceof BillingPolicy ) {
				return $policy;
			}
		}

		$plan = $this->plans->find( $contract->get_selling_plan_id() );

		return $plan instanceof Plan ? $plan->get_billing_policy() : null;
	}

	/**
	 * The hold anchor when it is a well-formed GMT datetime (`Y-m-d H:i:s`), else null.
	 *
	 * @param string $anchor Stored anchor meta value.
	 */
	private static function well_formed_anchor( string $anchor ): ?string {
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $anchor, new DateTimeZone( 'UTC' ) );

		return false !== $parsed && $parsed->format( 'Y-m-d H:i:s' ) === $anchor ? $anchor : null;
	}
}
