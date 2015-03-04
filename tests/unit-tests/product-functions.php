<?php
/**
 * Test WC product functions
 *
 * @since 2.3.0
 */
class WC_Tests_Product_Functions extends WC_Unit_Test_Case {
	/**
	 * @var object
	 * @access private
	 */
	private $_product = null;

	/**
	 * Helper method to get a product
	 *
	 * @since 2.3.0
	 * @access private
	 */
	private function _get_product() {
		$this->_product = WC_Helper_Product::create_simple_product();
	}

	/**
	 * Helper method to delete a product
	 *
	 * @since 2.3.0
	 * @access private
	 */
	private function _delete_product() {
		// Delete the previously created product
		WC_Helper_Product::delete_product( $this->_product->id );
		$this->_product = null;
	}

	/**
	 * Test wc_get_product()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_product() {
		$this->_get_product();
		$product_copy = wc_get_product( $this->_product->id );

		$this->assertEquals( $this->_product->id, $product_copy->id );

		$this->_delete_product();
	}

	/**
	 * Test wc_update_product_stock()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_update_product_stock() {
		$this->_get_product();

		update_post_meta( $this->_product->id, '_manage_stock', 'yes' );

		wc_update_product_stock( $this->_product->id, 5 );
		$this->assertEquals( 5, $this->_product->stock );

		$this->_delete_product();
	}
}
