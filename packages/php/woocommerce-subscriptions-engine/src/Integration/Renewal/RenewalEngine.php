<?php
/**
 * The renewal money-path, in two separated concerns joined by a {@see RenewalIntent}:
 *
 * - Selection ({@see RenewalSelector}, read-only) decides which cycle a due contract should
 *   bill, and whether it is due at all. The batch {@see RenewalDispatcher} runs it over the
 *   cycle-aware due scan; `process_due()` runs it for a single contract.
 * - Processing ({@see self::process()}) bills exactly the cycle it is handed: it claims that
 *   cycle `pending` (create-as-claim, stamping a crash-recovery lease, or reclaiming a
 *   stalled one), reconciles the renewal order AFTER the claim (reuse-or-build - so the cycle
 *   chain, not the mutable order, is the idempotency gate), charges, and completes.
 *
 * Completion is driven by the renewal order's paid state, not the charge call's return, so
 * synchronous and asynchronous gateways share one path: {@see self::complete_from_order()}
 * runs both as a post-charge reconciliation and from `woocommerce_payment_complete` / the
 * failed transition. A charge with no terminal outcome yet (an async method awaiting
 * confirmation) settles the cycle `processing`, which the lease never reclaims and the scan
 * never re-selects.
 *
 * `schedule()` keeps the schedule-time capability gate; the batch dispatcher drives renewals
 * off the due-index, so it no longer enqueues a per-contract Action Scheduler row (the
 * superseded per-contract {@see RenewalScheduler} is drained only).
 *
 * Integration zone: WordPress-native. Action Scheduler, WC orders, gateways.
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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\DueRenewal;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Renewal engine - schedule, select, process, complete.
 */
final class RenewalEngine {

