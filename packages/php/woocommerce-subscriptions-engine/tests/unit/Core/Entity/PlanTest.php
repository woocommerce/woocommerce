<?php
/**
 * Unit tests for the Plan entity (pure-Core behavior).
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Plan;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\BillingPolicy;

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

	public function test_to_storage_exposes_extension_slug_and_decoded_policies(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Owned',
				'billing_policy' => $this->billing(),
				'status'         => Plan::STATUS_ARCHIVED,
				'sort_order'     => 9,
				'extension_slug' => 'lite',
				'pricing_policy' => array( 'policies' => array() ),
			)
		);

		$storage = $plan->to_storage();

		$this->assertSame( 'lite', $storage['extension_slug'] );
		$this->assertSame( Plan::STATUS_ARCHIVED, $storage['status'] );
		$this->assertSame( 9, $storage['sort_order'] );
		$this->assertIsArray( $storage['billing_policy'] );
		$this->assertSame( array( 'policies' => array() ), $storage['pricing_policy'] );
	}

	/**
	 * An arbitrary extension payload, including vocabulary the engine does not know.
	 *
	 * @return array<string, mixed>
	 */
	private function arbitrary_payload(): array {
		return array(
			'policies'      => array(
				array(
					'type'  => 'tiered',
					'value' => -5,
					'tiers' => array( array( 'min' => 1 ), array( 'min' => 10 ) ),
				),
				array( 'type' => 'bogo' ),
			),
			'one_time_fees' => array(),
			'custom_key'    => array( 'nested' => array( 'deep' => '1.50' ) ),
		);
	}

	public function test_pricing_payload_round_trips_opaquely_through_storage(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Opaque',
				'billing_policy' => $this->billing(),
				'pricing_policy' => $this->arbitrary_payload(),
			)
		);

		$this->assertSame( $this->arbitrary_payload(), $plan->get_pricing_policy() );
		$this->assertSame( $this->arbitrary_payload(), $plan->to_storage()['pricing_policy'] );

		$hydrated = Plan::from_storage( $plan->to_storage() );

		$this->assertSame( $this->arbitrary_payload(), $hydrated->get_pricing_policy() );
	}

	public function test_set_pricing_policy_round_trips_and_clears(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Mutating',
				'billing_policy' => $this->billing(),
			)
		);

		$plan->set_pricing_policy( $this->arbitrary_payload() );
		$this->assertSame( $this->arbitrary_payload(), $plan->get_pricing_policy() );

		$plan->set_pricing_policy( null );
		$this->assertNull( $plan->get_pricing_policy() );
	}

	public function test_absent_pricing_payload_stays_null(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Plain',
				'billing_policy' => $this->billing(),
			)
		);

		$this->assertNull( $plan->get_pricing_policy() );
		$this->assertNull( $plan->to_storage()['pricing_policy'] );
		$this->assertNull( Plan::from_storage( $plan->to_storage() )->get_pricing_policy() );
	}

	public function test_empty_pricing_payload_is_accepted(): void {
		$plan = Plan::create(
			array(
				'name'           => 'Empty',
				'billing_policy' => $this->billing(),
				'pricing_policy' => array(),
			)
		);

		$this->assertSame( array(), $plan->get_pricing_policy() );
		$this->assertSame( array(), Plan::from_storage( $plan->to_storage() )->get_pricing_policy() );
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public function non_object_payloads(): array {
		return array(
			'list'   => array( array( array( 'type' => 'x' ) ) ),
			'string' => array( 'percentage' ),
			'int'    => array( 10 ),
		);
	}

	/**
	 * @dataProvider non_object_payloads
	 *
	 * @param mixed $payload Non-object payload.
	 */
	public function test_create_rejects_a_non_object_pricing_payload( $payload ): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'pricing_policy must be an object' );

		Plan::create(
			array(
				'name'           => 'Bad',
				'billing_policy' => $this->billing(),
				'pricing_policy' => $payload,
			)
		);
	}

	/**
	 * @dataProvider non_object_payloads
	 *
	 * @param mixed $payload Non-object payload.
	 */
	public function test_from_storage_rejects_a_non_object_pricing_payload( $payload ): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'pricing_policy must be an object' );

		Plan::from_storage(
			array(
				'name'           => 'Corrupted',
				'billing_policy' => array(
					'period'   => 'month',
					'interval' => 1,
				),
				'pricing_policy' => $payload,
			)
		);
	}
}
