<?php
/**
 * Data Store Tests for product variations: WC_Product_Variation_Data_Store.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class WC_Tests_Product_Variation_Data_Store
 */
class WC_Tests_Product_Variation_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Create and save a variable product with size and category attributes, then create a corresponding
	 * variation object with size "small" and color "red", and return it without saving it to database.
	 *
	 * @return WC_Product_Variation The created variation object.
	 */
	private function create_variation_object_for_existing_variable_product() {
		$attr_size  = WC_Helper_Product::create_product_attribute_object( 'size', array( 'small', 'large' ) );
		$attr_color = WC_Helper_Product::create_product_attribute_object( 'color', array( 'red', 'blue' ) );

		$product = new WC_Product_Variable();
		$product->set_attributes( array( $attr_size, $attr_color ) );
		$product->save();

		$variation = WC_Helper_Product::create_product_variation_object(
			$product->get_id(),
			'SMALL RED THING',
			10,
			array(
				'pa_size'  => 'small',
				'pa_color' => 'red',
			)
		);

		return $variation;
	}

	/**
	 * Return a simplified list with the attribute terms for a variation object.
	 *
	 * @param int $variation_id Id of the variation product.
	 *
	 * @return array Attributes as an "attribute"=>"term" associative array.
	 */
	private function get_attribute_terms_for_variation( $variation_id ) {
		$attribute_names           = wc_get_attribute_taxonomy_names();
		$variation_attribute_terms = wp_get_post_terms( $variation_id, $attribute_names );
		$terms                     = array();
		foreach ( $variation_attribute_terms as $term ) {
			$terms[ $term->taxonomy ] = $term->name;
		}
		return $terms;
	}

	/**
	 * @testdox Test that attribute terms are created for new variations.
	 */
	public function test_attribute_terms_are_created_for_new_variations() {
		$variation = $this->create_variation_object_for_existing_variable_product();

		$sut = new WC_Product_Variation_Data_Store_CPT();
		$sut->create( $variation );

		$terms = $this->get_attribute_terms_for_variation( $variation->get_id() );

		$expected = array(
			'pa_size'  => 'small',
			'pa_color' => 'red',
		);

		$this->assertEquals( $expected, $terms );

		$variation->set_attributes(
			array(
				'pa_size'  => 'large',
				'pa_color' => 'blue',
			)
		);

		$sut->update( $variation );

		$terms = $this->get_attribute_terms_for_variation( $variation->get_id() );

		$expected = array(
			'pa_size'  => 'large',
			'pa_color' => 'blue',
		);

		$this->assertEquals( $expected, $terms );
	}

	/**
	 * @testdox Test that attribute terms are updated for updated variations.
	 */
	public function test_attribute_terms_are_updated_for_modified_variations() {
		$variation = $this->create_variation_object_for_existing_variable_product();

		$sut = new WC_Product_Variation_Data_Store_CPT();
		$sut->create( $variation );

		$new_attributes = array(
			'pa_size'  => 'small',
			'pa_color' => 'red',
		);
		$variation->set_attributes( $new_attributes );
		$sut->update( $variation );

		$terms = $this->get_attribute_terms_for_variation( $variation->get_id() );

		$this->assertEquals( $new_attributes, $terms );
	}

	/**
	 * @testdox Test that attribute terms are removed for variations updated with "Any" value.
	 */
	public function test_attribute_terms_are_removed_for_variations_set_to_any_attribute_value() {
		$variation = $this->create_variation_object_for_existing_variable_product();

		$sut = new WC_Product_Variation_Data_Store_CPT();
		$sut->create( $variation );

		$new_attributes = array(
			'pa_size'  => 'small',
			'pa_color' => '',
		);
		$variation->set_attributes( $new_attributes );
		$sut->update( $variation );

		$terms = $this->get_attribute_terms_for_variation( $variation->get_id() );

		$expected = array( 'pa_size' => 'small' );

		$this->assertEquals( $expected, $terms );
	}
}
