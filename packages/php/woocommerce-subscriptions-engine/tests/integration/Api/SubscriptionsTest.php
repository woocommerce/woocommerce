<?php
/**
 * Integration tests for the interim Subscriptions renewal facade.
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
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Gateway\GatewayCapabilities;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions
 */
class SubscriptionsTest extends EngineIntegrationTestCase {

	/**
	 * Gateway id used for the renewal charge - declares `recurring` and completes
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
		$plan = $this->plan_view( $this->make_plan() );

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
	 * @testdox get_related_orders bounds the linked-order query to the page and still pages like an unbounded read.
	 */
	public function test_get_related_orders_bounds_the_query_to_the_page(): void {
		$contract    = $this->sign_up_contract();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Five renewal orders dated after the origin, newest last created.
		foreach ( array( 1, 2, 3, 4, 5 ) as $days_ahead ) {
			$order = wc_create_order();
			$this->assertInstanceOf( WC_Order::class, $order );
			$order->set_date_created( gmdate( 'Y-m-d H:i:s', time() + ( $days_ahead * DAY_IN_SECONDS ) ) );
			$order->update_meta_data( OrderLinkage::META_CONTRACT_ID, (string) $contract_id );
			$order->update_meta_data( OrderLinkage::META_RELATION_TYPE, OrderLinkage::RELATION_RENEWAL );
			$order->save();
		}

		$all_ids = array_map(
			static function ( WC_Order $order ): int {
				return $order->get_id();
			},
			Subscriptions::get_related_orders( $contract_id )
		);
		$this->assertCount( 6, $all_ids );

		$limits = array();
		$record = static function ( array $args ) use ( &$limits ): array {
			$limits[] = $args['limit'] ?? null;
			return $args;
		};
		add_filter( 'woocommerce_order_query_args', $record );

		try {
			foreach ( array( 0, 2, 4 ) as $offset ) {
				$page_ids = array_map(
					static function ( WC_Order $order ): int {
						return $order->get_id();
					},
					Subscriptions::get_related_orders( $contract_id, 2, $offset )
				);
				$this->assertSame( array_slice( $all_ids, $offset, 2 ), $page_ids, "Page at offset {$offset} matches the unbounded read." );
			}
		} finally {
			remove_filter( 'woocommerce_order_query_args', $record );
		}

		$this->assertSame( array( 2, 4, 6 ), $limits, 'The linked-order query is bounded to offset + limit.' );
	}

	/**
	 * @testdox renew_now returns null for an unknown contract.
	 */
	public function test_renew_now_unknown_contract_returns_null(): void {
		$this->assertNull( Subscriptions::renew_now( 999999 ) );
	}

	/**
	 * @testdox A purchased contract renews through the facade.
	 */
	public function test_buy_then_renew_through_the_facade(): void {
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
	}
}
