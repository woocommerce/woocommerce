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

	/**
	 * Test wc_get_product_types()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_product_types() {
		$product_types = (array) apply_filters( 'product_type_selector', array(
			'simple'   => __( 'Simple product', 'woocommerce' ),
			'grouped'  => __( 'Grouped product', 'woocommerce' ),
			'external' => __( 'External/Affiliate product', 'woocommerce' ),
			'variable' => __( 'Variable product', 'woocommerce' )
		) );

		$this->assertEquals( $product_types, wc_get_product_types() );
	}

	/**
	 * Test wc_product_has_unique_sku()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_product_has_unique_sku() {
		$product_1 = WC_Helper_Product::create_simple_product();

		$this->assertEquals( true, wc_product_has_unique_sku( $product_1->id, $product_1->sku ) );

		$product_2 = WC_Helper_Product::create_simple_product();
		$this->assertEquals( false, wc_product_has_unique_sku( $product_2->id, $product_2->sku ) );

		WC_Helper_Product::delete_product( $product_1->id );

		$this->assertEquals( true, wc_product_has_unique_sku( $product_2->id, $product_2->sku ) );

		WC_Helper_Product::delete_product( $product_2->id );
	}

	/**
	 * Test wc_get_product_id_by_sku()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_product_id_by_sku() {
		$this->_get_product();

		$this->assertEquals( $this->_product->id, wc_get_product_id_by_sku( $this->_product->sku ) );

		$this->_delete_product();
	}
}
