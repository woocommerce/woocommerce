<?php
/**
 * Tests for the WC_Product_Variation class.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class Product_Variation.
 *
 * @since 3.0
 */
class WC_Tests_Product_Variation extends WC_Unit_Test_Case {

	/**
	 * Test is_sold_individually().
	 *
	 * @since 2.3
	 */
	public function test_is_sold_individually() {
		// Create a variable product with sold individually.
		$product = new WC_Product_Variable();
		$product->set_sold_individually( true );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->save();

		$variation = wc_get_product( $variation->get_id() );
		$this->assertTrue( $variation->is_sold_individually() );
	}

	/**
	 * Check get_tax_class against different parent child scenarios
	 */
	public function test_get_tax_class() {
		$product = new WC_Product_Variable();
		$product->set_tax_class( 'standard' );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_tax_class( 'parent' );
		$variation->save();

		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( '', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( 'parent', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( '', $variation->get_tax_class( 'view' ) );

		$variation->set_tax_class( 'standard' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( '', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( '', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( '', $variation->get_tax_class( 'view' ) );

		$variation->set_tax_class( 'reduced-rate' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( 'reduced-rate', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( 'reduced-rate', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( 'reduced-rate', $variation->get_tax_class( 'view' ) );

		$product->set_tax_class( 'zero-rate' );
		$product->save();

		$variation->set_tax_class( 'parent' );
		$variation->save();
		$variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( 'zero-rate', $variation->get_tax_class( 'unfiltered' ) );
		$this->assertEquals( 'parent', $variation->get_tax_class( 'edit' ) );
		$this->assertEquals( 'zero-rate', $variation->get_tax_class( 'view' ) );
	}

	/**
	 * Test that WC_Product_Variation throws an exception
	 * when called with a product ID that belongs to a product
	 * of a different type.
	 *
	 * Ticket: https://github.com/woocommerce/woocommerce/issues/24956
	 */
	public function test_product_variation_should_throw_exception_when_instantiated_with_invalid_id() {
		$this->expectExceptionMessage( 'Invalid product type: passed ID does not correspond to a product variation.' );

		$variable_product = WC_Helper_Product::create_variation_product();
		new WC_Product_Variation( $variable_product->get_id() );
	}

	/**
	 * @testdox Test that get_variation_attributes returns the appropriate values.
	 *
	 * @param bool   $with_prefix Parameter for get_variation_attributes.
	 * @param string $expected_prefix Expected prefix on the returned attribute names.
	 *
	 * @testWith [true, "attribute_"]
	 *           [false, ""]
	 */
	public function test_get_variation_attributes( $with_prefix, $expected_prefix ) {
		$product = WC_Helper_Product::create_variation_product();
		$sut     = wc_get_product( $product->get_children()[2] );

		$expected = array(
			$expected_prefix . 'pa_size'   => 'huge',
			$expected_prefix . 'pa_colour' => 'red',
			$expected_prefix . 'pa_number' => '0',
		);

		$actual = $sut->get_variation_attributes( $with_prefix );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @testdox Test that the delete method removes the attribute terms for the variation.
	 */
	public function test_delete_removes_attribute_terms() {
		$product = WC_Helper_Product::create_variation_product();
		$sut     = wc_get_product( $product->get_children()[2] );
		$id      = $sut->get_id();

		$sut->delete( true );

		$attribute_names           = wc_get_attribute_taxonomy_names();
		$variation_attribute_terms = wp_get_post_terms( $id, $attribute_names );

		$this->assertEmpty( $variation_attribute_terms );
	}
}
