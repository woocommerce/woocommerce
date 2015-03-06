<?php

namespace WooCommerce\Tests\Product;

/**
 * Class Product_Simple
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class Product_Simple extends \WC_Unit_Test_Case {
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
		$this->_product = \WC_Helper_Product::create_simple_product();
	}

	/**
	 * Helper method to delete a product
	 *
	 * @since 2.3
	 * @access private
	 */
	private function _delete_product() {
		// Delete the previously created product
		\WC_Helper_Product::delete_product( $this->_product->id );
		$this->_product = null;
	}

	/**
	 * Clear out notices after each test
	 *
	 * @since 2.3
	 */
	public function tearDown() {

		remove_all_filters( 'woocommerce_product_add_to_cart_text' );
		remove_all_filters( 'woocommerce_product_single_add_to_cart_text' );
		remove_all_filters( 'woocommerce_product_needs_shipping' );
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
	 * Test single_add_to_cart_text()
	 *
	 * @since 2.3
	 */
	public function test_single_add_to_cart_text() {
		$this->_get_product();

		$this->assertEquals( __( 'Add to cart', 'woocommerce' ), $this->_product->single_add_to_cart_text() );

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

	/**
	 * Test get_permalink()
	 *
	 * @since 2.3
	 */
	public function test_get_permalink() {
		$this->_get_product();

		$this->assertEquals( get_permalink( $this->_product->id ), $this->_product->get_permalink() );

		$this->_delete_product();
	}

	/**
	 * Test get_sku()
	 *
	 * @since 2.3
	 */
	public function test_get_sku() {
		$this->_get_product();

		$this->assertEquals( $this->_product->sku, $this->_product->get_sku() );

		$this->_delete_product();
	}

	/**
	 * Test get_stock_quantity()
	 *
	 * @since 2.3
	 */
	public function test_get_stock_quantity() {
		$this->_get_product();

		$this->assertEmpty( $this->_product->get_stock_quantity() );

		$this->_product->manage_stock = 'yes';

		$this->assertEquals( 0, $this->_product->get_stock_quantity() );

		$this->_delete_product();
	}

	/**
	 * Test get_total_stock()
	 *
	 * @since 2.3
	 */
	public function test_get_total_stock() {
		$this->_get_product();

		$this->assertEmpty( $this->_product->get_total_stock() );

		$this->_product->manage_stock = 'yes';
		$this->assertEquals( 0, $this->_product->get_total_stock() );

		$this->_delete_product();
	}

	/**
	 * Test set_stock()
	 *
	 * @since 2.3
	 */
	public function test_set_stock() {
		$this->_get_product();

		$this->_product->manage_stock = 'yes';
		$this->assertEquals( 5, $this->_product->set_stock( 5 ) );
		$this->assertEquals( 2, $this->_product->set_stock( 3, 'subtract' ) );
		$this->assertEquals( 5, $this->_product->set_stock( 3, 'add' ) );

		$this->_delete_product();
	}

	/**
	 * Test reduce_stock()
	 *
	 * @since 2.3
	 */
	public function test_reduce_stock() {
		$this->_get_product();

		$this->_product->manage_stock = 'yes';
		$this->_product->set_stock( 5 );
		$this->assertEquals( 2, $this->_product->reduce_stock( 3 ) );

		$this->_delete_product();
	}

	/**
	 * Test increase_stock()
	 *
	 * @since 2.3
	 */
	public function test_increase_stock() {
		$this->_get_product();

		$this->_product->manage_stock = 'yes';
		$this->_product->set_stock( 5 );
		$this->assertEquals( 8, $this->_product->increase_stock( 3 ) );

		$this->_delete_product();
	}

	/**
	 * Test is_type()
	 *
	 * @since 2.3
	 */
	public function test_is_type() {
		$this->_get_product();

		$this->assertTrue( $this->_product->is_type( 'simple' ) );
		$this->assertFalse( $this->_product->is_type( 'grouped' ) );
		$this->assertFalse( $this->_product->is_type( 'variable' ) );
		$this->assertFalse( $this->_product->is_type( 'external' ) );

		$this->_delete_product();
	}

	/**
	 * Test is_downloadable()
	 *
	 * @since 2.3
	 */
	public function test_is_downloadable() {
		$this->_get_product();

		$this->assertEmpty( $this->_product->is_downloadable() );

		$this->_product->downloadable = 'yes';
		$this->assertTrue( $this->_product->is_downloadable() );

		$this->_product->downloadable = 'no';
		$this->assertFalse( $this->_product->is_downloadable() );

		$this->_delete_product();
	}

	/**
	 * Test is_virtual()
	 *
	 * @since 2.3
	 */
	public function test_is_virtual() {
		$this->_get_product();

		$this->assertEmpty( $this->_product->is_virtual() );

		$this->_product->virtual = 'yes';
		$this->assertTrue( $this->_product->is_virtual() );

		$this->_product->virtual = 'no';
		$this->assertFalse( $this->_product->is_virtual() );

		$this->_delete_product();
	}

	/**
	 * Test needs_shipping()
	 *
	 * @since 2.3
	 */
	public function test_needs_shipping() {
		$this->_get_product();

		$this->_product->virtual = 'yes';
		$this->assertFalse( $this->_product->needs_shipping() );

		$this->_product->virtual = 'no';
		$this->assertTrue( $this->_product->needs_shipping() );

		$this->_delete_product();
	}

	/**
	 * Test is_sold_individually()
	 *
	 * @since 2.3
	 */
	public function test_is_sold_individually() {
		$this->_get_product();

		$this->_product->sold_individually = 'yes';
		$this->assertTrue( $this->_product->is_sold_individually() );

		$this->_product->sold_individually = 'no';
		$this->assertFalse( $this->_product->is_sold_individually() );

		$this->_delete_product();
	}

	/**
	 * Test backorders_allowed()
	 *
	 * @since 2.3
	 */
	public function test_backorders_allowed() {
		$this->_get_product();

		$this->_product->backorders = 'yes';
		$this->assertTrue( $this->_product->backorders_allowed() );

		$this->_product->backorders = 'notify';
		$this->assertTrue( $this->_product->backorders_allowed() );

		$this->_product->backorders = 'no';
		$this->assertFalse( $this->_product->backorders_allowed() );

		$this->_delete_product();
	}

	/**
	 * Test backorders_require_notification()
	 *
	 * @since 2.3
	 */
	public function test_backorders_require_notification() {
		$this->_get_product();

		$this->_product->backorders = 'notify';
		$this->_product->manage_stock = 'yes';
		$this->assertTrue( $this->_product->backorders_require_notification() );

		$this->_product->backorders = 'yes';
		$this->assertFalse( $this->_product->backorders_require_notification() );

		$this->_product->backorders = 'no';
		$this->assertFalse( $this->_product->backorders_require_notification() );

		$this->_product->backorders = 'yes';
		$this->_product->manage_stock = 'no';
		$this->assertFalse( $this->_product->backorders_require_notification() );

		$this->_delete_product();
	}
}
