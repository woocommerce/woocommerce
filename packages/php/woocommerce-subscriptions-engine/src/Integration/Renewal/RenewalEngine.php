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
use WC_Order_Item_Product;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Renewal\RenewalCalculator;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
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

		$previous = $this->contracts->find_current_cycle( $contract_id );
		if ( null === $previous ) {
			// No billing chain to advance: checkout always creates cycle 1, so a chainless
			// contract is a manual/corrupt case the engine does not renew. Skip without throwing
			// (never silently bill it as cycle 1) so a scheduled action does not retry forever.
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): contract %d has no billing chain to advance - skipping.', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

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

		// Resolve the billing cadence from the contract's plan snapshot. A deleted/unresolvable
		// plan is a recoverable data condition, not a fatal: skip (logging only) like the guards
		// above so a scheduled action does not retry a permanent failure forever.
		$policy = $this->resolve_billing_policy( $contract );
		if ( null === $policy ) {
			wc_get_logger()->warning(
				sprintf( 'RenewalEngine::process_due(): cannot resolve the billing plan for contract %d - skipping. The selling plan may have been deleted.', $contract_id ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return null;
		}

		// Build the next cycle from the contract's live values (amount, currency, snapshots),
		// one cadence forward from the anchor.
		$new_cycle = RenewalCalculator::compute_next_cycle(
			$policy,
			array(
				'contract_id'       => $contract_id,
				'sequence_no'       => $previous->get_sequence_no() + 1,
				'count'             => $next_count,
				'period_start'      => $previous->get_ends_at_gmt(),
				'expected_total'    => $contract->get_billing_total(),
				'currency'          => $contract->get_currency(),
				'extension_slug'    => $contract->get_extension_slug(),
				'plan_snapshot_id'  => $contract->get_plan_snapshot_id(),
				'items_snapshot_id' => $contract->get_items_snapshot_id(),
			)
		);

		// Create-as-claim: the cycle is inserted `pending` before any charge. A concurrent or
		// duplicate fire loses the UNIQUE(contract_id, kind, count) race and is an idempotent no-op.
		if ( ! $this->claim_cycle( $new_cycle, $previous ) ) {
			return null;
		}

		$renewal_order = $this->build_renewal_order( $contract, $next_count, $new_cycle->get_expected_total() );
		if ( null === $renewal_order ) {
			// build_renewal_order logged the reason. The claimed cycle stays pending for
			// a later run/dunning to resolve; no schedule change is made here.
			return null;
		}

		do_action( self::RENEWAL_ORDER_CREATED_ACTION, $renewal_order, $contract );

		$this->attempt_charge( $renewal_order, $contract );

		$this->resolve_outcome( $contract, $new_cycle, $renewal_order );

		return $renewal_order;
	}

	/**
	 * The chargeable number this renewal targets - the idempotency anchor.
	 *
	 * One past the head cycle's count once it has settled forward (`billed`/`cancelled`): the
	 * chain advances. While the head is still unsettled (`pending`/`failed`) the same count is
	 * targeted again, so a retry resolves the in-flight cycle rather than skipping a number - and
	 * the order-meta pre-check / the create-as-claim UNIQUE then make the retry a no-op.
	 *
	 * Only called once a billing chain exists ({@see self::process_due()} skips a chainless
	 * contract), so the head must carry a count; a countless head is a corrupt chain to refuse.
	 *
	 * @param Cycle $previous The chain's most-recent cycle.
	 * @return int The chargeable number to target.
	 * @throws \RuntimeException If the head cycle has no count to advance from.
	 */
	private function target_count( Cycle $previous ): int {
		$count = $previous->get_count();
		if ( null === $count ) {
			// A counting renewal advances off the head cycle's count; a head with no count is a
			// corrupt chain we refuse to bill against rather than guess a number.
			throw new \RuntimeException(
				sprintf(
					'RenewalEngine::target_count(): contract %d head cycle %d has no count to advance from.',
					(int) $previous->get_contract_id(),
					(int) $previous->get_id()
				)
			);
		}

		$status          = $previous->get_status()->get_value();
		$settled_forward = CycleStatus::BILLED === $status || CycleStatus::CANCELLED === $status;

		return $settled_forward ? (int) $count + 1 : (int) $count;
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
	 * Claim the freshly-computed `pending` cycle as the create-as-claim. Returns false when
	 * the insert loses the `UNIQUE(contract_id, kind, count)` race (a concurrent/duplicate
	 * fire) - treated as an idempotent no-op.
	 *
	 * @param Cycle      $cycle    The pending cycle to claim.
	 * @param Cycle|null $previous The chain's previous cycle (for snapshot copy-forward), or null.
	 * @return bool True when the cycle was claimed; false when the claim was lost.
	 */
	private function claim_cycle( Cycle $cycle, ?Cycle $previous ): bool {
		try {
			$this->contracts->append_cycle( $cycle, $previous );
		} catch ( Throwable $e ) {
			// A duplicate (contract_id, kind, count) is rejected by the UNIQUE index: the cycle
			// was already claimed by a concurrent/earlier fire. Idempotent no-op.
			wc_get_logger()->info(
				sprintf( 'RenewalEngine::process_due(): could not claim cycle %d for contract %d (already claimed) - skipping. %s', (int) $cycle->get_count(), $cycle->get_contract_id(), $e->getMessage() ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $cycle->get_contract_id(),
				)
			);
			return false;
		}

		return true;
	}

	/**
	 * Resolve the renewal outcome from the order's paid state.
	 *
	 * Paid -> CAS the cycle `pending -> billed`, link the order, advance the contract's
	 * `next_payment_gmt` (the cycle's own period end) and `last_payment_gmt`, persist. Not
	 * paid -> CAS the cycle `pending -> failed` (recording a reason) and leave the contract
	 * schedule unchanged for a later dunning pass.
	 *
	 * @param Contract $contract      The contract being renewed.
	 * @param Cycle    $cycle         The claimed pending cycle to settle.
	 * @param WC_Order $renewal_order The charged renewal order.
	 */
	private function resolve_outcome( Contract $contract, Cycle $cycle, WC_Order $renewal_order ): void {
		$now = gmdate( 'Y-m-d H:i:s' );

		// Re-fetch the order: a gateway handler that called payment_complete() on its own
		// freshly-loaded instance leaves the passed object stale, which would misread a
		// successful charge as unpaid. Read the paid state from the fresh instance.
		$fresh = wc_get_order( $renewal_order->get_id() );
		$paid  = $fresh instanceof WC_Order ? $fresh->is_paid() : $renewal_order->is_paid();

		// The renewal order exists regardless of the charge outcome, so record it on the
		// cycle either way - a failed/pending cycle still references its order for dunning
		// and admin visibility.
		$cycle->set_order_id( $renewal_order->get_id() );

		if ( $paid ) {
			// CAS pending -> billed (the entity validates the transition).
			$cycle->set_status( CycleStatus::billed() );
			$this->contracts->update_cycle( $cycle );

			$contract->set_next_payment_gmt( $cycle->get_ends_at_gmt() );
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
	 * Build the renewal order from the contract's own stored state: its billing / shipping
	 * addresses and its (recurring) line items - never the origin order, whose cart may have
	 * carried one-time items that must not ride along onto a renewal. Applies the new cycle's
	 * expected total as ground truth, attaches the contract's payment token, and tags the
	 * renewal relation meta (contract id + chargeable number) so charge observers and the
	 * idempotency check can find it. Returns null (logged) when `wc_create_order()` fails.
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

		// Tag the renewal relation + chargeable number so the idempotency check can detect a
		// duplicate fire, and save before attaching the token so a crash between the two leaves
		// the order findable (no duplicate charge on the retry).
		$renewal_order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract->get_id() );
		$renewal_order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$renewal_order->update_meta_data( self::renewal_cycle_meta_key(), (string) $count );
		$renewal_order->save();

		$token_id = $instrument->get_token_id();
		if ( null !== $token_id ) {
			$token = \WC_Payment_Tokens::get( $token_id );
			if ( $token instanceof \WC_Payment_Token ) {
				$renewal_order->add_payment_token( $token );
			}
		}

		return $renewal_order;
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
