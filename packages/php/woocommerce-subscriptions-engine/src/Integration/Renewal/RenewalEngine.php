<?php
/**
 * The renewal money-path, in two separated concerns joined by a {@see RenewalIntent}:
 *
 * - Selection ({@see RenewalSelector}, read-only) decides which cycle a contract should
 *   bill, and whether it is due at all. The batch {@see RenewalDispatcher} runs it over the
 *   cycle-aware due scan; `renew_now()` runs its manual variant for a single contract.
 * - Processing ({@see self::process()}) bills exactly the cycle it is handed: it claims that
 *   cycle `pending` (create-as-claim, stamping a crash-recovery lease, or reclaiming a
 *   stalled one), reconciles the renewal order AFTER the claim (reuse-or-build, draft-first
 *   and linked onto the cycle before the order goes live - so the cycle chain, not the
 *   mutable order, is the idempotency gate), charges, and completes.
 *
 * Completion is driven by the renewal order's paid state, not the charge call's return, so
 * synchronous and asynchronous gateways share one path: {@see self::complete_from_order()}
 * runs both as a post-charge reconciliation and from `woocommerce_payment_complete` / the
 * failed transition, and every settlement lands through an atomic status compare-and-set so
 * it happens exactly once. A charge with no terminal outcome yet (an async method awaiting
 * confirmation) settles the cycle `processing`, which the lease never reclaims and the scan
 * never re-selects.
 *
 * The batch dispatcher drives renewals off the due-index; no per-contract Action
 * Scheduler rows exist.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use WC_Order;
use WC_Order_Item_Product;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DuplicateCycleException;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\RenewalCandidate;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Renewal engine - select, process, complete.
 */
final class RenewalEngine {

	/**
	 * Action fired after a renewal order is created, with `( $renewal_order, $contract )`.
	 */
	public const RENEWAL_ORDER_CREATED_ACTION = 'woocommerce_subscriptions_engine_renewal_order_created';

	/**
	 * Action fired after a renewal cycle is billed and the schedule advanced, with
	 * `( $contract, $cycle, $renewal_order )`.
	 */
	public const RENEWAL_BILLED_ACTION = 'woocommerce_subscriptions_engine_renewal_billed';

	/**
	 * Logger source tag.
	 */
	protected const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Crash-recovery lease window, in seconds. When a cycle is claimed `pending` its
	 * `claimed_until` is set this far ahead; a pending cycle still unsettled past that
	 * moment is treated as a crashed in-flight charge and is reclaimable on a later run.
	 * Generous enough to outlast a normal synchronous charge plus gateway round-trip. It
	 * guards only the submit window: an accepted async charge moves the cycle `processing`,
	 * which carries no lease and is never reclaimed here.
	 */
	private const LEASE_TTL_SECONDS = 900;

	/**
	 * Repository for loading and persisting contracts, and targeted cycle access.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Repository for loading the contract's selling plan (the cadence source).
	 *
	 * @var PlanRepository
	 */
	private $plans;

	/**
	 * The read-only cycle selector `renew_now()` runs for a single contract.
	 *
	 * @var RenewalSelector
	 */
	private $selector;

