<?php
/**
 * Integration tests for the public Subscriptions facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanGroupRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions
 */
class SubscriptionsTest extends EngineIntegrationTestCase {

	/**
	 * Gateway id used for the lifecycle charge - declares `recurring` and completes
	 * the charge inline (the dummy-gateway shape), matching the real gateway used in CI.
	 */
	private const GATEWAY = 'dummy';

	public function set_up(): void {
		parent::set_up();
		GatewayCapabilities::reset();
		$this->approve_charges_for( self::GATEWAY );
	}

	public function tear_down(): void {
		GatewayCapabilities::reset();
		parent::tear_down();
	}

	/**
	 * Sign up a contract via the checkout factory (cycle 1 billed).
	 *
	 * @return Contract The persisted contract with cycle 1 billed.
	 */
	private function sign_up_contract(): Contract {
		$group_id = ( new PlanGroupRepository() )->insert( PlanGroup::create( array( 'name' => 'Club' ) ) );
		$plan     = Plan::create(
			$group_id,
			array(
				'name'           => 'Monthly',
				'billing_policy' => new BillingPolicy( 'month', 1, null, null, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => 'engine-tests',
			)
		);
		( new PlanRepository() )->insert( $plan );

		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( self::GATEWAY );
		$order->set_total( '19.99' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		return ( new ContractFactory() )->create_from_order( $order, $plan );
	}

	/**
	 * @testdox get returns the contract, and null for an unknown id.
	 */
	public function test_get_round_trips_a_contract(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$loaded = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $loaded );
		$this->assertSame( $contract_id, $loaded->get_id() );

		$this->assertNull( Subscriptions::get( 999999 ) );
	}

	/**
	 * @testdox list returns recent contracts newest first.
	 */
	public function test_list_returns_recent_contracts(): void {
		$first  = $this->sign_up_contract();
		$second = $this->sign_up_contract();

		$contracts = Subscriptions::list();
		$ids       = array_map( static fn ( Contract $c ) => $c->get_id(), $contracts );

		// Newest first, and both signups are present.
		$this->assertSame( array( $second->get_id(), $first->get_id() ), array_slice( $ids, 0, 2 ) );
		$this->assertInstanceOf( Contract::class, $contracts[0] );
	}

	/**
	 * @testdox get_history returns the billing cycles newest first.
	 */
	public function test_get_history_returns_cycles(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$history = Subscriptions::get_history( $contract_id );
		$this->assertCount( 1, $history );
		$this->assertInstanceOf( Cycle::class, $history[0] );
		$this->assertSame( 1, $history[0]->get_count() );
	}

	/**
	 * @testdox cancel returns false for an unknown contract.
	 */
	public function test_cancel_unknown_contract_returns_false(): void {
		$this->assertFalse( Subscriptions::cancel( 999999 ) );
	}

	/**
	 * @testdox renew_now returns null for an unknown contract.
	 */
	public function test_renew_now_unknown_contract_returns_null(): void {
		$this->assertNull( Subscriptions::renew_now( 999999 ) );
	}

	/**
	 * @testdox The full lifecycle runs through the facade: buy, renew, cancel.
	 */
	public function test_full_lifecycle_buy_renew_cancel(): void {
		// Buy: signup builds cycle 1 (billed).
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );
		// Monthly plan, paid 2026-01-15: first renewal is one month out.
		$this->assertSame( '2026-02-15 00:00:00', $contract->get_next_payment_gmt() );

		// Renew: advance the chain a cycle through the facade.
		$renewal_order = Subscriptions::renew_now( $contract_id );
		$this->assertInstanceOf( WC_Order::class, $renewal_order );
		$this->assertTrue( $renewal_order->is_paid() );

		$history = Subscriptions::get_history( $contract_id );
		$this->assertCount( 2, $history );

		// Newest first: cycle 2 is billed, linked to the renewal order.
		$cycle_two = $history[0];
		$this->assertSame( 2, $cycle_two->get_count() );
		$this->assertTrue( $cycle_two->get_status()->equals( CycleStatus::billed() ) );
		$this->assertSame( $renewal_order->get_id(), $cycle_two->get_order_id() );

		// The schedule advanced one cadence (cycle 1 ended 2026-02-15 + 1 month).
		$after_renew = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $after_renew );
		$this->assertSame( '2026-03-15 00:00:00', $after_renew->get_next_payment_gmt() );

