<?php
/**
 * Unit tests for PricingPolicy.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\ValueObject;

use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PricingPolicy
 */
class PricingPolicyTest extends TestCase {

	public function test_empty_policy_returns_base_price(): void {
		$policy = PricingPolicy::from_array( array() );

		$this->assertSame( 25.0, $policy->calculate_price( 25.0 ) );
		$this->assertSame( array(), $policy->get_policies() );
		$this->assertSame( array(), $policy->get_one_time_fees() );
	}

	public function test_percentage_discount_applies(): void {
		$policy = PricingPolicy::from_array(
			array(
				'policies' => array(
					array(
						'type'  => 'percentage',
						'value' => 10,
					),
				),
			)
		);

		$this->assertSame( 90.0, $policy->calculate_price( 100.0 ) );
	}

	public function test_fixed_amount_is_clamped_at_zero(): void {
		$policy = PricingPolicy::from_array(
			array(
				'policies' => array(
					array(
						'type'  => 'fixed_amount',
						'value' => 30,
					),
				),
			)
		);

		$this->assertSame( 0.0, $policy->calculate_price( 20.0 ) );
	}

	public function test_price_replaces_base_and_starting_cycle_gates(): void {
		$policy = PricingPolicy::from_array(
			array(
				'policies' => array(
					array(
						'type'           => 'price',
						'value'          => 5,
						'starting_cycle' => 2,
					),
				),
			)
		);

		// Cycle 1 is before the rule's starting cycle, so the base price stands.
		$this->assertSame( 50.0, $policy->calculate_price( 50.0, 1 ) );
		// Cycle 2 onward the rule fires and replaces the price.
		$this->assertSame( 5.0, $policy->calculate_price( 50.0, 2 ) );
	}

	public function test_whole_number_values_normalize_to_float(): void {
		$policy = PricingPolicy::from_array(
			array(
				'policies'      => array(
					array(
						'type'  => 'percentage',
						'value' => 10,
					),
				),
				'one_time_fees' => array(
					array(
						'kind'    => 'enrollment',
						'amount'  => 15,
						'taxable' => true,
					),
				),
			)
		);

		$this->assertIsFloat( $policy->get_policies()[0]['value'] );
		$this->assertIsFloat( $policy->get_one_time_fees()[0]['amount'] );
	}

	public function test_fees_normalize_to_typed_shape(): void {
		$policy = PricingPolicy::from_array(
			array(
				'one_time_fees' => array(
					array(
						'kind'   => 'setup',
						'amount' => 5,
					),
					array(
						'kind'      => 'service',
						'amount'    => 7,
						'tax_class' => '',
					),
				),
			)
		);

		$fees = $policy->get_one_time_fees();

		// A fee without taxable/tax_class normalizes to taxable=false, tax_class=null.
		$this->assertFalse( $fees[0]['taxable'] );
		$this->assertNull( $fees[0]['tax_class'] );

		// A supplied empty-string tax_class is preserved (not coerced to null),
		// while a still-absent taxable normalizes to false.
		$this->assertFalse( $fees[1]['taxable'] );
		$this->assertSame( '', $fees[1]['tax_class'] );
	}

	/**
	 * @dataProvider provide_taxable_values
	 * @param mixed $supplied Raw taxable value as it might arrive from storage.
	 * @param bool  $expected Expected normalized boolean.
	 */
	public function test_taxable_is_interpreted_as_a_real_boolean( $supplied, bool $expected ): void {
		$policy = PricingPolicy::from_array(
			array(
				'one_time_fees' => array(
					array(
						'kind'    => 'setup',
						'amount'  => 5,
						'taxable' => $supplied,
					),
				),
			)
		);

		$this->assertSame( $expected, $policy->get_one_time_fees()[0]['taxable'] );
	}

	/**
	 * @return array<string, array{0: mixed, 1: bool}>
	 */
	public function provide_taxable_values(): array {
		return array(
			'bool true'    => array( true, true ),
			'bool false'   => array( false, false ),
			'string true'  => array( 'true', true ),
			'string false' => array( 'false', false ),
			'string one'   => array( '1', true ),
			'string zero'  => array( '0', false ),
			'unrecognized' => array( 'maybe', false ),
		);
	}
}
