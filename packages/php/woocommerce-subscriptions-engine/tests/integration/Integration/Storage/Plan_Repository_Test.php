<?php
/**
 * Integration tests for Plan_Repository (and Plan_Group_Repository).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use Engine_Integration_Test_Case;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan_Group;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\Billing_Policy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\Pricing_Policy;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Plan_Group_Repository;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Plan_Repository;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Plan_Repository
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\Plan_Group_Repository
 */
class Plan_Repository_Test extends Engine_Integration_Test_Case {

	private function make_group(): int {
		$group = Plan_Group::create(
			array(
				'name'          => 'Coffee club',
				'merchant_code' => 'coffee-club',
			)
		);

		return ( new Plan_Group_Repository() )->insert( $group );
	}

	public function test_plan_group_round_trips(): void {
		$repo = new Plan_Group_Repository();

		$id = $repo->insert(
			Plan_Group::create(
				array(
					'name'            => 'Boxes',
					'merchant_code'   => 'boxes',
					'options_display' => array( array( 'name' => 'Size' ) ),
					'app_id'          => 'wc-subscriptions',
				)
			)
		);

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan_Group::class, $fetched );
		$this->assertSame( $id, $fetched->get_id() );
		$this->assertSame( 'Boxes', $fetched->get_name() );
		$this->assertSame( 'boxes', $fetched->get_merchant_code() );
		$this->assertSame( 'wc-subscriptions', $fetched->get_app_id() );
		$this->assertSame( array( array( 'name' => 'Size' ) ), $fetched->get_options_display() );
	}

	public function test_plan_round_trips_with_policies_and_extension_slug(): void {
		$group_id = $this->make_group();
		$repo     = new Plan_Repository();

		$plan = Plan::create(
			$group_id,
			array(
				'name'           => 'Monthly',
				'description'    => 'A monthly plan',
				'options'        => array(
					array(
						'name'  => 'Monthly',
						'value' => 'monthly',
					),
				),
				'billing_policy' => Billing_Policy::from_array(
					array(
						'period'     => 'month',
						'interval'   => 1,
						'max_cycles' => 12,
					)
				),
				'pricing_policy' => Pricing_Policy::from_array(
					array(
						'policies' => array(
							array(
								'type'  => 'percentage',
								'value' => 10,
							),
						),
					)
				),
				'extension_slug' => 'lite',
			)
		);

		$id = $repo->insert( $plan );
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( $id, $plan->get_id() );

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertSame( 'Monthly', $fetched->get_name() );
		$this->assertSame( 'A monthly plan', $fetched->get_description() );
		$this->assertSame( $group_id, $fetched->get_group_id() );
		$this->assertSame( 'lite', $fetched->get_extension_slug() );
		$this->assertSame( 'month', $fetched->get_billing_policy()->get_period() );
		$this->assertSame( 12, $fetched->get_billing_policy()->get_max_cycles() );
		$this->assertNotNull( $fetched->get_pricing_policy() );
		$this->assertSame( 90.0, $fetched->calculate_price( 100.0 ) );
	}

	public function test_plan_without_optional_policies_round_trips(): void {
		$group_id = $this->make_group();
		$repo     = new Plan_Repository();

		$id = $repo->insert(
			Plan::create(
				$group_id,
				array(
					'name'           => 'Bare',
					'billing_policy' => Billing_Policy::from_array(
						array(
							'period'   => 'week',
							'interval' => 2,
						)
					),
				)
			)
		);

		$fetched = $repo->find( $id );

		$this->assertInstanceOf( Plan::class, $fetched );
		$this->assertNull( $fetched->get_pricing_policy() );
		$this->assertNull( $fetched->get_delivery_policy() );
		$this->assertNull( $fetched->get_extension_slug() );
	}

	public function test_update_persists_changes(): void {
		$group_id = $this->make_group();
		$repo     = new Plan_Repository();

		$plan = Plan::create(
			$group_id,
			array(
				'name'           => 'Before',
				'billing_policy' => Billing_Policy::from_array(
					array(
						'period'   => 'month',
						'interval' => 1,
					)
				),
			)
		);
		$id   = $repo->insert( $plan );

		$plan->set_name( 'After' );
		$this->assertTrue( $repo->update( $plan ) );

		$this->assertSame( 'After', $repo->find( $id )->get_name() );
	}

	public function test_delete_removes_the_row(): void {
		$group_id = $this->make_group();
		$repo     = new Plan_Repository();

		$id = $repo->insert(
			Plan::create(
				$group_id,
				array(
					'name'           => 'Doomed',
					'billing_policy' => Billing_Policy::from_array(
						array(
							'period'   => 'month',
							'interval' => 1,
						)
					),
				)
			)
		);

		$this->assertTrue( $repo->delete( $id ) );
		$this->assertNull( $repo->find( $id ) );
	}
}
