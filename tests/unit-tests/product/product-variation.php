<?php

/**
 * Class Product_Variation.
 * @package WooCommerce\Tests\Product
 * @since 3.0
 */
class WC_Tests_Product_Variation extends WC_Unit_Test_Case {

	/**
	 * Test is_sold_individually().
	 *
	 * @since 2.3
	 */
	public function test_is_sold_individually() {
		// Create a variable product with sold individually.
		$product = new WC_Product_Variable;
		$product->set_sold_individually( true );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'color' );
		$attribute->set_options( explode( WC_DELIMITER, 'green | red' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product->set_attributes( array( $attribute ) );
		$product->save();

		// Create a new variation with the color 'green'.
		$variation = new WC_Product_Variation;
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->set_status( 'private' );
		$variation->save();

		$variation = wc_get_product( $variation->get_id() );
		$this->assertTrue( $variation->is_sold_individually() );
	}

}
