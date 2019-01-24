<?php

/**
 * Test template funcitons.
 *
 * @package WooCommerce/Tests/Templates
 * @since   3.4.0
 */
class WC_Tests_Template_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_product_class().
	 *
	 * @covers ::wc_product_class()
	 * @covers ::wc_product_post_class()
	 * @covers ::wc_get_product_taxonomy_class()
	 * @since 3.4.0
	 */
	public function test_wc_get_product_class() {
		$category = wp_insert_term( 'Some Category', 'product_cat' );

		$product = new WC_Product_Simple();
		$product->set_virtual( true );
		$product->set_regular_price( '10' );
		$product->set_sale_price( '5' );
		$product->set_category_ids( array( $category['term_id'] ) );
		$product->save();

		$expected = array(
			'foo',
			'post-' . $product->get_id(),
			'product',
			'type-product',
			'status-publish',
			'product_cat-some-category',
			'first',
			'instock',
			'sale',
			'virtual',
			'purchasable',
			'product-type-simple',
		);

		$this->assertEquals( $expected, array_values( wc_get_product_class( 'foo', $product ) ) );

		// All taxonomies.
		add_filter( 'woocommerce_get_product_class_include_taxonomies', '__return_true' );
		$expected = array(
			'foo',
			'post-' . $product->get_id(),
			'product',
			'type-product',
			'status-publish',
			'product_cat-some-category',
			'instock',
			'sale',
			'virtual',
			'purchasable',
			'product-type-simple',
		);

		$this->assertEquals( $expected, array_values( wc_get_product_class( 'foo', $product ) ) );
		add_filter( 'woocommerce_get_product_class_include_taxonomies', '__return_false' );

		$product->delete( true );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	public function test_wc_dropdown_variation_attribute_options_no_attributes() {
		$this->expectOutputString( '<select id="" class="" name="attribute_" data-attribute_name="attribute_" data-show_option_none="yes"><option value="">Choose an option</option></select>' );

		wc_dropdown_variation_attribute_options();
	}

	public function test_wc_dropdown_variation_attribute_options_should_return_attributes_list() {
		$product = WC_Helper_Product::create_variation_product();

		$this->expectOutputString( '<select id="pa_size" class="" name="attribute_pa_size" data-attribute_name="attribute_pa_size" data-show_option_none="yes"><option value="">Choose an option</option><option value="large" >large</option><option value="small" >small</option></select>' );

		wc_dropdown_variation_attribute_options(
			array(
				'product' => $product,
				'attribute' => 'pa_size',
			)
		);
	}

	public function test_wc_dropdown_variation_attribute_options_should_return_attributes_list_and_selected_element() {
		$product = WC_Helper_Product::create_variation_product();
		$_REQUEST['attribute_pa_size'] = 'large';

		$this->expectOutputString( '<select id="pa_size" class="" name="attribute_pa_size" data-attribute_name="attribute_pa_size" data-show_option_none="yes"><option value="">Choose an option</option><option value="large"  selected=\'selected\'>large</option><option value="small" >small</option></select>' );

		wc_dropdown_variation_attribute_options(
			array(
				'product' => $product,
				'attribute' => 'pa_size',
			)
		);

		unset( $_REQUEST['attribute_pa_size'] );
	}
}
