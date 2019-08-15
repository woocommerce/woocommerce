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
	public function test_product_query_new() {
		$query = new WC_Product_Query();
		$this->assertEquals( '', $query->get( 'weight' ) );
		$types = $query->get( 'type' );
		sort( $types );
		$this->assertEquals( array( 'external', 'grouped', 'simple', 'variable' ), $types );
	}

	/**
	 * Test querying by product properties.
	 *
	 * @since 3.2
	 */
	public function test_product_query_standard() {
		$product1 = new WC_Product_Simple();
		$product1->set_sku( 'sku1' );
		$product1->set_regular_price( '10.00' );
		$product1->set_sale_price( '5.00' );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_sku( 'sku2' );
		$product2->set_regular_price( '12.50' );
		$product2->set_sale_price( '5.00' );
		$product2->save();

		// Just get some products.
		$query   = new WC_Product_Query();
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

		$product3 = new WC_Product_Simple();
		$product3->save();

		// Get multiple products using wildcard.
		$query = new WC_Product_Query();
		$query->set( 'sku', '*' );
		$results = $query->get_products();
		$this->assertEquals( 2, count( $results ) );

		// Test another field using wildcard.
		$query = new WC_Product_Query();
		$query->set( 'sale_price', '*' );
		$results = $query->get_products();
		$this->assertEquals( 2, count( $results ) );
	}

	/**
	 * Test querying by product date properties for dates stored in metadata.
	 *
	 * @since 3.2
	 */
	public function test_product_query_meta_date_queries() {
		$now          = current_time( 'mysql', true );
		$now_stamp    = strtotime( $now );
		$now_date     = date( 'Y-m-d', $now_stamp );
		$past_stamp   = $now_stamp - DAY_IN_SECONDS;
		$past         = date( 'Y-m-d', $past_stamp );
		$future_stamp = $now_stamp + DAY_IN_SECONDS;
		$future       = date( 'Y-m-d', $future_stamp );

		$product = new WC_Product_Simple();
		$product->set_date_on_sale_from( $now_stamp );
		$product->save();

		// Check WC_DateTime support.
		$query    = new WC_Product_Query(
			array(
				'date_on_sale_from' => $product->get_date_on_sale_from(),
			)
		);
		$products = $query->get_products();
		$this->assertEquals( 1, count( $products ) );

		// Check date support.
		$query = new WC_Product_Query(
			array(
				'date_on_sale_from' => $now_date,
			)
		);
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', $past );
		$this->assertEquals( 0, count( $query->get_products() ) );

		// Check timestamp support.
		$query = new WC_Product_Query(
			array(
				'date_on_sale_from' => $product->get_date_on_sale_from()->getTimestamp(),
			)
		);
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', $future_stamp );
		$this->assertEquals( 0, count( $query->get_products() ) );

		// Check comparison support.
		$query = new WC_Product_Query(
			array(
				'date_on_sale_from' => '>' . $past,
			)
		);
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', '<' . $past );
		$this->assertEquals( 0, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', '>=' . $now_date );
		$this->assertEquals( 1, count( $query->get_products() ) );

		// Check timestamp comparison support.
		$query = new WC_Product_Query(
			array(
				'date_on_sale_from' => '<' . $future_stamp,
			)
		);
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', '<' . $past_stamp );
		$this->assertEquals( 0, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', '>=' . $now_stamp );

		// Check date range support.
		$query = new WC_Product_Query(
			array(
				'date_on_sale_from' => $past . '...' . $future,
			)
		);
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', $now_date . '...' . $future );
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', $future . '...' . $now_date );
		$this->assertEquals( 0, count( $query->get_products() ) );

		// Check timestamp range support.
		$query = new WC_Product_Query(
			array(
				'date_on_sale_from' => $past_stamp . '...' . $future_stamp,
			)
		);
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', $now_stamp . '...' . $future_stamp );
		$this->assertEquals( 1, count( $query->get_products() ) );
		$query->set( 'date_on_sale_from', $future_stamp . '...' . $now_stamp );
		$this->assertEquals( 0, count( $query->get_products() ) );
	}
}
