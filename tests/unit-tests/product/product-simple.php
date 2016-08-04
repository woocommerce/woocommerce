<?php

/**
 * Class Product_Simple.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class WC_Tests_Product_Simple extends WC_Unit_Test_Case {

	/**
	 * Test add_to_cart_text().
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_text() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( __( 'Add to cart', 'woocommerce' ), $product->add_to_cart_text() );

		$product->stock_status = 'outofstock';
		$this->assertEquals( __( 'Read more', 'woocommerce' ), $product->add_to_cart_text() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test single_add_to_cart_text().
	 *
	 * @since 2.3
	 */
	public function test_single_add_to_cart_text() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( __( 'Add to cart', 'woocommerce' ), $product->single_add_to_cart_text() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_title().
	 *
	 * @since 2.3
	 */
	public function test_get_title() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( 'Dummy Product', $product->get_title() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_permalink().
	 *
	 * @since 2.3
	 */
	public function test_get_permalink() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( get_permalink( $product->id ), $product->get_permalink() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_sku().
	 *
	 * @since 2.3
	 */
	public function test_get_sku() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( $product->sku, $product->get_sku() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_stock_quantity().
	 *
	 * @since 2.3
	 */
	public function test_get_stock_quantity() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEmpty( $product->get_stock_quantity() );

		$product->manage_stock = 'yes';

		$this->assertEquals( 0, $product->get_stock_quantity() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_total_stock().
	 *
	 * @since 2.3
	 */
	public function test_get_total_stock() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEmpty( $product->get_total_stock() );

		$product->manage_stock = 'yes';
		$this->assertEquals( 0, $product->get_total_stock() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test set_stock().
	 *
	 * @since 2.3
	 */
	public function test_set_stock() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->manage_stock = 'yes';
		$this->assertEquals( 5, $product->set_stock( 5 ) );
		$this->assertEquals( 2, $product->set_stock( 3, 'subtract' ) );
		$this->assertEquals( 5, $product->set_stock( 3, 'add' ) );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test reduce_stock().
	 *
	 * @since 2.3
	 */
	public function test_reduce_stock() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->manage_stock = 'yes';
		$product->set_stock( 5 );
		$this->assertEquals( 2, $product->reduce_stock( 3 ) );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test increase_stock().
	 *
	 * @since 2.3
	 */
	public function test_increase_stock() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->manage_stock = 'yes';
		$product->set_stock( 5 );
		$this->assertEquals( 8, $product->increase_stock( 3 ) );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test is_type().
	 *
	 * @since 2.3
	 */
	public function test_is_type() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertTrue( $product->is_type( 'simple' ) );
		$this->assertFalse( $product->is_type( 'grouped' ) );
		$this->assertFalse( $product->is_type( 'variable' ) );
		$this->assertFalse( $product->is_type( 'external' ) );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test is_downloadable().
	 *
	 * @since 2.3
	 */
	public function test_is_downloadable() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEmpty( $product->is_downloadable() );

		$product->downloadable = 'yes';
		$this->assertTrue( $product->is_downloadable() );

		$product->downloadable = 'no';
		$this->assertFalse( $product->is_downloadable() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test is_virtual().
	 *
	 * @since 2.3
	 */
	public function test_is_virtual() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEmpty( $product->is_virtual() );

		$product->virtual = 'yes';
		$this->assertTrue( $product->is_virtual() );

		$product->virtual = 'no';
		$this->assertFalse( $product->is_virtual() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test needs_shipping().
	 *
	 * @since 2.3
	 */
	public function test_needs_shipping() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->virtual = 'yes';
		$this->assertFalse( $product->needs_shipping() );

		$product->virtual = 'no';
		$this->assertTrue( $product->needs_shipping() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test is_sold_individually().
	 *
	 * @since 2.3
	 */
	public function test_is_sold_individually() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->sold_individually = 'yes';
		$this->assertTrue( $product->is_sold_individually() );

		$product->sold_individually = 'no';
		$this->assertFalse( $product->is_sold_individually() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test backorders_allowed().
	 *
	 * @since 2.3
	 */
	public function test_backorders_allowed() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->backorders = 'yes';
		$this->assertTrue( $product->backorders_allowed() );

		$product->backorders = 'notify';
		$this->assertTrue( $product->backorders_allowed() );

		$product->backorders = 'no';
		$this->assertFalse( $product->backorders_allowed() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test backorders_require_notification().
	 *
	 * @since 2.3
	 */
	public function test_backorders_require_notification() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();

		$product->backorders = 'notify';
		$product->manage_stock = 'yes';
		$this->assertTrue( $product->backorders_require_notification() );

		$product->backorders = 'yes';
		$this->assertFalse( $product->backorders_require_notification() );

		$product->backorders = 'no';
		$this->assertFalse( $product->backorders_require_notification() );

		$product->backorders = 'yes';
		$product->manage_stock = 'no';
		$this->assertFalse( $product->backorders_require_notification() );

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}
}
