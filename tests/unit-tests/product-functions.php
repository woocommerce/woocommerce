<?php
/**
 * Test WC product functions
 *
 * @since 2.3.0
 */
class WC_Tests_Product_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_product()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_product() {
		$product      = WC_Helper_Product::create_simple_product();
		$product_copy = wc_get_product( $product->id );

		$this->assertEquals( $product->id, $product_copy->id );
	}

	/**
	 * Test wc_update_product_stock()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_update_product_stock() {
		$product = WC_Helper_Product::create_simple_product();

		update_post_meta( $product->id, '_manage_stock', 'yes' );

		wc_update_product_stock( $product->id, 5 );
		$this->assertEquals( 5, $product->stock );

		update_post_meta( $product->id, '_manage_stock', 'no' );
	}
}
