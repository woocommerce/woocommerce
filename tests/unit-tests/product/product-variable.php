<?php

/**
 * Class WC_Tests_Product_Variable.
 *
 * @package WooCommerce\Tests\Product
 */
class WC_Tests_Product_Variable extends WC_Unit_Test_Case {

	/**
	 * Test test_create_empty_variable().
	 */
	public function test_create_empty_variable() {
		$product    = new WC_Product_Variable();
		$product_id = $product->save();
		$prices     = $product->get_variation_prices();

		$this->assertArrayHasKey( 'regular_price', $prices  );
		$this->assertArrayHasKey( 'sale_price', $prices  );
		$this->assertArrayHasKey( 'price', $prices  );
		$this->assertTrue( $product_id > 0 );
	}
}
