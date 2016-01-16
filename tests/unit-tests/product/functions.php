<?php

namespace WooCommerce\Tests\Product;

/**
 * Class Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class Functions extends \WC_Unit_Test_Case {

	/**
	 * Test wc_get_product().
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
	 * Test wc_update_product_stock().
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
	 * Test wc_delete_product_transients().
	 *
	 * @since 2.4
	 */
	public function test_wc_delete_product_transients() {
		// Create product
		$product = \WC_Helper_Product::create_simple_product();

		update_post_meta( $product->id, '_regular_price', wc_format_decimal( 10 ) );
		update_post_meta( $product->id, '_price', wc_format_decimal( 5 ) );
		update_post_meta( $product->id, '_sale_price', wc_format_decimal( 5 ) );
		update_post_meta( $product->id, '_featured', 'yes' );

		wc_get_product_ids_on_sale();  // Creates the transient for on sale products
		wc_get_featured_product_ids(); // Creates the transient for featured products

		wc_delete_product_transients();

		$this->assertFalse( get_transient( 'wc_products_onsale' ) );
		$this->assertFalse( get_transient( 'wc_featured_products' ) );

		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_get_product_ids_on_sale().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_product_ids_on_sale() {
		$this->assertEquals( array(), wc_get_product_ids_on_sale() );

		delete_transient( 'wc_products_onsale' );

		// Create product
		$product = \WC_Helper_Product::create_simple_product();

		update_post_meta( $product->id, '_regular_price', wc_format_decimal( 10 ) );
		update_post_meta( $product->id, '_price', wc_format_decimal( 5 ) );
		update_post_meta( $product->id, '_sale_price', wc_format_decimal( 5 ) );

		$this->assertEquals( array( $product->id ), wc_get_product_ids_on_sale() );

		// Delete Product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_get_featured_product_ids().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_featured_product_ids() {
		$this->assertEquals( array(), wc_get_featured_product_ids() );

		delete_transient( 'wc_featured_products' );

		// Create product
		$product = \WC_Helper_Product::create_simple_product();

		update_post_meta( $product->id, '_featured', 'yes' );

		$this->assertEquals( array( $product->id ), wc_get_featured_product_ids() );

		// Delete Product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_placeholder_img().
	 *
	 * @since 2.4
	 */
	public function test_wc_placeholder_img() {
		$sizes = array(
			'shop_thumbnail' => array( 'width' => '180', 'height' => '180' ),
			'shop_single'    => array( 'width' => '600', 'height' => '600' ),
			'shop_catalog'   => array( 'width' => '300', 'height' => '300' )
		);

		foreach ( $sizes as $size => $values ) {
			$img = '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr__( 'Placeholder', 'woocommerce' ) . '" width="' . $values['width'] . '" class="woocommerce-placeholder wp-post-image" height="' . $values['height'] . '" />';
			$this->assertEquals( apply_filters( 'woocommerce_placeholder_img', $img ), wc_placeholder_img( $size ) );
		}

		$img = '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr__( 'Placeholder', 'woocommerce' ) . '" width="180" class="woocommerce-placeholder wp-post-image" height="180" />';
		$this->assertEquals( apply_filters( 'woocommerce_placeholder_img', $img ), wc_placeholder_img() );
	}

	/**
	 * Test wc_get_product_types().
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
	 * Test wc_product_has_unique_sku().
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
	 * Test wc_get_product_id_by_sku().
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