	/**
	 * Action fired after a contract is scheduled, with `( $contract, $when )`.
	 * Listeners observe a scheduled state, not an in-flight one.
	 */
	public const RENEWAL_SCHEDULED_ACTION = 'woocommerce_subscriptions_engine_renewal_scheduled';

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
	 * The read-only cycle selector `process_due()` runs for a single contract.
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
	 * Register the Action Scheduler drain callback and the order-driven completion listeners.
	 * Must run on every page load so AS can dispatch a stale per-contract row back into
	 * {@see self::handle_due_action()}, and so a renewal order reaching a terminal state
	 * completes its cycle through {@see self::handle_order_settled()}.
	 */
	public static function register_hooks(): void {
		add_action( RenewalScheduler::HOOK, array( __CLASS__, 'handle_due_action' ), 10, 1 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'handle_order_settled' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'handle_order_settled' ), 10, 1 );
	}

	/**
	 * Action Scheduler dispatch entry point - fires when a stale per-contract renewal row
	 * left by the superseded {@see RenewalScheduler} is drained. Routes through the instance
	 * `process_due()` so dispatch and any synchronous test driver share one code path.
	 *
	 * @param int $contract_id Contract whose renewal is firing.
	 */
	public static function handle_due_action( int $contract_id ): void {
		( new self() )->process_due( $contract_id );
	}

	/**
	 * Completion listener - fires when a renewal order reaches a paid or failed state, and
	 * settles the matching cycle from that state. Static so it can be a plain callback; the
	 * mapping and idempotency live in {@see self::complete_from_order()}. A non-renewal order
	 * is ignored there.
	 *
	 * @param int $order_id The order whose state changed.
	 */
	public static function handle_order_settled( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			( new self() )->complete_from_order( $order );
		}
	}

	/**
	 * Acknowledge `$contract` as eligible for autonomous renewal at its `next_payment_gmt`.
	 *
	 * The batch dispatcher ({@see RenewalDispatcher}) scans the contract due-index and
	 * drives every due renewal, so this no longer enqueues a per-contract Action Scheduler
	 * row - it keeps only the schedule-time capability gate and clears any stale per-contract
	 * row left by the superseded {@see RenewalScheduler}. Skips (clearing any stale row) when
	 * the contract is gateway-scheduled (the gateway runs its own schedule) or has no
	 * `next_payment_gmt`. The gate refuses a primitive-scheduled contract whose gateway does
	 * not declare the `recurring` capability via {@see CapabilityRegistry::supports()}, so a
	 * renewal nothing can charge is rejected at the boundary rather than failing later.
	 *
	 * @param Contract $contract Contract to acknowledge. Must have an id.
	 * @return bool True when the contract is eligible for dispatcher-driven renewal; false
	 *              when refused (gateway-scheduled, incapable gateway, no date, no id).
	 */
	public function schedule( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			return false;
		}

		// Gateway-scheduled: the gateway owns the schedule. Clear any stale row and bail.
		if ( Contract::SCHEDULE_SOURCE_GATEWAY === $contract->get_schedule_source() ) {
			RenewalScheduler::unschedule( $id );
			return false;
		}

		$next_payment_gmt = $contract->get_next_payment_gmt();
		if ( null === $next_payment_gmt ) {
			RenewalScheduler::unschedule( $id );
			return false;
		}

		// Schedule-time capability gate.
		$gateway_id = $contract->get_payment_instrument()->get_gateway();
		if ( null === $gateway_id || '' === $gateway_id || ! CapabilityRegistry::supports( (string) $gateway_id, GatewayCapabilities::RECURRING ) ) {
			RenewalScheduler::unschedule( $id );
			wc_get_logger()->warning(
				sprintf(
					'RenewalEngine::schedule(): not scheduling contract %d - gateway "%s" does not declare the "recurring" capability. Declare it via CapabilityRegistry, or set the contract to gateway-scheduled if the gateway runs its own renewals.',
					$id,
					(string) $gateway_id
				),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $id,
					'gateway_id'  => (string) $gateway_id,
				)
			);
			return false;
		}

		// The dispatcher drives renewals off the due-index, not a per-contract AS row.
		// Clear any stale row from the superseded per-contract scheduler so a leftover
		// does not double-fire alongside the dispatcher.
		RenewalScheduler::unschedule( $id );

		$when = new DateTimeImmutable( $next_payment_gmt, new DateTimeZone( 'UTC' ) );

		do_action( self::RENEWAL_SCHEDULED_ACTION, $contract, $when );

		return true;
	}

	/**
	 * Select and process the renewal due for a single contract at `$now` - the convenience
	 * entry the AS drain path, the {@see \Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions}
	 * facade, and direct test drivers use. The batch dispatcher does not call this: it selects
	 * from the cycle-aware scan and calls {@see self::process()} directly, so it never re-loads
	 * a head the scan already carried.
	 *
	 * Loads the head cycle, runs read-only selection ({@see RenewalSelector}), and processes
	 * the chosen cycle. A chainless contract (no head to advance) is parked; a pre-flight
	 * impossibility ({@see RenewalNotProcessable}, e.g. an unresolvable plan) parks too, so the
	 * contract leaves the due window rather than being retried every tick.
	 *
	 * @param int                    $contract_id Contract whose renewal is firing.
	 * @param DateTimeImmutable|null $now         The moment to select against; defaults to now (UTC).
	 * @return WC_Order|null The renewal order, or null when skipped/idempotent/parked.
	 */
	public function process_due( int $contract_id, ?DateTimeImmutable $now = null ): ?WC_Order {
		$now = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$head = $this->contracts->find_current_cycle( $contract_id );
		if ( null === $head ) {
			// No billing chain to advance: checkout always creates cycle 1, so a chainless
			// contract is a manual/corrupt case the engine does not renew. Park it so the
			// due-scan stops revisiting it - the cycle-aware scan already omits it (no head to
			// join), and this covers the single-contract path.
			$this->park( $contract_id );
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): contract %d has no billing chain to advance - parking (cleared its next payment).', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		$cycle_count = $this->selector->select_billing_cycle( DueRenewal::from_head( $contract_id, $head ), $now );
		if ( null === $cycle_count ) {
			return null;
		}

		try {
			return $this->process( new RenewalIntent( $contract_id, $cycle_count ), $now );
		} catch ( RenewalNotProcessable $e ) {
			$this->park( $contract_id );
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): cannot process contract %d - parking (cleared its next payment). %s', $contract_id, $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}
	}

	/**
	 * Renew a contract now at an admin's request, regardless of the schedule. Selection is by head
	 * state without the scheduled due-guard ({@see RenewalSelector::select_manual_cycle()}): a
	 * settled head is force-advanced to the next cycle (whose period continues from the previous
	 * end, so the schedule is preserved, not reset), while a failed or stalled head is re-attempted
	 * at its own count. Unlike `process_due()` it never parks the contract - a manual action should
	 * not clear the schedule when it cannot proceed.
	 *
	 * @param int                    $contract_id The contract to renew.
	 * @param DateTimeImmutable|null $now         The processing moment; defaults to now (UTC).
	 * @return WC_Order|null The renewal order, or null when the contract is not currently renewable.
	 */
	public function renew_now( int $contract_id, ?DateTimeImmutable $now = null ): ?WC_Order {
		$now = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$head = $this->contracts->find_current_cycle( $contract_id );
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

		$cycle_count = $this->selector->select_manual_cycle( DueRenewal::from_head( $contract_id, $head ) );
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
	 * unresolvable plan, a non-adjacent count) so the caller can park; returns null for an
	 * idempotent no-op (a live claim, an already-settled cycle, an unbuildable order).
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

		$head = $this->contracts->find_current_cycle( $contract_id );
		if ( null === $head ) {
			throw new RenewalNotProcessable( 'no billing chain to advance' );
		}

		$head_count = $head->get_count();
		if ( null === $head_count ) {
			throw new RenewalNotProcessable( esc_html( sprintf( 'head cycle %d has no count to advance from', (int) $head->get_id() ) ) );
		}

		// Claim the target cycle - the authoritative idempotency gate, ahead of any order lookup.
		if ( $cycle_count === $head_count + 1 ) {
			$cycle = $this->claim_advance( $contract, $head, $cycle_count, $now );
		} elseif ( $cycle_count === $head_count ) {
			$cycle = $this->reclaim_head( $contract_id, $cycle_count, $now );
		} else {
			throw new RenewalNotProcessable(
				esc_html( sprintf( 'cycle %d is not adjacent to head cycle %d - refusing to bill a gap.', $cycle_count, $head_count ) )
			);
		}

		if ( null === $cycle ) {
			return null;
		}

		// Reconcile the order AFTER the claim: reuse the one tagged for this count, or build one.
		// The cycle being settled is the price + period authority; a reclaimed cycle carries its
		// OWN stored total, so the order bills that, never a freshly-computed next period.
		$renewal_order = $this->find_renewal_order_for_cycle( $contract_id, $cycle_count );
		$order_created = false;
		if ( null === $renewal_order ) {
			$renewal_order = $this->build_renewal_order( $contract, $cycle_count, $cycle->get_expected_total() );
			if ( null === $renewal_order ) {
				// build_renewal_order logged the reason. The claimed cycle stays pending for a
				// later run to resolve; no schedule change is made here.
				return null;
			}
			$order_created = true;
		}

		// Charge only when the order is not already paid - a crash after the charge, or a prior
		// async attempt that has since settled, needs no second charge; completion handles it.
		$fresh = wc_get_order( $renewal_order->get_id() );
		$paid  = $fresh instanceof WC_Order ? $fresh->is_paid() : $renewal_order->is_paid();

		if ( ! $paid ) {
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
	 * On a UNIQUE(contract_id, kind, count) collision a concurrent worker already appended this
	 * number - {@see self::reclaim_head()} reclaims it when its lease has expired or skips a live
	 * claim.
	 *
	 * @param Contract          $contract    The contract being renewed.
	 * @param Cycle             $head        The chain's head cycle (the new cycle's predecessor).
	 * @param int               $cycle_count The chargeable number to append (the head's successor).
	 * @param DateTimeImmutable $now         The processing moment (the lease clock).
	 * @return Cycle|null The claimed (or reclaimed) pending cycle, or null when the claim is held.
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
		} catch ( Throwable $e ) {
			// A duplicate (contract_id, kind, count) is rejected by the UNIQUE index: a
			// concurrent worker already appended this number. Reclaim a stalled one or skip.
			return $this->reclaim_head( (int) $contract->get_id(), $cycle_count, $now );
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
		$head = $this->contracts->find_current_cycle( $contract_id );

		$is_pending_at_count = null !== $head
			&& $count === $head->get_count()
			&& $head->get_status()->equals( CycleStatus::pending() );

		if ( $is_pending_at_count && $this->lease_has_expired( $head, $now ) ) {
			// Crash recovery, race-safe: only the caller whose CAS UPDATE matches the
			// still-expired row reclaims it; a concurrent worker that already extended the
			// lease leaves this caller matching zero rows, so it skips.
			$won = $this->contracts->reclaim_expired_cycle( (int) $head->get_id(), $now->format( 'Y-m-d H:i:s' ), $this->lease_until( $now ) );

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
		if ( null !== $head && $count === $head->get_count() && $head->get_status()->equals( CycleStatus::failed() ) ) {
			// Race-safe: only the caller whose CAS UPDATE matches the still-failed row wins.
			if ( $this->contracts->reclaim_failed_cycle( (int) $head->get_id(), $this->lease_until( $now ) ) ) {
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
	 * @param Cycle             $cycle The cycle whose lease to test.
	 * @param DateTimeImmutable $now   The processing moment (the lease clock).
	 */
	private function lease_has_expired( Cycle $cycle, DateTimeImmutable $now ): bool {
		$claimed_until = $cycle->get_claimed_until_gmt();
		if ( null === $claimed_until ) {
			return false;
		}

		return strtotime( $claimed_until . ' UTC' ) <= $now->getTimestamp();
	}

	/**
	 * Complete a renewal from its order's paid state - the single completion routine, reached
	 * as a post-charge reconciliation in {@see self::process()} and from the order-status
	 * listener {@see self::handle_order_settled()}. Keying completion on the order (not the
	 * charge call's return) lets synchronous and asynchronous gateways share one path.
	 *
	 * Maps the order back to its cycle via the renewal relation meta and re-reads the head
	 * fresh, so it is idempotent: it acts only while the head is the still-in-flight cycle this
	 * order bills (`pending`/`processing` at the order's count) and no-ops once it is terminal
	 * or the chain has advanced. A non-renewal order is ignored.
	 *
	 * @param WC_Order $order The order whose state may settle a cycle.
	 */
	public function complete_from_order( WC_Order $order ): void {
		if ( OrderLinkage::RELATION_RENEWAL !== $order->get_meta( OrderLinkage::META_RELATION_TYPE ) ) {
			return;
		}

		$contract_id = ScalarCoercion::coerce_int( $order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		$count_meta  = $order->get_meta( self::renewal_cycle_meta_key() );
		if ( $contract_id <= 0 || ! is_numeric( $count_meta ) ) {
			return;
		}
		$count = (int) $count_meta;

		$contract = $this->contracts->find( $contract_id );
		if ( null === $contract ) {
			return;
		}

		$cycle = $this->contracts->find_current_cycle( $contract_id );
		if ( null === $cycle || $count !== $cycle->get_count() ) {
			// The chain advanced past this order's cycle (or has none): nothing to settle.
			return;
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
	 * Paid -> cycle `billed`, order linked, `next_payment_gmt` advanced to the cycle's OWN
	 * `ends_at_gmt` (the period actually charged, so a reclaimed cycle advances exactly one
	 * cadence, never skipping one). Failed -> cycle `failed` (recording a reason), schedule
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
		// successful charge. Read the outcome from the fresh instance.
		$fresh = wc_get_order( $order->get_id() );
		$order = $fresh instanceof WC_Order ? $fresh : $order;

		// The renewal order exists regardless of outcome, so record it on the cycle either way.
		$cycle->set_order_id( $order->get_id() );

		if ( $order->is_paid() ) {
			$cycle->set_status( CycleStatus::billed() );
			$cycle->set_claimed_until_gmt( null );
			$this->contracts->update_cycle( $cycle );

			// Advance to the period actually billed (this cycle's end), not a recomputed one.
			$contract->set_next_payment_gmt( $cycle->get_ends_at_gmt() );
			$contract->set_last_payment_gmt( $now );
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

		if ( $order->has_status( 'failed' ) ) {
			$cycle->set_status( CycleStatus::failed() );
			$cycle->set_reason( 'gateway-charge-failed' );
			$cycle->set_claimed_until_gmt( null );
			$this->contracts->update_cycle( $cycle );

			$contract->set_last_attempt_gmt( $now );
			$this->contracts->update( $contract );

			return;
		}

		// Neither paid nor failed: the gateway accepted the charge but has not confirmed it
		// (an async method). Park the cycle in `processing` until its outcome arrives; the
		// listener completes it then. Guard the transition so re-entry is a no-op.
		if ( CycleStatus::PROCESSING !== $cycle->get_status()->get_value() ) {
			$cycle->set_status( CycleStatus::processing() );
			$cycle->set_claimed_until_gmt( null );
			$this->contracts->update_cycle( $cycle );
		}

		$contract->set_last_attempt_gmt( $now );
		$this->contracts->update( $contract );
	}

	/**
	 * Build the renewal order from the contract's own stored state: its billing / shipping
	 * addresses and its (recurring) line items - never the origin order, whose cart may have
	 * carried one-time items that must not ride along onto a renewal. Applies the new cycle's
	 * expected total as ground truth, attaches the contract's payment token, and tags the
	 * renewal relation meta (contract id + chargeable number) so charge observers and the
	 * order-to-cycle mapping can find it. Returns null (logged) when `wc_create_order()` fails.
	 *
	 * @param Contract $contract       Contract being renewed.
	 * @param int      $count          The chargeable number this order bills.
	 * @param string   $expected_total The new cycle's expected total (the price authority).
	 * @return WC_Order|null The saved pending renewal order, or null on failure.
	 */
	private function build_renewal_order( Contract $contract, int $count, string $expected_total ): ?WC_Order {
		$renewal_order = wc_create_order(
			array(
				'customer_id' => $contract->get_customer_id(),
				'status'      => 'pending',
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
		// its cycle, and save before attaching the token so a crash between the two leaves the
		// order findable (no duplicate charge on the retry).
		$renewal_order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract->get_id() );
		$renewal_order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$renewal_order->update_meta_data( self::renewal_cycle_meta_key(), (string) $count );
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
		$gateway_id = $contract->get_payment_instrument()->get_gateway();
		if ( null === $gateway_id || '' === $gateway_id ) {
			return;
		}

		$amount = (float) $renewal_order->get_total();

		try {
			/**
			 * Fires to request a recurring charge for a renewal order. The gateway (or its
			 * adapter) captures against the stored token, then transitions the order via its
			 * own `payment_complete()` / failure handling.
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
	 * The renewal order tagged for `$contract_id` at `$cycle`, or null when none exists -
	 * the reuse lookup the post-claim order reconciliation runs.
	 *
	 * Queries on the contract id via the flat `meta_key` / `meta_value` shortcut, then
	 * narrows by relation type and cycle in PHP. The flat shortcut is used rather than a
	 * `meta_query` because the legacy CPT order store rejects `meta_query` with
	 * `wc_doing_it_wrong`; the shortcut round-trips through both stores.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $cycle       The cycle number the renewal would bill.
	 * @return WC_Order|null The existing renewal order for the number, or null when none.
	 */
	private function find_renewal_order_for_cycle( int $contract_id, int $cycle ): ?WC_Order {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'status'     => 'any',
				'type'       => 'shop_order',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $contract_id,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
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
				&& (string) $cycle === $order->get_meta( self::renewal_cycle_meta_key() ) ) {
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
	 * @param int $contract_id The contract to remove from the due set.
	 */
	public function park( int $contract_id ): void {
		$contract = $this->contracts->find( $contract_id );
		if ( null === $contract ) {
			return;
		}

		$contract->set_next_payment_gmt( null );
		$this->contracts->update( $contract );
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
