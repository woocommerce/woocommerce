<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Internal\Tax\LocalPickupTaxRule;

/**
 * Tests for the LocalPickupTaxRule class.
 */
class LocalPickupTaxRuleTest extends \WC_Unit_Test_Case {

	/**
	 * @testdox Applies only when a chosen shipping method is a local pickup method.
	 *
	 * @testWith [["local_pickup"], true]
	 *           [["legacy_local_pickup"], true]
	 *           [["flat_rate", "local_pickup"], true]
	 *           [["flat_rate"], false]
	 *           [[], false]
	 *
	 * @param string[] $shipping_method_ids Chosen shipping method IDs.
	 * @param bool     $expected            Whether the rule applies.
	 */
	public function test_applies_only_to_local_pickup_methods( array $shipping_method_ids, bool $expected ): void {
		$this->assertSame( $expected, LocalPickupTaxRule::applies_to_shipping_methods( $shipping_method_ids ) );
	}

	/**
	 * @testdox Does not apply when base tax for local pickup is disabled.
	 */
	public function test_does_not_apply_when_base_tax_is_disabled(): void {
		add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );

		$this->assertFalse( LocalPickupTaxRule::applies_to_shipping_methods( array( 'local_pickup' ) ) );
	}

	/**
	 * @testdox Applies to methods added through the woocommerce_local_pickup_methods filter.
	 */
	public function test_applies_to_filtered_local_pickup_methods(): void {
		add_filter(
			'woocommerce_local_pickup_methods',
			function ( $methods ) {
				$methods[] = 'store_collection';
				return $methods;
			}
		);

		$this->assertTrue( LocalPickupTaxRule::applies_to_shipping_methods( array( 'store_collection' ) ) );
	}

	/**
	 * @testdox Does not apply when the woocommerce_local_pickup_methods filter returns an invalid value.
	 *
	 * @testWith ["local_pickup"]
	 *           [null]
	 *
	 * @param mixed $filtered_value Value returned by the filter.
	 */
	public function test_does_not_apply_for_invalid_filtered_methods( $filtered_value ): void {
		add_filter(
			'woocommerce_local_pickup_methods',
			function () use ( $filtered_value ) {
				return $filtered_value;
			}
		);

		$this->assertFalse( LocalPickupTaxRule::applies_to_shipping_methods( array( 'local_pickup' ) ) );
	}
}
