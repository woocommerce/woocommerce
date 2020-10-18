<?php
/**
 * Class Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */

/**
 * WC_Tests_Product_Functions class.
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

		$this->assertCount( 9, wc_get_products( array( 'return' => 'ids' ) ) );

		// Test status.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'status' => 'draft',
			)
		);
		$this->assertEquals( array( $draft->get_id() ), $products );

		// Test type.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'type'   => 'variation',
			)
		);
		$this->assertCount( 6, $products );

		// Test parent.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'type'   => 'variation',
				'parent' => $variation->get_id(),
			)
		);
		$this->assertCount( 6, $products );

		// Test parent_exclude.
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'type'           => 'variation',
				'parent_exclude' => array( $variation->get_id() ),
			)
		);
		$this->assertCount( 0, $products );

		// Test skus.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'sku'    => 'GET TEST SKU',
			)
		);
		$this->assertCount( 2, $products );
		$this->assertContains( $product->get_id(), $products );
		$this->assertContains( $external->get_id(), $products );

		// Test categories.
		$products = wc_get_products(
			array(
				'return'   => 'ids',
				'category' => array( $term_cat_1->slug ),
			)
		);
		$this->assertCount( 3, $products );

		// Test tags.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'tag'    => array( $term_tag_1->slug ),
			)
		);
		$this->assertCount( 2, $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'tag'    => array( $term_tag_2->slug ),
			)
		);
		$this->assertCount( 1, $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'tag'    => array( $term_tag_1->slug, $term_tag_2->slug ),
			)
		);
		$this->assertCount( 3, $products );

		// Test limit.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 5,
			)
		);
		$this->assertCount( 5, $products );

		// Test offset.
		$products        = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
			)
		);
		$products_offset = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
				'offset' => 2,
			)
		);
		$this->assertCount( 2, $products );
		$this->assertCount( 2, $products_offset );
		$this->assertNotEquals( $products, $products_offset );

		// Test page.
		$products_page_1 = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
			)
		);
		$products_page_2 = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
				'page'   => 2,
			)
		);
		$this->assertCount( 2, $products_page_1 );
		$this->assertCount( 2, $products_page_2 );
		$this->assertNotEquals( $products_page_1, $products_page_2 );

		// Test exclude.
		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'limit'   => 200,
				'exclude' => array( $product->get_id() ),
			)
		);
		$this->assertNotContains( $product->get_id(), $products );

		// Test include.
		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'include' => array( $product->get_id() ),
			)
		);
		$this->assertContains( $product->get_id(), $products );

		// Test order and orderby.
		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'order'   => 'ASC',
				'orderby' => 'ID',
				'limit'   => 2,
			)
		);
		$this->assertEquals( array( $product->get_id(), $product_2->get_id() ), $products );

		// Test paginate.
		$products = wc_get_products( array( 'paginate' => true ) );
		$this->assertGreaterThan( 0, $products->total );
		$this->assertGreaterThan( 0, $products->max_num_pages );
		$this->assertNotEmpty( $products->products );

		$product->delete( true );
		$product_2->delete( true );
		$external->delete( true );
		$external_2->delete( true );
		$grouped->delete( true );
		$draft->delete( true );
		$variation->delete( true );
	}

	/**
	 * Tests wc_get_products() with dimension parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_dimensions() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_width( '12.5' );
		$product_1->set_height( '5' );
		$product_1->set_weight( '11.4' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_width( '10' );
		$product_2->set_height( '5' );
		$product_2->set_weight( '15' );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'width'  => 12.5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'height' => 5.0,
			)
		);
		sort( $products );
		$this->assertEquals( array( $product_1->get_id(), $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'weight' => 15,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with price parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_price() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_regular_price( '12.5' );
		$product_1->set_price( '12.5' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_regular_price( '14' );
		$product_2->set_sale_price( '12.5' );
		$product_2->set_price( '12.5' );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'        => 'ids',
				'regular_price' => 12.5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'sale_price' => 12.5,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'price'  => 12.5,
			)
		);
		sort( $products );
		$this->assertEquals( array( $product_1->get_id(), $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with total_sales parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_total_sales() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_total_sales( 4 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_total_sales( 2 );
		$product_2->save();

		$product_3 = new WC_Product_Simple();
		$product_3->save();

		$products = wc_get_products(
			array(
				'return'      => 'ids',
				'total_sales' => 4,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
		$product_3->delete( true );
	}

	/**
	 * Tests wc_get_products() with boolean parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_booleans() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_virtual( true );
		$product_1->set_downloadable( true );
		$product_1->set_featured( true );
		$product_1->set_sold_individually( true );
		$product_1->set_backorders( 'no' );
		$product_1->set_manage_stock( false );
		$product_1->set_reviews_allowed( true );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_virtual( false );
		$product_2->set_downloadable( false );
		$product_2->set_featured( false );
		$product_2->set_sold_individually( false );
		$product_2->set_backorders( 'notify' );
		$product_2->set_manage_stock( true );
		$product_2->set_reviews_allowed( false );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'virtual' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'virtual' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'downloadable' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'   => 'ids',
				'featured' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'   => 'ids',
				'featured' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'            => 'ids',
				'sold_individually' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'backorders' => 'notify',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'manage_stock' => true,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'reviews_allowed' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'reviews_allowed' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with visibility parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_visibility() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_catalog_visibility( 'visible' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_catalog_visibility( 'hidden' );
		$product_2->save();

		$product_3 = new WC_Product_Simple();
		$product_3->set_catalog_visibility( 'search' );
		$product_3->save();

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'visibility' => 'visible',
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'visibility' => 'hidden',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'visibility' => 'search',
			)
		);
		sort( $products );
		$this->assertEquals( array( $product_1->get_id(), $product_3->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
		$product_3->delete( true );
	}

	/**
	 * Tests wc_get_products() with stock parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_stock() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_manage_stock( true );
		$product_1->set_stock_status( 'instock' );
		$product_1->set_stock_quantity( 5 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_manage_stock( true );
		$product_2->set_stock_status( 'outofstock' );
		$product_2->set_stock_quantity( 0 );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'stock_quantity' => 5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'stock_quantity' => 0,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'stock_status' => 'outofstock',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with tax parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_tax() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_tax_status( 'taxable' );
		$product_1->set_tax_class( 'reduced-rate' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_tax_status( 'none' );
		$product_2->set_tax_class( 'standard' );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'tax_status' => 'taxable',
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'tax_status' => 'none',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'    => 'ids',
				'tax_class' => 'reduced-rate',
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with shipping parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_shipping_class() {
		$shipping_class_1 = wp_insert_term( 'Bulky', 'product_shipping_class' );
		$shipping_class_2 = wp_insert_term( 'Standard', 'product_shipping_class' );

		$product_1 = new WC_Product_Simple();
		$product_1->set_shipping_class_id( $shipping_class_1['term_id'] );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_shipping_class_id( $shipping_class_2['term_id'] );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'shipping_class' => 'bulky',
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'shipping_class' => 'standard',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with download parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_download() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_downloadable( true );
		$product_1->set_download_limit( 5 );
		$product_1->set_download_expiry( 90 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_downloadable( true );
		$product_2->set_download_limit( -1 );
		$product_2->set_download_expiry( -1 );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'download_limit' => 5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'download_limit' => -1,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'download_expiry' => 90,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'download_expiry' => -1,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );
	}

	/**
	 * Tests wc_get_products() with reviews parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_reviews() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_average_rating( 5.0 );
		$product_1->set_review_count( 5 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_average_rating( 3.0 );
		$product_2->set_review_count( 1 );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'average_rating' => 5.0,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'average_rating' => 3.0,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'review_count' => 5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'review_count' => 1,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Test wc_get_product().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product() {
		$product = WC_Helper_Product::create_simple_product();

		$product_copy = wc_get_product( $product->get_id() );

		$this->assertEquals( $product->get_id(), $product_copy->get_id() );
	}

	/**
	 * Test wc_get_product_object().
	 *
	 * @since 3.9.0
	 */
	public function test_wc_get_product_object() {
		$this->assertInstanceOf( 'WC_Product_Simple', wc_get_product_object( 'simple' ) );
		$this->assertInstanceOf( 'WC_Product_Grouped', wc_get_product_object( 'grouped' ) );
		$this->assertInstanceOf( 'WC_Product_External', wc_get_product_object( 'external' ) );
		$this->assertInstanceOf( 'WC_Product_Variable', wc_get_product_object( 'variable' ) );
		$this->assertInstanceOf( 'WC_Product_Variation', wc_get_product_object( 'variation' ) );

		// Test incorrect type.
		$this->assertInstanceOf( 'WC_Product_Simple', wc_get_product_object( 'foo+bar' ) );
	}

	/**
	 * Test wc_update_product_stock().
	 *
	 * @since 2.3
	 */
	public function test_wc_update_product_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->save();

		wc_update_product_stock( $product->get_id(), 5 );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 5, $product->get_stock_quantity() );
	}

	/**
	 * Test: test_wc_update_product_stock_increase_decrease.
	 */
	public function test_wc_update_product_stock_increase_decrease() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->save();
		wc_update_product_stock( $product->get_id(), 5 );

		$new_value = wc_update_product_stock( $product->get_id(), 1, 'increase' );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 6, $product->get_stock_quantity() );
		$this->assertEquals( 6, $new_value );

		$new_value = wc_update_product_stock( $product->get_id(), 1, 'decrease' );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 5, $product->get_stock_quantity() );
		$this->assertEquals( 5, $new_value );
	}

	/**
	 * Test: test_wc_update_product_stock_should_return_false_if_invalid_product.
	 */
	public function test_wc_update_product_stock_should_return_false_if_invalid_product() {
		$this->assertFalse( wc_update_product_stock( 1 ) );
	}

	/**
	 * Test: test_wc_update_product_stock_should_return_stock_quantity_if_no_stock_quantity_given.
	 */
	public function test_wc_update_product_stock_should_return_stock_quantity_if_no_stock_quantity_given() {
		$stock_quantity = 5;
		$product        = WC_Helper_Product::create_simple_product();
		$product->set_stock_quantity( $stock_quantity );
		$product->set_manage_stock( true );
		$product->save();

		$this->assertEquals( $stock_quantity, wc_update_product_stock( $product ) );
	}

	/**
	 * Test: test_wc_update_product_stock_should_return_null_if_not_managing_stock.
	 */
	public function test_wc_update_product_stock_should_return_null_if_not_managing_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_quantity( 5 );
		$product->save();

		$this->assertNull( wc_update_product_stock( $product, 3 ) );
	}

	/**
	 * Test wc_update_product_stock_status().
	 */
	public function test_wc_update_product_stock_status_should_change_stock_status() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( 'instock', $product->get_stock_status() );

		wc_update_product_stock_status( $product->get_id(), 'outofstock' );
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( 'outofstock', $product->get_stock_status() );
	}

	/**
	 * Test wc_delete_product_transients().
	 *
	 * @since 2.4
	 */
	public function test_wc_delete_product_transients() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product->save();

		wc_get_product_ids_on_sale();  // Creates the transient for on sale products.
		wc_get_featured_product_ids(); // Creates the transient for featured products.

		wc_delete_product_transients();

		$this->assertFalse( get_transient( 'wc_products_onsale' ) );
		$this->assertFalse( get_transient( 'wc_featured_products' ) );
	}

	/**
	 * Test wc_get_product_ids_on_sale().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_product_ids_on_sale() {
		$this->assertEquals( array(), wc_get_product_ids_on_sale() );

		delete_transient( 'wc_products_onsale' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product->save();

		$this->assertEquals( array( $product->get_id() ), wc_get_product_ids_on_sale() );
	}

	/**
	 * Test wc_get_featured_product_ids().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_featured_product_ids() {
		$this->assertEquals( array(), wc_get_featured_product_ids() );

		delete_transient( 'wc_featured_products' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_featured( true );
		$product->save();

		$this->assertEquals( array( $product->get_id() ), wc_get_featured_product_ids() );
	}

	/**
	 * Test wc_placeholder_img().
	 *
	 * @since 2.4
	 */
	public function test_wc_placeholder_img() {
		$this->assertTrue( (bool) strstr( wc_placeholder_img(), wc_placeholder_img_src() ) );

		// Test custom class attribute is honoured.
		$attr = array( 'class' => 'custom-class' );
		$this->assertContains( 'class="custom-class"', wc_placeholder_img( 'woocommerce_thumbnail', $attr ) );
	}

	/**
	 * Test wc_get_product_types().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_types() {
		$product_types = (array) apply_filters(
			'product_type_selector',
			array(
				'simple'   => 'Simple product',
				'grouped'  => 'Grouped product',
				'external' => 'External/Affiliate product',
				'variable' => 'Variable product',
			)
		);

		$this->assertEquals( $product_types, wc_get_product_types() );
	}

	/**
	 * @expectedException WC_Data_Exception
	 */
	public function test_wc_product_has_unique_sku() {
		$product_1 = WC_Helper_Product::create_simple_product();

		$this->assertTrue( wc_product_has_unique_sku( $product_1->get_id(), $product_1->get_sku() ) );

		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_sku( $product_1->get_sku() );
	}

	/**
	 * Test wc_get_product_id_by_sku().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_id_by_sku() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( $product->get_id(), wc_get_product_id_by_sku( $product->get_sku() ) );
	}

	/**
	 * Test wc_get_min_max_price_meta_query()
	 *
	 * @expectedDeprecated wc_get_min_max_price_meta_query()
	 */
	public function test_wc_get_min_max_price_meta_query() {
		$meta_query = wc_get_min_max_price_meta_query(
			array(
				'min_price' => 10,
				'max_price' => 100,
			)
		);

		$this->assertEquals(
			array(
				'key'     => '_price',
				'value'   => array( 10, 100 ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(10,2)',
			),
			$meta_query
		);
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
		$product_4->set_sku( 'some-custom-sku' );

		$product_1_id = $product_1->save();
		$product_2_id = $product_2->save();
		$product_3_id = $product_3->save();
		$product_4_id = $product_4->save();

		wc_product_force_unique_sku( $product_4_id );
		$product_4 = wc_get_product( $product_4_id );
		$this->assertEquals( $product_4->get_sku( 'edit' ), 'some-custom-sku-1' );

		$product_1->delete( true );
		$product_2->delete( true );
		$product_3->delete( true );
		$product_4->delete( true );
	}

	/**
	 * Test wc_is_attribute_in_product_name
	 *
	 * @since 3.0.2
	 */
	public function test_wc_is_attribute_in_product_name() {
		$this->assertTrue( wc_is_attribute_in_product_name( 'L', 'Product &ndash; L' ) );
		$this->assertTrue( wc_is_attribute_in_product_name( 'Two Words', 'Product &ndash; L, Two Words' ) );
		$this->assertTrue( wc_is_attribute_in_product_name( 'Blue', 'Product &ndash; The Cool One &ndash; Blue, Large' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'L', 'Product' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'L', 'Product L Thing' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'Blue', 'Product &ndash; Large, Blueish' ) );
	}

	/**
	 * Test: test_wc_get_attachment_image_attributes.
	 */
	public function test_wc_get_attachment_image_attributes() {
		$image_attr = array(
			'src'    => 'https://wc.local/wp-content/uploads/2018/02/single-1-250x250.jpg',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => 'https://wc.local/wp-content/uploads/2018/02/single-1-250x250.jpg 250w, https://wc.local/wp-content/uploads/2018/02/single-1-350x350.jpg 350w, https://wc.local/wp-content/uploads/2018/02/single-1-150x150.jpg 150w, https://wc.local/wp-content/uploads/2018/02/single-1-300x300.jpg 300w, https://wc.local/wp-content/uploads/2018/02/single-1-768x768.jpg 768w, https://wc.local/wp-content/uploads/2018/02/single-1-100x100.jpg 100w, https://wc.local/wp-content/uploads/2018/02/single-1.jpg 800w',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		// Test regular image attr.
		$this->assertEquals( $image_attr, wc_get_attachment_image_attributes( $image_attr ) );

		$image_attr = array(
			'src'    => '',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => '',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		// Test blank src image attr, this is used in lazy loading.
		$this->assertEquals( $image_attr, wc_get_attachment_image_attributes( $image_attr ) );

		$image_attr    = array(
			'src'    => 'https://wc.local/wp-content/woocommerce_uploads/my-image.jpg',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => 'https://wc.local/wp-content/woocommerce_uploads/my-image-250x250.jpg 250w, https://wc.local/wp-content/woocommerce_uploads/my-image-350x350 350w',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		$expected_attr = array(
			'src'    => WC()->plugin_url() . '/assets/images/placeholder.png',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => '',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		// Test image hosted in woocommerce_uploads which is not allowed, think shops selling photos.
		$this->assertEquals( $expected_attr, wc_get_attachment_image_attributes( $image_attr ) );

		unset( $image_attr, $expected_attr );
	}

	/**
	 * Test wc_get_product_stock_status_options().
	 *
	 * @since 3.6.0
	 */
	public function test_wc_get_product_stock_status_options() {
		$status_options = (array) apply_filters(
			'woocommerce_product_stock_status_options',
			array(
				'instock'     => 'In stock',
				'outofstock'  => 'Out of stock',
				'onbackorder' => 'On backorder',
			)
		);

		$this->assertEquals( $status_options, wc_get_product_stock_status_options() );
	}
}
