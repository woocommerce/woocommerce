<?php
/**
 * Integration tests for the interim Subscriptions lifecycle and renewal facade.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api;

use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Contracts;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Api\View\ContractView;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
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
	 * Sign up a contract through the contracts facade (cycle 1 billed). The monthly plan's
	 * cadence is frozen onto the contract's plan snapshot at signup.
	 *
	 * @param int $customer_id Owning customer id; 0 gives the order a new customer.
	 * @return Contract The persisted contract with cycle 1 billed.
	 */
	private function sign_up_contract( int $customer_id = 0 ): Contract {
		$plan = Plan::create(
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
		if ( $customer_id > 0 ) {
			$order->set_customer_id( $customer_id );
		}
		$order->save();

		$contract = ( new ContractRepository() )->find( $this->sign_up_from_order( $order, $plan ) );
		$this->assertInstanceOf( Contract::class, $contract );

		return $contract;
	}

	/**
	 * @testdox get_related_orders returns the contract's linked orders (the origin order).
	 */
	public function test_get_related_orders_returns_the_linked_orders(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$orders = Subscriptions::get_related_orders( $contract_id );

		$this->assertCount( 1, $orders );
		$this->assertInstanceOf( WC_Order::class, $orders[0] );
		$this->assertSame( $contract->get_origin_order_id(), $orders[0]->get_id() );
	}

	/**
	 * @testdox get_related_orders lists an origin order that also carries the contract meta once.
	 */
	public function test_get_related_orders_lists_a_meta_tagged_origin_once(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$origin = wc_get_order( (int) $contract->get_origin_order_id() );
		$this->assertInstanceOf( WC_Order::class, $origin );
		$origin->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract_id );
		$origin->save();

		$orders = Subscriptions::get_related_orders( $contract_id );

		$this->assertCount( 1, $orders );
		$this->assertSame( $origin->get_id(), $orders[0]->get_id() );
	}

	/**
	 * @testdox get_related_orders is empty for a contract with no linked orders.
	 */
	public function test_get_related_orders_is_empty_when_none_are_linked(): void {
		$this->assertSame( array(), Subscriptions::get_related_orders( 987654 ) );
	}

	/**
	 * @testdox get_related_orders windows with limit/offset, newest first.
	 */
	public function test_get_related_orders_windows_with_limit_and_offset(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Link three renewal orders backdated 1-3 days before the origin (created
		// "now"), so the full set is 4 orders newest-first: origin, renewal1,
		// renewal2, renewal3.
		$renewal_ids = array();
		foreach ( array( 3, 2, 1 ) as $days_ago ) {
			$order = wc_create_order();
			$this->assertInstanceOf( WC_Order::class, $order );
			$order->set_date_created( gmdate( 'Y-m-d H:i:s', time() - ( $days_ago * DAY_IN_SECONDS ) ) );
			$order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract_id );
			$order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
			$order->save();
			$renewal_ids[ $days_ago ] = $order->get_id();
		}

		$all = Subscriptions::get_related_orders( $contract_id );
		$this->assertCount( 4, $all, 'The default window stays "all".' );

		$first_page = Subscriptions::get_related_orders( $contract_id, 2, 0 );
		$this->assertCount( 2, $first_page );
		$this->assertSame( $contract->get_origin_order_id(), $first_page[0]->get_id(), 'Newest linked order first.' );
		$this->assertSame( $renewal_ids[1], $first_page[1]->get_id() );

		$second_page = Subscriptions::get_related_orders( $contract_id, 2, 2 );
		$this->assertCount( 2, $second_page );
		$this->assertSame( $renewal_ids[2], $second_page[0]->get_id() );
		$this->assertSame( $renewal_ids[3], $second_page[1]->get_id() );

		$past_the_end = Subscriptions::get_related_orders( $contract_id, 2, 4 );
		$this->assertSame( array(), $past_the_end );

		// A zero limit means none - never WP_Query's posts-per-page default.
		$this->assertSame( array(), Subscriptions::get_related_orders( $contract_id, 0 ) );
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

		$history = Contracts::get_cycles( $contract_id );
		$this->assertCount( 2, $history );

		// Newest first: cycle 2 is billed, linked to the renewal order.
		$cycle_two = $history[0];
		$this->assertSame( 2, $cycle_two->get_count() );
		$this->assertSame( CycleStatus::BILLED, $cycle_two->get_status() );
		$this->assertSame( $renewal_order->get_id(), $cycle_two->get_order_id() );

		// The schedule advanced one cadence (cycle 1 ended 2026-02-15 + 1 month).
		$after_renew = Contracts::get( $contract_id );
		$this->assertInstanceOf( ContractView::class, $after_renew );
		$this->assertSame( '2026-03-15 00:00:00', $after_renew->get_next_payment_gmt() );

		// Cancel: the contract goes terminal.
		$this->assertTrue( Subscriptions::cancel( $contract_id ) );

		$after_cancel = Contracts::get( $contract_id );
		$this->assertInstanceOf( ContractView::class, $after_cancel );
		$this->assertSame( ContractStatus::CANCELLED, $after_cancel->get_status() );
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
		$held = Contracts::get( $contract_id );
		$this->assertInstanceOf( ContractView::class, $held );
		$this->assertSame( ContractStatus::ON_HOLD, $held->get_status() );
		$this->assertNull( $held->get_next_payment_gmt(), 'Hold disarms the next-due moment.' );

		$this->assertTrue( Subscriptions::reactivate( $contract_id ) );
		$active = Contracts::get( $contract_id );
		$this->assertInstanceOf( ContractView::class, $active );
		$this->assertSame( ContractStatus::ACTIVE, $active->get_status() );
		$this->assertNotNull( $active->get_next_payment_gmt(), 'Reactivate re-arms the next-due moment.' );

		$this->assertTrue( Subscriptions::cancel_at_period_end( $contract_id ) );
		$pending = Contracts::get( $contract_id );
		$this->assertInstanceOf( ContractView::class, $pending );
		$this->assertSame( ContractStatus::PENDING_CANCELLATION, $pending->get_status() );
		$this->assertNull( $pending->get_next_payment_gmt(), 'Cancel at period end disarms the next-due moment.' );
		$this->assertNotNull( $pending->get_end_gmt(), 'The former next-due moment becomes the end date.' );
	}
}
