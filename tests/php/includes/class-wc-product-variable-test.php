<?php

/**
 * Tests for WC_Product_Variable.
 */
class WC_Product_Variable_Test extends \WC_Unit_Test_Case {
	/**
	 * @testdox 'get_available_variations' returns the variations as arrays if no parameters is passed.
	 */
	public function test_get_available_variations_returns_array_when_no_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations();

		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
	}

	/**
	 * @testdox 'get_available_variations' returns the variations as arrays if the parameter passed is 'array'.
	 */
	public function test_get_available_variations_returns_array_when_array_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'array' );

		$this->assertTrue( is_array( $variations[0] ) );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]['sku'] );
	}

	/**
	 * @testdox 'get_available_variations' returns the variations as objects if the parameter passed is 'objects'.
	 */
	public function test_get_available_variations_returns_object_when_objects_parameter_is_passed() {
		$product = WC_Helper_Product::create_variation_product();

		$variations = $product->get_available_variations( 'objects' );

		$this->assertInstanceOf( WC_Product_Variation::class, $variations[0] );
		$this->assertEquals( 'DUMMY SKU VARIABLE SMALL', $variations[0]->get_sku() );
	}

	/**
	 * @testdox 'save' adds empty 'attribute_' meta values for any newly added variation attribute, and removes the existing meta values for removed attributes.
	 */
	public function test_save_updates_meta_for_added_and_removed_variation_attributes() {
		$product = WC_Helper_Product::create_variation_product();

		$attributes             = array_values( $product->get_attributes() );
		$removed_attribute_name = $attributes[0]->get_name();
		$attributes[0]          = WC_Helper_Product::create_product_attribute_object( 'foobar', array( 'foo', 'bar' ) );

		$product->set_attributes( $attributes );
		$product->save();

		$variation_ids = $product->get_children();
		foreach ( $variation_ids as $variation_id ) {
			// There's an empty attribute value for the added attribute...
			$this->assertEquals( array( '' ), get_post_meta( $variation_id, 'attribute_pa_foobar' ) );
			// ...and the attribute value for the removed attribute has been removed as well.
			$this->assertEquals( array(), get_post_meta( $variation_id, 'attribute_' . $removed_attribute_name ) );
		}
	}
}
