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
	 * @testdox When a product is saved or deleted its parent should be scheduled for sync at the end of the request.
	 *
	 * @testWith ["save"]
	 *           ["delete"]
	 *
	 * @param string $operation The method to test, "save" or "delete".
	 */
	public function test_deferred_sync_on_save_and_delete( $operation ) {
		$defer_sync_invoked = false;

		$defer_product_callback = function() use ( &$defer_sync_invoked ) {
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
}
