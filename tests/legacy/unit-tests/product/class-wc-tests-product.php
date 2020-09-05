<?php
/**
 * Unit tests for the base product class.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Tests for Product class.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */
class WC_Tests_Product extends WC_Unit_Test_Case {

	/**
	 * @var WC_Product
	 */
	protected $product;

	/**
	 * Runs before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->product = new WC_Product();
		$this->product->save();
	}


	/**
	 * @testdox When a product is saved or deleted its parent should be scheduled for sync at the end of the request.
	 *
	 * @testWith ["save"]
	 *           ["delete"]
	 *
	 * @param string $operation The method to test, "save" or "delete".
	 */
	public function test_deferred_sync_on_save_and_delete( $operation ) {
		$defer_sync_invoked = false;

		$defer_product_callback = function () use ( &$defer_sync_invoked ) {
			$defer_sync_invoked = true;
		};

		$product = $this->getMockBuilder( WC_Product::class )
						->setMethods( array( 'maybe_defer_product_sync' ) )
						->getMock();

		$product->method( 'maybe_defer_product_sync' )
				->will( $this->returnCallback( $defer_product_callback ) );

		$product->$operation();

		$this->assertTrue( $defer_sync_invoked );
	}

	/**
	 * @testdox Test that stock status is set to the proper value when saving, if the product manages stock levels.
	 *
	 * @testWith [5, 4, true, "instock"]
	 *           [5, 4, false, "instock"]
	 *           [4, 4, true, "onbackorder"]
	 *           [4, 4, false, "outofstock"]
	 *           [3, 4, true, "onbackorder"]
	 *           [3, 4, false, "outofstock"]
	 *
	 * @param int    $stock_quantity Current stock quantity for the product.
	 * @param bool   $notify_no_stock_amount Value for the woocommerce_notify_no_stock_amount option.
	 * @param bool   $accepts_backorders Whether the product accepts backorders or not.
	 * @param string $expected_stock_status The expected stock status of the product after being saved.
	 */
	public function test_stock_status_on_save_when_managing_stock( $stock_quantity, $notify_no_stock_amount, $accepts_backorders, $expected_stock_status ) {
		update_option( 'woocommerce_notify_no_stock_amount', $notify_no_stock_amount );
		$this->product->set_backorders( $accepts_backorders ? 'yes' : 'no' );
		$this->product->set_manage_stock( 'yes' );
		$this->product->set_stock_status( '' );
		$this->product->set_stock_quantity( $stock_quantity );
		$this->product->save();

		$this->assertEquals( $expected_stock_status, $this->product->get_stock_status() );
	}
}
