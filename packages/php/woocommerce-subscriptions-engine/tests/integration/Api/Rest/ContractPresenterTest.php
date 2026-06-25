<?php
/**
 * Integration tests for the customer-portal presenter, focused on the post-freeze
 * enrichment: the recurring-summary cadence is sourced from the contract's frozen plan
 * snapshot (so it survives a live-plan edit) and the related-orders block flows through
 * the Api\Subscriptions facade rather than the order-linkage internals.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Api\Rest;

use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractPresenter;
use Automattic\WooCommerce\SubscriptionsEngine\Api\Subscriptions;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanGroupRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Api\Rest\ContractPresenter
 */
class ContractPresenterTest extends EngineIntegrationTestCase {

	/**
	 * Sign up a contract on a monthly plan, freezing the monthly cadence onto its snapshot.
	 *
	 * @return Contract The persisted contract with cycle 1 billed.
	 */
	private function sign_up_monthly(): Contract {
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
		$order->set_payment_method( 'dummy' );
		$order->set_total( '19.99' );
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		return ( new ContractFactory() )->create_from_order( $order, $plan );
	}

	/**
	 * @testdox the recurring summary cadence comes from the frozen snapshot, not the live plan.
	 */
	public function test_recurring_summary_cadence_comes_from_the_snapshot(): void {
		$contract    = $this->sign_up_monthly();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		// Edit the live plan to a different cadence AFTER signup. The old presenter joined the
		// live plan and would now read "yearly"; the enriched one reads the frozen snapshot.
		$plans = new PlanRepository();
		$plan  = $plans->find( $contract->get_selling_plan_id() );
		$this->assertInstanceOf( Plan::class, $plan );
		$plan->set_billing_policy( new BillingPolicy( 'year', 2, null, null, null ) );
		$plans->update( $plan );

		$loaded = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $loaded );

		$detail = ( new ContractPresenter() )->build_detail( $loaded );
		$this->assertIsString( $detail['recurring_summary'] );

		// Frozen monthly cadence wins; the live plan's edited yearly cadence must not appear.
		$this->assertStringContainsString( '/ month', $detail['recurring_summary'] );
		$this->assertStringNotContainsString( 'year', $detail['recurring_summary'] );
	}

	/**
	 * @testdox the detail's related orders flow through the facade and include the origin order.
	 */
	public function test_related_orders_flow_through_the_facade(): void {
		$contract    = $this->sign_up_monthly();
		$contract_id = $contract->get_id();
		$this->assertNotNull( $contract_id );

		$loaded = Subscriptions::get( $contract_id );
		$this->assertInstanceOf( Contract::class, $loaded );

		$detail  = ( new ContractPresenter() )->build_detail( $loaded );
		$related = $detail['related_orders'];
		$this->assertIsArray( $related );
		$this->assertCount( 1, $related );

		$row = $related[0];
		$this->assertIsArray( $row );

		$origin_id = $contract->get_origin_order_id();
		$this->assertNotNull( $origin_id );
		$order = wc_get_order( $origin_id );
		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( $order->get_order_number(), $row['number'] );
	}

	/**
	 * @testdox a contract with no hydrated snapshot degrades to a price with no cadence suffix.
	 */
	public function test_missing_snapshot_degrades_to_no_cadence(): void {
		// A manually-seeded contract carries no plan snapshot, so the cadence accessor is null
		// and the summary is the bare price - the documented non-fatal degrade.
		$contract = Contract::create(
			array(
				'customer_id'     => 1,
				'currency'        => 'USD',
				'selling_plan_id' => 1,
				'start_gmt'       => '2026-01-01 00:00:00',
				'billing_total'   => '19.99',
			)
		);

		$detail = ( new ContractPresenter() )->build_detail( $contract );
		$this->assertIsString( $detail['recurring_summary'] );
		$this->assertStringNotContainsString( '/', $detail['recurring_summary'] );
		$this->assertStringContainsString( '19.99', $detail['recurring_summary'] );
	}
}
