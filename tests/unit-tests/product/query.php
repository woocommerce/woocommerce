<?php

/**
 * Test WC_Product_Query
 * @package WooCommerce\Tests\Product
 */
class WC_Tests_WC_Product_Query extends WC_Unit_Test_Case {

	/**
	 * Test basic methods on a new WC_Product_Query.
	 *
	 * @since 3.2
	 */
	public function test_order_query_new() {
		$query = new WC_Product_Query();
		$this->assertEquals( '', $query->get( 'weight' ) );
		$this->assertEquals( array( 'product', 'product_variation' ), $query->get( 'type' ) );
	}

	/**
	 * Test querying by product properties.
	 *
	 * @since 3.2
	 */
	public function test_order_query_standard() {
		$product1 = new WC_Product_Simple();
		$product1->set_sku( 'sku1' );
		$product1->set_regular_price( '10.00' );
		$product1->set_sale_price( '5.00' );
		$product1->save();

		$product2 = new WC_Product_Variation();
		$product2->set_sku( 'sku2' );
		$product2->set_regular_price( '12.50' );
		$product2->set_sale_price( '5.00' );
		$product2->save();

		// Just get some products.
		$query = new WC_Product_Query();
		$results = $query->get_products();
		$this->assertEquals( 2, count( $results ) );

		// Get products with a specific property..
		$query->set( 'sku', 'sku2' );
		$results = $query->get_products();
		$this->assertEquals( 1, count( $results ) );
		$this->assertEquals( $results[0]->get_id(), $product2->get_id() );

		// Get products with two specific properties.
		$query->set( 'regular_price', '12.50' );
		$results = $query->get_products();
		$this->assertEquals( 1, count( $results ) );
		$this->assertEquals( $results[0]->get_id(), $product2->get_id() );

		// Get multiple products that have the same specific property.
		$query = new WC_Product_Query();
		$query->set( 'sale_price', '5.00' );
		$results = $query->get_products();
		$this->assertEquals( 2, count( $results ) );

		// Limit to one result.
		$query->set( 'limit', 1 );
		$results = $query->get_products();
		$this->assertEquals( 1, count( $results ) );
	}
}
