<?php
/**
 * Integration tests for ContractFactory.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Checkout;

use EngineIntegrationTestCase;
use WC_Order;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\PlanGroup;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\ContractRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanGroupRepository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\PlanRepository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\ContractFactory
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Checkout\OrderLinkage
 */
class ContractFactoryTest extends EngineIntegrationTestCase {

	/**
	 * @param int|null                              $max_cycles Maximum number of billing cycles, or null for unlimited.
	 * @param array{length: int, unit: string}|null $trial      Native trial duration, or null for none.
	 */
	private function make_plan( ?int $max_cycles = null, ?array $trial = null ): Plan {
		$group_id = ( new PlanGroupRepository() )->insert(
			PlanGroup::create( array( 'name' => 'Coffee club' ) )
		);

		$plan = Plan::create(
			$group_id,
			array(
				'name'           => 'Monthly coffee',
				'billing_policy' => new BillingPolicy( 'month', 1, null, $max_cycles, $trial ),
				'category'       => Plan::DEFAULT_CATEGORY,
				'extension_slug' => 'lite',
			)
		);
		( new PlanRepository() )->insert( $plan );

		return $plan;
	}

	private function make_order(): WC_Order {
		$order = new WC_Order();
		$order->set_currency( 'USD' );
		$order->set_payment_method( 'woocommerce_payments' );
		$order->set_payment_method_title( 'Credit card' );
		$order->set_total( '19.99' );
		$order->set_address(
			array(
				'first_name' => 'Ada',
				'last_name'  => 'Lovelace',
				'country'    => 'US',
				'email'      => 'ada@example.test',
			),
			'billing'
		);
		$order->save();

		return $order;
	}

	public function test_create_from_order_persists_and_links_contract(): void {
		$order = $this->make_order();
		$plan  = $this->make_plan();

		$contract = ( new ContractFactory() )->create_from_order( $order, $plan );

		$this->assertNotNull( $contract->get_id() );
		$this->assertSame( ContractStatus::ACTIVE, $contract->get_status() );
		$this->assertSame( 'USD', $contract->get_currency() );
		$this->assertSame( $plan->get_id(), $contract->get_selling_plan_id() );
		$this->assertSame( $order->get_id(), $contract->get_origin_order_id() );
		$this->assertSame( 'lite', $contract->get_extension_slug() );
		$this->assertSame( 'woocommerce_payments', $contract->get_payment_instrument()->get_gateway() );

		// Persisted and reloadable.
		$reloaded = ( new ContractRepository() )->find( $contract->get_id() );
		$this->assertInstanceOf( Contract::class, $reloaded );
		$this->assertSame( $contract->get_id(), $reloaded->get_id() );

		// Order is tagged with the parent relation.
		$tagged_order = wc_get_order( $order->get_id() );
		$this->assertInstanceOf( WC_Order::class, $tagged_order );
		$this->assertSame( (string) $contract->get_id(), $tagged_order->get_meta( OrderLinkage::META_CONTRACT_ID ) );
		$this->assertSame( OrderLinkage::RELATION_PARENT, $tagged_order->get_meta( OrderLinkage::META_RELATION_TYPE ) );
	}

	public function test_first_renewal_date_follows_billing_cadence(): void {
		$order = $this->make_order();
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		$contract = ( new ContractFactory() )->create_from_order( $order, $this->make_plan() );

		// Monthly cadence anchored on the paid date.
		$this->assertSame( '2026-02-15 00:00:00', $contract->get_next_payment_gmt() );
	}

	public function test_native_trial_delays_first_renewal(): void {
		$order = $this->make_order();
		$order->set_date_paid( '2026-01-15 00:00:00' );
		$order->save();

		$plan = $this->make_plan(
			null,
			array(
				'length' => 14,
				'unit'   => 'day',
			)
		);

		$contract = ( new ContractFactory() )->create_from_order( $order, $plan );

		// First bill is the trial end, not one month out.
		$this->assertSame( '2026-01-29 00:00:00', $contract->get_next_payment_gmt() );
	}

	public function test_overrides_take_precedence(): void {
		$order = $this->make_order();
		$plan  = $this->make_plan();

		$contract = ( new ContractFactory() )->create_from_order(
			$order,
			$plan,
			array(
				'billing_total'    => '49.00',
				'next_payment_gmt' => '2026-12-01 00:00:00',
			)
		);

		$this->assertSame( '49.00', $contract->get_billing_total() );
		$this->assertSame( '2026-12-01 00:00:00', $contract->get_next_payment_gmt() );
	}

	public function test_unsaved_plan_is_rejected(): void {
		$order = $this->make_order();
		$plan  = Plan::create(
			1,
			array(
				'name'           => 'Monthly coffee',
				'billing_policy' => new BillingPolicy( 'month', 1, null, null, null ),
				'category'       => Plan::DEFAULT_CATEGORY,
			)
		);

		$this->expectException( \RuntimeException::class );
		( new ContractFactory() )->create_from_order( $order, $plan );
	}

	public function test_unsaved_order_is_rejected(): void {
		// An order that was never saved reports id 0, which would persist
		// origin_order_id => 0 and link the contract to a non-existent order.
		$order = new WC_Order();
		$order->set_currency( 'USD' );

		$this->expectException( \RuntimeException::class );
		( new ContractFactory() )->create_from_order( $order, $this->make_plan() );
	}
}
