<?php
/**
 * Test WC product simple class
 *
 * @since 2.3
 */
class WC_Tests_Product_Simple extends WC_Unit_Test_Case {
	/**
	 * @var object
	 * @access private
	 */
	private $_product = null;

	/**
	 * Helper method to get a product
	 *
	 * @since 2.3
	 * @access private
	 */
	private function _get_product() {
		$this->_product = WC_Helper_Product::create_simple_product();
	}

	/**
	 * Helper method to delete a product
	 *
	 * @since 2.3
	 * @access private
	 */
	private function _delete_product() {
		// Delete the previously created product
		WC_Helper_Product::delete_product( $this->_product->id );
		$this->_product = null;
	}

	/**
	 * Clear out notices after each test
	 *
	 * @since 2.3
	 */
	public function tearDown() {

		remove_all_filters( 'woocommerce_product_add_to_cart_text' );
		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * Test add_to_cart_text()
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_text() {
		$this->_get_product();

		$this->assertEquals( __( 'Add to cart', 'woocommerce' ), $this->_product->add_to_cart_text() );

		$this->_product->stock_status = 'outofstock';
		$this->assertEquals( __( 'Read More', 'woocommerce' ), $this->_product->add_to_cart_text() );

		$this->_delete_product();
	}

	/**
	 * Test get_title()
	 *
	 * @since 2.3
	 */
	public function test_get_title() {
		$this->_get_product();

		$this->assertEquals( 'Dummy Product', $this->_product->get_title() );
	}
}
