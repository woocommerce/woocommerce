<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class WC_Tests_Product_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests wc_get_products().
	 *
	 * @since 3.0.0
	 */
	public function test_wc_get_products() {
		$test_cat_1 = wp_insert_term( 'Testing 1', 'product_cat' );
		$test_tag_1 = wp_insert_term( 'Tag 1', 'product_tag' );
		$test_tag_2 = wp_insert_term( 'Tag 2', 'product_tag' );
		$term_cat_1 = get_term_by( 'id', $test_cat_1['term_id'], 'product_cat' );
		$term_tag_1 = get_term_by( 'id', $test_tag_1['term_id'], 'product_tag' );
		$term_tag_2 = get_term_by( 'id', $test_tag_2['term_id'], 'product_tag' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_tag_ids( array( $test_tag_1['term_id'] ) );
		$product->set_category_ids( array( $test_cat_1['term_id'] ) );
		$product->set_sku( 'GET TEST SKU SIMPLE' );
		$product->save();

		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_category_ids( array( $test_cat_1['term_id'] ) );
		$product_2->save();

		$external = WC_Helper_Product::create_simple_product();
		$external->set_category_ids( array( $test_cat_1['term_id'] ) );
		$external->set_sku( 'GET TEST SKU EXTERNAL' );
		$external->save();

		$external_2 = WC_Helper_Product::create_simple_product();
		$external_2->set_tag_ids( array( $test_tag_2['term_id'] ) );
		$external_2->save();

		$grouped = WC_Helper_Product::create_grouped_product();

		$variation = WC_Helper_Product::create_variation_product();
		$variation->set_tag_ids( array( $test_tag_1['term_id'] ) );
		$variation->save();

		$draft = WC_Helper_Product::create_simple_product();
		$draft->set_status( 'draft' );
		$draft->save();

		$this->assertEquals( 9, count( wc_get_products( array( 'return' => 'ids' ) ) ) );

		// test status
		$products = wc_get_products( array( 'return' => 'ids', 'status' => 'draft' ) );
		$this->assertEquals( array( $draft->get_id() ), $products );

		// test type
		$products = wc_get_products( array( 'return' => 'ids', 'type' => 'variation' ) );
		$this->assertEquals( 2, count( $products ) );

		// test parent
		$products = wc_get_products( array( 'return' => 'ids', 'type' => 'variation', 'parent' => $variation->get_id() ) );
		$this->assertEquals( 2, count( $products ) );

		// test skus
		$products = wc_get_products( array( 'return' => 'ids', 'sku' => 'GET TEST SKU' ) );
		$this->assertEquals( 2, count( $products ) );
		$this->assertContains( $product->get_id(), $products );
		$this->assertContains( $external->get_id(), $products );

		// test categories
		$products = wc_get_products( array( 'return' => 'ids', 'category' => array( $term_cat_1->slug ) ) );
		$this->assertEquals( 3, count( $products ) );

		// test tags
		$products = wc_get_products( array( 'return' => 'ids', 'tag' => array( $term_tag_1->slug ) ) );
		$this->assertEquals( 2, count( $products ) );

		$products = wc_get_products( array( 'return' => 'ids', 'tag' => array( $term_tag_2->slug ) ) );
		$this->assertEquals( 1, count( $products ) );

		$products = wc_get_products( array( 'return' => 'ids', 'tag' => array( $term_tag_1->slug, $term_tag_2->slug ) ) );
		$this->assertEquals( 3, count( $products ) );

		// test limit
		$products = wc_get_products( array( 'return' => 'ids', 'limit' => 5 ) );
		$this->assertEquals( 5, count( $products ) );

		// test offset
		$products = wc_get_products( array( 'return' => 'ids', 'limit' => 2 ) );
		$products_offset = wc_get_products( array( 'return' => 'ids', 'limit' => 2, 'offset' => 2 ) );
		$this->assertEquals( 2, count( $products ) );
		$this->assertEquals( 2, count( $products_offset ) );
		$this->assertNotEquals( $products, $products_offset );

		// test page
		$products_page_1 = wc_get_products( array( 'return' => 'ids', 'limit' => 2 ) );
		$products_page_2 = wc_get_products( array( 'return' => 'ids', 'limit' => 2, 'page' => 2 ) );
		$this->assertEquals( 2, count( $products_page_1 ) );
		$this->assertEquals( 2, count( $products_page_2 ) );
		$this->assertNotEquals( $products_page_1, $products_page_2 );

		// test exclude
		$products = wc_get_products( array( 'return' => 'ids', 'limit' => 200, 'exclude' => array( $product->get_id() ) ) );
		$this->assertNotContains( $product->get_id(), $products );

		$variation->delete( true );
	}

	/**
	 * Test wc_get_product().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product() {

		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product_copy = wc_get_product( $product->get_id() );

		$this->assertEquals( $product->get_id(), $product_copy->get_id() );

		// Delete Product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test wc_update_product_stock().
	 *
	 * @since 2.3
	 */
	public function test_wc_update_product_stock() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		update_post_meta( $product->get_id(), '_manage_stock', 'yes' );
		wc_update_product_stock( $product->get_id(), 5 );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 5, $product->get_stock_quantity() );

		// Delete Product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	public function test_wc_update_product_stock_increase_decrease() {
		$product = WC_Helper_Product::create_simple_product();

		update_post_meta( $product->get_id(), '_manage_stock', 'yes' );
		wc_update_product_stock( $product->get_id(), 5 );

		$new_value = wc_update_product_stock( $product->get_id(), 1, 'increase' );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 6, $product->get_stock_quantity() );
		$this->assertEquals( 6, $new_value );

		$new_value = wc_update_product_stock( $product->get_id(), 1, 'decrease' );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 5, $product->get_stock_quantity() );
		$this->assertEquals( 5, $new_value );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test wc_delete_product_transients().
	 *
	 * @since 2.4
	 */
	public function test_wc_delete_product_transients() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		update_post_meta( $product->get_id(), '_regular_price', wc_format_decimal( 10 ) );
		update_post_meta( $product->get_id(), '_price', wc_format_decimal( 5 ) );
		update_post_meta( $product->get_id(), '_sale_price', wc_format_decimal( 5 ) );

		wc_get_product_ids_on_sale();  // Creates the transient for on sale products
		wc_get_featured_product_ids(); // Creates the transient for featured products

		wc_delete_product_transients();

		$this->assertFalse( get_transient( 'wc_products_onsale' ) );
		$this->assertFalse( get_transient( 'wc_featured_products' ) );

		WC_Helper_Product::delete_product( $product->get_id() );
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
		$product = WC_Helper_Product::create_simple_product();

		update_post_meta( $product->get_id(), '_regular_price', wc_format_decimal( 10 ) );
		update_post_meta( $product->get_id(), '_price', wc_format_decimal( 5 ) );
		update_post_meta( $product->get_id(), '_sale_price', wc_format_decimal( 5 ) );

		$this->assertEquals( array( $product->get_id() ), wc_get_product_ids_on_sale() );

		// Delete Product
		WC_Helper_Product::delete_product( $product->get_id() );
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
		$product = WC_Helper_Product::create_simple_product();
		$product->set_featured( true );
		$product->save();

		$this->assertEquals( array( $product->get_id() ), wc_get_featured_product_ids() );

		// Delete Product
		WC_Helper_Product::delete_product( $product->get_id() );
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
			'shop_catalog'   => array( 'width' => '300', 'height' => '300' ),
		);

		foreach ( $sizes as $size => $values ) {
			$img = '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="' . $values['width'] . '" class="woocommerce-placeholder wp-post-image" height="' . $values['height'] . '" />';
			$this->assertEquals( apply_filters( 'woocommerce_placeholder_img', $img ), wc_placeholder_img( $size ) );
		}

		$img = '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="180" class="woocommerce-placeholder wp-post-image" height="180" />';
		$this->assertEquals( apply_filters( 'woocommerce_placeholder_img', $img ), wc_placeholder_img() );
	}

	/**
	 * Test wc_get_product_types().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_types() {
		$product_types = (array) apply_filters( 'product_type_selector', array(
			'simple'   => 'Simple product',
			'grouped'  => 'Grouped product',
			'external' => 'External/Affiliate product',
			'variable' => 'Variable product',
		) );

		$this->assertEquals( $product_types, wc_get_product_types() );
	}

	/**
	 * Test wc_product_has_unique_sku().
	 *
	 * @since 2.3
	 */
	public function test_wc_product_has_unique_sku() {
		$product_1 = WC_Helper_Product::create_simple_product();

		$this->assertTrue( wc_product_has_unique_sku( $product_1->get_id(), $product_1->get_sku() ) );

		$product_2 = WC_Helper_Product::create_simple_product();
		// we need to manually set a sku, because WC_Product now uses wc_product_has_unique_sku before setting
		// so we need to manually set it to test the functionality.
		update_post_meta( $product_2->get_id(), '_sku', $product_1->get_sku() );

		$this->assertFalse( wc_product_has_unique_sku( $product_2->get_id(), $product_1->get_sku() ) );

		WC_Helper_Product::delete_product( $product_1->get_id() );

		$this->assertTrue( wc_product_has_unique_sku( $product_2->get_id(), $product_2->get_sku() ) );

		WC_Helper_Product::delete_product( $product_2->get_id() );
	}

	/**
	 * Test wc_get_product_id_by_sku().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_id_by_sku() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( $product->get_id(), wc_get_product_id_by_sku( $product->get_sku() ) );

		// Delete Product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test wc_get_min_max_price_meta_query()
	 *
	 * @since 3.0.0
	 */
	public function test_wc_get_min_max_price_meta_query() {
		$meta_query = wc_get_min_max_price_meta_query( array( 'min_price' => 10, 'max_price' => 100 ) );

		$this->assertEquals( array(
			'key'     => '_price',
			'value'   => array( 10, 100 ),
			'compare' => 'BETWEEN',
			'type'    => 'DECIMAL',
		), $meta_query );
	}

	/**
	 * Test wc_product_force_unique_sku
	 *
	 * @since 3.0.0
	 */
	public function test_wc_product_force_unique_sku() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$product_2 = WC_Helper_Product::create_simple_product();
		$product_3 = WC_Helper_Product::create_simple_product();
		$product_4 = WC_Helper_Product::create_simple_product();

		$product_1->set_sku( 'some-custom-sku' );
		$product_2->set_sku( 'another-custom-sku' );
		$product_3->set_sku( 'another-custom-sku-1' );
		$product_4->set_sku( '' );

		$product_1_id = $product_1->save();
		$product_2_id = $product_2->save();
		$product_3_id = $product_3->save();
		$product_4_id = $product_4->save();

		wc_product_force_unique_sku( $product_4_id );
		$this->assertEquals( get_post_meta( $product_4_id, '_sku', true ), '' );

		update_post_meta( $product_4_id, '_sku', 'some-custom-sku' );
		wc_product_force_unique_sku( $product_4_id );
		$this->assertEquals( get_post_meta( $product_4_id, '_sku', true ), 'some-custom-sku-1' );

		update_post_meta( $product_4_id, '_sku', 'another-custom-sku' );
		wc_product_force_unique_sku( $product_4_id );
		$this->assertEquals( get_post_meta( $product_4_id, '_sku', true ), 'another-custom-sku-2' );
	}

	/**
	 * Test wc_is_attribute_in_product_name
	 *
	 * @since 3.0.2
	 */
	public function test_wc_is_attribute_in_product_name() {
		$this->assertTrue( wc_is_attribute_in_product_name( 'L', 'Product &ndash; L') );
		$this->assertTrue( wc_is_attribute_in_product_name( 'Two Words', 'Product &ndash; L, Two Words' ) );
		$this->assertTrue( wc_is_attribute_in_product_name( 'Blue', 'Product &ndash; The Cool One &ndash; Blue, Large' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'L', 'Product' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'L', 'Product L Thing' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'Blue', 'Product &ndash; Large, Blueish' ) );
	}
}
