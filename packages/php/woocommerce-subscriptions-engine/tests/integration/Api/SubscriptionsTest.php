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
}
