<?php
/**
 * Unit tests for the WC_Product_Variable class.
 *
 * @package WooCommerce\Tests\Product
 */

/**
 * Class WC_Tests_Product_Variable.
 */
class WC_Tests_Product_Variable extends WC_Unit_Test_Case {

	/**
	 * Test test_create_empty_variable().
	 */
	public function test_create_empty_variable() {
		$product    = new WC_Product_Variable();
		$product_id = $product->save();
		$prices     = $product->get_variation_prices();

		$this->assertArrayHasKey( 'regular_price', $prices );
		$this->assertArrayHasKey( 'sale_price', $prices );
		$this->assertArrayHasKey( 'price', $prices );
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
	 * Create a variable product with two variations.
	 *
	 * @return array An array containing first the main product, and then the two variation products.
	 */
	private function get_variable_product_with_children() {
		$product = new WC_Product_Variable();
		$product->save();

		$child1 = new WC_Product_Variation();
		$child1->set_parent_id( $product->get_id() );
		$child1->save();

		$child2 = new WC_Product_Variation();
		$child2->set_parent_id( $product->get_id() );
		$child2->save();

		$product->set_children( array( $child1->get_id(), $child2->get_id() ) );

		return array( $product, $child1, $child2 );
	}

	/**
	 * Test that variable products have the correct status when syncing with their children.
	 *
	 * @since 3.3.0
	 */
	public function test_variable_product_stock_status_sync() {
		list($product, $child1, $child2) = $this->get_variable_product_with_children();

		// Product should be in stock if a child is in stock.
		$child1->set_stock_status( 'instock' );
		$child1->save();
		$child2->set_stock_status( 'outofstock' );
		$child2->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'instock', $product->get_stock_status() );

		$child2->set_stock_status( 'onbackorder' );
		$child2->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'instock', $product->get_stock_status() );

		// Product should be out of stock if all children are out of stock.
		$child1->set_stock_status( 'outofstock' );
		$child1->save();
		$child2->set_stock_status( 'outofstock' );
		$child2->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'outofstock', $product->get_stock_status() );

		// Product should be on backorder if all children are on backorder.
		$child1->set_stock_status( 'onbackorder' );
		$child1->save();
		$child2->set_stock_status( 'onbackorder' );
		$child2->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'onbackorder', $product->get_stock_status() );

		// Product should be on backorder if at least one child is on backorder and the rest are out of stock.
		$child1->set_stock_status( 'outofstock' );
		$child1->save();
		$child2->set_stock_status( 'onbackorder' );
		$child2->save();
		WC_Product_Variable::sync( $product );
		$this->assertEquals( 'onbackorder', $product->get_stock_status() );
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
		list( $product, $child1, $child2 ) = $this->get_variable_product_with_children();

		update_option( 'woocommerce_notify_no_stock_amount', $notify_no_stock_amount );

		$child1->set_stock_status( '' );
		$child1->save();
		$child2->set_stock_status( '' );
		$child2->save();

		$product->set_manage_stock( 'yes' );
		$product->set_stock_status( '' );
		$product->set_backorders( $accepts_backorders ? 'yes' : 'no' );
		$product->set_stock_quantity( $stock_quantity );
		$product->save();

		$this->assertEquals( $expected_stock_status, $product->get_stock_status() );
	}
}
