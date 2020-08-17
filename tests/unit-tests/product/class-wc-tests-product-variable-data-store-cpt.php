<?php
/**
 * Data Store Tests for variable products: WC_Product_Variable_Data_Store.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class WC_Tests_Product_Variable_Data_Store
 */
class WC_Tests_Product_Variable_Data_Store extends WC_Unit_Test_Case {

	/**
	 * @testdox Test that "delete" on a variation removes the associated attribute terms too.
	 */
	public function test_attribute_terms_are_deleted_for_deleted_variations() {
		$product      = WC_Helper_Product::create_variation_product();
		$variation    = wc_get_product( $product->get_children()[2] );
		$variation_id = $variation->get_id();

		$sut = new WC_Product_Variable_Data_Store_CPT();
		$sut->delete_variations( $product->get_id(), true );

		$attribute_names           = wc_get_attribute_taxonomy_names();
		$variation_attribute_terms = wp_get_post_terms( $variation_id, $attribute_names );

		$this->assertEmpty( $variation_attribute_terms );
	}
}
