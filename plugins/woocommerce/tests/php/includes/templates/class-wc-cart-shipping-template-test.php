<?php
/**
 * Tests for the cart shipping template.
 */

declare( strict_types = 1 );

/**
 * Cart shipping template test.
 */
class WC_Cart_Shipping_Template_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Shipping method labels target the rendered radio input when a package has a non-numeric key.
	 */
	public function test_shipping_method_label_targets_normalized_package_index(): void {
		$flat_rate     = new WC_Shipping_Rate( 'flat_rate:1', 'Flat rate', 0, array(), 'flat_rate', 1 );
		$free_shipping = new WC_Shipping_Rate( 'free_shipping:2', 'Free shipping', 0, array(), 'free_shipping', 2 );

		$markup = $this->capture_output_from(
			'wc_get_template',
			'cart/cart-shipping.php',
			array(
				'package'                  => array( 'destination' => array() ),
				'available_methods'        => array( $flat_rate, $free_shipping ),
				'show_package_details'     => false,
				'show_shipping_calculator' => false,
				'package_details'          => '',
				'package_name'             => 'Package',
				'index'                    => 'vendor-package',
				'chosen_method'            => $flat_rate->get_id(),
				'formatted_destination'    => '',
				'has_calculated_shipping'  => true,
			)
		);

		$this->assertStringContainsString( 'id="shipping_method_0_flat_rate1"', $markup, 'The package index should be normalized in the input ID.' );
		$this->assertStringContainsString( 'for="shipping_method_0_flat_rate1"', $markup, 'The label should target the normalized input ID.' );
		$this->assertStringNotContainsString( 'vendor-package', $markup, 'The raw package key should not be rendered in an attribute.' );
	}
}
