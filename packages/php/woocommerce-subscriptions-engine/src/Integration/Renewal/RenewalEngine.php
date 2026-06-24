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
 * semantics (capability gating, the renewal order, the charge).
 *
 * The AS coupling lives in {@see RenewalScheduler}; this class delegates to it.
 * The schedule is read from the contract's live `next_payment_gmt` and the
 * chain's most-recent cycle through targeted cycle access.
 *
 * One AS job per contract. Advancing the chain at fire time - appending the next
 * cycle with `count = MAX(count) + 1`, recording the charge outcome on the cycle,
 * advancing the contract's live `next_payment_gmt`, and re-arming the next due
 * moment - is the dispatcher slice's money-path and is not built here, so this unit
 * does not drive a live renewal loop. The long-term batch dispatcher (a few
 * recurring jobs scanning a due index with lease claims) arrives with that slice.
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
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Gateway\CapabilityRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

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
	 * Repository for loading and persisting contracts, and targeted cycle access.
	 *
	 * @var ContractRepository
	 */
	private $contracts;

	/**
	 * Build a renewal engine over the given contract repository.
	 *
	 * The plan repository the advance path needs to read billing policy is
	 * reintroduced with the dispatcher slice that rebuilds advancement.
	 *
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
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
	 *  3. Idempotency guard: if a renewal order for the next chargeable number in
	 *     the billing chain already exists, do not create a second one. Tolerates
	 *     AS retries without double-charging.
	 *  4. Build the renewal order and attempt the gateway charge.
	 *
	 * Advancing the chain - appending the next cycle with `count = MAX(count) + 1`,
	 * recording the charge outcome on the cycle, refreshing `next_payment_gmt`, and
	 * re-arming the next due moment - is the dispatcher slice's money-path and is
	 * not built here, so this unit does not drive a live renewal loop. The chain's
	 * next chargeable number and the amount are read from the repository (the
	 * per-chain `MAX(count)` and the current cycle's `expected_total`).
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

		$next_count = $this->next_chargeable_count( $contract_id );

		// Idempotency: a renewal order already tagged for the next chargeable
		// number means this action already ran (AS retry, double-fire). Bail
		// without creating a second order.
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

		$renewal_order = $this->build_renewal_order( $contract, $next_count );
		if ( null === $renewal_order ) {
			// build_renewal_order logged the reason.
			return null;
		}

		do_action( self::RENEWAL_ORDER_CREATED_ACTION, $renewal_order, $contract );

		$this->attempt_charge( $renewal_order, $contract );

		// Advancing the chain (append the next cycle, record the outcome, advance
		// the contract's live next_payment_gmt) and re-arming the next due moment is
		// the dispatcher slice's money-path - deferred, so the loop is not driven from here.

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
		// For now update() persists the contract-row cache/status ONLY. Closing the
		// scheduled cycle (status cancelled + reason) and any chain/cycle head
		// transition is the dispatcher money-path; do not upgrade this to
		// save() until that cycle-transition logic is wired.
		$this->contracts->update( $contract );

		RenewalScheduler::unschedule( $id );

		return true;
	}

	/**
	 * The next chargeable number in the contract's billing chain.
	 *
	 * The per-chain count is `MAX(count)` over the cycle rows; the next charge is
	 * one past it. A chain with no counting cycle yet (none stored, or only
	 * non-counting trial periods) starts at 1. This is the idempotency anchor the
	 * renewal order is tagged with; the dispatcher slice is what actually appends
	 * the cycle that carries this count.
	 *
	 * @param int $contract_id Contract id.
	 * @return int The next chargeable number.
	 */
	private function next_chargeable_count( int $contract_id ): int {
		$max = $this->contracts->max_count( $contract_id );

		return null === $max ? 1 : $max + 1;
	}

	/**
	 * Build a renewal order cloned from the contract's origin order.
	 *
	 * Clones line/fee/shipping/tax/coupon items and addresses from the origin,
	 * applies the current cycle's expected total as ground truth, attaches the
	 * contract's payment token, and tags the order with the renewal relation meta
	 * (contract id + the chargeable number) so charge observers and the idempotency
	 * check can find it. Returns null (logged) when the origin order cannot be
	 * loaded or `wc_create_order()` fails.
	 *
	 * @param Contract $contract Contract being renewed.
	 * @param int      $count    The chargeable number this order bills.
	 * @return WC_Order|null The saved pending renewal order, or null on failure.
	 */
	private function build_renewal_order( Contract $contract, int $count ): ?WC_Order {
		$origin_order_id = $contract->get_origin_order_id();
		if ( null === $origin_order_id ) {
			// A manual/admin contract has no origin order to clone from. The renewal
			// order path needs a source order, so it is not supported for these yet.
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

		// Clone every relevant line type. `set_id( 0 )` turns each clone into a
		// fresh row attached to the renewal order rather than UPDATE-ing the
		// origin's row.
		foreach ( $origin->get_items( array( 'line_item', 'fee', 'shipping', 'tax', 'coupon' ) ) as $item ) {
			$clone = clone $item;
			$clone->set_id( 0 );
			$renewal_order->add_item( $clone );
		}

		// The current cycle's expected_total is the price authority for the cycle -
		// applied after add_item() so the line items do not recompute over it. The
		// granular discount/shipping/tax breakdown lives in the cycle's items
		// snapshot; reconstructing it onto the renewal order is the dispatcher
		// money-path's job. For now only the order total is needed to compile and charge.
		$renewal_order->set_total( $this->current_cycle_total( (int) $contract->get_id() ) );

		$token_id = $instrument->get_token_id();
		if ( null !== $token_id ) {
			$token = \WC_Payment_Tokens::get( $token_id );
			if ( $token instanceof \WC_Payment_Token ) {
				$renewal_order->add_payment_token( $token );
			}
		}

		// Tag with the renewal relation + the chargeable number this order bills, so
		// the idempotency check can detect a duplicate fire for the same number.
		$renewal_order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract->get_id() );
		$renewal_order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$renewal_order->update_meta_data( self::renewal_cycle_meta_key(), (string) $count );

		$renewal_order->save();

		return $renewal_order;
	}

	/**
	 * The amount the contract's current cycle expects to bill.
	 *
	 * Reads the current cycle's `expected_total` through targeted cycle access. A
	 * contract with no cycle yet yields '0' - the charge is then a no-op rather
	 * than a fatal, which is the safe state for a contract with nothing to bill.
	 * The full per-cycle money path - reconstructing the discount/shipping/tax
	 * breakdown and appending the next cycle - lands with the dispatcher.
	 *
	 * @param int $contract_id The contract being renewed.
	 * @return string Decimal-safe amount string.
	 */
	private function current_cycle_total( int $contract_id ): string {
		$cycle = $this->contracts->find_current_cycle( $contract_id );

		return null === $cycle ? '0' : $cycle->get_expected_total();
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

		// This query does not paginate, so wc_get_orders() returns a plain list of
		// orders. The guard narrows the declared WC_Order[]|stdClass return type and
		// treats any unexpected non-array result as "no matching renewal".
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
