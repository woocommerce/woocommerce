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
}
