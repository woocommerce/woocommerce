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

	 /**
	  * Test the automatic stock status transitions done on variable product save.
	  *
	  * @since 3.3.0
	  */
	public function test_variable_product_auto_stock_status() {
		$product = new WC_Product_Variable();

	 	// Product should not have quantity and stock status should be based on children stock status if not managing stock.
	 	$product->set_manage_stock( false );
	 	$product->set_stock_quantity( 5 );
	 	$product->set_stock_status( 'instock' );
	 	$product->save();
	 	$this->assertEquals( '', $product->get_stock_quantity() );
	 	$this->assertEquals( 'outofstock', $product->get_stock_status() );

	 	$product->set_manage_stock( true );

	 	// Product should be out of stock if managing orders, no backorders allowed, and quantity too low.
	 	$product->set_stock_quantity( 0 );
	 	$product->set_stock_status( 'instock' );
	 	$product->set_backorders( 'no' );
	 	$product->save();
	 	$this->assertEquals( 0, $product->get_stock_quantity() );
	 	$this->assertEquals( 'outofstock', $product->get_stock_status() );

	 	// Product should be on backorder if managing orders, backorders allowed, and quantity too low.
	 	$product->set_stock_quantity( 0 );
	 	$product->set_stock_status( 'instock' );
	 	$product->set_backorders( 'yes' );
	 	$product->save();
	 	$this->assertEquals( 0, $product->get_stock_quantity() );
	 	$this->assertEquals( 'onbackorder', $product->get_stock_status() );

	 	// Product should go to in stock if backordered and inventory increases.
	 	$product->set_stock_quantity( 5 );
	 	$product->set_stock_status( 'onbackorder' );
	 	$product->set_backorders( 'notify' );
	 	$product->save();
	 	$this->assertEquals( 5, $product->get_stock_quantity() );
	 	$this->assertEquals( 'instock', $product->get_stock_status() );

	 	// Product should go to in stock if out of stock and inventory increases.
	 	$product->set_stock_quantity( 3 );
	 	$product->set_stock_status( 'outofstock' );
	 	$product->set_backorders( 'no' );
	 	$product->save();
	 	$this->assertEquals( 3, $product->get_stock_quantity() );
	 	$this->assertEquals( 'instock', $product->get_stock_status() );
	}

	/**
	 * Test that variable products have the correct status when syncing with their children.
	 *
	 * @since 3.3.0
	 */
	public function test_variable_product_stock_status_sync() {
		$product = new WC_Product_Variable();
		$product->save();

		$child1 = new WC_Product_Variation();
		$child1->set_parent_id( $product->get_id() );
		$child1->save();

		$child2 = new WC_Product_Variation();
		$child2->set_parent_id( $product->get_id() );
		$child2->save();

		$product->set_children( array( $child1->get_id(), $child2->get_id() ) );

		// Product should be in stock if a child is in stock.
		$child1->set_stock_status( 'instock' );
		$child1->save();
		$child2->set_stock_status( 'outofstock' );
		$child2->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'instock', $product->get_stock_status() );

		// Product should be out of stock if all children are out of stock.
		$child1->set_stock_status( 'outofstock' );
		$child1->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'outofstock', $product->get_stock_status() );

		// Product should be on backorder if a child is on backorder.
		$child1->set_stock_status( 'onbackorder' );
		$child1->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'onbackorder', $product->get_stock_status() );
	}
}
