<?php
/**
 * Integration tests for RenewalEngine (and RenewalScheduler).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Renewal;

use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalScheduler;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanGroupRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalScheduler
 */
class RenewalEngineTest extends EngineIntegrationTestCase {

	private const GATEWAY = 'engine_test_gateway';

	/**
	 * A gateway that always approves the scheduled charge, marking the renewal order
	 * paid inline (the dummy-gateway shape: `payment_complete()` within the action).
	 */
	private const GATEWAY_APPROVING = 'engine_test_gateway_approve';

	/**
	 * A gateway that declares `recurring` but never completes the charge, so the
	 * renewal order stays unpaid (the failed-charge path).
	 */
	private const GATEWAY_DECLINING = 'engine_test_gateway_decline';

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
	}

	public function tear_down(): void {
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	private function make_plan( ?int $max_cycles = null ): int {
		return (int) $this->make_plan_object( $max_cycles )->get_id();
	}

	/**
	 * Persist a monthly plan and return the entity (the ContractFactory needs the plan).
	 *
	 * @param int|null $max_cycles Maximum billing cycles, or null for open-ended.
	 */
	private function make_plan_object( ?int $max_cycles = null ): Plan {
		$group_id = ( new PlanGroupRepository() )->insert( PlanGroup::create( array( 'name' => 'Club' ) ) );

		$plan = Plan::create(
			$group_id,
			array(
				'name'           => 'Monthly',
				'billing_policy' => new BillingPolicy( 'month', 1, null, $max_cycles, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => 'engine-tests',
			)
		);
		( new PlanRepository() )->insert( $plan );

		return $plan;
	}

	/**
	 * Sign up a contract via the checkout factory so its billing chain holds cycle 1
	 * (billed), the starting point the renewal advances from.
	 *
	 * @param string   $gateway    Gateway id stamped on the order/contract.
	 * @param int|null $max_cycles Maximum billing cycles, or null for open-ended.
	 * @return Contract The persisted contract with cycle 1 billed.
	 */
	private function sign_up_contract( string $gateway, ?int $max_cycles = null ): Contract {
		$plan = $this->make_plan_object( $max_cycles );

		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( $gateway );
		$order->set_total( '19.99' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		return ( new ContractFactory() )->create_from_order( $order, $plan );
	}

	private function make_origin_order(): WC_Order {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( self::GATEWAY );
		$order->set_total( '19.99' );
		$order->save();

		return $order;
	}

	private function make_contract( int $plan_id, int $origin_order_id ): Contract {
		// A lean contract row (no cycles). The renewal-advancement tests that
		// exercise the money-path are skipped until the dispatcher slice; when they
		// are reactivated they will append a billing cycle (with an expected_total)
		// so the renewal amount resolves off the current cycle.
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => $plan_id,
				'origin_order_id'  => $origin_order_id,
				'payment_method'   => self::GATEWAY,
				'start_gmt'        => '2026-01-15 00:00:00',
				'next_payment_gmt' => '2026-02-15 00:00:00',
			)
		);
		( new ContractRepository() )->insert( $contract );

		return $contract;
	}

	/**
	 * @testdox schedule is gated on the gateway's recurring capability.
	 */
	public function test_schedule_is_gated_on_recurring_capability(): void {
		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();

		// No capability declared: scheduling is refused.
		$this->assertFalse( $engine->schedule( $contract ) );

		// Declared: the contract is acknowledged as eligible (the capability gate passes).
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );
		$this->assertTrue( $engine->schedule( $contract ) );
	}

	/**
	 * @testdox schedule no longer enqueues a per-contract AS row (the dispatcher drives renewals).
	 *
	 * The batch dispatcher scans the contract due-index, so schedule() acknowledges
	 * eligibility (and keeps the capability gate) without enqueuing a per-contract Action
	 * Scheduler row. Repeated calls still enqueue nothing.
	 */
	public function test_schedule_does_not_enqueue_a_per_contract_row(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();
		$this->assertTrue( $engine->schedule( $contract ) );
		$this->assertTrue( $engine->schedule( $contract ) );

		// No per-contract row is enqueued by the superseded scheduler.
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );

		$pending = as_get_scheduled_actions(
			array(
				'hook'   => RenewalScheduler::HOOK,
				'args'   => array( $contract_id ),
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);
		$this->assertCount( 0, $pending );
	}

	/**
	 * @testdox process_due creates a renewal order tagged for the next chargeable number.
	 */
	public function test_process_due_creates_renewal_order(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );

		// The renewal order is created and tagged with the renewal relation + chargeable number.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertSame( (string) $contract_id, $renewal_order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		$this->assertSame( OrderLinkage::RELATION_RENEWAL, $renewal_order->get_meta( OrderLinkage::META_RELATION_TYPE ) );

		// The chain holds cycle 1 (from signup), so the renewal targets the next number, 2.
		$this->assertSame( '2', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox process_due skips when a renewal order is already tagged for the cycle.
	 *
	 * Covers the order-meta pre-check: the first run tags a renewal order for the cycle, so
	 * the retried due action is suppressed before a second cycle/order is created.
	 */
	public function test_process_due_skips_when_a_renewal_order_is_already_tagged(): void {
		GatewayCapabilities::declare( self::GATEWAY_DECLINING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );
		$engine = new RenewalEngine();

		$first = $engine->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $first );

		// The charge did not settle, so the head stays at the same chargeable number; a retried
		// due action is suppressed by the order-meta pre-check rather than creating a second order.
		$retry = $engine->process_due( $contract_id );
		$this->assertNull( $retry );

		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox process_due skips when the cycle is already claimed (create-as-claim UNIQUE).
	 *
	 * Covers the claim_next_cycle catch path: a pending cycle for the target count exists with
	 * NO tagged renewal order, so the order-meta pre-check finds nothing and process_due reaches
	 * the append_cycle insert, which loses the UNIQUE(contract_id, kind, count) race. It returns
	 * null and creates no duplicate cycle or order.
	 */
	public function test_process_due_skips_when_the_cycle_is_already_claimed(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Pre-claim cycle 2 pending directly (no tagged renewal order), so the order-meta
		// pre-check does not fire and the claim collides on the UNIQUE index instead.
		$repo     = new ContractRepository();
		$previous = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );
		$claimed = Cycle::create(
			array(
				'contract_id'    => $contract_id,
				'sequence_no'    => $previous->get_sequence_no() + 1,
				'count'          => 2,
				'status'         => CycleStatus::pending(),
				'starts_at_gmt'  => '2026-02-15 00:00:00',
				'ends_at_gmt'    => '2026-03-15 00:00:00',
				'expected_total' => '19.99',
				'currency'       => 'USD',
			)
		);
		$repo->append_cycle( $claimed, $previous );

		$result = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertNull( $result );

		// No renewal order was created for count 2, and only the one pre-claimed cycle exists.
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		$at_count_2 = array_filter(
			$repo->find_cycle_history( $contract_id ),
			static function ( Cycle $cycle ): bool {
				return 2 === $cycle->get_count();
			}
		);
		$this->assertCount( 1, $at_count_2 );
	}

	/**
	 * @testdox process_due reclaims a stalled pending cycle whose crash-recovery lease has expired.
	 *
	 * A charge that claimed cycle 2 pending then crashed before settling (no renewal order
	 * tagged) leaves a stuck pending cycle. Once its `claimed_until` lease has expired, a
	 * later run reclaims that same cycle (re-stamping the lease), charges it, and settles it
	 * billed - no duplicate cycle is created.
	 */
	public function test_process_due_reclaims_a_stalled_cycle_with_an_expired_lease(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Seed a stalled pending cycle 2 with an already-expired lease (no tagged order).
		$repo     = new ContractRepository();
		$previous = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );
		$stalled    = $this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$stalled_id = $stalled->get_id();
		$this->assertNotNull( $stalled_id );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );

		// The reclaimed cycle is billed and resolved through a renewal order.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );
		$this->assertSame( '2', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );

		$head = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		// SAME cycle row reclaimed (not a duplicate), now billed with the lease cleared.
		$this->assertSame( $stalled_id, $head->get_id() );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
		$this->assertNull( $head->get_claimed_until_gmt() );

		// The schedule advances to the RECLAIMED cycle's own end (2026-03-15), not a
		// recomputed next period (2026-04-15) - a reclaim must not skip a billing cycle.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
		$this->assertNotNull( $reloaded->get_last_payment_gmt() );

		// Exactly one billing cycle for count 2 (no duplicate claimed).
		$at_count_2 = array_filter(
			$repo->find_cycle_history( $contract_id ),
			static function ( Cycle $cycle ): bool {
				return 2 === $cycle->get_count();
			}
		);
		$this->assertCount( 1, $at_count_2 );
	}

	/**
	 * @testdox process_due leaves a pending cycle alone while its crash-recovery lease is live.
	 *
	 * A pending cycle 2 with a still-live `claimed_until` lease is an active claim (a
	 * concurrent worker), so a second run skips it: no charge, no duplicate, and the live
	 * lease is left untouched.
	 */
	public function test_process_due_leaves_a_live_leased_cycle_alone(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Seed a pending cycle 2 whose lease is still in the future (a live claim).
		$repo     = new ContractRepository();
		$previous = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );
		$live_until = gmdate( 'Y-m-d H:i:s', time() + 3600 );
		$claimed    = $this->make_pending_cycle_2( $contract_id, $previous, $live_until );

		$result = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertNull( $result );

		// No renewal order for count 2, and the seeded cycle is untouched (still pending, lease intact).
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		$head = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( $claimed->get_id(), $head->get_id() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::pending() ) );
		$this->assertSame( $live_until, $head->get_claimed_until_gmt() );
	}

	/**
	 * @testdox process_due advances the chain: cycle 2 billed, order linked, schedule moved.
	 */
	public function test_process_due_advances_the_chain_on_a_successful_charge(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );

		// The renewal order bills the new cycle's expected_total (carried forward from
		// cycle 1's recurring amount) and is paid by the approving gateway.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );
		$this->assertSame( '2', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );

		$repo  = new ContractRepository();
		$cycle = $repo->find_current_cycle( $contract_id );

		// Cycle 2 exists, billed, count 2, linked to the renewal order, refs carried forward.
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_sequence_no() );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
		$this->assertSame( $renewal_order->get_id(), $cycle->get_order_id() );
		$this->assertSame( '19.99000000', $cycle->get_expected_total() );
		$this->assertSame( 'engine-tests', $cycle->get_extension_slug() );

		// The contract schedule advanced one cadence; last_payment recorded.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
		$this->assertNotNull( $reloaded->get_last_payment_gmt() );
		$this->assertSame( ContractStatus::ACTIVE, $reloaded->get_status() );
	}

	/**
	 * @testdox process_due builds the renewal order from the contract's own line items and addresses.
	 */
	public function test_process_due_builds_renewal_from_contract_items_and_addresses(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$product = new \WC_Product_Simple();
		$product->set_name( 'Monthly Filters' );
		$product->set_regular_price( '19.99' );
		$product_id = (int) $product->save();

		// Sign up from an order carrying a real recurring line item + addresses, so the contract
		// stores them and the renewal builder has something other than the origin order to read.
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( self::GATEWAY_APPROVING );
		$order->set_total( '39.98' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->set_billing_address(
			array(
				'first_name' => 'Ada',
				'last_name'  => 'Lovelace',
				'country'    => 'US',
				'email'      => 'ada@example.test',
			)
		);
		$order->set_shipping_address(
			array(
				'first_name' => 'Ada',
				'last_name'  => 'Lovelace',
				'country'    => 'US',
			)
		);
		$line = new \WC_Order_Item_Product();
		$line->set_name( 'Monthly Filters' );
		$line->set_product_id( $product_id );
		$line->set_quantity( 2 );
		$line->set_subtotal( '39.98' );
		$line->set_total( '39.98' );
		$order->add_item( $line );
		$order->save();

		$contract    = ( new ContractFactory() )->create_from_order( $order, $this->make_plan_object() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		// Exactly the contract's recurring line item, carried from the contract.
		$items = array_values( $renewal_order->get_items() );
		$this->assertCount( 1, $items );
		$item = $items[0];
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );
		$this->assertSame( $product_id, $item->get_product_id() );
		$this->assertSame( 'Monthly Filters', $item->get_name() );
		$this->assertSame( 2, $item->get_quantity() );

		// Addresses are taken from the contract, not re-read off the origin order.
		$this->assertSame( 'Ada', $renewal_order->get_billing_first_name() );
		$this->assertSame( 'US', $renewal_order->get_billing_country() );
		$this->assertSame( 'US', $renewal_order->get_shipping_country() );
	}

	/**
	 * @testdox process_due on a failed charge marks the cycle failed and leaves the schedule.
	 */
	public function test_process_due_marks_the_cycle_failed_when_the_charge_does_not_settle(): void {
		// Declared recurring, but no handler completes the charge: the order stays unpaid.
		GatewayCapabilities::declare( self::GATEWAY_DECLINING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertFalse( $renewal_order->is_paid() );

		$repo  = new ContractRepository();
		$cycle = $repo->find_current_cycle( $contract_id );

		// Cycle 2 exists and failed, but the renewal order is recorded on it even though the
		// charge did not settle (for dunning + admin visibility).
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::failed() ) );
		$this->assertSame( $renewal_order->get_id(), $cycle->get_order_id() );

		// The contract schedule is untouched (left for dunning), still active.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-02-15 00:00:00', $reloaded->get_next_payment_gmt() );
		$this->assertSame( ContractStatus::ACTIVE, $reloaded->get_status() );

		// Failure bookkeeping: the attempt is recorded, but not a successful payment.
		$this->assertNull( $reloaded->get_last_payment_gmt() );
		$this->assertNotNull( $reloaded->get_last_attempt_gmt() );
	}

	/**
	 * @testdox process_due retry of an unsettled cycle adds no duplicate cycle/order.
	 *
	 * A failed charge leaves cycle 2 `failed` with its order (and the schedule unchanged).
	 * Re-firing targets the same count, so the order-meta pre-check makes it an idempotent
	 * no-op: no second order, no second cycle for count 2. (Forward advancement after a
	 * SUCCESSFUL bill is a distinct renewal, not a retry.)
	 */
	public function test_process_due_retry_of_an_unsettled_cycle_is_idempotent(): void {
		GatewayCapabilities::declare( self::GATEWAY_DECLINING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();
		$first  = $engine->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $first );
		$this->assertFalse( $first->is_paid() );

		// A retry while cycle 2 is unsettled (failed) creates no duplicate.
		$second = $engine->process_due( $contract_id );
		$this->assertNull( $second );

		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		// Exactly one billing cycle for count 2.
		$history    = ( new ContractRepository() )->find_cycle_history( $contract_id );
		$at_count_2 = array_filter(
			$history,
			static function ( Cycle $cycle ): bool {
				return 2 === $cycle->get_count();
			}
		);
		$this->assertCount( 1, $at_count_2 );
	}

	/**
	 * @testdox process_due falls back to the live plan cadence when the cycle carries no snapshot.
	 *
	 * After cycle 1 is billed (terminal) find_current_cycle() hydrates it WITHOUT its snapshot
	 * value objects, so resolve_plan_snapshot() rebuilds the cadence from the live selling plan.
	 * The renewal advances normally on that fallback.
	 */
	public function test_process_due_falls_back_to_live_plan_when_cycle_has_no_snapshot(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Precondition: the billed head cycle carries no in-memory plan snapshot, so the
		// money-path must use the live-plan fallback to know the cadence.
		$repo = new ContractRepository();
		$head = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertNull( $head->get_plan_snapshot() );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		// Advanced one monthly cadence from the live plan (cycle 1 ended 2026-02-15).
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );

		$cycle = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox process_due parks a contract that has no billing chain to advance.
	 *
	 * Checkout always creates cycle 1, so a chainless (lean / manual) contract is a case the
	 * engine does not renew. process_due must skip (return null) rather than silently bill it as
	 * cycle 1 or throw, AND park it - clear its schedule so the oldest-due-first scan does not
	 * revisit it every tick and starve the healthy renewals behind it.
	 */
	public function test_process_due_parks_a_contract_with_no_billing_chain(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		// A lean contract is persisted with no cycle chain (but with a due date).
		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$result = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertNull( $result );

		// Nothing was claimed: no cycle, no renewal order.
		$repo = new ContractRepository();
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
		$this->assertNull( $repo->find_current_cycle( $contract_id ) );

		// Parked: next_payment_gmt is cleared, so find_due no longer returns it.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertNull( $reloaded->get_next_payment_gmt(), 'A chainless contract is parked out of the due set.' );
	}

	/**
	 * @testdox process_due parks a contract whose plan resolves to nothing (no snapshot, deleted live plan).
	 *
	 * When neither the contract's plan snapshot nor the live selling plan resolves, the renewal
	 * cannot bill. process_due skips AND parks (clears the schedule) so the un-renewable contract
	 * leaves the due set instead of holding the front of the scan forever.
	 */
	public function test_process_due_parks_a_contract_whose_plan_is_unresolvable(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Give it a billing chain (so it passes the chainless guard) but no plan snapshot...
		$repo = new ContractRepository();
		$repo->append_cycle(
			Cycle::create(
				array(
					'contract_id'    => $contract_id,
					'sequence_no'    => 1,
					'count'          => 1,
					'status'         => CycleStatus::billed(),
					'starts_at_gmt'  => '2026-01-15 00:00:00',
					'ends_at_gmt'    => '2026-02-15 00:00:00',
					'expected_total' => '19.99',
					'currency'       => 'USD',
				)
			)
		);

		// ...then delete the live plan, so neither the snapshot nor the live plan resolves.
		( new PlanRepository() )->delete( $plan_id );

		$result = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertNull( $result );

		// No cycle 2 claimed, and the contract is parked out of the due set.
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertNull( $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox process_due resumes a stalled renewal whose order was saved but never charged.
	 *
	 * A run that claimed cycle 2 pending and saved its renewal order, then crashed before the
	 * charge, leaves a stalled pending cycle (expired lease) plus an unpaid, un-tokenised order.
	 * The idempotency pre-check would once have skipped forever on the existing order; instead the
	 * run must resume that SAME order - charge it, settle the cycle billed, advance the schedule.
	 */
	public function test_process_due_resumes_a_stalled_renewal_with_an_unpaid_order(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		// A crashed attempt: cycle 2 pending with an expired lease, plus its saved-but-uncharged order.
		$stalled    = $this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$stalled_id = $stalled->get_id();
		$this->assertNotNull( $stalled_id );
		$ghost = $this->make_ghost_renewal_order( $contract_id, 2, false );

		$resumed = ( new RenewalEngine() )->process_due( $contract_id );

		// The SAME order is resumed and charged - no second order for the number.
		$this->assertInstanceOf( WC_Order::class, $resumed );
		$this->assertSame( $ghost->get_id(), $resumed->get_id() );
		$paid_order = wc_get_order( $ghost->get_id() );
		$this->assertInstanceOf( WC_Order::class, $paid_order );
		$this->assertTrue( $paid_order->is_paid() );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		// The stalled cycle is reclaimed (same row) and settled billed, lease cleared.
		$head = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( $stalled_id, $head->get_id() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
		$this->assertNull( $head->get_claimed_until_gmt() );

		// The schedule advances to the reclaimed cycle's own end - one cadence, not skipped.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox process_due settles a stalled renewal whose order was already paid, without re-charging.
	 *
	 * A crash AFTER the gateway was paid but before the cycle settled leaves a paid renewal order
	 * on a stalled pending cycle. Recovery must settle it from that paid state and never fire a
	 * second charge, which could double-charge the customer.
	 */
	public function test_process_due_settles_a_stalled_renewal_with_a_paid_order_without_recharging(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		$this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$ghost = $this->make_ghost_renewal_order( $contract_id, 2, true );

		// Spy on the charge hook (before the approving handler): an already-paid order must not be charged.
		$charge_attempts = 0;
		add_action(
			'woocommerce_subscriptions_engine_scheduled_payment_' . self::GATEWAY_APPROVING,
			static function ( $amount, $order ) use ( &$charge_attempts ): void {
				unset( $amount, $order );
				++$charge_attempts;
			},
			5,
			2
		);

		$resumed = ( new RenewalEngine() )->process_due( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $resumed );
		$this->assertSame( $ghost->get_id(), $resumed->get_id() );
		$this->assertSame( 0, $charge_attempts, 'An already-paid order must not be charged again.' );

		// The cycle is settled billed from the paid state and the schedule advances one cadence.
		$head = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox process_due renews from the contract's own plan snapshot even when the live plan is deleted.
	 *
	 * The contract's frozen snapshot is the cadence source of truth, so a deleted live selling
	 * plan no longer blocks the renewal - the chain advances on the snapshot's terms.
	 */
	public function test_process_due_renews_from_contract_snapshot_when_live_plan_deleted(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Delete the live selling plan; the contract keeps its frozen snapshot.
		( new PlanRepository() )->delete( $contract->get_selling_plan_id() );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		// Cycle 2 was billed from the snapshot's cadence.
		$cycle = ( new ContractRepository() )->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox process_due expires the contract when it hits max cycles.
	 */
	public function test_process_due_expires_contract_at_max_cycles(): void {
		$this->markTestSkipped( 'Max-cycle expiry lands with the dispatcher.' );
	}

	/**
	 * @testdox process_due skips a non-active contract and creates no renewal order.
	 */
	public function test_process_due_skips_non_active_contract(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );
		$contract->set_status( ContractStatus::ON_HOLD );
		( new ContractRepository() )->update( $contract );

		$this->assertNull( ( new RenewalEngine() )->process_due( $contract_id ) );

		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
	}

	/**
	 * @testdox process_due skips a gateway-scheduled contract and creates no renewal order.
	 */
	public function test_process_due_skips_gateway_scheduled_contract(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => $plan_id,
				'origin_order_id'  => $order->get_id(),
				'payment_method'   => self::GATEWAY,
				'start_gmt'        => '2026-01-15 00:00:00',
				'next_payment_gmt' => '2026-02-15 00:00:00',
				'schedule_source'  => Contract::SCHEDULE_SOURCE_GATEWAY,
			)
		);
		$contract_id = ( new ContractRepository() )->insert( $contract );

		// Active, but the gateway owns the schedule: the primitive path bails.
		$this->assertNull( ( new RenewalEngine() )->process_due( $contract_id ) );
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
	}

	/**
	 * @testdox process_due skips an unknown contract.
	 */
	public function test_process_due_skips_unknown_contract(): void {
		$this->assertNull( ( new RenewalEngine() )->process_due( 999999 ) );
	}

	/**
	 * @testdox cancel transitions the contract to cancelled and clears any stale pending row.
	 *
	 * schedule() no longer enqueues a per-contract row, but a stale row may linger from
	 * before the dispatcher superseded the per-contract scheduler. cancel() must still clear
	 * it, so seed one directly and assert it is gone afterwards.
	 */
	public function test_cancel_transitions_and_unschedules(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Seed a stale per-contract row (as the superseded scheduler would have left).
		RenewalScheduler::schedule( $contract_id, new \DateTimeImmutable( '2026-02-15 00:00:00', new \DateTimeZone( 'UTC' ) ) );
		$this->assertTrue( RenewalScheduler::is_scheduled( $contract_id ) );

		$this->assertTrue( ( new Cancellation() )->cancel( $contract ) );

		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( ContractStatus::CANCELLED, $reloaded->get_status() );
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	/**
	 * @testdox cancel closes a mid-charge pending cycle.
	 */
	public function test_cancel_closes_a_pending_cycle(): void {
		$contract    = $this->sign_up_contract( self::GATEWAY );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Append a pending cycle 2 (a charge caught mid-flight).
		$repo     = new ContractRepository();
		$previous = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );
		$pending = Cycle::create(
			array(
				'contract_id'    => $contract_id,
				'sequence_no'    => $previous->get_sequence_no() + 1,
				'count'          => 2,
				'status'         => CycleStatus::pending(),
				'starts_at_gmt'  => '2026-02-15 00:00:00',
				'ends_at_gmt'    => '2026-03-15 00:00:00',
				'expected_total' => '19.99',
				'currency'       => 'USD',
			)
		);
		$repo->append_cycle( $pending, $previous );

		$this->assertTrue( ( new Cancellation() )->cancel( $contract ) );

		// The contract is terminal and the pending cycle is cancelled.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( ContractStatus::CANCELLED, $reloaded->get_status() );

		$head = $repo->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::cancelled() ) );
	}

	/**
	 * @testdox cancel with only settled cycles leaves them untouched.
	 */
	public function test_cancel_leaves_a_settled_cycle_untouched(): void {
		$contract    = $this->sign_up_contract( self::GATEWAY );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertTrue( ( new Cancellation() )->cancel( $contract ) );

		// Cycle 1 stays billed (only a pending head is closed by cancel).
		$head = ( new ContractRepository() )->find_current_cycle( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox A gateway-scheduled contract is not scheduled by the engine.
	 */
	public function test_gateway_scheduled_contract_is_not_scheduled(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => $plan_id,
				'origin_order_id'  => $order->get_id(),
				'payment_method'   => self::GATEWAY,
				'start_gmt'        => '2026-01-15 00:00:00',
				'next_payment_gmt' => '2026-02-15 00:00:00',
				'schedule_source'  => Contract::SCHEDULE_SOURCE_GATEWAY,
			)
		);
		$contract_id = ( new ContractRepository() )->insert( $contract );

		$this->assertFalse( ( new RenewalEngine() )->schedule( $contract ) );
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	/**
	 * Append a pending cycle 2 (no tagged renewal order) with the given crash-recovery
	 * lease, so the create-as-claim collides on it and the reclaim-vs-skip path is exercised.
	 *
	 * @param int    $contract_id   Contract id.
	 * @param Cycle  $previous      The chain's current cycle (cycle 1).
	 * @param string $claimed_until The lease expiry GMT string to stamp on the pending cycle.
	 * @return Cycle The appended pending cycle 2.
	 */
	private function make_pending_cycle_2( int $contract_id, Cycle $previous, string $claimed_until ): Cycle {
		$cycle = Cycle::create(
			array(
				'contract_id'    => $contract_id,
				'sequence_no'    => $previous->get_sequence_no() + 1,
				'count'          => 2,
				'status'         => CycleStatus::pending(),
				'starts_at_gmt'  => '2026-02-15 00:00:00',
				'ends_at_gmt'    => '2026-03-15 00:00:00',
				'expected_total' => '19.99',
				'currency'       => 'USD',
				'claimed_until'  => $claimed_until,
			)
		);
		( new ContractRepository() )->append_cycle( $cycle, $previous );

		return $cycle;
	}

	/**
	 * A renewal order left as a crash would leave it: saved with the renewal-relation meta for
	 * `$count` but never charged (no payment token). Optionally pre-marked paid, to model a crash
	 * AFTER the gateway was paid but before the cycle settled.
	 *
	 * @param int  $contract_id Contract id.
	 * @param int  $count       Chargeable number the order bills.
	 * @param bool $paid        Whether the gateway had already been paid before the crash.
	 * @return WC_Order The ghost renewal order.
	 */
	private function make_ghost_renewal_order( int $contract_id, int $count, bool $paid ): WC_Order {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_total( '19.99' );
		$order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract_id );
		$order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
		$order->update_meta_data( '_subscription_renewal_cycle', (string) $count );

		if ( $paid ) {
			$order->set_status( 'processing' );
			$order->set_date_paid( '2026-02-15 00:00:00' );
		} else {
			$order->set_status( 'pending' );
		}
		$order->save();

		return $order;
	}

	/**
	 * Renewal orders tagged for a contract at a given chargeable number, narrowed
	 * in PHP (store-agnostic, like the engine's own idempotency check).
	 *
	 * @param int $contract_id Contract id.
	 * @param int $count       Chargeable number.
	 * @return array<int, WC_Order>
	 */
	private function renewal_orders_for_cycle( int $contract_id, int $count ): array {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'type'       => 'shop_order',
				'status'     => 'any',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $contract_id,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return array_values(
			array_filter(
				is_array( $orders ) ? $orders : array(),
				static function ( $order ) use ( $count ) {
					return $order instanceof WC_Order
						&& OrderLinkage::RELATION_RENEWAL === $order->get_meta( OrderLinkage::META_RELATION_TYPE )
						&& (string) $count === $order->get_meta( '_subscription_renewal_cycle' );
				}
			)
		);
	}
}
