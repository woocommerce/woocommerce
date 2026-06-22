<?php
/**
 * RenewalEngine - the seam between consumers and the renewal money-path.
 *
 * `schedule()` is what a consumer (or the checkout factory's caller) invokes
 * when a contract's next-payment date is set or moved; `process_due()` is what
 * Action Scheduler calls back into when the scheduled moment arrives. Action
 * Scheduler is the wrong thing to expose directly - hook names, group
 * conventions, and dedup behaviour are implementation choices the engine should
 * be free to change - so this class wraps them and adds the contract-aware
 * semantics (capability gating, the advance, the renewal order, the charge).
 *
 * The AS coupling lives in {@see RenewalScheduler}; this class delegates to it.
 * The pure date math lives in {@see RenewalCalculator} (Core); this class reads
 * the contract row, calls Core for the decision, then writes the result back.
 *
 * POC shape: one AS job per contract, advance-on-fire. The long-term batch
 * dispatcher and the split of attempt-vs-success accounting arrive with the
 * cycles/attempts reshape - see the package architecture notes. Until then this
 * advances the contract clock at fire time so the chain keeps moving.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
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
	 * Logger source tag.
	 */
	const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Repository for loading and persisting contracts.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Repository for loading plans (the BillingPolicy source).
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
	 * Register the Action Scheduler callback.
	 *
	 * Must run on every page load (not just activation) so AS can dispatch a
	 * due action back into {@see self::process_due()}. Wired from
	 * {@see \Automattic\WooCommerce\SubscriptionsEngine\Integration\Bootstrap}.
	 */
	public static function register_hooks(): void {
		add_action( RenewalScheduler::HOOK, array( __CLASS__, 'handle_due_action' ), 10, 1 );
	}

	/**
	 * Action Scheduler dispatch entry point - fires when a renewal is due.
	 *
	 * Static so it can be registered as a plain callback; constructs an engine
	 * with default repositories and routes through the instance `process_due()`
	 * so the dispatch path and any synchronous test driver share one code path.
	 *
	 * @param int $contract_id Contract whose renewal is firing.
	 */
	public static function handle_due_action( int $contract_id ): void {
		( new self() )->process_due( $contract_id );
	}

	/**
	 * Schedule (or re-schedule) the next renewal for `$contract` at its
	 * `next_payment_gmt`.
	 *
	 * Re-scheduling replaces: at most one pending AS row exists per contract.
	 * The clear-then-enqueue is unconditional, so callers moving the date
	 * forward just call `schedule()` again.
	 *
	 * **Capability gate (the schedule-time guard).** A primitive-scheduled
	 * contract is only enqueued when its gateway declares the `recurring`
	 * capability via {@see CapabilityRegistry::supports()}. An incapable gateway
	 * would create renewals nothing can charge, so we refuse at the boundary
	 * (log + no enqueue) rather than failing later on a customer-facing order.
	 * Gateway-scheduled contracts (`schedule_source = gateway`) are never
	 * enqueued here - the gateway runs its own schedule; any stale primitive row
	 * is cleared.
	 *
	 * Does nothing when the contract has no `next_payment_gmt` (nothing to
	 * anchor on) - any stale row is cleared.
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

		// Gateway-scheduled: the gateway owns the schedule. Clear any stale
		// primitive row and bail.
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

		// Clear-then-enqueue keeps the single-row-per-contract invariant: AS
		// does not dedup on hook+args, so without the clear a re-schedule would
		// leave two rows and fire twice.
		RenewalScheduler::unschedule( $id );
		RenewalScheduler::schedule( $id, $when );

		do_action( self::RENEWAL_SCHEDULED_ACTION, $contract, $when );

		return true;
	}

	/**
	 * Run the renewal due for `$contract_id`. Fired by the AS hook.
	 *
	 * Steps:
	 *  1. Load the contract; bail (log) if it is gone - a stale AS row firing
	 *     against a deleted contract is not worth throwing over (AS would retry
	 *     a permanent failure forever).
	 *  2. Skip non-active contracts (on-hold / pending-cancellation / terminal).
	 *     The lifecycle path should have cleared the AS row, but a row can slip
	 *     through (migration, manual SQL); skipping is the safe default. Skip
	 *     gateway-scheduled contracts the same way - the gateway owns the charge.
	 *  3. Idempotency guard: if a renewal order for this contract's *current*
	 *     cycle already exists, do not create a second one. Tolerates AS
	 *     retries without double-charging or double-advancing.
	 *  4. Build the renewal order, attempt the gateway charge, advance the
	 *     contract clock (cycle_count + next_payment_gmt, or terminal on
	 *     max_cycles), persist, and re-schedule the next cycle.
	 *
	 * Returns the renewal order, or null when the renewal was skipped.
	 *
	 * @param int $contract_id Contract whose renewal cycle is firing.
	 * @return WC_Order|null The created renewal order, or null when skipped.
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

		// Idempotency: a renewal order already tagged for this contract's
		// current cycle means this action already ran (AS retry, double-fire).
		// Bail without creating a second order or advancing again.
		if ( $this->renewal_exists_for_cycle( $contract_id, $contract->get_cycle_count() + 1 ) ) {
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process_due(): renewal for contract %d cycle %d already exists - skipping (idempotent retry).', $contract_id, $contract->get_cycle_count() + 1 ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		$renewal_order = $this->build_renewal_order( $contract );
		if ( null === $renewal_order ) {
			// build_renewal_order logged the reason; do not advance a contract
			// whose renewal we could not create.
			return null;
		}

		$this->advance_contract( $contract );
		$this->contracts->update( $contract );

		do_action( self::RENEWAL_ORDER_CREATED_ACTION, $renewal_order, $contract );

		$this->attempt_charge( $renewal_order, $contract );

		// Re-arm the next cycle when the contract is still active (i.e. it did
		// not just hit max_cycles).
		if ( ContractStatus::ACTIVE === $contract->get_status() ) {
			$this->schedule( $contract );
		} else {
			RenewalScheduler::unschedule( $contract_id );
		}

		return $renewal_order;
	}

	/**
	 * Cancel `$contract`: transition to cancelled and clear its pending renewal.
	 *
	 * Status moves through the Core state machine ({@see Contract::set_status()}),
	 * which raises a `DomainException` on an illegal transition (for example
	 * cancelling an already-expired contract). On success the contract is
	 * persisted and its AS row cleared.
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

		RenewalScheduler::unschedule( $id );

		return true;
	}

	/**
	 * Advance `$contract` one cycle: bump the paid-cycle count and either
	 * compute the next bill date or end the contract on max_cycles.
	 *
	 * The decision is delegated to the pure Core {@see RenewalCalculator} with
	 * an explicit `$now`. When the plan is missing (deleted out from under the
	 * contract) the clock cannot advance; the cycle count still moves and the
	 * next date is cleared, leaving a visible "active, no next date" state.
	 *
	 * @param Contract $contract Contract to advance in place.
	 */
	private function advance_contract( Contract $contract ): void {
		$now       = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$new_count = $contract->get_cycle_count() + 1;
		$plan      = $this->plans->find( $contract->get_selling_plan_id() );

		$contract->set_cycle_count( $new_count );
		$contract->set_last_payment_gmt( $now->format( 'Y-m-d H:i:s' ) );

		if ( null === $plan ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::advance_contract(): contract %d references a missing plan %d - cannot compute the next bill date. Cycle advanced; next_payment_gmt cleared.', (int) $contract->get_id(), $contract->get_selling_plan_id() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => (int) $contract->get_id(),
				)
			);
			$contract->set_next_payment_gmt( null );
			return;
		}

		$policy = $plan->get_billing_policy();

		if ( RenewalCalculator::has_reached_max_cycles( $policy, $new_count ) ) {
			$contract->set_next_payment_gmt( null );
			$contract->set_status( ContractStatus::EXPIRED );
			return;
		}

		// Anchor the next cycle on the cycle that just billed. Using the
		// contract's prior next_payment_gmt as the anchor (not `now`) keeps the
		// cadence on its calendar grid even if the job fired late.
		$anchor_gmt = $contract->get_next_payment_gmt();
		$anchor     = null !== $anchor_gmt
			? new DateTimeImmutable( $anchor_gmt, new DateTimeZone( 'UTC' ) )
			: $now;

		$next = RenewalCalculator::next_bill_date( $policy, $anchor );
		$contract->set_next_payment_gmt( $next->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * Build a renewal order cloned from the contract's origin order.
	 *
	 * Clones line/fee/shipping/tax/coupon items and addresses from the origin,
	 * applies the contract's stored recurring totals as ground truth, attaches
	 * the contract's payment token, and tags the order with the renewal
	 * relation meta (contract id + current cycle) so charge observers and the
	 * idempotency check can find it. Returns null (logged) when the origin order
	 * cannot be loaded or `wc_create_order()` fails - the caller then skips the
	 * advance.
	 *
	 * @param Contract $contract Contract being renewed.
	 * @return WC_Order|null The saved pending renewal order, or null on failure.
	 */
	private function build_renewal_order( Contract $contract ): ?WC_Order {
		$origin = wc_get_order( $contract->get_origin_order_id() );
		if ( ! $origin instanceof WC_Order ) {
			wc_get_logger()->error(
				sprintf( 'RenewalEngine: cannot build renewal for contract %d - origin order %d not found.', (int) $contract->get_id(), $contract->get_origin_order_id() ),
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

		// Clone every relevant line type. `set_id( 0 )` turns each clone into a
		// fresh row attached to the renewal order rather than UPDATE-ing the
		// origin's row.
		foreach ( $origin->get_items( array( 'line_item', 'fee', 'shipping', 'tax', 'coupon' ) ) as $item ) {
			$clone = clone $item;
			$clone->set_id( 0 );
			$renewal_order->add_item( $clone );
		}

		// Contract totals are the price authority for the cycle - applied after
		// add_item() so the line items do not recompute over them.
		$renewal_order->set_discount_total( $contract->get_discount_total() );
		$renewal_order->set_shipping_total( $contract->get_shipping_total() );
		$renewal_order->set_cart_tax( $contract->get_tax_total() );
		$renewal_order->set_total( $contract->get_billing_total() );

		$token_id = $instrument->get_token_id();
		if ( null !== $token_id ) {
			$token = \WC_Payment_Tokens::get( $token_id );
			if ( $token instanceof \WC_Payment_Token ) {
				$renewal_order->add_payment_token( $token );
			}
		}

		// Tag with the renewal relation + the cycle this order bills, so the
		// idempotency check can detect a duplicate fire for the same cycle.
		$renewal_order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract->get_id() );
		$renewal_order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$renewal_order->update_meta_data( self::renewal_cycle_meta_key(), (string) ( $contract->get_cycle_count() + 1 ) );

		$renewal_order->save();

		return $renewal_order;
	}

	/**
	 * Attempt the gateway charge for `$renewal_order`.
	 *
	 * Mirrors WooCommerce Subscriptions' scheduled-payment dispatch: fire
	 * `woocommerce_subscriptions_engine_scheduled_payment_{gateway}` with the
	 * amount and the order so the contract's gateway (or its adapter) captures
	 * using the stored token. The engine does not implement gateway charging
	 * itself - it hands off to whatever gateway integration is registered, and
	 * the resulting WC payment-complete / failed status is what downstream
	 * accounting observes. A gateway that registers no handler leaves the order
	 * `pending` (uncharged), which is the correct safe state for a contract
	 * scheduled against a gateway that cannot actually charge.
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
			 * Fires to request a recurring charge for a renewal order.
			 *
			 * The contract's gateway (or a gateway adapter) hooks the
			 * gateway-specific variant and captures against the stored token,
			 * then transitions the order via the gateway's own
			 * `payment_complete()` / failure handling.
			 *
			 * @param float    $amount        The amount to charge.
			 * @param WC_Order $renewal_order The renewal order being charged.
			 */
			do_action( 'woocommerce_subscriptions_engine_scheduled_payment_' . $gateway_id, $amount, $renewal_order );
		} catch ( Throwable $e ) {
			// A throwing gateway handler must not leave the AS action in a
			// retry-forever loop or roll back the advance we already persisted.
			// Log and move on; the order stays pending for dunning to pick up.
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
	 * Whether a renewal order tagged for `$contract_id` at `$cycle` already exists.
	 *
	 * The idempotency check for AS retries. Queries on the single most-selective
	 * key (the contract id) via the flat `meta_key` / `meta_value` shortcut, then
	 * narrows by relation type and cycle in PHP. The flat shortcut is used rather
	 * than a three-clause `meta_query` because the legacy CPT order store (the
	 * fallback under HPOS, and the only store with HPOS off) rejects `meta_query`
	 * with `wc_doing_it_wrong` and drops it; the flat shortcut round-trips
	 * through both stores. A contract has a handful of renewal orders, so the
	 * in-memory narrowing is cheap.
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