	/**
	 * Build a renewal engine over the given collaborators.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 * @param PlanRepository|null     $plans     Plan repository; default instance when omitted.
	 * @param RenewalSelector|null    $selector  Cycle selector; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null, ?PlanRepository $plans = null, ?RenewalSelector $selector = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
		$this->plans     = $plans ?? new PlanRepository();
		$this->selector  = $selector ?? new RenewalSelector();
	}

	/**
	 * Register the order-driven completion listeners on THIS instance. Must run on every page
	 * load so a renewal order reaching a terminal state completes its cycle through
	 * {@see self::handle_order_settled()}. Instance-based (not static) so the boot-built
	 * engine - with whatever collaborators it was constructed over - is the one the listeners
	 * run.
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_payment_complete', array( $this, 'handle_order_settled' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed', array( $this, 'handle_order_settled' ), 10, 1 );

		// payment_complete() never fires for a renewal settled by hand - an admin marking a
		// cash-on-delivery-style order processing/completed. Listen to the paid-status
		// transitions too; the CAS settle keeps the double-fire (payment_complete plus its
		// own status transition) idempotent.
		foreach ( wc_get_is_paid_statuses() as $paid_status ) {
			add_action( 'woocommerce_order_status_' . $paid_status, array( $this, 'handle_order_settled' ), 10, 1 );
		}
	}

	/**
	 * Completion listener - fires when a renewal order reaches a paid or failed state, and
	 * settles the matching cycle from that state. The mapping and idempotency live in
	 * {@see self::complete_from_order()}. A non-renewal order is ignored there.
	 *
	 * @param int $order_id The order whose state changed.
	 */
	public function handle_order_settled( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$this->complete_from_order( $order );
		}
	}

	/**
	 * Renew a contract now at an admin's request, regardless of the schedule. Selection is by head
	 * state without the scheduled due-guard ({@see RenewalSelector::select_manual_cycle()}): a
	 * settled head is force-advanced to the next cycle (whose period continues from the previous
	 * end, so the schedule is preserved, not reset), while a failed or stalled head is re-attempted
	 * at its own count. Unlike the scheduled path it never parks the contract - a manual action
	 * should not clear the schedule when it cannot proceed.
	 *
	 * @param int                    $contract_id The contract to renew.
	 * @param DateTimeImmutable|null $now         The processing moment; defaults to now (UTC).
	 * @return WC_Order|null The renewal order, or null when the contract is not currently renewable.
	 */
	public function renew_now( int $contract_id, ?DateTimeImmutable $now = null ): ?WC_Order {
		$now = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$head = $this->contracts->find_chain_head( $contract_id );
		if ( null === $head ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::renew_now(): contract %d has no billing chain to renew.', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		$cycle_count = $this->selector->select_manual_cycle( RenewalCandidate::from_cycle( $head ) );
		if ( null === $cycle_count ) {
			return null;
		}

		try {
			return $this->process( new RenewalIntent( $contract_id, $cycle_count ), $now );
		} catch ( RenewalNotProcessable $e ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::renew_now(): cannot renew contract %d. %s', $contract_id, $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}
	}

	/**
	 * Bill the cycle named by `$intent` - the trigger-agnostic processing primitive.
	 *
	 * It owns no "which cycle" or "is it due" policy: selection (scheduled, admin, or early
	 * renewal) decides the target elsewhere and hands it in, so one primitive serves every
	 * trigger and a caller can force a renewal the scheduled guard would otherwise defer.
	 *
	 * The structural invariants it does enforce keep the money-path safe whatever the caller:
	 * it skips (logging, never throwing - a scheduled action would retry a permanent condition
	 * forever) when the contract is gone, gateway-scheduled, or inactive, and refuses a cycle
	 * that is neither the head nor its immediate successor (no billing a gap). The claim is the
	 * concurrency gate: appending the successor collides on `UNIQUE(contract_id, kind, count)`
	 * and the head is reclaimed only through the lease compare-and-set, so a cycle is charged at
	 * most once even under overlapping runs. Order reconciliation follows the claim, so the
	 * cycle chain - not the mutable order - is the idempotency authority.
	 *
	 * Throws {@see RenewalNotProcessable} for a pre-flight impossibility (no chain, an
	 * unresolvable plan, a non-adjacent count, a gateway that cannot charge renewals) so the
	 * caller can park; returns null for an idempotent no-op (a live claim, an already-settled
	 * cycle, an unbuildable order).
	 *
	 * @param RenewalIntent     $intent The contract and cycle count to bill.
	 * @param DateTimeImmutable $now    The processing moment (the lease clock for a claim).
	 * @return WC_Order|null The renewal order, or null when skipped/idempotent.
	 * @throws RenewalNotProcessable When the renewal cannot start at all.
	 */
	public function process( RenewalIntent $intent, DateTimeImmutable $now ): ?WC_Order {
		$contract_id = $intent->get_contract_id();
		$cycle_count = $intent->get_cycle_count();

		$contract = $this->contracts->find( $contract_id );
		if ( null === $contract ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process(): unknown contract %d - skipping (stale scheduled action).', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		if ( Contract::SCHEDULE_SOURCE_GATEWAY === $contract->get_schedule_source() ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process(): contract %d is gateway-scheduled - skipping. The gateway owns the renewal; this primitive row should not have fired.', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		if ( ContractStatus::ACTIVE !== $contract->get_status() ) {
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process(): contract %d is %s, not active - skipping renewal. No order created.', $contract_id, $contract->get_status() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
					'status'      => $contract->get_status(),
				)
			);
			return null;
		}

		// Pre-flight capability gate, ahead of the claim so an unchargeable renewal never
		// claims a cycle or creates an order. Without it the charge hook would fire into
		// nothing and the cycle would park `processing` - a stall that misreads as an
		// in-flight charge. Every attempt is futile until the payment method is updated, so
		// the throw lets the scheduled caller park the contract out of the due set.
		$gateway_id = $contract->get_payment_instrument()->get_gateway();
		if ( null === $gateway_id || '' === $gateway_id ) {
			throw new RenewalNotProcessable( 'the contract has no payment gateway to charge renewals with' );
		}
		if ( ! CapabilityRegistry::supports( (string) $gateway_id, GatewayCapabilities::RECURRING ) ) {
			throw new RenewalNotProcessable(
				esc_html( sprintf( 'gateway "%s" does not declare the "recurring" capability - unchargeable until the payment method is updated.', $gateway_id ) )
			);
		}

		$head = $this->contracts->find_chain_head( $contract_id );
		if ( null === $head ) {
			throw new RenewalNotProcessable( 'no billing chain to advance' );
		}

		$head_count = $head->get_count();
		if ( null === $head_count ) {
			throw new RenewalNotProcessable( esc_html( sprintf( 'head cycle %d has no count to advance from', (int) $head->get_id() ) ) );
		}

		// Claim the target cycle - the authoritative idempotency gate, ahead of any order lookup.
		$reclaimed = false;
		if ( $cycle_count === $head_count + 1 ) {
			$cycle = $this->claim_advance( $contract, $head, $cycle_count, $now );
			if ( null === $cycle ) {
				// The append collided: this number was already claimed by an earlier or
				// concurrent run. Take over a stalled claim, or skip a live one.
				$cycle     = $this->reclaim_head( $contract_id, $cycle_count, $now );
				$reclaimed = null !== $cycle;
			}
		} elseif ( $cycle_count === $head_count ) {
			$cycle     = $this->reclaim_head( $contract_id, $cycle_count, $now );
			$reclaimed = null !== $cycle;
		} else {
			throw new RenewalNotProcessable(
				esc_html( sprintf( 'cycle %d is not adjacent to head cycle %d - refusing to bill a gap.', $cycle_count, $head_count ) )
			);
		}

		if ( null === $cycle ) {
			return null;
		}

		// Reconcile the order AFTER the claim: reuse the one linked or tagged for this cycle, or
		// build one. The cycle being settled is the price + period authority; a reclaimed cycle
		// carries its OWN stored total, so the order bills that, never a freshly-computed next
		// period. A cycle appended by THIS run cannot have an order yet - order work strictly
		// follows the claim - so the lookup (and its meta scan) runs only for a reclaimed cycle,
		// where an earlier attempt may have left one.
		$renewal_order = $reclaimed ? $this->find_renewal_order_for_cycle( $cycle ) : null;
		$order_created = false;
		if ( null === $renewal_order ) {
			$renewal_order = $this->build_renewal_order( $contract, $cycle );
			if ( null === $renewal_order ) {
				// build_renewal_order logged the reason. The claimed cycle stays pending for a
				// later run to resolve; no schedule change is made here.
				return null;
			}
			$order_created = true;
		} elseif ( $cycle->get_order_id() !== $renewal_order->get_id() ) {
			// Found via the meta fallback: heal the missing cycle link before the order acts.
			$cycle->set_order_id( $renewal_order->get_id() );
			$this->contracts->update_cycle( $cycle );
		}

		// A reused order abandoned mid-creation may still be a draft: promote it before the
		// charge (a draft is not payable).
		if ( $renewal_order->has_status( OrderStatus::CHECKOUT_DRAFT ) ) {
			$renewal_order->set_status( OrderStatus::PENDING );
			$renewal_order->save();
		}

		// Charge only when the order is not already paid - a crash after the charge, or a prior
		// async attempt that has since settled, needs no second charge; completion handles it.
		// The order was built or loaded moments ago with no gateway in between, so its own paid
		// state is current.
		if ( ! $renewal_order->is_paid() ) {
			$this->ensure_payment_token( $renewal_order, $contract );
			// The created action fires once, for a genuinely new order only. A reused order - a
			// reclaimed stall resuming an earlier attempt - already announced its creation, so
			// re-firing would double one-time side effects (customer emails, analytics).
			if ( $order_created ) {
				do_action( self::RENEWAL_ORDER_CREATED_ACTION, $renewal_order, $contract );
			}
			$this->attempt_charge( $renewal_order, $contract );
		}

		// Complete from the order's paid state. Idempotent and re-reading fresh, so a sync
		// gateway that already settled the cycle via the nested payment_complete listener is a
		// no-op here, and an async charge with no terminal outcome yet lands on `processing`.
		$this->complete_from_order( $renewal_order );

		return $renewal_order;
	}

	/**
	 * Resolve the billing policy the next cycle bills under, from the contract's own plan
	 * snapshot - the live source of truth, so a contract updated since an earlier cycle bills
	 * on its current terms. Falls back to the contract's selling plan when it carries no
	 * snapshot, and returns null when neither resolves (a deleted plan) so the caller skips
	 * gracefully rather than mis-billing.
	 *
	 * @param Contract $contract The contract being renewed.
	 * @return BillingPolicy|null The billing policy, or null when unresolvable.
	 */
	private function resolve_billing_policy( Contract $contract ): ?BillingPolicy {
		$snapshot = $this->contracts->find_plan_snapshot( $contract->get_plan_snapshot_id() );
		if ( $snapshot instanceof PlanSnapshot ) {
			$payload = $snapshot->to_array();
			if ( isset( $payload['billing_policy'] ) && is_array( $payload['billing_policy'] ) ) {
				try {
					return BillingPolicy::from_array( self::string_keyed( $payload['billing_policy'] ) );
				} catch ( \DomainException $e ) {
					// A corrupt stored policy must not crash the scheduled run; fall through to the
					// live plan below so the renewal can still resolve on current terms.
					wc_get_logger()->warning(
						sprintf( 'RenewalEngine: contract %d has an unreadable plan-snapshot billing policy; falling back to the live plan. %s', (int) $contract->get_id(), $e->getMessage() ),
						array(
							'source'      => self::LOG_SOURCE,
							'contract_id' => (int) $contract->get_id(),
						)
					);
				}
			}
		}

		$plan = $this->plans->find( $contract->get_selling_plan_id() );
		return $plan instanceof Plan ? $plan->get_billing_policy() : null;
	}

	/**
	 * Claim the head's successor cycle as the create-as-claim: resolve the cadence, compute the
	 * new `pending` cycle one period past the head, stamp a crash-recovery lease, and insert it.
	 * Returns ONLY a freshly appended cycle - on a UNIQUE(contract_id, kind, count) collision
	 * (another worker already appended this number) it returns null and the caller routes
	 * through {@see self::reclaim_head()}. Any other write failure is logged as an error (never
	 * mistaken for the benign collision) and also returns null, so the contract is retried on a
	 * later tick. Keeping the fresh/reclaimed distinction at the caller lets it skip order
	 * lookups a brand-new cycle cannot need.
	 *
	 * @param Contract          $contract    The contract being renewed.
	 * @param Cycle             $head        The chain's head cycle (the new cycle's predecessor).
	 * @param int               $cycle_count The chargeable number to append (the head's successor).
	 * @param DateTimeImmutable $now         The processing moment (the lease clock).
	 * @return Cycle|null The freshly appended pending cycle, or null on an append collision.
	 * @throws RenewalNotProcessable When the billing plan cannot be resolved (a deleted plan).
	 */
	private function claim_advance( Contract $contract, Cycle $head, int $cycle_count, DateTimeImmutable $now ): ?Cycle {
		$policy = $this->resolve_billing_policy( $contract );
		if ( null === $policy ) {
			throw new RenewalNotProcessable( 'cannot resolve the billing plan (the selling plan may have been deleted)' );
		}

		$new_cycle = RenewalCalculator::compute_next_cycle(
			$policy,
			array(
				'contract_id'       => (int) $contract->get_id(),
				'sequence_no'       => $head->get_sequence_no() + 1,
				'count'             => $cycle_count,
				'period_start'      => $head->get_ends_at_gmt(),
				'expected_total'    => $contract->get_billing_total(),
				'currency'          => $contract->get_currency(),
				'extension_slug'    => $contract->get_extension_slug(),
				'plan_snapshot_id'  => $contract->get_plan_snapshot_id(),
				'items_snapshot_id' => $contract->get_items_snapshot_id(),
			)
		);
		$new_cycle->set_claimed_until_gmt( $this->lease_until( $now ) );

		try {
			$this->contracts->append_cycle( $new_cycle, $head );
		} catch ( DuplicateCycleException $e ) {
			// The UNIQUE(contract_id, kind, count) index rejected the row: another worker
			// already appended this number. Null routes the caller to the reclaim path.
			return null;
		} catch ( Throwable $e ) {
			// A real write failure, not the benign collision - surface it instead of
			// mistaking it for a claim race. The contract is retried on a later scan tick.
			wc_get_logger()->error(
				sprintf( 'RenewalEngine::claim_advance(): cannot claim cycle %d for contract %d - will retry on a later scan. %s', $cycle_count, (int) $contract->get_id(), $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			return null;
		}

		return $new_cycle;
	}

	/**
	 * Reclaim the chain head at `$count` for a re-attempt, or skip. Re-reads the head; two heads
	 * are reclaimable, each via an atomic compare-and-set so that among concurrent workers only
	 * the one whose UPDATE matches the row wins (the rest match zero rows and skip, so the cycle
	 * is charged at most once):
	 *
	 * - a `pending` cycle whose `claimed_until` lease has expired - a charge that claimed but
	 *   never settled (crash recovery), via {@see ContractRepository::reclaim_expired_cycle()};
	 * - a `failed` cycle - an admin-triggered retry that flips it back to `pending`, via
	 *   {@see ContractRepository::reclaim_failed_cycle()}. Scheduled selection never routes a
	 *   failed head here; only a manual trigger does.
	 *
	 * A still-leased pending cycle (a live claim), a settled cycle, or a `processing` head
	 * (awaiting its gateway) is a no-op (null).
	 *
	 * @param int               $contract_id The contract being renewed.
	 * @param int               $count       The chargeable number to reclaim.
	 * @param DateTimeImmutable $now         The processing moment (the lease clock).
	 * @return Cycle|null The reclaimed cycle (this caller won the CAS), or null to skip.
	 */
	private function reclaim_head( int $contract_id, int $count, DateTimeImmutable $now ): ?Cycle {
		$head = $this->contracts->find_chain_head( $contract_id );

		if ( null === $head || $count !== $head->get_count() ) {
			// The chain moved on (or vanished) between selection and the claim: nothing at
			// this number to reclaim.
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process(): cycle %d for contract %d is no longer the chain head - skipping.', $count, $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);

			return null;
		}

		if ( $head->get_status()->equals( CycleStatus::pending() ) && $this->lease_has_expired( $head, $now ) ) {
			// Crash recovery, race-safe: only the caller whose CAS UPDATE matches the
			// still-expired row reclaims it; a concurrent worker that already extended the
			// lease leaves this caller matching zero rows, so it skips.
			$won = $this->contracts->reclaim_expired_cycle( (int) $head->get_id(), self::LEASE_TTL_SECONDS );

			if ( $won ) {
				wc_get_logger()->info(
					sprintf( 'RenewalEngine::process(): reclaiming stalled cycle %d for contract %d (lease expired) - re-attempting.', $count, $contract_id ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $contract_id,
					)
				);

				return $head;
			}

			// Another worker won the reclaim CAS between our read and write: skip.
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process(): cycle %d for contract %d was reclaimed by another worker - skipping.', $count, $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);

			return null;
		}

		// Admin retry: flip a failed head back to pending and re-attempt its charge. Scheduled
		// selection never routes a failed head here; only a manual trigger does.
		if ( $head->get_status()->equals( CycleStatus::failed() ) ) {
			// Race-safe: only the caller whose CAS UPDATE matches the still-failed row wins.
			if ( $this->contracts->reclaim_failed_cycle( (int) $head->get_id(), self::LEASE_TTL_SECONDS ) ) {
				wc_get_logger()->info(
					sprintf( 'RenewalEngine::process(): retrying failed cycle %d for contract %d - re-attempting.', $count, $contract_id ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $contract_id,
					)
				);

				return $head;
			}

			return null;
		}

		// A live lease (concurrent worker), an already-settled cycle, or one awaiting its
		// gateway (`processing`): idempotent no-op.
		wc_get_logger()->info(
			sprintf( 'RenewalEngine::process(): cycle %d for contract %d is already claimed or settled - skipping.', $count, $contract_id ),
			array(
				'source'      => self::LOG_SOURCE,
				'contract_id' => $contract_id,
			)
		);

		return null;
	}

	/**
	 * The lease expiry to stamp on a freshly-claimed cycle: `$now` + {@see self::LEASE_TTL_SECONDS},
	 * as a GMT string. `$now` is the same processing moment the due-guard uses, so a single
	 * `process()` call reads one clock throughout.
	 *
	 * @param DateTimeImmutable $now The processing moment.
	 */
	private function lease_until( DateTimeImmutable $now ): string {
		return gmdate( 'Y-m-d H:i:s', $now->getTimestamp() + self::LEASE_TTL_SECONDS );
	}

	/**
	 * Whether `$cycle`'s crash-recovery lease has expired (it is reclaimable).
	 *
	 * Every cycle the engine claims stamps a lease, so an in-flight pending cycle carries
	 * one. A cycle with NO lease recorded is treated as NOT expired (not reclaimable): the
	 * engine cannot prove it is stale, so it is left as a live claim rather than risk
	 * re-charging a cycle some other path created. Only an explicit lease whose moment has
	 * passed is reclaimable.
	 *
	 * A cheap local pre-check only: the reclaim compare-and-set runs its own expiry predicate
	 * on the DATABASE clock, so a skewed PHP clock here cannot win a takeover early - at worst
	 * it wastes (fast clock) or defers (slow clock) a reclaim attempt by the skew.
	 *
	 * @param Cycle             $cycle The cycle whose lease to test.
	 * @param DateTimeImmutable $now   The processing moment (the lease clock).
	 */
	private function lease_has_expired( Cycle $cycle, DateTimeImmutable $now ): bool {
		$claimed_until = $cycle->get_claimed_until_gmt();
		if ( null === $claimed_until ) {
			return false;
		}

		$expires_at = strtotime( $claimed_until . ' UTC' );

		// An unparsable lease is treated as live (not reclaimable): never re-charge on bad data.
		return false !== $expires_at && $expires_at <= $now->getTimestamp();
	}

	/**
	 * Complete a renewal from its order's paid state - the single completion routine, reached
	 * as a post-charge reconciliation in {@see self::process()} and from the order-status
	 * listener {@see self::handle_order_settled()}. Keying completion on the order (not the
	 * charge call's return) lets synchronous and asynchronous gateways share one path.
	 *
	 * The order's renewal meta locates the contract; whether this order settles the head is
	 * then decided by the CYCLE's own data: a head linked to an order settles only from that
	 * order, and the order meta count is consulted only for an unlinked head (a pre-link
	 * crash). Re-reading the head fresh keeps it idempotent: it acts only while the head is the
	 * still-in-flight cycle this order bills (`pending`/`processing`) and no-ops once it is
	 * terminal or the chain has advanced. A non-renewal order is ignored.
	 *
	 * @param WC_Order $order The order whose state may settle a cycle.
	 */
	public function complete_from_order( WC_Order $order ): void {
		if ( OrderLinkage::RELATION_RENEWAL !== $order->get_meta( OrderLinkage::META_RELATION_TYPE ) ) {
			return;
		}

		$contract_id = ScalarCoercion::coerce_int( $order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		if ( $contract_id <= 0 ) {
			return;
		}

		$contract = $this->contracts->find( $contract_id );
		if ( null === $contract ) {
			return;
		}

		$cycle = $this->contracts->find_chain_head( $contract_id );
		if ( null === $cycle ) {
			return;
		}

		$linked_id = $cycle->get_order_id();
		if ( null !== $linked_id ) {
			// The head knows its order: settle only from that order, whatever the meta says.
			if ( $linked_id !== $order->get_id() ) {
				return;
			}
		} else {
			// Unlinked head (a crash before the link was stamped): fall back to the order's
			// chargeable-number meta to decide whether it bills this head.
			$count_meta = $order->get_meta( self::renewal_cycle_meta_key() );
			if ( ! is_numeric( $count_meta ) || (int) $count_meta !== $cycle->get_count() ) {
				return;
			}
		}

		$status = $cycle->get_status()->get_value();
		if ( CycleStatus::PENDING !== $status && CycleStatus::PROCESSING !== $status ) {
			// Already terminal: idempotent no-op (a concurrent path settled it first).
			return;
		}

		$this->settle_cycle( $contract, $cycle, $order );
	}

	/**
	 * Settle an in-flight cycle from `$order`'s paid state, and advance the contract on success.
	 *
	 * Every outcome lands through {@see ContractRepository::transition_cycle_status()} - an
	 * atomic compare-and-set on the status the caller read - so among racing settlers (the
	 * post-charge reconciliation and the order-status listener can overlap across workers)
	 * exactly one wins each transition, and the billed action fires exactly once per cycle.
	 *
	 * Paid -> cycle `billed`, `next_payment_gmt` advanced to the cycle's OWN `ends_at_gmt` (the
	 * period actually charged, so a reclaimed cycle advances exactly one cadence, never
	 * skipping one) and `last_payment_gmt` taken from the order's paid date - inputs that do
	 * not move between invocations. Failed -> cycle `failed` (recording a reason), schedule
	 * left for a later dunning pass. Neither yet -> cycle `processing`: the gateway accepted an
	 * async charge whose outcome will arrive later; the crash-recovery lease is cleared (a
	 * submitted charge is no longer a mid-submit window to reclaim) and the schedule is left
	 * untouched until the order settles.
	 *
	 * @param Contract $contract The contract being renewed.
	 * @param Cycle    $cycle    The in-flight (`pending`/`processing`) cycle to settle.
	 * @param WC_Order $order    The renewal order carrying the outcome.
	 */
	private function settle_cycle( Contract $contract, Cycle $cycle, WC_Order $order ): void {
		$now = gmdate( 'Y-m-d H:i:s' );

		// Re-fetch the order: a gateway handler that called payment_complete() on its own
		// freshly-loaded instance leaves the passed object stale, which would misread a
		// successful charge. Never settle from the stale copy: when the fresh read fails (the
		// order vanished mid-flight) the cycle stays in flight for a later run to resolve.
		$fresh = wc_get_order( $order->get_id() );
		if ( ! $fresh instanceof WC_Order ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::settle_cycle(): renewal order %d could not be re-read - leaving cycle %d unsettled.', $order->get_id(), (int) $cycle->get_id() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
					'order_id'    => $order->get_id(),
				)
			);
			return;
		}
		$order = $fresh;

		$cycle_id    = (int) $cycle->get_id();
		$read_status = $cycle->get_status()->get_value();

		if ( $order->is_paid() ) {
			if ( ! $this->contracts->transition_cycle_status( $cycle_id, $read_status, CycleStatus::BILLED, $order->get_id() ) ) {
				// Another settler won the CAS; its transition carried the side effects.
				return;
			}
			// Sync the entity with the row the CAS just wrote, for the action payload.
			$cycle->set_order_id( $order->get_id() );
			$cycle->set_status( CycleStatus::billed() );
			$cycle->set_claimed_until_gmt( null );

			// Advance to the period actually billed (this cycle's end), not a recomputed one;
			// the payment moment comes from the order itself.
			$paid_at = $order->get_date_paid();
			$contract->set_next_payment_gmt( $cycle->get_ends_at_gmt() );
			$contract->set_last_payment_gmt( null !== $paid_at ? gmdate( 'Y-m-d H:i:s', $paid_at->getTimestamp() ) : $now );
			$contract->set_last_attempt_gmt( $now );
			$this->contracts->update( $contract );

			/**
			 * Fires after a renewal cycle is billed and the contract schedule advanced.
			 *
			 * @param Contract $contract The renewed contract.
			 * @param Cycle    $cycle    The newly-billed cycle.
			 * @param WC_Order $order    The paid renewal order.
			 */
			do_action( self::RENEWAL_BILLED_ACTION, $contract, $cycle, $order );

			return;
		}

		if ( $order->has_status( OrderStatus::FAILED ) ) {
			if ( ! $this->contracts->transition_cycle_status( $cycle_id, $read_status, CycleStatus::FAILED, $order->get_id(), 'gateway-charge-failed' ) ) {
				return;
			}
			$cycle->set_order_id( $order->get_id() );
			$cycle->set_status( CycleStatus::failed() );
			$cycle->set_reason( 'gateway-charge-failed' );
			$cycle->set_claimed_until_gmt( null );

			$contract->set_last_attempt_gmt( $now );
			$this->contracts->update( $contract );

			return;
		}

		// Neither paid nor failed: the gateway accepted the charge but has not confirmed it
		// (an async method). Park the cycle in `processing` until its outcome arrives; the
		// listener completes it then. Only a pending cycle needs the write - a processing one
		// re-entering here is already parked.
		if ( CycleStatus::PENDING === $read_status ) {
			$this->contracts->transition_cycle_status( $cycle_id, CycleStatus::PENDING, CycleStatus::PROCESSING, $order->get_id() );
		}

		$contract->set_last_attempt_gmt( $now );
		$this->contracts->update( $contract );
	}

	/**
	 * Build the renewal order for `$cycle` from the contract's own stored state: its billing /
	 * shipping addresses and its (recurring) line items - never the origin order, whose cart may
	 * have carried one-time items that must not ride along onto a renewal. Applies the cycle's
	 * expected total as ground truth, attaches the contract's payment token, and tags the
	 * renewal relation meta (contract id + chargeable number) so charge observers and the
	 * order-to-cycle mapping can find it.
	 *
	 * Created draft-first: the order starts as `checkout-draft`, is linked onto the claimed
	 * cycle (`order_id`), and only then becomes `pending`. A crash mid-way therefore leaves
	 * either a linked draft the resume path promotes, or an unlinked draft that fires no emails
	 * and is swept by core's stale-draft cleanup - never a live pending order the cycle does not
	 * know about. Returns null (logged) when `wc_create_order()` fails.
	 *
	 * @param Contract $contract Contract being renewed.
	 * @param Cycle    $cycle    The claimed cycle this order bills (count + expected total).
	 * @return WC_Order|null The saved pending renewal order, or null on failure.
	 */
	private function build_renewal_order( Contract $contract, Cycle $cycle ): ?WC_Order {
		$count          = (int) $cycle->get_count();
		$expected_total = $cycle->get_expected_total();

		$renewal_order = wc_create_order(
			array(
				'customer_id' => $contract->get_customer_id(),
				'status'      => OrderStatus::CHECKOUT_DRAFT,
				'created_via' => 'woocommerce_subscriptions_engine_renewal',
			)
		);

		if ( is_wp_error( $renewal_order ) ) {
			wc_get_logger()->error(
				sprintf( 'RenewalEngine: wc_create_order() failed for contract %d: %s', (int) $contract->get_id(), $renewal_order->get_error_message() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			return null;
		}

		$instrument = $contract->get_payment_instrument();

		$renewal_order->set_currency( $contract->get_currency() );
		if ( null !== $instrument->get_gateway() ) {
			$renewal_order->set_payment_method( (string) $instrument->get_gateway() );
		}
		if ( null !== $instrument->get_title() ) {
			$renewal_order->set_payment_method_title( (string) $instrument->get_title() );
		}

		// Addresses come from the contract (its live source of truth), not the origin order. The
		// array setters only hydrate the order in memory (persisted by the save() below), unlike
		// the legacy set_address() which writes post meta directly.
		$addresses = $contract->get_addresses();
		if ( isset( $addresses['billing'] ) && is_array( $addresses['billing'] ) ) {
			$renewal_order->set_billing_address( $addresses['billing'] );
		}
		if ( isset( $addresses['shipping'] ) && is_array( $addresses['shipping'] ) ) {
			$renewal_order->set_shipping_address( $addresses['shipping'] );
		}

		// Only the contract's recurring line items - the origin order's one-time cart items are
		// deliberately excluded so a mixed checkout cannot leak onto a renewal. A line for a
		// since-deleted product makes WC_Order_Item_Product::set_product_id() throw; treat the
		// whole build as a recoverable skip (logged, null) rather than let it reach the scheduler
		// as a permanent failure that retries forever.
		try {
			foreach ( $contract->get_items() as $item ) {
				$line = new WC_Order_Item_Product();
				$line->set_name( self::item_string( $item, 'item_name' ) );
				$line->set_product_id( self::item_int( $item, 'product_id' ) );
				$line->set_variation_id( self::item_int( $item, 'variation_id' ) );
				$line->set_quantity( max( 1, self::item_int( $item, 'quantity' ) ) );
				$line->set_subtotal( self::item_string( $item, 'subtotal' ) );
				$line->set_total( self::item_string( $item, 'total' ) );
				$renewal_order->add_item( $line );
			}
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'RenewalEngine: cannot build renewal items for contract %d (a product may have been deleted): %s', (int) $contract->get_id(), $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			return null;
		}

		// The new cycle's expected_total is the price authority - applied after add_item() so
		// the line items do not recompute over it. Reconstructing the granular discount /
		// shipping / tax breakdown is a later money-path's job.
		$renewal_order->set_total( $expected_total );

		// Tag the renewal relation + chargeable number so completion can map the order back to
		// its cycle, and save (still a draft) before any linking so a crash between the two
		// leaves the order findable (no duplicate charge on the retry).
		$renewal_order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract->get_id() );
		$renewal_order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$renewal_order->update_meta_data( self::renewal_cycle_meta_key(), (string) $count );
		$renewal_order->save();

		// Link the order onto the claimed cycle BEFORE the order goes live: the cycle row is the
		// idempotency authority, so the link must exist by the time the order can act (emails,
		// charges). Only then does the draft become a real pending order.
		$cycle->set_order_id( $renewal_order->get_id() );
		$this->contracts->update_cycle( $cycle );

		$renewal_order->set_status( OrderStatus::PENDING );
		$renewal_order->save();

		$this->ensure_payment_token( $renewal_order, $contract );

		return $renewal_order;
	}

	/**
	 * Attach the contract's stored payment token to `$order` when it carries none. Idempotent:
	 * a no-op when the order already has a token, so it is safe both on a freshly-built order
	 * and when resuming a renewal order a crash may have left un-tokenised before its charge.
	 *
	 * @param WC_Order $order    The renewal order to tokenise.
	 * @param Contract $contract The contract whose payment instrument holds the token.
	 */
	private function ensure_payment_token( WC_Order $order, Contract $contract ): void {
		if ( array() !== $order->get_payment_tokens() ) {
			return;
		}

		$token_id = $contract->get_payment_instrument()->get_token_id();
		if ( null === $token_id ) {
			return;
		}

		$token = \WC_Payment_Tokens::get( $token_id );
		if ( $token instanceof \WC_Payment_Token ) {
			$order->add_payment_token( $token );
		}
	}

	/**
	 * Read a contract-item field as a string, defaulting to empty when absent or non-scalar.
	 *
	 * @param array<string, mixed> $item The contract item row.
	 * @param string               $key  Field key.
	 */
	private static function item_string( array $item, string $key ): string {
		$value = $item[ $key ] ?? null;
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Read a contract-item field as an int, defaulting to 0 when absent or non-numeric.
	 *
	 * @param array<string, mixed> $item The contract item row.
	 * @param string               $key  Field key.
	 */
	private static function item_int( array $item, string $key ): int {
		$value = $item[ $key ] ?? null;
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Coerce a decoded array to a string-keyed array for the typed value-object factories.
	 *
	 * @param array<mixed, mixed> $value The decoded array.
	 * @return array<string, mixed>
	 */
	private static function string_keyed( array $value ): array {
		$out = array();
		foreach ( $value as $key => $item ) {
			$out[ (string) $key ] = $item;
		}
		return $out;
	}

	/**
	 * Attempt the gateway charge for `$renewal_order`.
	 *
	 * Fires `woocommerce_subscriptions_engine_scheduled_payment_{gateway}` so the
	 * registered gateway integration captures against the stored token; the engine does
	 * not charge itself. A gateway that registers no handler leaves the order `pending`
	 * (uncharged) - the safe state when it cannot actually charge.
	 *
	 * @param WC_Order $renewal_order The pending renewal order to charge.
	 * @param Contract $contract      The contract being renewed.
	 */
	private function attempt_charge( WC_Order $renewal_order, Contract $contract ): void {
		// process() pre-flights the gateway (present + declares `recurring`) before any claim,
		// so the instrument is chargeable by the time the money-path reaches the charge.
		$gateway_id = (string) $contract->get_payment_instrument()->get_gateway();

		$amount = (float) $renewal_order->get_total();

		try {
			/**
			 * Fires to request a recurring charge for a renewal order. The gateway (or its
			 * adapter) captures against the stored token, then transitions the order via its
			 * own `payment_complete()` / failure handling. The gateway is expected to reach a
			 * terminal order state for errors it can detect - mark the order failed on a
			 * decline or an unrecoverable processing error. An order left neither paid nor
			 * failed is treated as an async charge awaiting confirmation: its cycle parks in
			 * `processing` until the order settles (or is resolved manually).
			 *
			 * @param float    $amount        The amount to charge.
			 * @param WC_Order $renewal_order The renewal order being charged.
			 */
			do_action( 'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway_id, $amount, $renewal_order );
		} catch ( Throwable $e ) {
			// A throwing gateway handler must not leave the AS action in a retry-forever
			// loop. Log and move on; the order stays pending for dunning to pick up.
			wc_get_logger()->error(
				sprintf( 'RenewalEngine: gateway charge for renewal order %d (contract %d) threw: %s', $renewal_order->get_id(), (int) $contract->get_id(), $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
					'order_id'    => $renewal_order->get_id(),
				)
			);
		}
	}

	/**
	 * The renewal order for `$cycle`, or null when none exists - the reuse lookup the
	 * post-claim order reconciliation runs for a RECLAIMED cycle only. A freshly appended
	 * cycle skips it entirely (no order can exist before its claim), which keeps the meta
	 * scan below off the every-renewal path.
	 *
	 * The cycle's own `order_id` reference resolves directly (we already hold the row, and the
	 * link is stamped at order creation). The meta search is the fallback for a cycle that was
	 * claimed but never linked - a crash between creating the order and linking it: it queries
	 * on the contract id via the flat `meta_key` / `meta_value` shortcut, then narrows by
	 * relation type and chargeable number in PHP. The flat shortcut is used rather than a
	 * `meta_query` because the legacy CPT order store rejects `meta_query` with
	 * `wc_doing_it_wrong`; the shortcut round-trips through both stores. Statuses are passed
	 * explicitly (not `'any'`) because a crash-abandoned order may still be a `checkout-draft`,
	 * which `'any'` excludes.
	 *
	 * @param Cycle $cycle The claimed cycle whose renewal order to resolve.
	 * @return WC_Order|null The existing renewal order for the cycle, or null when none.
	 */
	private function find_renewal_order_for_cycle( Cycle $cycle ): ?WC_Order {
		$linked_id = $cycle->get_order_id();
		if ( null !== $linked_id ) {
			$linked = wc_get_order( $linked_id );
			if ( $linked instanceof WC_Order ) {
				return $linked;
			}
			// The linked order is gone (deleted): fall through to the meta search.
		}

		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => array_keys( wc_get_order_statuses() ),
				'type'       => 'shop_order',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID,        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $cycle->get_contract_id(),    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		// Unpaginated, so wc_get_orders() returns a plain list. The guard narrows the
		// declared return type and treats any non-array result as "no matching renewal".
		if ( ! is_array( $orders ) ) {
			return null;
		}

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			if ( OrderLinkage::RELATION_RENEWAL === $order->get_meta( OrderLinkage::META_RELATION_TYPE )
				&& (string) $cycle->get_count() === $order->get_meta( self::renewal_cycle_meta_key() ) ) {
				return $order;
			}
		}

		return null;
	}

	/**
	 * Park a contract that cannot be auto-renewed by clearing its `next_payment_gmt`, so it
	 * leaves the due-index and the scan stops revisiting it every tick - which would otherwise
	 * let a cluster of un-renewable contracts hold the front of the oldest-due-first scan and
	 * starve healthy renewals. A no-op when the contract is gone. A repair (fixing the
	 * underlying data and rescheduling) re-arms it.
	 *
	 * Best-effort, never throws: parking protects the scan, it is not a correctness requirement,
	 * and it runs inside the dispatcher's per-contract error handling - a failure here (the row
	 * vanished mid-park, a write error) must not stall the rest of the batch. On failure the
	 * contract simply stays due and the park is re-attempted next tick.
	 *
	 * @param int $contract_id The contract to remove from the due set.
	 */
	public function park( int $contract_id ): void {
		try {
			$contract = $this->contracts->find( $contract_id );
			if ( null === $contract ) {
				return;
			}

			$contract->set_next_payment_gmt( null );
			$this->contracts->update( $contract );
		} catch ( Throwable $e ) {
			wc_get_logger()->error(
				sprintf( 'RenewalEngine::park(): failed to park contract %d - %s', $contract_id, $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
		}
	}

	/**
	 * Order meta key recording which cycle a renewal order bills.
	 *
	 * Read on both sides of the charge: before it, to find and reuse an existing unsettled order
	 * for a cycle ({@see self::find_renewal_order_for_cycle()}) rather than build a duplicate;
	 * after it, to map the settled order back to its cycle ({@see self::complete_from_order()}).
	 * Internal to the engine.
	 */
	private static function renewal_cycle_meta_key(): string {
		return '_subscription_renewal_cycle';
	}
}