		// Cancel: the contract goes terminal.
		$this->assertTrue( Subscriptions::cancel( $contract_id ) );

		$after_cancel = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $after_cancel );
		$this->assertSame( ContractStatus::CANCELLED, $after_cancel->get_status() );
	}

	/**
	 * Seed a contract for a customer at a status, returning its id.
	 *
	 * @param int    $customer_id Owning customer.
	 * @param string $status      Contract status.
	 */
	private function seed_for_customer( int $customer_id, string $status = ContractStatus::ACTIVE ): int {
		$contract = Contract::create(
			array(
				'customer_id'      => $customer_id,
				'status'           => $status,
				'currency'         => 'USD',
				'selling_plan_id'  => 1,
				'start_gmt'        => '2026-01-01 00:00:00',
				'next_payment_gmt' => '2099-02-01 00:00:00',
				'billing_total'    => '19.99',
			)
		);

		return ( new ContractRepository() )->insert( $contract );
	}

	/**
	 * @testdox list_for_customer returns only the requested customer's contracts.
	 */
	public function test_list_for_customer_is_owner_scoped(): void {
		$mine_a = $this->seed_for_customer( 41 );
		$mine_b = $this->seed_for_customer( 41 );
		$theirs = $this->seed_for_customer( 42 );

		$ids = array_map(
			static fn ( Contract $c ) => (int) $c->get_id(),
			Subscriptions::list_for_customer( 41 )
		);

		$this->assertContains( $mine_a, $ids );
		$this->assertContains( $mine_b, $ids );
		$this->assertNotContains( $theirs, $ids );
		$this->assertCount( 2, $ids );
	}

	/**
	 * @testdox get_for_customer returns the contract when the customer owns it.
	 */
	public function test_get_for_customer_returns_the_owned_contract(): void {
		$id = $this->seed_for_customer( 43 );

		$contract = Subscriptions::get_for_customer( $id, 43 );

		$this->assertInstanceOf( Contract::class, $contract );
		$this->assertSame( $id, $contract->get_id() );
	}

	/**
	 * @testdox get_for_customer is null for both a foreign owner and an unknown id (asymmetric).
	 */
	public function test_get_for_customer_is_null_for_foreign_and_unknown(): void {
		$id = $this->seed_for_customer( 44 );

		$this->assertNull( Subscriptions::get_for_customer( $id, 45 ), 'foreign-owned reads as not found' );
		$this->assertNull( Subscriptions::get_for_customer( 987654, 44 ), 'unknown id reads as not found' );
	}

	/**
	 * @testdox the lifecycle verbs return false for an unknown contract.
	 */
	public function test_lifecycle_actions_return_false_for_an_unknown_contract(): void {
		$this->assertFalse( Subscriptions::hold( 987654 ) );
		$this->assertFalse( Subscriptions::reactivate( 987654 ) );
		$this->assertFalse( Subscriptions::cancel_at_period_end( 987654 ) );
	}

	/**
	 * @testdox the portal lifecycle runs through the facade: hold, reactivate, cancel at period end.
	 */
	public function test_portal_lifecycle_hold_reactivate_cancel_at_period_end(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$this->assertTrue( Subscriptions::hold( $contract_id ) );
		$held = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $held );
		$this->assertSame( ContractStatus::ON_HOLD, $held->get_status() );

		$this->assertTrue( Subscriptions::reactivate( $contract_id ) );
		$active = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $active );
		$this->assertSame( ContractStatus::ACTIVE, $active->get_status() );

		$this->assertTrue( Subscriptions::cancel_at_period_end( $contract_id ) );
		$pending = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $pending );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $pending->get_status() );
	}
}
