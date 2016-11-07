<?php
/**
 * Data Store Tests
 * @package WooCommerce\Tests\Product
 * @since 2.7.0
 */
class WC_Tests_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Make sure WC_Data_Store returns an exception if we try to load a data
	 * store that doesn't exist.
	 *
	 * @since 2.7.0
	 */
	function test_invalid_store_throws_exception() {
		try {
			$product_store = new WC_Data_Store( 'bogus' );
		} catch ( Exception $e ) {
			$this->assertEquals( $e->getMessage(), 'Invalid data store.' );
			return;
		}
		$this->fail( 'Invalid data store exception not correctly raised.' );
	}

	/**
	 * Make sure ::load returns null if an invalid store is found.
	 *
	 * @since 2.7.0
	 */
	function test_invalid_store_load_returns_null() {
		$product_store = WC_Data_Store::load( 'product-test' );
		$this->assertNull( $product_store );
	}

	/**
	 * Make sure we can swap out stores.
	 *
	 * @since 2.7.0
	 */
	function test_store_swap() {
		$product_store = new WC_Data_Store( 'product' );
		$this->assertEquals( 'WC_Product_Data_Store', $product_store->get_current_class_name() );

		add_filter( 'woocommerce_product_data_store', array( $this, 'set_dummy_product_store' ) );

		$product_store = new WC_Data_Store( 'product' );
		$this->assertEquals( 'WC_Product_Data_Store_Dummy', $product_store->get_current_class_name() );

		add_filter( 'woocommerce_product_data_store', array( $this, 'set_default_product_store' ) );
	}

	/**
	 * Test to see if product_simple -> returns to product if unregistered.
	 *
	 * @since 2.7.0
	 */
	function test_store_sub_type() {
		$product_store = WC_Data_Store::load( 'product_simple' );
		$this->assertEquals( 'WC_Product_Data_Store_Posts', $product_store->get_current_class_name() );
	}

	/**
	 * Helper function/filter to swap out the default product store for a 'dummy' one.
	 *
	 * @since 2.7.0
	 */
	function set_dummy_product_store( $store ) {
		include( dirname( dirname( dirname( __FILE__ ) ) ) . '/framework/class-wc-product-data-store-dummy.php' );
		return 'WC_Product_Data_Store_Dummy';
	}

	/**
	 * Helper function/filter to swap out the 'dummy' store for the default one.
	 *
	 * @since 2.7.0
	 */
	function set_default_product_store( $store ) {
		return 'WC_Product_Data_Store_Posts';
	}
}
