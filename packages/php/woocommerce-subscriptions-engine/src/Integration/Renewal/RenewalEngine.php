<?php
/**
 * The seam between consumers and the renewal money-path: `schedule()` enqueues a
 * contract's next renewal, `process_due()` runs it when Action Scheduler fires.
 * Wraps Action Scheduler (whose hook names and dedup behaviour stay private) and
 * adds the contract-aware semantics: capability gating, the renewal order, the charge.
 * One AS job per contract; the AS coupling lives in {@see RenewalScheduler}.
 *
 * Advancing the chain at fire time (appending the next cycle, recording the outcome,
 * advancing `next_payment_gmt`, re-arming the next due moment) is the dispatcher
 * slice's money-path and is not built here, so this unit does not drive a live loop.
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
	 * @param ContractRepository|null $contracts Contract repository; default instance when omitted.
	 */
	public function __construct( ?ContractRepository $contracts = null ) {
		$this->contracts = $contracts ?? new ContractRepository();
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
	 * Then guards idempotency - if a renewal order for the next chargeable number in
	 * the billing chain already exists, no second one is created (tolerating AS retries) -
	 * and builds the renewal order and attempts the gateway charge. Advancing the chain
	 * is the dispatcher slice's money-path, so this does not drive a live renewal loop.
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

		// Idempotency: a renewal order already tagged for this number means the action
		// already ran (AS retry, double-fire). Bail without creating a second order.
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

		// Advancing the chain and re-arming the next due moment is the dispatcher
		// slice's money-path - deferred, so the loop is not driven from here.

		return $renewal_order;
	}

	/**
	 * Cancel `$contract`: transition to cancelled and clear its pending renewal.
	 *
	 * Status moves through the Core state machine ({@see Contract::set_status()}),
	 * which raises a `DomainException` on an illegal transition.
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
		// update() persists the contract-row cache/status ONLY. Closing the scheduled
		// cycle and any chain/cycle head transition is the dispatcher money-path.
		$this->contracts->update( $contract );

		RenewalScheduler::unschedule( $id );

		return true;
	}

	/**
	 * The next chargeable number in the contract's billing chain - one past the
	 * per-chain `MAX(count)`, or 1 for a chain with no counting cycle yet. This is
	 * the idempotency anchor the renewal order is tagged with.
	 *
	 * @param int $contract_id Contract id.
	 * @return int The next chargeable number.
	 */
	private function next_chargeable_count( int $contract_id ): int {
		$max = $this->contracts->max_count( $contract_id );

		return null === $max ? 1 : $max + 1;
	}

	/**
	 * Build a renewal order cloned from the contract's origin order: clones
	 * line/fee/shipping/tax/coupon items and addresses, applies the current cycle's
	 * expected total as ground truth, attaches the contract's payment token, and tags
	 * the renewal relation meta (contract id + chargeable number) so charge observers
	 * and the idempotency check can find it. Returns null (logged) when the origin
	 * order cannot be loaded or `wc_create_order()` fails.
	 *
	 * @param Contract $contract Contract being renewed.
	 * @param int      $count    The chargeable number this order bills.
	 * @return WC_Order|null The saved pending renewal order, or null on failure.
	 */
	private function build_renewal_order( Contract $contract, int $count ): ?WC_Order {
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

		// The current cycle's expected_total is the price authority - applied after
		// add_item() so the line items do not recompute over it. Reconstructing the
		// granular discount/shipping/tax breakdown is the dispatcher money-path's job.
		$renewal_order->set_total( $this->current_cycle_total( (int) $contract->get_id() ) );

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
	 * The amount the contract's current cycle expects to bill (its `expected_total`).
	 * A contract with no cycle yet yields '0', making the charge a no-op rather than a
	 * fatal - the safe state for a contract with nothing to bill.
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
