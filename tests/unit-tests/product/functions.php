<?php

namespace WooCommerce\Tests\Product;

/**
 * Class Functions
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class Functions extends \WC_Unit_Test_Case {

	/**
	 * Test wc_get_product()
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product() {

		// Create product
		$product = \WC_Helper_Product::create_simple_product();

		$product_copy = wc_get_product( $product->id );

		$this->assertEquals( $product->id, $product_copy->id );

		// Delete Product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_update_product_stock()
	 *
	 * @since 2.3
	 */
	public function test_wc_update_product_stock() {
		// Create product 
		$product = \WC_Helper_Product::create_simple_product();

		update_post_meta( $product->id, '_manage_stock', 'yes' );

		wc_update_product_stock( $product->id, 5 );
		$this->assertEquals( 5, $product->stock );

		// Delete Product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_get_product_types()
	 *
	 * @since 2.3
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
	 * @since 2.3
	 */
	public function test_wc_product_has_unique_sku() {
		$product_1 = \WC_Helper_Product::create_simple_product();

		$this->assertEquals( true, wc_product_has_unique_sku( $product_1->id, $product_1->sku ) );

		$product_2 = \WC_Helper_Product::create_simple_product();
		$this->assertEquals( false, wc_product_has_unique_sku( $product_2->id, $product_2->sku ) );

		\WC_Helper_Product::delete_product( $product_1->id );

		$this->assertEquals( true, wc_product_has_unique_sku( $product_2->id, $product_2->sku ) );

		\WC_Helper_Product::delete_product( $product_2->id );
	}

	/**
	 * Test wc_get_product_id_by_sku()
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_id_by_sku() {
		// Create product 
		$product = \WC_Helper_Product::create_simple_product();

		$this->assertEquals( $product->id, wc_get_product_id_by_sku( $product->sku ) );

		// Delete Product
		\WC_Helper_Product::delete_product( $product->id );
	}
}
