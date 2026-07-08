<?php
/**
 * Unit tests for the Plan entity (pure-Core behavior: validation + pricing).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan
 */
class PlanTest extends TestCase {

	private function billing(): BillingPolicy {
		return BillingPolicy::from_array(
			array(
				'period'   => 'month',
				'interval' => 1,
			)
		);
	}

	public function test_create_defaults_category_and_extension_slug(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Monthly box',
				'billing_policy' => $this->billing(),
			)
		);

		$this->assertNull( $plan->get_id() );
		$this->assertSame( Plan::DEFAULT_CATEGORY, $plan->get_category() );
		$this->assertSame( Plan::STATUS_ACTIVE, $plan->get_status() );
		$this->assertSame( 0, $plan->get_sort_order() );
		$this->assertNull( $plan->get_merchant_code() );
		$this->assertNull( $plan->get_extension_slug() );
	}

	public function test_merchant_code_round_trips_through_create_and_storage(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Coded',
				'billing_policy' => $this->billing(),
				'merchant_code'  => 'monthly-box',
			)
		);

		$this->assertSame( 'monthly-box', $plan->get_merchant_code() );
		$this->assertSame( 'monthly-box', $plan->to_storage()['merchant_code'] );

		$hydrated = Plan::from_storage( $plan->to_storage() );

		$this->assertSame( 'monthly-box', $hydrated->get_merchant_code() );
	}

	public function test_absent_merchant_code_is_null_in_storage(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Uncoded',
				'billing_policy' => $this->billing(),
			)
		);

		$this->assertNull( $plan->to_storage()['merchant_code'] );
		$this->assertNull( Plan::from_storage( $plan->to_storage() )->get_merchant_code() );
	}

	public function test_calculate_price_delegates_to_pricing_policy(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Discounted',
				'billing_policy' => $this->billing(),
				'pricing_policy' => PricingPolicy::from_array(
					array(
						'policies' => array(
							array(
								'type'  => 'percentage',
								'value' => 20,
							),
						),
					)
				),
			)
		);

		$this->assertSame( 80.0, $plan->calculate_price( 100.0 ) );
	}

	public function test_calculate_price_without_pricing_policy_returns_base(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Plain',
				'billing_policy' => $this->billing(),
			)
		);

		$this->assertSame( 42.0, $plan->calculate_price( 42.0 ) );
	}

	public function test_status_and_sort_order_are_mutable(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Ordered',
				'billing_policy' => $this->billing(),
				'sort_order'     => 3,
			)
		);

		$plan->set_status( Plan::STATUS_ARCHIVED );
		$plan->set_sort_order( 7 );

		$this->assertSame( Plan::STATUS_ARCHIVED, $plan->get_status() );
		$this->assertSame( 7, $plan->get_sort_order() );
	}

	public function test_invalid_status_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );

		Plan::create(
			array(
				'name'           => 'Bad status',
				'billing_policy' => $this->billing(),
				'status'         => 'deleted',
			)
		);
	}

	public function test_invalid_pricing_policy_type_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );

		Plan::create(
			array(
				'name'           => 'Bad',
				'billing_policy' => $this->billing(),
				'pricing_policy' => PricingPolicy::from_array(
					array(
						'policies' => array(
							array(
								'type'  => 'mystery',
								'value' => 1,
							),
						),
					)
				),
			)
		);
	}

	public function test_percentage_over_one_hundred_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );

		Plan::create(
			array(
				'name'           => 'Too much',
				'billing_policy' => $this->billing(),
				'pricing_policy' => PricingPolicy::from_array(
					array(
						'policies' => array(
							array(
								'type'  => 'percentage',
								'value' => 150,
							),
						),
					)
				),
			)
		);
	}

	public function test_to_storage_exposes_extension_slug_and_decoded_policies(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Owned',
				'billing_policy' => $this->billing(),
				'status'         => Plan::STATUS_ARCHIVED,
				'sort_order'     => 9,
				'extension_slug' => 'lite',
			)
		);

		$storage = $plan->to_storage();

		$this->assertSame( 'lite', $storage['extension_slug'] );
		$this->assertSame( Plan::STATUS_ARCHIVED, $storage['status'] );
		$this->assertSame( 9, $storage['sort_order'] );
		$this->assertIsArray( $storage['billing_policy'] );
	}

	public function test_from_storage_rejects_corrupted_stored_pricing_policy(): void {
		$this->expectException( InvalidArgumentException::class );

		// A stored row whose pricing policy was tampered with outside engine flows
		// (percentage over 100) must fail loud on hydration, not feed billing math.
		Plan::from_storage(
			array(
				'name'           => 'Corrupted',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => array(
					'policies' => array(
						array(
							'type'  => 'percentage',
							'value' => 150,
						),
					),
				),
			)
		);
	}
}
