<?php
/**
 * Test template funcitons.
 *
 * @package WooCommerce/Tests/Templates
 * @since   3.4.0
 */

/**
 * WC_Tests_Template_Functions class.
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

		$product  = wc_get_product( $product ); // Reload so status is current.
		$expected = array(
			'foo',
			'product',
			'type-product',
			'post-' . $product->get_id(),
			'status-publish',
			'first',
			'instock',
			'product_cat-some-category',
			'sale',
			'virtual',
			'purchasable',
			'product-type-simple',
		);
		$actual   = array_values( wc_get_product_class( 'foo', $product ) );

		$this->assertEquals( $expected, $actual, print_r( $actual, true ) );

		// All taxonomies.
		add_filter( 'woocommerce_get_product_class_include_taxonomies', '__return_true' );
		$expected = array(
			'foo',
			'product',
			'type-product',
			'post-' . $product->get_id(),
			'status-publish',
			'instock',
			'product_cat-some-category',
			'sale',
			'virtual',
			'purchasable',
			'product-type-simple',
		);
		$actual   = array_values( wc_get_product_class( 'foo', $product ) );

		$this->assertEquals( $expected, $actual, print_r( $actual, true ) );
		add_filter( 'woocommerce_get_product_class_include_taxonomies', '__return_false' );

		$product->delete( true );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * Test: test_wc_dropdown_variation_attribute_options_no_attributes.
	 */
	public function test_wc_dropdown_variation_attribute_options_no_attributes() {
		$this->expectOutputString( '<select id="" class="" name="attribute_" data-attribute_name="attribute_" data-show_option_none="yes"><option value="">Choose an option</option></select>' );

		wc_dropdown_variation_attribute_options();
	}

	/**
	 * Test: test_wc_dropdown_variation_attribute_options_should_return_attributes_list.
	 */
	public function test_wc_dropdown_variation_attribute_options_should_return_attributes_list() {
		$product = WC_Helper_Product::create_variation_product();

		$this->expectOutputString( '<select id="pa_size" class="" name="attribute_pa_size" data-attribute_name="attribute_pa_size" data-show_option_none="yes"><option value="">Choose an option</option><option value="large" >large</option><option value="small" >small</option></select>' );

		wc_dropdown_variation_attribute_options(
			array(
				'product'   => $product,
				'attribute' => 'pa_size',
			)
		);
	}

	/**
	 * Test: test_wc_dropdown_variation_attribute_options_should_return_attributes_list_and_selected_element.
	 */
	public function test_wc_dropdown_variation_attribute_options_should_return_attributes_list_and_selected_element() {
		$product                       = WC_Helper_Product::create_variation_product();
		$_REQUEST['attribute_pa_size'] = 'large';

		$this->expectOutputString( '<select id="pa_size" class="" name="attribute_pa_size" data-attribute_name="attribute_pa_size" data-show_option_none="yes"><option value="">Choose an option</option><option value="large"  selected=\'selected\'>large</option><option value="small" >small</option></select>' );

		wc_dropdown_variation_attribute_options(
			array(
				'product'   => $product,
				'attribute' => 'pa_size',
			)
		);

		unset( $_REQUEST['attribute_pa_size'] );
	}

	/**
	 * Test wc_query_string_form_fields.
	 *
	 * @return void
	 */
	public function test_wc_query_string_form_fields() {
		$actual_html   = wc_query_string_form_fields( '?test=1', array(), '', true );
		$expected_html = '<input type="hidden" name="test" value="1" />';
		$this->assertEquals( $expected_html, $actual_html );

		$actual_html   = wc_query_string_form_fields( '?test=1&test2=something', array(), '', true );
		$expected_html = '<input type="hidden" name="test" value="1" /><input type="hidden" name="test2" value="something" />';
		$this->assertEquals( $expected_html, $actual_html );

		$actual_html   = wc_query_string_form_fields( '?test.something=something.else', array(), '', true );
		$expected_html = '<input type="hidden" name="test.something" value="something.else" />';
		$this->assertEquals( $expected_html, $actual_html );

		$actual_html   = wc_query_string_form_fields( '?test+something=something+else', array(), '', true );
		$expected_html = '<input type="hidden" name="test+something" value="something+else" />';
		$this->assertEquals( $expected_html, $actual_html );

		$actual_html   = wc_query_string_form_fields( '?test%20something=something%20else', array(), '', true );
		$expected_html = '<input type="hidden" name="test%20something" value="something%20else" />';
		$this->assertEquals( $expected_html, $actual_html );
	}
}
