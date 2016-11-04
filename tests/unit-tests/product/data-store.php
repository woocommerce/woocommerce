<?php
/**
 * Products Data Store Tests
 * @package WooCommerce\Tests\Product
 * @since 2.7.0
 */
class WC_Tests_Product_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Make sure the default product store loads.
	 *
	 * @since 2.7.0
	 */
	function test_product_store_loads() {
		$product_store = new WC_Data_Store( 'product' );
		$this->assertTrue( is_callable( array( $product_store, 'read' ) ) );
		$this->assertEquals( 'WC_Product_Data_Store_Posts', $product_store->get_current_class_name() );
	}

	/**
	 * Test product store read for a simple product.
	 *
	 * @since 2.7.0
	 */
	function test_product_store_read_simple() {
		$simple        = WC_Helper_Product::create_simple_product();
		$product       = new WC_Product_Simple( $simple->get_id() );

		$this->assertEquals( 'Dummy Product', $product->get_name() );
		$this->assertEquals( '10', $product->get_regular_price() );
	}

	/**
	 * Test product store read for an external product.
	 *
	 * @since 2.7.0
	 */
	function test_product_store_read_external() {
		$external      = WC_Helper_Product::create_external_product();
		$product       = new WC_Product_External( $external->get_id() );

		$this->assertEquals( 'Dummy External Product', $product->get_name() );
		$this->assertEquals( '10', $product->get_regular_price() );
		$this->assertEquals( 'http://woocommerce.com', $product->get_product_url() );
		$this->assertEquals( 'Buy external product', $product->get_button_text() );
	}


}
