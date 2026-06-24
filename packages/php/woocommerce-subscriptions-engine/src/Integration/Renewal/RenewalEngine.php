<?php
/**
 * The seam between consumers and the renewal money-path: `schedule()` enqueues a
 * contract's next renewal, `process_due()` runs it when fired (by Action Scheduler
 * or driven directly). Wraps Action Scheduler (whose hook names and dedup behaviour
 * stay private) and adds the contract-aware semantics: capability gating, the renewal
 * order, the charge. One AS job per contract; the AS coupling lives in {@see RenewalScheduler}.
 *
 * `process_due()` advances the billing chain at fire time - it claims the next cycle
 * `pending` (create-as-claim), charges its `expected_total`, then settles `billed` or
 * `failed` and advances the contract schedule on success. It stays a single synchronous
 * entry; re-arming the next due moment via a recurring scan is a later slice.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\NextCycleSpec;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Renewal engine - schedule, advance, charge, cancel.
 */
final class RenewalEngine {

	/**
	 * Action fired after a contract is scheduled, with `( $contract, $when )`.
	 * Listeners observe a scheduled state, not an in-flight one.
	 */
	const RENEWAL_SCHEDULED_ACTION = 'woocommerce_subscriptions_engine_renewal_scheduled';

	/**
	 * Action fired after a renewal order is created, with `( $renewal_order, $contract )`.
	 */
	const RENEWAL_ORDER_CREATED_ACTION = 'woocommerce_subscriptions_engine_renewal_order_created';

	/**
	 * Action fired after a renewal cycle is billed and the schedule advanced, with
	 * `( $contract, $cycle, $renewal_order )`.
	 */
	const RENEWAL_BILLED_ACTION = 'woocommerce_subscriptions_engine_renewal_billed';

	/**
	 * Action fired after a contract is cancelled, with `( $contract )`.
	 */
	const CONTRACT_CANCELLED_ACTION = 'woocommerce_subscriptions_engine_contract_cancelled';

