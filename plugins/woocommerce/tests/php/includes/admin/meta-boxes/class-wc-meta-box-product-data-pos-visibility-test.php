<?php
declare( strict_types = 1 );

/**
 * Tests for POS visibility support based on product type.
 *
 * @package WooCommerce\Tests\Admin\MetaBoxes
 */

/**
 * Class WC_Meta_Box_Product_Data_POS_Visibility_Test
 *
 * Tests to verify which product types are supported in Point of Sale.
 * POS supports only Simple and Variable non-downloadable products.
 */
class WC_Meta_Box_Product_Data_POS_Visibility_Test extends WC_Unit_Test_Case {

	/**
	 * Helper function to check if a product is supported in POS.
	 * This mirrors the logic in html-product-data-advanced.php.
	 *
	 * @param WC_Product $product The product to check.
	 * @return bool True if supported in POS, false otherwise.
	 */
	private function is_product_supported_in_pos( $product ) {
		return $product->is_type( array( 'simple', 'variable' ) ) && ! $product->is_downloadable();
	}

	/**
	 * Test that simple non-downloadable products are supported in POS.
	 */
	public function test_simple_non_downloadable_product_is_supported_in_pos() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'downloadable' => false ) );

		$this->assertTrue(
			$this->is_product_supported_in_pos( $product ),
			'Simple non-downloadable products should be supported in POS.'
		);
	}

	/**
	 * Test that variable non-downloadable products are supported in POS.
	 */
	public function test_variable_non_downloadable_product_is_supported_in_pos() {
		$product = WC_Helper_Product::create_variation_product();

		$this->assertTrue(
			$this->is_product_supported_in_pos( $product ),
			'Variable non-downloadable products should be supported in POS.'
		);
	}

	/**
	 * Test that downloadable simple products are not supported in POS.
	 */
	public function test_downloadable_simple_product_is_not_supported_in_pos() {
		$product = WC_Helper_Product::create_simple_product( true, array( 'downloadable' => true ) );

		$this->assertFalse(
			$this->is_product_supported_in_pos( $product ),
			'Downloadable simple products should not be supported in POS.'
		);
	}

	/**
	 * Test that grouped products are not supported in POS.
	 */
	public function test_grouped_product_is_not_supported_in_pos() {
		$product = WC_Helper_Product::create_grouped_product();

		$this->assertFalse(
			$this->is_product_supported_in_pos( $product ),
			'Grouped products should not be supported in POS.'
		);
	}

	/**
	 * Test that external products are not supported in POS.
	 */
	public function test_external_product_is_not_supported_in_pos() {
		$product = WC_Helper_Product::create_external_product();

		$this->assertFalse(
			$this->is_product_supported_in_pos( $product ),
			'External products should not be supported in POS.'
		);
	}
}
