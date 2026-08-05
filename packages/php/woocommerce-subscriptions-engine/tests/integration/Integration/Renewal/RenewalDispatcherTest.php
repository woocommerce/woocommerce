<?php
/**
 * Integration tests for the batch RenewalDispatcher.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Renewal;

use DateTimeImmutable;
use DateTimeZone;
use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Ownership\ConsumerRegistry;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalDispatcher;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Renewal\RenewalDispatcher
 */
class RenewalDispatcherTest extends EngineIntegrationTestCase {

	/**
	 * A gateway that always approves the scheduled charge inline (the dummy-gateway shape).
	 */
	private const GATEWAY_APPROVING = 'engine_dispatch_gateway_approve';

	/**
	 * The consumer slug registered to open the processing gate in charging tests.
	 */
	private const CONSUMER = 'engine-tests-consumer';

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
		ConsumerRegistry::reset();
	}

	public function tear_down(): void {
		// Clear any recurring scan action a scheduling test enqueued so it cannot leak between tests.
		as_unschedule_all_actions( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP );
		ConsumerRegistry::reset();
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	/**
	 * The scan moment used by the tests (cycle 1 ends 2026-02-15, so a renewal is due then).
	 */
	private function scan_now(): DateTimeImmutable {
		return new DateTimeImmutable( '2026-02-15 00:00:00', new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Persist a monthly plan and return the entity (the ContractFactory needs the plan).
	 */
	private function make_plan_object(): Plan {
		$plan = Plan::create(
			array(
				'name'           => 'Monthly',
				'billing_policy' => new BillingPolicy( 'month', 1, null, null, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => 'engine-tests',
			)
		);
		( new PlanRepository() )->insert( $plan );

		return $plan;
	}

	/**
	 * Sign up a contract via the checkout factory so its billing chain holds cycle 1 (billed),
	 * with its next payment due at the given date.
	 *
	 * @param string $gateway          Gateway id stamped on the order/contract.
	 * @param string $next_payment_gmt The contract's next-payment date.
	 * @return Contract The persisted contract with cycle 1 billed.
	 */
	private function sign_up_contract( string $gateway, string $next_payment_gmt ): Contract {
		$plan = $this->make_plan_object();

		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( $gateway );
		$order->set_total( '19.99' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		$contract = ( new ContractFactory() )->create_from_order( $order, $plan );

		// The factory anchors the first renewal off the plan cadence; pin the schedule date
		// the test reasons about so due/not-due is explicit.
		$contract->set_next_payment_gmt( $next_payment_gmt );
		( new ContractRepository() )->update( $contract );

		return $contract;
	}

	/**
	 * @testdox run skips the whole scan and charges nothing when no consumer is registered.
	 */
	public function test_run_is_gated_when_no_consumer_is_registered(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-02-15 00:00:00' );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// No consumer registered: the gate is closed.
		$this->assertTrue( ConsumerRegistry::is_empty() );

		$processed = ( new RenewalDispatcher() )->run_batch( $this->scan_now() );

		$this->assertSame( 0, $processed, 'A gated run processes no contracts.' );

		// The contract was not advanced: still cycle 1, schedule unmoved.
		$repo = new ContractRepository();
		$this->assertSame( 1, $repo->max_count( $contract_id ) );
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-02-15 00:00:00', $reloaded->get_next_payment_gmt() );
	}

	/**
	 * @testdox run renews a due contract once a consumer is registered: cycle 2 billed, schedule advanced.
	 */
	public function test_run_renews_a_due_contract_when_a_consumer_is_registered(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );
		ConsumerRegistry::register( self::CONSUMER );

		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-02-15 00:00:00' );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$processed = ( new RenewalDispatcher() )->run_batch( $this->scan_now() );
		$this->assertSame( 1, $processed );

		$repo  = new ContractRepository();
		$cycle = $repo->find_chain_head( $contract_id );

		// Cycle 2 was billed via the dummy gateway and the schedule advanced one cadence.
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
		$this->assertSame( ContractStatus::ACTIVE, $reloaded->get_status() );

		// A renewal order was created for cycle 2.
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox run leaves a not-yet-due contract untouched.
	 */
	public function test_run_leaves_a_not_yet_due_contract_untouched(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );
		ConsumerRegistry::register( self::CONSUMER );

		// Due far in the future, after the scan moment.
		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-06-15 00:00:00' );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$processed = ( new RenewalDispatcher() )->run_batch( $this->scan_now() );
		$this->assertSame( 0, $processed );

		// Untouched: still cycle 1, schedule unmoved, no renewal order.
		$repo = new ContractRepository();
		$this->assertSame( 1, $repo->max_count( $contract_id ) );
		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( '2026-06-15 00:00:00', $reloaded->get_next_payment_gmt() );
		$this->assertCount( 0, $this->renewal_orders_for_cycle( $contract_id, 2 ) );
	}

	/**
	 * @testdox run honours the batch limit, renewing at most a batch of due contracts per tick.
	 */
	public function test_run_honours_the_batch_limit(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );
		ConsumerRegistry::register( self::CONSUMER );

		// Three due contracts, but a batch size of two: the third drains on the next tick.
		$first  = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-01-20 00:00:00' );
		$second = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-01-25 00:00:00' );
		$third  = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-02-01 00:00:00' );

		$repo       = new ContractRepository();
		$dispatcher = new RenewalDispatcher( $repo, null );

		$processed = $dispatcher->run_batch( $this->scan_now(), 2 );
		$this->assertSame( 2, $processed, 'A single tick processes at most the batch size.' );

		// The two oldest-due contracts advanced; the third is still at cycle 1.
		$this->assertSame( 2, $repo->max_count( (int) $first->get_id() ) );
		$this->assertSame( 2, $repo->max_count( (int) $second->get_id() ) );
		$this->assertSame( 1, $repo->max_count( (int) $third->get_id() ) );

		// The next tick drains the remaining due contract.
		$processed_next = $dispatcher->run_batch( $this->scan_now(), 2 );
		$this->assertSame( 1, $processed_next );
		$this->assertSame( 2, $repo->max_count( (int) $third->get_id() ) );
	}

	/**
	 * @testdox ensure_scheduled enqueues exactly one recurring scan action and is idempotent.
	 */
	public function test_ensure_scheduled_is_idempotent(): void {
		ConsumerRegistry::register( self::CONSUMER );

		// The engine schedules the recurring action (and sets its re-check option) once at
		// bootstrap, committed before the per-test transaction. Reset both so this test starts
		// from a clean, unscheduled slate; the rollback restores the bootstrap state afterwards.
		as_unschedule_all_actions( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP );
		delete_option( 'woocommerce_subscriptions_engine_dispatch_scheduled_check' );
		$this->assertFalse( as_next_scheduled_action( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP ), 'No recurring action after the reset.' );

		RenewalDispatcher::ensure_scheduled();
		$this->assertNotFalse( as_next_scheduled_action( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP ) );

		// A second call must not enqueue a duplicate.
		RenewalDispatcher::ensure_scheduled();

		$pending = as_get_scheduled_actions(
			array(
				'hook'   => RenewalDispatcher::HOOK,
				'group'  => RenewalDispatcher::GROUP,
				'status' => 'pending',
			),
			'ids'
		);
		$this->assertCount( 1, $pending, 'Exactly one recurring scan action is enqueued.' );
	}

	/**
	 * @testdox ensure_scheduled enqueues nothing while no consumer is registered.
	 *
	 * A store without a consumer extension runs no renewals, so it carries no recurring
	 * scan action either - the scheduling is gated exactly like the run.
	 */
	public function test_ensure_scheduled_skips_without_a_consumer(): void {
		as_unschedule_all_actions( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP );
		delete_option( 'woocommerce_subscriptions_engine_dispatch_scheduled_check' );

		RenewalDispatcher::ensure_scheduled();

		$this->assertFalse(
			as_next_scheduled_action( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP ),
			'No recurring action is enqueued while the consumer registry is empty.'
		);
	}

	/**
	 * @testdox ensure_scheduled removes the recurring scan once every consumer is gone.
	 */
	public function test_ensure_scheduled_removes_the_job_when_consumers_are_gone(): void {
		as_unschedule_all_actions( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP );
		delete_option( 'woocommerce_subscriptions_engine_dispatch_scheduled_check' );

		ConsumerRegistry::register( self::CONSUMER );
		RenewalDispatcher::ensure_scheduled();
		$this->assertNotFalse( as_next_scheduled_action( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP ) );

		// Every consumer deactivates: the next gated boot removes the recurring scan.
		ConsumerRegistry::reset();
		RenewalDispatcher::ensure_scheduled();
		$this->assertFalse(
			as_next_scheduled_action( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP ),
			'The recurring scan does not keep ticking after the last consumer deactivates.'
		);

		// A returning consumer schedules promptly again.
		ConsumerRegistry::register( self::CONSUMER );
		RenewalDispatcher::ensure_scheduled();
		$this->assertNotFalse( as_next_scheduled_action( RenewalDispatcher::HOOK, array(), RenewalDispatcher::GROUP ) );
	}

	/**
	 * @testdox run_batch is a no-op for a non-positive limit.
	 */
	public function test_run_batch_returns_zero_for_a_non_positive_limit(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );
		ConsumerRegistry::register( self::CONSUMER );

		$contract = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-01-20 00:00:00' );

		$dispatcher = new RenewalDispatcher();
		$this->assertSame( 0, $dispatcher->run_batch( $this->scan_now(), 0 ) );
		$this->assertSame( 0, $dispatcher->run_batch( $this->scan_now(), -5 ) );

		// Nothing was renewed by the no-op ticks.
		$this->assertSame( 1, ( new ContractRepository() )->max_count( (int) $contract->get_id() ) );
	}

	/**
	 * @testdox handle_tick routes the Action Scheduler dispatch through run(), advancing a due renewal.
	 */
	public function test_handle_tick_routes_through_run(): void {
		$this->approve_charges_for( self::GATEWAY_APPROVING );
		ConsumerRegistry::register( self::CONSUMER );

		// Due in the past relative to the real dispatch moment, so the tick picks it up.
		$contract    = $this->sign_up_contract( self::GATEWAY_APPROVING, '2026-02-15 00:00:00' );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Drive the Action Scheduler entry point (uses the real "now").
		( new RenewalDispatcher() )->handle_tick();

		// The dispatch reached the money-path: cycle 2 billed.
		$cycle = ( new ContractRepository() )->find_chain_head( $contract_id );
		$this->assertInstanceOf( Cycle::class, $cycle );
		$this->assertSame( 2, $cycle->get_count() );
		$this->assertTrue( $cycle->get_status()->equals( CycleStatus::billed() ) );
	}

	/**
	 * Renewal orders tagged for a contract at a given chargeable number, narrowed in PHP.
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
