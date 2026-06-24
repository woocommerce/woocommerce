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
		$this->assertFalse( RenewalScheduler::is_scheduled( $contract_id ) );

		// Declare it and the schedule sticks.
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );
		$this->assertTrue( $engine->schedule( $contract ) );
		$this->assertTrue( RenewalScheduler::is_scheduled( $contract_id ) );
	}

	/**
	 * @testdox schedule replaces any existing pending row (one row per contract).
	 */
	public function test_schedule_replaces_existing_row(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
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

	/**
	 * @testdox process_due creates a renewal order tagged for the next chargeable number.
	 */
	public function test_process_due_creates_renewal_order(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$renewal_order = ( new RenewalEngine() )->process_due( $contract_id );

		// Order creation is wired; advancing the chain is the dispatcher slice, so
		// only order creation + tagging is asserted here.
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertSame( (string) $contract_id, $renewal_order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		$this->assertSame( OrderLinkage::RELATION_RENEWAL, $renewal_order->get_meta( OrderLinkage::META_RELATION_TYPE ) );

		// A lean contract has no counting cycle yet, so the next chargeable number is 1.
		$this->assertSame( '1', $renewal_order->get_meta( '_subscription_renewal_cycle' ) );
		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
	}

	/**
	 * @testdox process_due is idempotent: a retried due action creates no second order.
	 */
	public function test_process_due_is_idempotent_for_a_retried_cycle(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );
		$engine = new RenewalEngine();

		$first = $engine->process_due( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $first );

		// A retried due action for the same chargeable number is suppressed.
		$retry = $engine->process_due( $contract_id );
		$this->assertNull( $retry );

		$this->assertCount( 1, $this->renewal_orders_for_cycle( $contract_id, 1 ) );
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
	 * @testdox cancel transitions the contract to cancelled and clears its pending row.
	 */
	public function test_cancel_transitions_and_unschedules(): void {
		GatewayCapabilities::declare( self::GATEWAY, array( GatewayCapabilities::RECURRING ) );

		$plan_id     = $this->make_plan();
		$order       = $this->make_origin_order();
		$contract    = $this->make_contract( $plan_id, $order->get_id() );
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
