<?php
/**
 * Integration tests for RenewalEngine.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Contracts\Cancellation;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalIntent;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalEngine
 */
class RenewalEngineTest extends EngineIntegrationTestCase {

	private const GATEWAY = 'engine_test_gateway';

	/**
	 * A gateway that always approves the scheduled charge, marking the renewal order
	 * paid inline (the dummy-gateway shape: `payment_complete()` within the action).
	 */
	private const GATEWAY_APPROVING = 'engine_test_gateway_approve';

	/**
	 * A gateway that declares `recurring` and reports a hard decline synchronously,
	 * moving the renewal order to `failed` (the failed-charge path).
	 */
	private const GATEWAY_DECLINING = 'engine_test_gateway_decline';

	/**
	 * A gateway that declares `recurring` but registers no handler, so the renewal order
	 * stays `pending` (uncharged) - the async-pending shape: neither paid nor failed, which
	 * the money-path parks in `processing` awaiting a later confirmation.
	 */
	private const GATEWAY_PENDING = 'engine_test_gateway_pending';

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
		ConsumerRegistry::reset();
	}

	public function tear_down(): void {
		ConsumerRegistry::reset();
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	/**
	 * Drive one scheduled scan tick over the batch dispatcher - the production path for
	 * scheduled renewals - and report what it did to `$contract_id`: the head cycle's
	 * renewal order when the tick touched the contract's chain, or null when it did not
	 * (not due, excluded by the scan, or nothing changed).
	 *
	 * @param int                     $contract_id The contract under test.
	 * @param \DateTimeImmutable|null $now         The scan moment; defaults to now (UTC).
	 */
	private function run_scheduled_renewal( int $contract_id, ?\DateTimeImmutable $now = null ): ?WC_Order {
		ConsumerRegistry::register( 'engine-tests' );

		$repo   = new ContractRepository();
		$before = $repo->find_chain_head( $contract_id );

		( new RenewalDispatcher() )->run_batch( $now, 50 );

		$after = $repo->find_chain_head( $contract_id );
		if ( null === $after ) {
			return null;
		}

		$untouched = null !== $before
			&& $before->get_sequence_no() === $after->get_sequence_no()
			&& $before->get_status()->get_value() === $after->get_status()->get_value()
			&& $before->get_order_id() === $after->get_order_id()
			&& $before->get_claimed_until_gmt() === $after->get_claimed_until_gmt();

		if ( $untouched ) {
			return null;
		}

		$order_id = $after->get_order_id();
		$order    = null === $order_id ? false : wc_get_order( $order_id );

		return $order instanceof WC_Order ? $order : null;
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
		$plan = Plan::create(
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
	 * @testdox the scheduled scan creates a renewal order tagged for the next chargeable number.
	 */
	public function test_scheduled_renewal_creates_renewal_order(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );

		// The renewal order is created and tagged with the renewal relation + chargeable number.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertSame( (string) $contract_id, $renewal_order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		$this->assertSame( OrderLinkage::RELATION_RENEWAL, $renewal_order->get_meta( OrderLinkage::META_RELATION_TYPE ) );

		// The chain holds cycle 1 (from signup), so the renewal targets the next number, 2.
		$this->assertSame( '2', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox the scheduled scan does not re-select a failed cycle (no second order).
	 *
	 * A declined charge settles cycle 2 `failed`. A `failed` head is not selectable (dunning is
	 * deferred), so a retried run is a no-op - no second cycle, no second order for the number.
	 */
	public function test_scheduled_renewal_does_not_reselect_a_failed_cycle(): void {
		$this->fail_charges_for( self::GATEWAY_DECLINING );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$first = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $first );

		// Cycle 2 settled failed; a retried run does not re-select it (no duplicate order).
		$retry = $this->run_scheduled_renewal( $contract_id );
		$this->assertNull( $retry );

		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox the scheduled scan skips when the head cycle is a live pending claim.
	 *
	 * A pending cycle for the target count already exists with no lease recorded (a live claim,
	 * not a crashed one). Selection targets that same count, and the money-path's reclaim step
	 * finds a non-reclaimable pending head, so it returns null and creates no duplicate cycle or
	 * order.
	 */
	public function test_scheduled_renewal_skips_when_the_cycle_is_already_claimed(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Pre-claim cycle 2 pending directly (no tagged renewal order), so the order-meta
		// pre-check does not fire and the claim collides on the UNIQUE index instead.
		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
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

		$result = $this->run_scheduled_renewal( $contract_id );
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
	 * @testdox the scheduled scan reclaims a stalled pending cycle whose crash-recovery lease has expired.
	 *
	 * A charge that claimed cycle 2 pending then crashed before settling (no renewal order
	 * tagged) leaves a stuck pending cycle. Once its `claimed_until` lease has expired, a
	 * later run reclaims that same cycle (re-stamping the lease), charges it, and settles it
	 * billed - no duplicate cycle is created.
	 */
	public function test_scheduled_renewal_reclaims_a_stalled_cycle_with_an_expired_lease(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Seed a stalled pending cycle 2 whose lease expired a minute before the run (no tagged order).
		$now      = new \DateTimeImmutable( '2026-02-15 00:05:00', new \DateTimeZone( 'UTC' ) );
		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );
		$stalled    = $this->make_pending_cycle_2( $contract_id, $previous, $now->modify( '-60 seconds' )->format( 'Y-m-d H:i:s' ) );
		$stalled_id = $stalled->get_id();
		$this->assertNotNull( $stalled_id );

		$renewal_order = $this->run_scheduled_renewal( $contract_id, $now );

		// The reclaimed cycle is billed and resolved through a renewal order.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );
		$this->assertSame( '2', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );

		$head = $repo->find_chain_head( $contract_id );
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
	 * @testdox the scheduled scan leaves a pending cycle alone while its crash-recovery lease is live.
	 *
	 * A pending cycle 2 with a still-live `claimed_until` lease is an active claim (a
	 * concurrent worker), so a second run skips it: no charge, no duplicate, and the live
	 * lease is left untouched.
	 */
	public function test_scheduled_renewal_leaves_a_live_leased_cycle_alone(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Seed a pending cycle 2 whose lease is still an hour in the future (a live claim).
		$now      = new \DateTimeImmutable( '2026-02-15 00:05:00', new \DateTimeZone( 'UTC' ) );
		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );
		$live_until = $now->modify( '+1 hour' )->format( 'Y-m-d H:i:s' );
		$claimed    = $this->make_pending_cycle_2( $contract_id, $previous, $live_until );

		$result = $this->run_scheduled_renewal( $contract_id, $now );
		$this->assertNull( $result );

		// No renewal order for count 2, and the seeded cycle is untouched (still pending, lease intact).
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( $claimed->get_id(), $head->get_id() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::pending() ) );
		$this->assertSame( $live_until, $head->get_claimed_until_gmt() );
	}

	/**
	 * @testdox the scheduled scan advances the chain: cycle 2 billed, order linked, schedule moved.
	 */
	public function test_scheduled_renewal_advances_the_chain_on_a_successful_charge(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );

		// The renewal order bills the new cycle's expected_total (carried forward from
		// cycle 1's recurring amount) and is paid by the approving gateway.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );
		$this->assertSame( '2', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );

		$repo  = new ContractRepository();
		$cycle = $repo->find_chain_head( $contract_id );

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
	 * @testdox the scheduled scan builds the renewal order from the contract's own line items and addresses.
	 */
	public function test_scheduled_renewal_builds_renewal_from_contract_items_and_addresses(): void {
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

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
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
	 * @testdox the scheduled scan on a declined charge marks the cycle failed and leaves the schedule.
	 */
	public function test_scheduled_renewal_marks_the_cycle_failed_when_the_gateway_declines(): void {
		// The gateway reports a hard decline synchronously: the renewal order moves to failed.
		$this->fail_charges_for( self::GATEWAY_DECLINING );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertFalse( $renewal_order->is_paid() );

		$repo  = new ContractRepository();
		$cycle = $repo->find_chain_head( $contract_id );

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
	 * @testdox the scheduled scan retry of a failed cycle adds no duplicate cycle/order.
	 *
	 * A declined charge leaves cycle 2 `failed` with its order (and the schedule unchanged).
	 * A `failed` head is not selectable, so re-firing is an idempotent no-op: no second order,
	 * no second cycle for count 2. (Forward advancement after a SUCCESSFUL bill is a distinct
	 * renewal, not a retry.)
	 */
	public function test_scheduled_renewal_retry_of_a_failed_cycle_is_idempotent(): void {
		$this->fail_charges_for( self::GATEWAY_DECLINING );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$first = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $first );
		$this->assertFalse( $first->is_paid() );

		// A retry while cycle 2 is failed creates no duplicate.
		$second = $this->run_scheduled_renewal( $contract_id );
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
	 * @testdox the scheduled scan falls back to the live plan cadence when the cycle carries no snapshot.
	 *
	 * After cycle 1 is billed (terminal) find_chain_head() hydrates it WITHOUT its snapshot
	 * value objects, so resolve_plan_snapshot() rebuilds the cadence from the live selling plan.
	 * The renewal advances normally on that fallback.
	 */
	public function test_scheduled_renewal_falls_back_to_live_plan_when_cycle_has_no_snapshot(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Precondition: the billed head cycle carries no in-memory plan snapshot, so the
		// money-path must use the live-plan fallback to know the cadence.
		$repo = new ContractRepository();
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertNull( $head->get_plan_snapshot() );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		// Advanced one monthly cadence from the live plan (cycle 1 ended 2026-02-15).
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );

		$cycle = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox the scheduled scan never surfaces a contract with no billing chain.
	 *
	 * Checkout always creates cycle 1, so a chainless (lean / manual) contract is a case the
	 * engine does not renew. The cycle-aware scan excludes it in SQL (no head cycle to join),
	 * so a tick claims nothing and bills nothing for it and its schedule is left untouched -
	 * the exclusion, not a park, is what keeps it out of the batch budget.
	 */
	public function test_scheduled_renewal_ignores_a_contract_with_no_billing_chain(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		// A lean contract is persisted with no cycle chain (but with a due date).
		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$result = $this->run_scheduled_renewal( $contract_id );
		$this->assertNull( $result );

		// Nothing was claimed: no cycle, no renewal order.
		$repo = new ContractRepository();
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
		$this->assertNull( $repo->find_chain_head( $contract_id ) );

		// Not parked - never selected: the schedule stays as it was.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-02-15 00:00:00', $reloaded->get_next_payment_gmt(), 'The scan exclusion leaves the schedule untouched.' );
	}

	/**
	 * @testdox the scheduled scan parks a contract whose plan resolves to nothing (no snapshot, deleted live plan).
	 *
	 * When neither the contract's plan snapshot nor the live selling plan resolves, the renewal
	 * cannot bill. The dispatcher catches the pre-flight impossibility and parks the contract
	 * (clears the schedule) so it leaves the due set instead of holding the front of the scan forever.
	 */
	public function test_scheduled_renewal_parks_a_contract_whose_plan_is_unresolvable(): void {
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

		$result = $this->run_scheduled_renewal( $contract_id );
		$this->assertNull( $result );

		// No cycle 2 claimed, and the contract is parked out of the due set.
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertNull( $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox the scheduled scan resumes a stalled renewal whose order was saved but never charged.
	 *
	 * A run that claimed cycle 2 pending and saved its renewal order, then crashed before the
	 * charge, leaves a stalled pending cycle (expired lease) plus an unpaid, un-tokenised order.
	 * The idempotency pre-check would once have skipped forever on the existing order; instead the
	 * run must resume that SAME order - charge it, settle the cycle billed, advance the schedule.
	 */
	public function test_scheduled_renewal_resumes_a_stalled_renewal_with_an_unpaid_order(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		// A crashed attempt: cycle 2 pending with an expired lease, plus its saved-but-uncharged order.
		$stalled    = $this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$stalled_id = $stalled->get_id();
		$this->assertNotNull( $stalled_id );
		$ghost = $this->make_ghost_renewal_order( $contract_id, 2, false );

		$resumed = $this->run_scheduled_renewal( $contract_id );

		// The SAME order is resumed and charged - no second order for the number.
		$this->assertInstanceOf( WC_Order::class, $resumed );
		$this->assertSame( $ghost->get_id(), $resumed->get_id() );
		$paid_order = wc_get_order( $ghost->get_id() );
		$this->assertInstanceOf( WC_Order::class, $paid_order );
		$this->assertTrue( $paid_order->is_paid() );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		// The stalled cycle is reclaimed (same row) and settled billed, lease cleared.
		$head = $repo->find_chain_head( $contract_id );
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
	 * @testdox the scheduled scan settles a stalled renewal whose order was already paid, without re-charging.
	 *
	 * A crash AFTER the gateway was paid but before the cycle settled leaves a paid renewal order
	 * on a stalled pending cycle. Recovery must settle it from that paid state and never fire a
	 * second charge, which could double-charge the customer.
	 */
	public function test_scheduled_renewal_settles_a_stalled_renewal_with_a_paid_order_without_recharging(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
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

		$resumed = $this->run_scheduled_renewal( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $resumed );
		$this->assertSame( $ghost->get_id(), $resumed->get_id() );
		$this->assertSame( 0, $charge_attempts, 'An already-paid order must not be charged again.' );

		// The cycle is settled billed from the paid state and the schedule advances one cadence.
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox the scheduled scan renews from the contract's own plan snapshot even when the live plan is deleted.
	 *
	 * The contract's frozen snapshot is the cadence source of truth, so a deleted live selling
	 * plan no longer blocks the renewal - the chain advances on the snapshot's terms.
	 */
	public function test_scheduled_renewal_renews_from_contract_snapshot_when_live_plan_deleted(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Delete the live selling plan; the contract keeps its frozen snapshot.
		( new PlanRepository() )->delete( $contract->get_selling_plan_id() );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		// Cycle 2 was billed from the snapshot's cadence.
		$cycle = ( new ContractRepository() )->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox the scheduled scan expires the contract when it hits max cycles.
	 */
	public function test_scheduled_renewal_expires_contract_at_max_cycles(): void {
		$this->markTestSkipped( 'Max-cycle expiry lands with the dispatcher.' );
	}

	/**
	 * @testdox the scheduled scan skips a non-active contract and creates no renewal order.
	 */
	public function test_scheduled_renewal_skips_non_active_contract(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );
		$contract->set_status( ContractStatus::ON_HOLD );
		( new ContractRepository() )->update( $contract );

		$this->assertNull( $this->run_scheduled_renewal( $contract_id ) );

		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
	}

	/**
	 * @testdox the scheduled scan skips a gateway-scheduled contract and creates no renewal order.
	 */
	public function test_scheduled_renewal_skips_gateway_scheduled_contract(): void {
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
		$this->assertNull( $this->run_scheduled_renewal( $contract_id ) );
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
	}

	/**
	 * @testdox the scheduled scan skips an unknown contract.
	 */
	public function test_scheduled_renewal_skips_unknown_contract(): void {
		$this->assertNull( $this->run_scheduled_renewal( 999999 ) );
	}

	/**
	 * @testdox cancel transitions the contract to cancelled.
	 *
	 * The due scan only selects active contracts, so cancellation needs no schedule
	 * cleanup - the status transition alone removes the contract from renewal.
	 */
	public function test_cancel_transitions_the_contract(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertTrue( ( new Cancellation() )->cancel( $contract ) );

		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( ContractStatus::CANCELLED, $reloaded->get_status() );
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
		$previous = $repo->find_chain_head( $contract_id );
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

		$head = $repo->find_chain_head( $contract_id );
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
		$head = ( new ContractRepository() )->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
	}


	/**
	 * @testdox the scheduled scan leaves the cycle processing when the charge is neither paid nor failed.
	 *
	 * A gateway that accepts the charge but confirms later (async) leaves the renewal order
	 * neither paid nor failed. The money-path settles the cycle `processing` - awaiting the
	 * gateway - without advancing the schedule or treating it as a failure. A `processing` head
	 * is not re-selected, so a later run is a no-op until the order settles.
	 */
	public function test_scheduled_renewal_leaves_the_cycle_processing_when_the_charge_is_pending(): void {
		// Declared recurring, but no handler settles the order: it stays pending (async-accepted shape).
		GatewayCapabilities::declare( self::GATEWAY_PENDING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_PENDING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine        = new RenewalEngine();
		$renewal_order = $this->run_scheduled_renewal( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertFalse( $renewal_order->is_paid() );

		$repo  = new ContractRepository();
		$cycle = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::processing() ) );
		// A processing cycle carries no crash-recovery lease.
		$this->assertNull( $cycle->get_claimed_until_gmt() );

		// The schedule is not advanced (the charge has not confirmed).
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-02-15 00:00:00', $reloaded->get_next_payment_gmt() );

		// A processing head is not re-selected: a later run is a no-op, no duplicate order.
		$this->assertNull( $this->run_scheduled_renewal( $contract_id ) );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox complete_from_order bills a processing cycle once its order is paid, and is idempotent.
	 *
	 * The async confirmation path: a cycle left `processing` is settled `billed` (and the schedule
	 * advanced) when its order becomes paid and completion is driven from that order - the same
	 * routine the order-status listener runs. Re-driving it is a no-op.
	 */
	public function test_complete_from_order_bills_a_processing_cycle_when_paid(): void {
		GatewayCapabilities::declare( self::GATEWAY_PENDING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_PENDING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();
		$order  = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $order );

		$repo            = new ContractRepository();
		$processing_head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $processing_head );
		$this->assertTrue( $processing_head->get_status()->equals( CycleStatus::processing() ) );

		// The async confirmation arrives: the order is paid. Drive completion as the listener would.
		$order->payment_complete();
		$paid_order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $paid_order );
		$engine->complete_from_order( $paid_order );

		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );

		// Idempotent: re-driving completion on the already-billed cycle changes nothing.
		$engine->complete_from_order( $paid_order );
		$billed_head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $billed_head );
		$this->assertTrue( $billed_head->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox the scheduled scan does not charge the next cycle ahead of its period (the selection due-guard).
	 *
	 * After cycle 2 is billed its period runs to 2026-03-15. A scheduled scan tick at
	 * a moment before that end must be a no-op - no cycle 3, no order - because selection owns the
	 * due-guard, anchored on the head cycle's immutable end. process() itself bills whatever cycle
	 * it is handed, so the "is it due" decision lives in the selector, not the money-path.
	 */
	public function test_scheduled_renewal_does_not_charge_ahead_of_the_period(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Bill cycle 2 (its period ends 2026-03-15).
		$order2 = $this->run_scheduled_renewal( $contract_id, new \DateTimeImmutable( '2026-02-15 00:00:00', new \DateTimeZone( 'UTC' ) ) );
		$this->assertInstanceOf( WC_Order::class, $order2 );

		$repo = new ContractRepository();
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );

		// The scheduled path before cycle 2's period ends: selection skips, so nothing advances.
		$ahead = $this->run_scheduled_renewal( $contract_id, new \DateTimeImmutable( '2026-03-01 00:00:00', new \DateTimeZone( 'UTC' ) ) );
		$this->assertNull( $ahead );

		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 3 ) );
		$still = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $still );
		$this->assertSame( 2, $still->get_count() );
	}

	/**
	 * @testdox process bills the cycle it is handed, even before that cycle's scheduled due date.
	 *
	 * process() owns no due policy: handed the next cycle directly (as a future admin or early
	 * renewal trigger would), it bills it regardless of the scheduled due-guard - which lives only
	 * in selection. A scheduled run at this same moment would skip; forcing the intent does not.
	 */
	public function test_process_bills_the_handed_cycle_before_its_scheduled_due_date(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Cycle 1's period ends 2026-02-15; force cycle 2 well before then.
		$order = ( new RenewalEngine() )->process(
			new RenewalIntent( $contract_id, 2 ),
			new \DateTimeImmutable( '2026-01-20 00:00:00', new \DateTimeZone( 'UTC' ) )
		);

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertTrue( $order->is_paid() );

		$head = ( new ContractRepository() )->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox renew_now forces the next cycle before its scheduled due date, keeping the schedule.
	 *
	 * The scheduled path would defer (the head's period has not ended), but an admin renewal
	 * bypasses the due-guard. The forced cycle continues from the previous period's end, so the
	 * schedule is preserved (a prepay), not reset to the moment of the manual renewal.
	 */
	public function test_renew_now_forces_the_next_cycle_before_its_due_date(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// A moment well before cycle 1's period end (2026-02-15): not yet due on the schedule.
		$now = new \DateTimeImmutable( '2026-02-01 00:00:00', new \DateTimeZone( 'UTC' ) );

		$renewal_order = ( new RenewalEngine() )->renew_now( $contract_id, $now );

		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );

		$repo  = new ContractRepository();
		$cycle = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );

		// Schedule preserved: cycle 2 runs from cycle 1's end (2026-02-15), so next payment is one
		// cadence on from that (2026-03-15) - not one cadence from the manual-renewal moment.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox renew_now retries a failed head in place and bills it on success.
	 *
	 * A failed renewal (the gateway declined) is not re-selected by the scheduled path, but an
	 * admin retry re-attempts the SAME cycle: it flips failed -> pending, reuses the failed order,
	 * and settles billed once the charge succeeds - without advancing to a new cycle.
	 */
	public function test_renew_now_retries_a_failed_head_in_place(): void {
		$this->fail_charges_for( self::GATEWAY_DECLINING );

		$contract    = $this->sign_up_contract( self::GATEWAY_DECLINING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();

		// Scheduled run advances to cycle 2, which the gateway declines: a failed head.
		$this->run_scheduled_renewal( $contract_id );

		$repo   = new ContractRepository();
		$failed = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $failed );
		$this->assertSame( 2, $failed->get_count() );
		$this->assertTrue( $failed->get_status()->equals( CycleStatus::failed() ) );

		// The customer fixes their payment method; the same gateway now approves the retry.
		remove_all_actions( 'woocommerce_subscriptions_engine_scheduled_payment_' . self::GATEWAY_DECLINING );
		add_action(
			'woocommerce_subscriptions_engine_scheduled_payment_' . self::GATEWAY_DECLINING,
			static function ( $amount, $renewal_order ): void {
				unset( $amount );
				if ( $renewal_order instanceof WC_Order && $renewal_order->needs_payment() ) {
					$renewal_order->payment_complete();
				}
			},
			10,
			2
		);

		$retry = $engine->renew_now( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $retry );
		$this->assertTrue( $retry->is_paid() );

		// The SAME cycle 2 is now billed - retried in place, not advanced to a cycle 3.
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );

		// The failed order was reused, not duplicated.
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox renew_now returns null for a contract with no billing chain, and does not park it.
	 *
	 * Unlike the scheduled path, a manual renewal never clears the schedule when it cannot proceed.
	 */
	public function test_renew_now_returns_null_for_a_chainless_contract_without_parking(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertNull( ( new RenewalEngine() )->renew_now( $contract_id ) );

		// The schedule is untouched (not parked): next_payment remains as set.
		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-02-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox the scheduled scan resumes a draft order already linked on the cycle: promotes, charges, settles.
	 *
	 * A crash between linking the draft onto the cycle and promoting it to pending leaves a
	 * linked `checkout-draft`. The resume path must resolve it directly through the cycle's
	 * order reference, promote it, charge it, and settle - one order, no duplicate.
	 */
	public function test_scheduled_renewal_resumes_a_linked_draft_order(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		$cycle = $this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$draft = $this->make_ghost_renewal_order( $contract_id, 2, false, 'checkout-draft' );
		$cycle->set_order_id( $draft->get_id() );
		$repo->update_cycle( $cycle );

		$resumed = $this->run_scheduled_renewal( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $resumed );
		$this->assertSame( $draft->get_id(), $resumed->get_id(), 'The linked draft is reused, not duplicated.' );
		$this->assertTrue( $resumed->is_paid() );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * @testdox the scheduled scan finds an unlinked draft via the meta fallback and heals the cycle link.
	 *
	 * A crash between saving the draft and linking it onto the cycle leaves an unlinked
	 * `checkout-draft` carrying only the renewal meta. The fallback search must still surface
	 * it (drafts are excluded from a plain 'any'-status query), heal the link, and resume.
	 */
	public function test_scheduled_renewal_reuses_an_unlinked_draft_order(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		$this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$draft = $this->make_ghost_renewal_order( $contract_id, 2, false, 'checkout-draft' );

		$resumed = $this->run_scheduled_renewal( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $resumed );
		$this->assertSame( $draft->get_id(), $resumed->get_id(), 'The abandoned draft is reused, not duplicated.' );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
		$this->assertSame( $draft->get_id(), $head->get_order_id(), 'The cycle link is healed from the meta match.' );
	}

	/**
	 * @testdox a fresh claim builds its own order and never adopts a pre-tagged stray.
	 *
	 * The order lookup runs only for a reclaimed cycle: a cycle appended by this run cannot
	 * have an order yet, so the every-renewal path skips the meta scan - and an anomalous
	 * stray order carrying matching renewal meta (no cycle was ever claimed for it) is not
	 * adopted or charged.
	 */
	public function test_a_fresh_claim_builds_its_own_order_and_ignores_a_pre_tagged_stray(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// A stray order tagged for the not-yet-claimed cycle 2 (a data anomaly: order work
		// always follows the claim, so nothing legitimate produces this).
		$stray = $this->make_ghost_renewal_order( $contract_id, 2, false );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertNotSame( $stray->get_id(), $renewal_order->get_id(), 'The fresh claim does not adopt the stray.' );
		$this->assertTrue( $renewal_order->is_paid() );

		$head = ( new ContractRepository() )->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 2, $head->get_count() );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
		$this->assertSame( $renewal_order->get_id(), $head->get_order_id(), 'The cycle is linked to its own order, not the stray.' );

		$stray_after = wc_get_order( $stray->get_id() );
		$this->assertInstanceOf( WC_Order::class, $stray_after );
		$this->assertFalse( $stray_after->is_paid(), 'The stray order is never charged.' );
	}

	/**
	 * @testdox complete_from_order settles once: repeats move no dates and re-fire no actions.
	 */
	public function test_complete_from_order_settles_the_schedule_once_when_invoked_repeatedly(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$billed_fired = 0;
		add_action(
			RenewalEngine::RENEWAL_BILLED_ACTION,
			static function () use ( &$billed_fired ): void {
				++$billed_fired;
			},
			10,
			0
		);

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine        = new RenewalEngine();
		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$fired_after_process = $billed_fired;

		$repo  = new ContractRepository();
		$after = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $after );
		$next_payment = $after->get_next_payment_gmt();
		$last_payment = $after->get_last_payment_gmt();

		// A late duplicate completion (a repeated webhook, a concurrent worker's retry): no-op.
		$fresh = wc_get_order( $renewal_order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $fresh );
		$engine->complete_from_order( $fresh );
		$fired_after_repeat = $billed_fired;

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( $next_payment, $reloaded->get_next_payment_gmt(), 'The schedule does not move again.' );
		$this->assertSame( $last_payment, $reloaded->get_last_payment_gmt(), 'The payment record does not move again.' );
		$this->assertSame( 1, $fired_after_process, 'The billed action fires exactly once for the renewal.' );
		$this->assertSame( 1, $fired_after_repeat, 'The billed action does not re-fire.' );
	}

	/**
	 * @testdox complete_from_order ignores an order the head is not linked to.
	 *
	 * The cycle's own order reference is the settlement authority: a rogue paid order carrying
	 * matching renewal meta must not settle a head that is linked to a different order.
	 */
	public function test_complete_from_order_ignores_an_order_the_head_is_not_linked_to(): void {
		GatewayCapabilities::declare( self::GATEWAY_APPROVING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		// The head is claimed with a live lease and linked to (unpaid) order A.
		$cycle   = $this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() + 3600 ) );
		$order_a = $this->make_ghost_renewal_order( $contract_id, 2, false );
		$cycle->set_order_id( $order_a->get_id() );
		$repo->update_cycle( $cycle );

		// A rogue paid order B carries the same renewal meta but is not the head's order.
		$order_b = $this->make_ghost_renewal_order( $contract_id, 2, true );
		( new RenewalEngine() )->complete_from_order( $order_b );

		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::pending() ), 'The linked head is not settled by a rogue order.' );
		$this->assertSame( $order_a->get_id(), $head->get_order_id() );
	}

	/**
	 * @testdox complete_from_order leaves the cycle in flight when the order cannot be re-read.
	 *
	 * Settlement never trusts the stale in-memory order: when the fresh read fails (the order
	 * was deleted mid-flight) the cycle stays as it was, for a later run to resolve.
	 */
	public function test_complete_from_order_leaves_the_cycle_in_flight_when_the_order_is_gone(): void {
		GatewayCapabilities::declare( self::GATEWAY_APPROVING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$repo     = new ContractRepository();
		$previous = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $previous );

		$this->make_pending_cycle_2( $contract_id, $previous, gmdate( 'Y-m-d H:i:s', time() + 3600 ) );
		$ghost = $this->make_ghost_renewal_order( $contract_id, 2, true );
		$ghost->delete( true );

		// The in-memory instance still carries the meta, but the stored order is gone.
		( new RenewalEngine() )->complete_from_order( $ghost );

		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::pending() ), 'No settlement happens from a stale order copy.' );
	}

	/**
	 * @testdox the scheduled scan parks a contract whose gateway cannot charge renewals.
	 *
	 * A gateway that does not declare the `recurring` capability makes every attempt futile
	 * until the payment method is updated: the charge hook would fire into nothing and the
	 * cycle would stall `processing`. The pre-flight refuses before any claim, and the
	 * dispatcher parks the contract out of the due set; fixing the payment method plus a
	 * manual renewal (or a repair) re-arms it.
	 */
	public function test_scheduled_renewal_parks_a_contract_whose_gateway_cannot_charge(): void {
		// Signed up against a gateway that never declares the `recurring` capability.
		$contract    = $this->sign_up_contract( self::GATEWAY );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertNull( $this->run_scheduled_renewal( $contract_id ) );

		// Nothing was claimed: the head is still cycle 1 and no renewal order exists.
		$repo = new ContractRepository();
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 1, $head->get_count() );
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		// Parked: the contract left the due set until the payment method is repaired.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertNull( $reloaded->get_next_payment_gmt(), 'An unchargeable contract is parked out of the due set.' );
	}

	/**
	 * @testdox renew_now refuses a contract whose gateway cannot charge, without parking it.
	 */
	public function test_renew_now_refuses_an_unchargeable_gateway_without_parking(): void {
		$contract    = $this->sign_up_contract( self::GATEWAY );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertNull( ( new RenewalEngine() )->renew_now( $contract_id ) );

		// No claim, no order, and the schedule is untouched (a manual action never parks).
		$repo = new ContractRepository();
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertSame( 1, $head->get_count() );
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-02-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox a manual paid-status change settles a processing cycle (cash-on-delivery shape).
	 *
	 * A gateway-less settlement never calls payment_complete(): an admin marks the renewal
	 * order processing/completed by hand. The paid-status transition listeners must complete
	 * the cycle from that, or manual methods stay locked in `processing` forever.
	 */
	public function test_manual_paid_status_change_settles_the_cycle(): void {
		GatewayCapabilities::declare( self::GATEWAY_PENDING, array( GatewayCapabilities::RECURRING ) );

		$contract    = $this->sign_up_contract( self::GATEWAY_PENDING );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// No charge handler: the charge stays unconfirmed and the cycle parks `processing`.
		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		$repo = new ContractRepository();
		$head = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::processing() ) );

		// An admin marks the order paid by hand; the status-transition listener settles.
		$renewal_order->update_status( 'processing' );

		$settled = $repo->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $settled );
		$this->assertTrue( $settled->get_status()->equals( CycleStatus::billed() ), 'The manual paid transition bills the cycle.' );

		// The schedule advanced to the billed cycle's own period end.
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox the scheduled scan materializes an all-cycles bogo as bonus line quantity, money-neutral.
	 */
	public function test_scheduled_renewal_materializes_bogo_bonus_quantity(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract_with_line_item(
			self::GATEWAY_APPROVING,
			PricingPolicy::from_array(
				array(
					'policies' => array(
						array( 'type' => 'bogo' ),
					),
				)
			)
		);
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );

		// The line carries paid + bonus units: 2 paid earn 2 free.
		$items = array_values( $renewal_order->get_items() );
		$this->assertCount( 1, $items );
		$item = $items[0];
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );
		$this->assertSame( 4, $item->get_quantity() );

		// Money-neutral: the line amounts still price the paid units only, and the
		// order total is exactly the cycle's expected_total (the price authority).
		$this->assertSame( 39.98, (float) $item->get_subtotal() );
		$this->assertSame( 39.98, (float) $item->get_total() );
		$this->assertSame( 39.98, (float) $renewal_order->get_total() );

		$head = ( new ContractRepository() )->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $head );
		$this->assertTrue( $head->get_status()->equals( CycleStatus::billed() ) );
		$this->assertSame( '39.98000000', $head->get_expected_total() );
	}

	/**
	 * @testdox a first-cycle-only bogo grants no bonus on the cycle-2 renewal order.
	 *
	 * `duration_cycles: 1` scopes the bogo benefit to cycle 1 (the origin order, whose
	 * materialization is the consumer's checkout, not the engine's). The engine-built
	 * cycle-2 renewal is outside the window: paid quantity only.
	 */
	public function test_scheduled_renewal_grants_no_bonus_when_the_bogo_window_has_ended(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract_with_line_item(
			self::GATEWAY_APPROVING,
			PricingPolicy::from_array(
				array(
					'policies' => array(
						array(
							'type'            => 'bogo',
							'duration_cycles' => 1,
						),
					),
				)
			)
		);
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		$items = array_values( $renewal_order->get_items() );
		$this->assertCount( 1, $items );
		$item = $items[0];
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );
		$this->assertSame( 2, $item->get_quantity(), 'No bonus outside the bogo window.' );
		$this->assertSame( 39.98, (float) $renewal_order->get_total() );
	}

	/**
	 * @testdox the bogo bonus materializes from the contract's frozen snapshot even when the live plan is deleted.
	 */
	public function test_scheduled_renewal_materializes_bogo_from_the_snapshot_when_the_live_plan_is_deleted(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract_with_line_item(
			self::GATEWAY_APPROVING,
			PricingPolicy::from_array(
				array(
					'policies' => array(
						array( 'type' => 'bogo' ),
					),
				)
			)
		);
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// The live plan goes away; the contract's frozen snapshot carries the terms.
		( new PlanRepository() )->delete( $contract->get_selling_plan_id() );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		$items = array_values( $renewal_order->get_items() );
		$this->assertCount( 1, $items );
		$item = $items[0];
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );
		$this->assertSame( 4, $item->get_quantity(), 'The snapshot terms grant the bonus without the live plan.' );
	}

	/**
	 * @testdox a discount added to the live plan after signup does not change a contract's frozen terms.
	 *
	 * The snapshot records an explicit "no pricing policy" at signup; a bogo entry added
	 * to the live plan later must not leak onto the contract's renewals (and the base
	 * quantity regression holds: no pricing policy means quantities are untouched).
	 */
	public function test_scheduled_renewal_honors_the_snapshots_explicit_lack_of_a_pricing_policy(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract_with_line_item( self::GATEWAY_APPROVING, null );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// The merchant later adds a bogo discount to the live plan.
		$plans = new PlanRepository();
		$plan  = $plans->find( $contract->get_selling_plan_id() );
		$this->assertInstanceOf( Plan::class, $plan );
		$plan->set_pricing_policy(
			PricingPolicy::from_array(
				array(
					'policies' => array(
						array( 'type' => 'bogo' ),
					),
				)
			)
		);
		$this->assertTrue( $plans->update( $plan ) );

		$renewal_order = $this->run_scheduled_renewal( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );

		$items = array_values( $renewal_order->get_items() );
		$this->assertCount( 1, $items );
		$item = $items[0];
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $item );
		$this->assertSame( 2, $item->get_quantity(), 'Frozen terms: the later live-plan discount does not apply.' );
		$this->assertSame( 39.98, (float) $renewal_order->get_total() );
	}

	/**
	 * Sign up a contract from an order carrying a real product line (quantity 2, USD
	 * 39.98) on a monthly plan with the given pricing policy - the shape the BOGO
	 * materialization tests read renewal line quantities from.
	 *
	 * @param string             $gateway        Gateway id stamped on the order/contract.
	 * @param PricingPolicy|null $pricing_policy The plan's pricing policy, or null for none.
	 * @return Contract The persisted contract with cycle 1 billed.
	 */
	private function sign_up_contract_with_line_item( string $gateway, ?PricingPolicy $pricing_policy ): Contract {
		$plan = Plan::create(
			array(
				'name'           => 'Monthly',
				'billing_policy' => new BillingPolicy( 'month', 1, null, null, null ),
				'pricing_policy' => $pricing_policy,
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => 'engine-tests',
			)
		);
		( new PlanRepository() )->insert( $plan );

		$product = new \WC_Product_Simple();
		$product->set_name( 'Monthly Filters' );
		$product->set_regular_price( '19.99' );
		$product_id = (int) $product->save();

		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( $gateway );
		$order->set_total( '39.98' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$line = new \WC_Order_Item_Product();
		$line->set_name( 'Monthly Filters' );
		$line->set_product_id( $product_id );
		$line->set_quantity( 2 );
		$line->set_subtotal( '39.98' );
		$line->set_total( '39.98' );
		$order->add_item( $line );
		$order->save();

		return ( new ContractFactory() )->create_from_order( $order, $plan );
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
	 * AFTER the gateway was paid but before the cycle settled, or given an explicit status - a
	 * `checkout-draft` models a crash during the draft-first creation window.
	 *
	 * @param int         $contract_id Contract id.
	 * @param int         $count       Chargeable number the order bills.
	 * @param bool        $paid        Whether the gateway had already been paid before the crash.
	 * @param string|null $status      Explicit order status, overriding the paid/pending default.
	 * @return WC_Order The ghost renewal order.
	 */
	private function make_ghost_renewal_order( int $contract_id, int $count, bool $paid, ?string $status = null ): WC_Order {
		// A ghost models a crash where settlement never ran: silence the order-settled
		// listeners while seeding it (a paid status transition would settle the cycle
		// mid-setup), then restore them on a fresh engine.
		remove_all_actions( 'woocommerce_payment_complete' );
		remove_all_actions( 'woocommerce_order_status_failed' );
		foreach ( wc_get_is_paid_statuses() as $paid_status ) {
			remove_all_actions( 'woocommerce_order_status_' . $paid_status );
		}

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
		if ( null !== $status ) {
			$order->set_status( $status );
		}
		$order->save();

		( new RenewalEngine() )->register_hooks();

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
