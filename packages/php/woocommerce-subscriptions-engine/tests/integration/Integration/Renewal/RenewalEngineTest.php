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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
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

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
	}

	public function tear_down(): void {
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	private function make_plan( ?int $max_cycles = null ): int {
		$group_id = ( new PlanGroupRepository() )->insert( PlanGroup::create( array( 'name' => 'Club' ) ) );

		$plan = Plan::create(
			$group_id,
			array(
				'name'           => 'Monthly',
				'billing_policy' => new BillingPolicy( 'month', 1, null, $max_cycles, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
			)
		);
		( new PlanRepository() )->insert( $plan );

		return (int) $plan->get_id();
	}

	private function make_origin_order(): WC_Order {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( self::GATEWAY );
		$order->set_total( '19.99' );
		$order->save();

		return $order;
	}

	/**
	 * Renewal orders tagged for a contract at a given cycle, narrowed in PHP
	 * (store-agnostic, like the engine's own idempotency check).
	 *
	 * @param int $contract_id Contract id.
	 * @param int $cycle       Cycle number.
	 * @return array<int, WC_Order>
	 */
	private function renewal_orders_for_cycle( int $contract_id, int $cycle ): array {
		$orders = wc_get_orders(
			array(
				'limit'      => -1,
				'type'       => 'shop_order',
				'status'     => 'any',
				'meta_key'   => OrderLinkage::META_CONTRACT_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => (string) $contract_id,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$this->assertIsArray( $orders );

		return array_values(
			array_filter(
				$orders,
				static function ( $order ) use ( $cycle ) {
					return $order instanceof WC_Order
						&& OrderLinkage::RELATION_RENEWAL === $order->get_meta( OrderLinkage::META_RELATION_TYPE )
						&& (string) $cycle === $order->get_meta( '_subscription_renewal_cycle' );
				}
			)
		);
	}

	private function make_contract( int $plan_id, int $origin_order_id, ?int $max_cycles = null ): Contract {
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => $plan_id,
				'origin_order_id'  => $origin_order_id,
				'payment_method'   => self::GATEWAY,
				'billing_total'    => '19.99',
				'start_gmt'        => '2026-01-15 00:00:00',
				'next_payment_gmt' => '2026-02-15 00:00:00',
			)
		);
		( new ContractRepository() )->insert( $contract );

		return $contract;
	}

	public function test_schedule_is_gated_on_recurring_capability(): void {
		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();

		// No capability declared: scheduling is refused.
		$this->assertFalse( $engine->schedule( $contract ) );
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );

		// Declare it and the schedule sticks.
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );
		$this->assertTrue( $engine->schedule( $contract ) );
		$this->assertTrue( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	public function test_schedule_replaces_existing_row(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();
		$engine->schedule( $contract );
		$engine->schedule( $contract );

		// Exactly one pending row for the contract.
		$pending = as_get_scheduled_actions(
			array(
				'hook'   => RenewalScheduler::HOOK,
				'args'   => array( $contract_id ),
				'status' => \ActionScheduler_Store::STATUS_PENDING,
			),
			'ids'
		);
		$this->assertCount( 1, $pending );
	}

	public function test_process_due_creates_renewal_and_advances(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine        = new RenewalEngine();
		$renewal_order = $engine->process_due( $contract_id );

		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertSame( (string) $contract_id, $renewal_order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		$this->assertSame( OrderLinkage::RELATION_RENEWAL, $renewal_order->get_meta( OrderLinkage::META_RELATION_TYPE ) );
		$this->assertSame( '19.99', $renewal_order->get_total() );

		// Contract advanced: cycle_count incremented, next bill date moved one month.
		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( 1, $reloaded->get_cycle_count() );
		$this->assertSame( '2026-03-15 00:00:00', $reloaded->get_next_payment_gmt() );
		$this->assertSame( ContractStatus::ACTIVE, $reloaded->get_status() );

		// Next cycle re-armed.
		$this->assertTrue( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	public function test_process_due_is_idempotent_for_a_retried_cycle(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$repo     = new ContractRepository();
		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );
		$engine   = new RenewalEngine();

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// First fire creates the cycle-1 renewal and advances to cycle 1.
		$first = $engine->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $first );

		// Simulate an Action Scheduler retry of the same due action: rewind the
		// persisted contract to its pre-advance state (cycle 0, original next
		// date) so the retry attempts cycle 1 again - exactly what a duplicate
		// dispatch would do before the advance committed.
		$rewound = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $rewound );
		$rewound->set_cycle_count( 0 );
		$rewound->set_status( ContractStatus::ACTIVE );
		$rewound->set_next_payment_gmt( '2026-02-15 00:00:00' );
		$repo->update( $rewound );

		$retry = $engine->process_due( $contract_id );

		// The per-cycle guard suppresses the retry: no second order, no advance.
		$this->assertNull( $retry );

		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 1 ) );

		$reloaded = $repo->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( 0, $reloaded->get_cycle_count() );
	}

	public function test_process_due_expires_contract_at_max_cycles(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id  = $this->make_plan( 1 );
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();
		$engine->process_due( $contract_id );

		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( 1, $reloaded->get_cycle_count() );
		$this->assertSame( ContractStatus::EXPIRED, $reloaded->get_status() );
		$this->assertNull( $reloaded->get_next_payment_gmt() );
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	public function test_process_due_skips_non_active_contract(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );
		$contract->set_status( ContractStatus::ON_HOLD );
		( new ContractRepository() )->update( $contract );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertNull( ( new RenewalEngine() )->process_due( $contract_id ) );

		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( 0, $reloaded->get_cycle_count() );
	}

	public function test_process_due_skips_unknown_contract(): void {
		$this->assertNull( ( new RenewalEngine() )->process_due( 999999 ) );
	}

	public function test_cancel_transitions_and_unschedules(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = $this->make_contract( $plan_id, $order->get_id() );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$engine = new RenewalEngine();
		$engine->schedule( $contract );
		$this->assertTrue( RenewalScheduler::is_scheduled( $contract_id ) );

		$this->assertTrue( $engine->cancel( $contract ) );

		$reloaded = ( new ContractRepository() )->find( $contract_id );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( ContractStatus::CANCELLED, $reloaded->get_status() );
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	public function test_gateway_scheduled_contract_is_not_scheduled(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id  = $this->make_plan();
		$order    = $this->make_origin_order();
		$contract = Contract::create(
			array(
				'customer_id'      => 1,
				'currency'         => 'USD',
				'selling_plan_id'  => $plan_id,
				'origin_order_id'  => $order->get_id(),
				'payment_method'   => self::GATEWAY,
				'billing_total'    => '19.99',
				'start_gmt'        => '2026-01-15 00:00:00',
				'next_payment_gmt' => '2026-02-15 00:00:00',
				'schedule_source'  => Contract::SCHEDULE_SOURCE_GATEWAY,
			)
		);
		( new ContractRepository() )->insert( $contract );

		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertFalse( ( new RenewalEngine() )->schedule( $contract ) );
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );
	}
}
