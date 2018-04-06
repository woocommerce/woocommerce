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
	 * @covers wc_product_class()
	 * @covers wc_product_post_class()
	 * @since 2.3.0
	 */
	public function test_wc_get_product_class() {
		$product = new WC_Product_Simple();
		$product->set_virtual( true );
		$product->set_regular_price( '10' );
		$product->set_sale_price( '5' );
		$product->save();

		$expected = array(
			'foo',
			'post-' . $product->get_id(),
			'product',
			'type-product',
			'status-publish',
			'first',
			'instock',
			'sale',
			'virtual',
			'purchasable',
			'product-type-simple',
		);

		$this->assertEquals( $expected, array_values( wc_get_product_class( 'foo', $product ) ) );
	}
}