	/**
	 * Logger source tag.
	 */
	const LOG_SOURCE = 'woocommerce-subscriptions-engine';

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
	 * Build a renewal engine over the given repositories.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 * @param PlanRepository|null     $plans     Plan repository; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null, ?PlanRepository $plans = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
		$this->plans     = $plans ?? new PlanRepository();
	}

	/**
	 * Register the Action Scheduler callback. Must run on every page load (not just
	 * activation) so AS can dispatch a due action back into {@see self::handle_due_action()}.
	 */
	public static function register_hooks(): void {
		add_action( RenewalScheduler::HOOK, array( __CLASS__, 'handle_due_action' ), 10, 1 );
	}

	/**
	 * Action Scheduler dispatch entry point - fires when a renewal is due.
	 *
	 * Static so it can be registered as a plain callback; routes through the instance
	 * `process_due()` so dispatch and any synchronous test driver share one code path.
	 *
	 * @param int $contract_id Contract whose renewal is firing.
	 */
	public static function handle_due_action( int $contract_id ): void {
		( new self() )->process_due( $contract_id );
	}

	/**
	 * Schedule (or re-schedule) the next renewal for `$contract` at its `next_payment_gmt`.
	 *
	 * Clear-then-enqueue keeps at most one pending AS row per contract, so callers
	 * moving the date forward just call `schedule()` again. Skips (and clears any stale
	 * row) when the contract is gateway-scheduled (the gateway runs its own schedule) or
	 * has no `next_payment_gmt`. Capability gate: a primitive-scheduled contract is only
	 * enqueued when its gateway declares the `recurring` capability via
	 * {@see CapabilityRegistry::supports()}, so renewals nothing can charge are refused
	 * at the boundary rather than failing later on a customer-facing order.
	 *
	 * @param Contract $contract Contract to schedule. Must have an id.
	 * @return bool True when an AS row was enqueued; false when scheduling was
	 *              skipped (gateway-scheduled, incapable gateway, no date, no id).
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

		$when = new DateTimeImmutable( $next_payment_gmt, new DateTimeZone( 'UTC' ) );

		// Clear-then-enqueue: AS does not dedup on hook+args, so without the clear a
		// re-schedule would leave two rows and fire twice.
		RenewalScheduler::unschedule( $id );
		RenewalScheduler::schedule( $id, $when );

		do_action( self::RENEWAL_SCHEDULED_ACTION, $contract, $when );

		return true;
	}

	/**
	 * Run the renewal due for `$contract_id`. Fired by the AS hook.
	 *
	 * Loads the contract and skips (logging only, never throwing - AS would retry a
	 * permanent failure forever) when it is gone, gateway-scheduled, or not active.
	 * Then advances the billing chain: it creates the next cycle `pending` as the
	 * create-as-claim (the `UNIQUE(contract_id, kind, count)` index makes a concurrent
	 * or retried fire a no-op), builds and charges the renewal order at that cycle's
	 * `expected_total`, and resolves the outcome - on a paid order the cycle settles
	 * `billed`, the order is linked, and the contract schedule advances one cadence; on
	 * an unpaid order the cycle settles `failed` and the schedule is left untouched.
	 *
	 * Writes are ordered durable-intent-first (cycle create -> charge -> cycle resolve ->
	 * contract advance) with no surrounding transaction. The single synchronous entry a
	 * later batch dispatcher calls per-claimed-contract.
	 *
	 * @param int $contract_id Contract whose renewal cycle is firing.
	 * @return WC_Order|null The created renewal order, or null when skipped/idempotent.
	 */
	public function process_due( int $contract_id ): ?WC_Order {
		$contract = $this->contracts->find( $contract_id );
		if ( null === $contract ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): unknown contract %d - skipping (stale scheduled action).', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		if ( Contract::SCHEDULE_SOURCE_GATEWAY === $contract->get_schedule_source() ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): contract %d is gateway-scheduled - skipping. The gateway owns the renewal; this primitive row should not have fired.', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		if ( ContractStatus::ACTIVE !== $contract->get_status() ) {
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process_due(): contract %d is %s, not active - skipping renewal. No order created.', $contract_id, $contract->get_status() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
					'status'      => $contract->get_status(),
				)
			);
			return null;
		}

		$previous   = $this->contracts->find_current_cycle( $contract_id );
		$next_count = $this->target_count( $previous );

		// Idempotency pre-check: a renewal order already tagged for this number means the
		// action already ran (AS retry, double-fire). Bail before claiming a new cycle.
		if ( $this->renewal_exists_for_cycle( $contract_id, $next_count ) ) {
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process_due(): renewal for contract %d cycle %d already exists - skipping (idempotent retry).', $contract_id, $next_count ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		// Resolve the next period from the plan cadence. A deleted/unresolvable plan is a
		// recoverable data condition, not a fatal: skip (logging only) like the guards
		// above so a scheduled action does not retry a permanent failure forever.
		$spec = $this->compute_next_cycle_spec( $contract, $previous );
		if ( null === $spec ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): cannot resolve the billing plan for contract %d - skipping. The selling plan may have been deleted.', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		// Create-as-claim: the next cycle is inserted `pending` before any charge. A
		// concurrent/duplicate fire loses the UNIQUE(contract_id, kind, count) race and
		// is treated as an idempotent no-op.
		$new_cycle = $this->claim_next_cycle( $contract, $previous, $next_count, $spec );
		if ( null === $new_cycle ) {
			return null;
		}

		$renewal_order = $this->build_renewal_order( $contract, $next_count, $spec->get_expected_total() );
		if ( null === $renewal_order ) {
			// build_renewal_order logged the reason. The claimed cycle stays pending for
			// a later run/dunning to resolve; no schedule change is made here.
			return null;
		}

		do_action( self::RENEWAL_ORDER_CREATED_ACTION, $renewal_order, $contract );

		$this->attempt_charge( $renewal_order, $contract );

		$this->resolve_outcome( $contract, $new_cycle, $renewal_order, $spec );

		return $renewal_order;
	}

	/**
	 * Cancel `$contract`: transition to cancelled, close any mid-charge cycle, and clear
	 * its pending renewal.
	 *
	 * Status moves through the Core state machine ({@see Contract::set_status()}), which
	 * raises a `DomainException` on an illegal transition. When the chain's most-recent
	 * cycle is still `pending` (a charge caught mid-flight) it is transitioned `cancelled`
	 * so a stale claim is not left open; a settled cycle is untouched.
	 *
	 * @param Contract $contract Contract to cancel. Must have an id.
	 * @return bool True when the contract was cancelled and persisted.
	 * @throws \RuntimeException If the contract has no id.
	 */
	public function cancel( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new \RuntimeException( 'RenewalEngine::cancel(): cannot cancel a contract that has no id.' );
		}

		$contract->set_status( ContractStatus::CANCELLED );
		$this->contracts->update( $contract );

		// Close a charge caught mid-flight: a still-pending head cycle is cancelled so no
		// stale claim is left open. A settled (billed/failed/cancelled) cycle is left as is.
		$current = $this->contracts->find_current_cycle( $id );
		if ( null !== $current && $current->get_status()->equals( CycleStatus::pending() ) ) {
			$current->set_status( CycleStatus::cancelled() );
			$this->contracts->update_cycle( $current );
		}

		RenewalScheduler::unschedule( $id );

		/**
		 * Fires after a contract is cancelled and its pending renewal cleared.
		 *
		 * @param Contract $contract The cancelled contract.
		 */
		do_action( self::CONTRACT_CANCELLED_ACTION, $contract );

		return true;
	}

	/**
	 * The chargeable number this renewal targets - the idempotency anchor.
	 *
	 * One past the head cycle's count once it has settled forward (`billed`/`cancelled`):
	 * the chain advances. While the head is still unsettled (`pending`/`failed`) the same
	 * count is targeted again, so a retry resolves the in-flight cycle rather than skipping
	 * a number - and the order-meta pre-check / the create-as-claim UNIQUE then make the
	 * retry a no-op. A chain with no counting cycle yet starts at 1.
	 *
	 * @param Cycle|null $previous The chain's most-recent cycle, or null when empty.
	 * @return int The chargeable number to target.
	 */
	private function target_count( ?Cycle $previous ): int {
		if ( null === $previous || null === $previous->get_count() ) {
			return 1;
		}

		$count  = (int) $previous->get_count();
		$status = $previous->get_status()->get_value();

		$settled_forward = CycleStatus::BILLED === $status || CycleStatus::CANCELLED === $status;

		return $settled_forward ? $count + 1 : $count;
	}

	/**
	 * Compute the shape of the next cycle from the previous one and the plan cadence, or
	 * null when the plan cannot be resolved.
	 *
	 * Delegates the period + amount math to the pure {@see RenewalCalculator}. With no
	 * previous cycle (a lean contract with no chain yet) the period is computed from the
	 * plan cadence anchored on the contract's live next-payment date (or now) and bills the
	 * contract's live recurring total - a real one-cadence period, never zero-duration.
	 * Returns null when the selling plan is gone so the caller can skip gracefully.
	 *
	 * @param Contract   $contract The contract being renewed.
	 * @param Cycle|null $previous The chain's most-recent cycle, or null when empty.
	 * @return NextCycleSpec|null The computed next-cycle shape, or null when unresolvable.
	 */
	private function compute_next_cycle_spec( Contract $contract, ?Cycle $previous ): ?NextCycleSpec {
		$snapshot = $this->resolve_plan_snapshot( $contract, $previous );
		if ( null === $snapshot ) {
			return null;
		}

		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		if ( null === $previous ) {
			// No chain yet: anchor on the contract's live next-payment date (or now) and run
			// one cadence forward, billing the live recurring total.
			$start  = $contract->get_next_payment_gmt() ?? $now->format( 'Y-m-d H:i:s' );
			$anchor = new DateTimeImmutable( $start, new DateTimeZone( 'UTC' ) );

			return RenewalCalculator::compute_first_cycle( $snapshot, $anchor, $contract->get_billing_total(), $contract->get_currency() );
		}

		return RenewalCalculator::compute_next_cycle( $previous, $snapshot, $now );
	}

	/**
	 * Insert the next cycle `pending` as the create-as-claim. Returns the inserted cycle,
	 * or null when the insert loses the `UNIQUE(contract_id, kind, count)` race (a
	 * concurrent/duplicate fire) - treated as an idempotent no-op.
	 *
	 * @param Contract      $contract The contract being renewed.
	 * @param Cycle|null    $previous The chain's previous cycle (for snapshot copy-forward), or null.
	 * @param int           $count    The chargeable number to claim.
	 * @param NextCycleSpec $spec     The computed next-cycle shape.
	 * @return Cycle|null The inserted pending cycle, or null when the claim is lost.
	 */
	private function claim_next_cycle( Contract $contract, ?Cycle $previous, int $count, NextCycleSpec $spec ): ?Cycle {
		$sequence_no = null === $previous ? 1 : $previous->get_sequence_no() + 1;

		$cycle = Cycle::create(
			array(
				'contract_id'       => (int) $contract->get_id(),
				'sequence_no'       => $sequence_no,
				'count'             => $count,
				'status'            => CycleStatus::pending(),
				'starts_at_gmt'     => $spec->get_starts_at_gmt(),
				'ends_at_gmt'       => $spec->get_ends_at_gmt(),
				'expected_total'    => $spec->get_expected_total(),
				'currency'          => $spec->get_currency(),
				'extension_slug'    => $contract->get_extension_slug(),
				'plan_snapshot_id'  => $contract->get_plan_snapshot_id(),
				'items_snapshot_id' => $contract->get_items_snapshot_id(),
			)
		);

		try {
			$this->contracts->append_cycle( $cycle, $previous );
		} catch ( Throwable $e ) {
			// A duplicate (contract_id, kind, count) is rejected by the UNIQUE index: the
			// cycle was already claimed by a concurrent/earlier fire. Idempotent no-op.
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process_due(): could not claim cycle %d for contract %d (already claimed) - skipping. %s', $count, (int) $contract->get_id(), $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			return null;
		}

		return $cycle;
	}

	/**
	 * Resolve the renewal outcome from the order's paid state.
	 *
	 * Paid -> CAS the cycle `pending -> billed`, link the order, advance the contract's
	 * `next_payment_gmt` (the computed next period end) and `last_payment_gmt`, persist.
	 * Not paid -> CAS the cycle `pending -> failed` (recording a reason) and leave the
	 * contract schedule unchanged for a later dunning pass.
	 *
	 * @param Contract      $contract      The contract being renewed.
	 * @param Cycle         $cycle         The claimed pending cycle to settle.
	 * @param WC_Order      $renewal_order The charged renewal order.
	 * @param NextCycleSpec $spec          The computed next-cycle shape (the next schedule date).
	 */
	private function resolve_outcome( Contract $contract, Cycle $cycle, WC_Order $renewal_order, NextCycleSpec $spec ): void {
		$now = gmdate( 'Y-m-d H:i:s' );

		// Re-fetch the order: a gateway handler that called payment_complete() on its own
		// freshly-loaded instance leaves the passed object stale, which would misread a
		// successful charge as unpaid. Read the paid state from the fresh instance.
		$fresh = wc_get_order( $renewal_order->get_id() );
		$paid  = $fresh instanceof WC_Order ? $fresh->is_paid() : $renewal_order->is_paid();

		if ( $paid ) {
			// CAS pending -> billed (the entity validates the transition) + link the order.
			$cycle->set_status( CycleStatus::billed() );
			$cycle->set_order_id( $renewal_order->get_id() );
			$this->contracts->update_cycle( $cycle );

			$contract->set_next_payment_gmt( $spec->get_ends_at_gmt() );
			$contract->set_last_payment_gmt( $now );
			$contract->set_last_attempt_gmt( $now );
			$this->contracts->update( $contract );

			/**
			 * Fires after a renewal cycle is billed and the contract schedule advanced.
			 *
			 * @param Contract $contract      The renewed contract.
			 * @param Cycle    $cycle         The newly-billed cycle.
			 * @param WC_Order $renewal_order The paid renewal order.
			 */
			do_action( self::RENEWAL_BILLED_ACTION, $contract, $cycle, $renewal_order );

			return;
		}

		// Not paid: settle the cycle failed and leave the contract schedule for dunning.
		$cycle->set_status( CycleStatus::failed() );
		$cycle->set_reason( 'gateway-charge-not-settled' );
		$this->contracts->update_cycle( $cycle );

		$contract->set_last_attempt_gmt( $now );
		$this->contracts->update( $contract );
	}

	/**
	 * Resolve the plan snapshot for the cadence computation, or null when none is
	 * available.
	 *
	 * The previous cycle's frozen snapshot is the canonical source, but a settled
	 * (terminal) cycle is hydrated without its snapshot value objects, so the snapshot is
	 * rebuilt from the contract's selling plan when the cycle does not carry one (or there
	 * is no previous cycle). For the flat recurring case the cadence is stable, so the live
	 * plan's policy matches the frozen one. Returns null when the selling plan can no longer
	 * be loaded (a deleted plan) so the caller can skip gracefully rather than mis-bill.
	 *
	 * @param Contract   $contract The contract being renewed.
	 * @param Cycle|null $previous The chain's previous cycle, or null when the chain is empty.
	 * @return PlanSnapshot|null The plan snapshot to compute the next cadence from, or null.
	 */
	private function resolve_plan_snapshot( Contract $contract, ?Cycle $previous ): ?PlanSnapshot {
		if ( null !== $previous ) {
			$snapshot = $previous->get_plan_snapshot();
			if ( $snapshot instanceof PlanSnapshot ) {
				return $snapshot;
			}
		}

		$plan = $this->plans->find( $contract->get_selling_plan_id() );
		if ( $plan instanceof Plan ) {
			return PlanSnapshot::from_array(
				array(
					'selling_plan_id' => $plan->get_id(),
					'billing_policy'  => $plan->get_billing_policy()->to_array(),
				)
			);
		}

		return null;
	}

	/**
	 * Build a renewal order cloned from the contract's origin order: clones
	 * line/fee/shipping/tax/coupon items and addresses, applies the new cycle's
	 * expected total as ground truth, attaches the contract's payment token, and tags
	 * the renewal relation meta (contract id + chargeable number) so charge observers
	 * and the idempotency check can find it. Returns null (logged) when the origin
	 * order cannot be loaded or `wc_create_order()` fails.
	 *
	 * @param Contract $contract       Contract being renewed.
	 * @param int      $count          The chargeable number this order bills.
	 * @param string   $expected_total The new cycle's expected total (the price authority).
	 * @return WC_Order|null The saved pending renewal order, or null on failure.
	 */
	private function build_renewal_order( Contract $contract, int $count, string $expected_total ): ?WC_Order {
		$origin_order_id = $contract->get_origin_order_id();
		if ( null === $origin_order_id ) {
			// A manual/admin contract has no origin order to clone from - not supported yet.
			wc_get_logger()->error(
				sprintf( 'RenewalEngine: cannot build renewal for contract %d - it has no origin order to clone.', (int) $contract->get_id() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			return null;
		}

		$origin = wc_get_order( $origin_order_id );
		if ( ! $origin instanceof WC_Order ) {
			wc_get_logger()->error(
				sprintf( 'RenewalEngine: cannot build renewal for contract %d - origin order %d not found.', (int) $contract->get_id(), $origin_order_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			return null;
		}

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

		$renewal_order->set_address( $origin->get_address( 'billing' ), 'billing' );
		$renewal_order->set_address( $origin->get_address( 'shipping' ), 'shipping' );

		// `set_id( 0 )` turns each clone into a fresh row on the renewal order rather
		// than UPDATE-ing the origin's row.
		foreach ( $origin->get_items( array( 'line_item', 'fee', 'shipping', 'tax', 'coupon' ) ) as $item ) {
			$clone = clone $item;
			$clone->set_id( 0 );
			$renewal_order->add_item( $clone );
		}

		// The new cycle's expected_total is the price authority - applied after
		// add_item() so the line items do not recompute over it. Reconstructing the
		// granular discount/shipping/tax breakdown is a later money-path's job.
		$renewal_order->set_total( $expected_total );

		$token_id = $instrument->get_token_id();
		if ( null !== $token_id ) {
			$token = \WC_Payment_Tokens::get( $token_id );
			if ( $token instanceof \WC_Payment_Token ) {
				$renewal_order->add_payment_token( $token );
			}
		}

		// Tag the renewal relation + chargeable number so the idempotency check can
		// detect a duplicate fire for the same number.
		$renewal_order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract->get_id() );
		$renewal_order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$renewal_order->update_meta_data( self::renewal_cycle_meta_key(), (string) $count );

		$renewal_order->save();

		return $renewal_order;
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
	 * Whether a renewal order tagged for `$contract_id` at `$cycle` already exists -
	 * the idempotency check for AS retries.
	 *
	 * Queries on the contract id via the flat `meta_key` / `meta_value` shortcut, then
	 * narrows by relation type and cycle in PHP. The flat shortcut is used rather than a
	 * `meta_query` because the legacy CPT order store rejects `meta_query` with
	 * `wc_doing_it_wrong`; the shortcut round-trips through both stores.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $cycle       The cycle number the renewal would bill.
	 */
	private function renewal_exists_for_cycle( int $contract_id, int $cycle ): bool {
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
			return false;
		}

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			if ( OrderLinkage::RELATION_RENEWAL === $order->get_meta( OrderLinkage::META_RELATION_TYPE )
				&& (string) $cycle === $order->get_meta( self::renewal_cycle_meta_key() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Order meta key recording which cycle a renewal order bills.
	 *
	 * Used by the per-cycle idempotency check. Internal to the engine.
	 */
	private static function renewal_cycle_meta_key(): string {
		return '_subscription_renewal_cycle';
	}
}
