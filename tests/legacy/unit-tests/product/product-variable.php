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

	/**
	 * Setup for a test for is_visible.
	 *
	 * @param array $filtering_attributes Simulated filtering attributes as an array of attribute_name => [term1, term2...].
	 * @param bool  $hide_out_of_stock_products Should the woocommerce_hide_out_of_stock_items option be set?.
	 * @param bool  $is_visible_from_parent Return value of is_visible from base class.
	 *
	 * @return WC_Product_Variable A properly configured instance of WC_Product_Variable to test.
	 */
	private function prepare_visibility_test( $filtering_attributes, $hide_out_of_stock_products = true, $is_visible_from_parent = true ) {
		foreach ( $filtering_attributes as $attribute_name => $terms ) {
			$filtering_attributes[ $attribute_name ]['query_type'] = 'ANY_QUERY_TYPE';
			$filtering_attributes[ $attribute_name ]['terms']      = $terms;
		}

		update_option( 'woocommerce_hide_out_of_stock_items', $hide_out_of_stock_products ? 'yes' : 'no' );

		$sut = $this
			->getMockBuilder( WC_Product_Variable::class )
			->setMethods( array( 'parent_is_visible_core', 'get_layered_nav_chosen_attributes' ) )
			->getMock();

		$sut = WC_Helper_Product::create_variation_product( $sut, true );
		$sut->save();

		$sut->method( 'parent_is_visible_core' )->willReturn( $is_visible_from_parent );
		$sut->method( 'get_layered_nav_chosen_attributes' )->willReturn( $filtering_attributes );

		return $sut;
	}

	/**
	 * Configure the stock status for the attribute-based variations of a product.
	 *
	 * @param WC_Product_Variable $product Product with the variations to configure.
	 * @param array               $attributes An array of attribute_name => [attribute_values], only the matching variations will have stock.
	 */
	private function set_variations_with_stock( $product, $attributes ) {
		$variation_ids = $product->get_children();
		foreach ( $variation_ids as $id ) {
			$variation         = wc_get_product( $id );
			$attribute_matches = true;
			foreach ( $attributes as $name => $values ) {
				if ( ! in_array( $variation->get_attribute( $name ), $values, true ) ) {
					$attribute_matches = false;
				}
			}
			$variation->set_stock_status( $attribute_matches ? 'instock' : 'outofstock' );
			$variation->save();
		}
	}

	/**
	 * @testdox The product should be invisible when the parent 'is_visible' method returns false.
	 */
	public function test_is_invisible_when_parent_is_visible_returns_false() {
		$sut = $this->prepare_visibility_test( array(), '', false, false );

		$this->assertFalse( $sut->is_visible() );
	}

	/**
	 * @testdox The product should be visible when no nav filtering is supplied if at least one variation has stock.
	 *
	 * Note that if no variations have stock the base is_visible will already return false.
	 */
	public function test_is_visible_when_no_filtering_supplied_and_at_least_one_variation_has_stock() {
		$sut = $this->prepare_visibility_test( array(), '' );

		$this->set_variations_with_stock( $sut, array( 'pa_size' => array( 'small' ) ) );

		$this->assertTrue( $sut->is_visible() );
	}

	/**
	 * @testdox Test product visibility when the variation requested in nav filtering has no stock, result depends on woocommerce_hide_out_of_stock_items option.
	 *
	 * @param bool $hide_out_of_stock Value for woocommerce_hide_out_of_stock_items.
	 * @param bool $expected_visibility Expected value of is_visible for the tested product.
	 *
	 * @testWith [true, false]
	 *           [false, true]
	 */
	public function test_visibility_when_supplied_filter_has_no_stock( $hide_out_of_stock, $expected_visibility ) {
		$sut = $this->prepare_visibility_test( array( 'pa_size' => array( 'large' ) ), $hide_out_of_stock );

		$this->set_variations_with_stock( $sut, array( 'pa_size' => array( 'small' ) ) );

		$this->assertEquals( $expected_visibility, $sut->is_visible() );
	}

	/**
	 * @testdox Product should always be visible when only one of the variations requested in nav filtering has stock.
	 *
	 * @param bool $hide_out_of_stock Value for woocommerce_hide_out_of_stock_items.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_visibility_when_multiple_filter_values_supplied_and_only_one_has_stock( $hide_out_of_stock ) {
		$sut = $this->prepare_visibility_test( array( 'pa_size' => array( 'small', 'large' ) ), $hide_out_of_stock );

		$this->set_variations_with_stock( $sut, array( 'pa_size' => array( 'small' ) ) );

		$this->assertTrue( $sut->is_visible() );
	}

	/**
	 * @testdox Product should be visible when all of the variations requested in nav filtering have stock.
	 *
	 * @param bool $hide_out_of_stock Value for woocommerce_hide_out_of_stock_items.
	 *
	 * @testWith [true]
	 *           [false]
	 */
	public function test_visibility_when_multiple_filter_values_supplied_and_all_of_them_have_stock( $hide_out_of_stock ) {
		$sut = $this->prepare_visibility_test( array( 'pa_size' => array( 'small', 'large' ) ), $hide_out_of_stock );

		$this->set_variations_with_stock( $sut, array( 'pa_size' => array( 'small', 'large' ) ) );

		$this->assertTrue( $sut->is_visible() );
	}

	/**
	 * @testdox Product should be visible when multiple filters are present, and there's a variation matching all of them.
	 */
	public function test_visibility_when_multiple_filters_are_used_and_all_of_them_match() {
		$sut = $this->prepare_visibility_test(
			array(
				'pa_size'   => array( 'huge' ),
				'pa_colour' => array( 'blue' ),
			),
			true
		);

		$this->set_variations_with_stock(
			$sut,
			array(
				'pa_size'   => array( 'huge' ),
				'pa_colour' => array( 'blue' ),
				'pa_number' => array( '2' ),
			)
		);

		$this->assertTrue( $sut->is_visible() );
	}

	/**
	 * @testdox Product should not be visible when multiple filters are present, and there are no variations matching all of them.
	 */
	public function test_visibility_when_multiple_filters_are_used_and_one_of_them_does_not_match() {
		$sut = $this->prepare_visibility_test(
			array(
				'pa_size'   => array( 'small', 'huge' ),
				'pa_colour' => array( 'red' ),
			),
			true
		);

		$this->set_variations_with_stock(
			$sut,
			array(
				'pa_size'   => array( 'huge' ),
				'pa_colour' => array( 'blue' ),
				'pa_number' => array( '2' ),
			)
		);

		$this->assertFalse( $sut->is_visible() );
	}

	/**
	 * @testdox Attributes having "Any..." as value should not count when searching for matching attributes.
	 */
	public function test_visibility_when_multiple_filters_are_used_and_an_attribute_has_any_value() {
		$sut = $this->prepare_visibility_test(
			array(
				'pa_size'   => array( 'huge' ),
				'pa_number' => array( '34' ),
			),
			true
		);

		$this->set_variations_with_stock(
			$sut,
			array(
				'pa_size'   => array( 'huge' ),
				'pa_colour' => array( 'blue' ),
				'pa_number' => array( '' ),
			)
		);

		$this->assertTrue( $sut->is_visible() );
	}
}
