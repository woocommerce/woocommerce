<?php

/**
 * Class Product_Simple.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class WC_Tests_Product_Simple extends WC_Unit_Test_Case {

	/**
	 * @var WC_Product
	 */
	protected $product;

	public function setUp(): void {
		parent::setUp();

		$this->product = WC_Helper_Product::create_simple_product();
	}

	/**
	 * Test add_to_cart_text().
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_text() {
		$this->assertEquals( 'Add to cart', $this->product->add_to_cart_text() );

		$this->product->set_stock_status( 'outofstock' );
		$this->product->save();

		$this->assertEquals( 'Read more', $this->product->add_to_cart_text() );
	}

	/**
	 * Test single_add_to_cart_text().
	 *
	 * @since 2.3
	 */
	public function test_single_add_to_cart_text() {
		$this->assertEquals( 'Add to cart', $this->product->single_add_to_cart_text() );
	}

	/**
	 * Test get_title().
	 *
	 * @since 2.3
	 */
	public function test_get_title() {
		$this->assertEquals( 'Dummy Product', $this->product->get_name() );
	}

	/**
	 * Test get_permalink().
	 *
	 * @since 2.3
	 */
	public function test_get_permalink() {
		$this->assertEquals( get_permalink( $this->product->get_id() ), $this->product->get_permalink() );
	}

	/**
	 * Test get_sku().
	 *
	 * @since 2.3
	 */
	public function test_get_sku() {
		$this->assertMatchesRegularExpression( '/^DUMMY SKU\d+$/', $this->product->get_sku() );
	}

	/**
	 * Test get_stock_quantity().
	 *
	 * @since 2.3
	 */
	public function test_get_stock_quantity() {
		$this->assertEmpty( $this->product->get_stock_quantity() );

		$this->product->manage_stock = 'yes';

		$this->assertEquals( 0, $this->product->get_stock_quantity() );
	}

	/**
	 * Test is_type().
	 *
	 * @since 2.3
	 */
	public function test_is_type() {
		$this->assertTrue( $this->product->is_type( 'simple' ) );
		$this->assertFalse( $this->product->is_type( 'grouped' ) );
		$this->assertFalse( $this->product->is_type( 'variable' ) );
		$this->assertFalse( $this->product->is_type( 'external' ) );
	}

	/**
	 * Test is_downloadable().
	 *
	 * @since 2.3
	 */
	public function test_is_downloadable() {
		$this->assertEmpty( $this->product->is_downloadable() );

		$this->product->set_downloadable( 'yes' );
		$this->assertTrue( $this->product->is_downloadable() );

		$this->product->set_downloadable( 'no' );
		$this->assertFalse( $this->product->is_downloadable() );
	}

	/**
	 * Test is_virtual().
	 *
	 * @since 2.3
	 */
	public function test_is_virtual() {
		$this->assertEmpty( $this->product->is_virtual() );

		$this->product->set_virtual( 'yes' );
		$this->assertTrue( $this->product->is_virtual() );

		$this->product->set_virtual( 'no' );
		$this->assertFalse( $this->product->is_virtual() );
	}

	/**
	 * Test needs_shipping().
	 *
	 * @since 2.3
	 */
	public function test_needs_shipping() {
		$this->product->set_virtual( 'yes' );
		$this->assertFalse( $this->product->needs_shipping() );

		$this->product->set_virtual( 'no' );
		$this->assertTrue( $this->product->needs_shipping() );
	}

	/**
	 * Test is_sold_individually().
	 *
	 * @since 2.3
	 */
	public function test_is_sold_individually() {
		$this->product->set_sold_individually( 'yes' );
		$this->assertTrue( $this->product->is_sold_individually() );

		$this->product->set_sold_individually( 'no' );
		$this->assertFalse( $this->product->is_sold_individually() );
	}

	/**
	 * Test backorders_allowed().
	 *
	 * @since 2.3
	 */
	public function test_backorders_allowed() {
		$this->product->set_backorders( 'yes' );
		$this->assertTrue( $this->product->backorders_allowed() );

		$this->product->set_backorders( 'notify' );
		$this->assertTrue( $this->product->backorders_allowed() );

		$this->product->set_backorders( 'no' );
		$this->assertFalse( $this->product->backorders_allowed() );
	}

	/**
	 * Test backorders_require_notification().
	 *
	 * @since 2.3
	 */
	public function test_backorders_require_notification() {
		$this->product->set_backorders( 'notify' );
		$this->product->set_manage_stock( 'yes' );
		$this->assertTrue( $this->product->backorders_require_notification() );

		$this->product->set_backorders( 'yes' );
		$this->assertFalse( $this->product->backorders_require_notification() );

		$this->product->set_backorders( 'no' );
		$this->assertFalse( $this->product->backorders_require_notification() );

		$this->product->set_backorders( 'yes' );
		$this->product->set_manage_stock( 'no' );
		$this->assertFalse( $this->product->backorders_require_notification() );
	}
}
