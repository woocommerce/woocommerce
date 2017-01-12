<?php
/**
 * Data Store Tests: Tests WC_Products's WC_Data_Store.
 * @package WooCommerce\Tests\Product
 * @since 2.7.0
 */
class WC_Tests_Product_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Make sure the default product store loads.
	 *
	 * @since 2.7.0
	 */
	function test_product_store_loads() {
		$product_store = new WC_Data_Store( 'product' );
		$this->assertTrue( is_callable( array( $product_store, 'read' ) ) );
		$this->assertEquals( 'WC_Product_Data_Store_CPT', $product_store->get_current_class_name() );
	}

	/**
	 * Test creating a new product.
	 *
	 * @since 2.7.0
	 */
	 function test_product_create() {
		 $product = new WC_Product;
		 $product->set_regular_price( 42 );
		 $product->set_name( 'My Product' );
		 $product->save();

		 $read_product = new WC_Product( $product->get_id() );

		 $this->assertEquals( '42', $read_product->get_regular_price() );
		 $this->assertEquals( 'My Product', $read_product->get_name() );
	 }

	/**
	 * Test reading a product.
	 *
	 * @since 2.7.0
	 */
	function test_product_read() {
		$product = WC_Helper_Product::create_simple_product();
		$product = new WC_Product( $product->get_id() );

		$this->assertEquals( '10', $product->get_regular_price() );
	}

	/**
	 * Test updating a product.
	 *
	 * @since 2.7.0
	 */
	function test_product_update() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( '10', $product->get_regular_price() );

		$product->set_regular_price( 15 );
		$product->save();

		// Reread from database
		$product = new WC_Product( $product->get_id() );

		$this->assertEquals( '15', $product->get_regular_price() );
	}

	/**
	 * Test trashing a product.
	 *
	 * @since 2.7.0
	 */
	function test_product_trash() {
		$product = WC_Helper_Product::create_simple_product();
		$product->delete();
		$this->assertEquals( 'trash', $product->get_status() );
	}

	/**
	 * Test deleting a product.
	 *
	 * @since 2.7.0
	 */
	function test_product_delete() {
		$product = WC_Helper_Product::create_simple_product();
		$product->delete( true );
		$this->assertEquals( 0, $product->get_id() );
	}

	/**
	 * Test creating a new grouped product.
	 *
	 * @since 2.7.0
	 */
	function test_grouped_product_create() {
		$simple_product = WC_Helper_Product::create_simple_product();
		$product = new WC_Product_Grouped;
		$product->set_children( array( $simple_product->get_id() ) );
		$product->set_name( 'My Grouped Product' );
		$product->save();
		$read_product = new WC_Product_Grouped( $product->get_id() );
		$this->assertEquals( 'My Grouped Product', $read_product->get_name() );
		$this->assertEquals( array( $simple_product->get_id() ), $read_product->get_children() );
	 }

	/**
	 * Test getting / reading an grouped product.
	 *
	 * @since 2.7.0
	 */
	function test_grouped_product_read() {
		$product	  = WC_Helper_Product::create_grouped_product();
		$read_product = new WC_Product_Grouped( $product->get_id() );
		$this->assertEquals( 'Dummy Grouped Product', $read_product->get_name() );
		$this->assertEquals( 2, count( $read_product->get_children() ) );
	}
	/**
	 * Test updating an grouped product.
	 *
	 * @since 2.7.0
	 */
	function test_grouped_product_update() {
		$product		= WC_Helper_Product::create_grouped_product();
		$simple_product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( 'Dummy Grouped Product', $product->get_name() );
		$this->assertEquals( 2, count( $product->get_children() ) );
		$children   = $product->get_children();
		$children[] = $simple_product->get_id();
		$product->set_children( $children );
		$product->set_name( 'Dummy Grouped Product 2' );
		$product->save();
		// Reread from database
		$product = new WC_Product_Grouped( $product->get_id() );
		$this->assertEquals( 3, count( $product->get_children() ) );
		$this->assertEquals( 'Dummy Grouped Product 2', $product->get_name() );
	}

	/**
	 * Test creating a new external product.
	 *
	 * @since 2.7.0
	 */
	function test_external_product_create() {
		 $product = new WC_Product_External;
		 $product->set_regular_price( 42 );
		 $product->set_button_text( 'Test CRUD' );
		 $product->set_product_url( 'http://automattic.com' );
		 $product->set_name( 'My External Product' );
		 $product->save();

		 $read_product = new WC_Product_External( $product->get_id() );

		 $this->assertEquals( '42', $read_product->get_regular_price() );
		 $this->assertEquals( 'Test CRUD', $read_product->get_button_text() );
		 $this->assertEquals( 'http://automattic.com', $read_product->get_product_url() );
		 $this->assertEquals( 'My External Product', $read_product->get_name() );
	 }

	/**
	 * Test getting / reading an external product. Make sure both our external
	 * product data and the main product data are present.
	 *
	 * @since 2.7.0
	 */
	function test_external_product_read() {
		$product = WC_Helper_Product::create_external_product();
		$product = new WC_Product_External( $product->get_id() );

		$this->assertEquals( 'Buy external product', $product->get_button_text() );
		$this->assertEquals( '10', $product->get_regular_price() );
	}

	/**
	 * Test updating an external product. Make sure both our external
	 * product data and the main product data are written to and present.
	 *
	 * @since 2.7.0
	 */
	function test_external_product_update() {
		$product = WC_Helper_Product::create_external_product();

		$this->assertEquals( 'Buy external product', $product->get_button_text() );
		$this->assertEquals( '10', $product->get_regular_price() );

		$product->set_button_text( 'Buy my external product' );
		$product->set_regular_price( 15 );
		$product->save();

		// Reread from database
		$product = new WC_Product_External( $product->get_id() );

		$this->assertEquals( 'Buy my external product', $product->get_button_text() );
		$this->assertEquals( '15', $product->get_regular_price() );
	}

	/**
	 * Test reading a variable product.
	 *
	 * @since 2.7.0
	 */
	public function test_variable_read() {
		$product = WC_Helper_Product::create_variation_product();
		$children = $product->get_children();

		// Test sale prices too
		update_post_meta( $children[0], '_price', '8' );
		update_post_meta( $children[0], '_sale_price', '8' );
		delete_transient( 'wc_var_prices_' . $product->get_id() );

		$product = new WC_Product_Variable( $product->get_id() );

		$this->assertEquals( 2, count( $product->get_children() ) );

		$expected_prices['price'][ $children[0] ] = 8.00;
		$expected_prices['price'][ $children[1] ] = 15.00;
		$expected_prices['regular_price'][ $children[0] ] = 10.00;
		$expected_prices['regular_price'][ $children[1] ] = 15.00;
		$expected_prices['sale_price'][ $children[0] ] = 8.00;
		$expected_prices['sale_price'][ $children[1] ] = 15.00;

		$this->assertEquals( $expected_prices, $product->get_variation_prices() );

		$expected_attributes = array( 'pa_size' => array( 'small', 'large' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );
		$product->delete();
	}

	/**
	 * Test variable and variations.
	 *
	 * @since 2.7.0
	 */
	function test_variables_and_variations() {
		$product = new WC_Product_Variable;
		$product->set_name( 'Variable Product' );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'pa_color' );
		$attribute->set_options( explode( WC_DELIMITER, 'green | red' ) );
		$attribute->set_visible( false );
		$attribute->set_variation( true );

		$product->set_attributes( array( $attribute ) );
		$product->save();

		$this->assertEquals( 'Variable Product', $product->get_name() );

		$variation = new WC_Product_Variation;
		$variation->set_name( 'Variation #1 of Dummy Variable CRUD Product' );
		$variation->set_parent_id( $product->get_id() );
		$variation->set_regular_price( 10 );
		$variation->set_sku( 'CRUD DUMMY SKU VARIABLE GREEN' );
		$variation->set_manage_stock( 'no' );
		$variation->set_downloadable( 'no' );
		$variation->set_virtual( 'no' );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->save();

		$this->assertEquals( 'Variation #1 of Dummy Variable CRUD Product', $variation->get_name() );
		$this->assertEquals( 'CRUD DUMMY SKU VARIABLE GREEN', $variation->get_sku() );
		$this->assertEquals( 10, $variation->get_price() );

		$product = new WC_Product_Variable( $product->get_id() );
		$children = $product->get_children();
		$this->assertEquals( $variation->get_id(), $children[0] );

		$expected_attributes = array( 'pa_color' => array( 'green' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );

		$variation_2 = new WC_Product_Variation;
		$variation_2->set_name( 'Variation #2 of Dummy Variable CRUD Product' );
		$variation_2->set_parent_id( $product->get_id() );
		$variation_2->set_regular_price( 10 );
		$variation_2->set_sku( 'CRUD DUMMY SKU VARIABLE RED' );
		$variation_2->set_manage_stock( 'no' );
		$variation_2->set_downloadable( 'no' );
		$variation_2->set_virtual( 'no' );
		$variation_2->set_stock_status( 'instock' );
		$variation_2->set_attributes( array( 'pa_color' => 'red' ) );
		$variation_2->save();

		$this->assertEquals( 'Variation #2 of Dummy Variable CRUD Product', $variation_2->get_name() );
		$this->assertEquals( 'CRUD DUMMY SKU VARIABLE RED', $variation_2->get_sku() );
		$this->assertEquals( 10, $variation_2->get_price() );

		$product = new WC_Product_Variable( $product->get_id() );
		$children = $product->get_children();
		$this->assertEquals( $variation_2->get_id(), $children[1] );
		$this->assertEquals( 2, count( $children ) );

		$expected_attributes = array( 'pa_color' => array( 'green', 'red' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );

		$variation_2->set_name( 'UPDATED - Variation #2 of Dummy Variable CRUD Product' );
		$variation_2->set_regular_price( 15 );
		$variation_2->set_sale_price( 9.99 );
		$variation_2->set_date_on_sale_to( '32532537600' );
		$variation_2->save();

		$product = new WC_Product_Variable( $product->get_id() );
		$expected_prices['price'][ $children[0] ] = 10.00;
		$expected_prices['price'][ $children[1] ] = 9.99;
		$expected_prices['regular_price'][ $children[0] ] = 10.00;
		$expected_prices['regular_price'][ $children[1] ] = 15.00;
		$expected_prices['sale_price'][ $children[0] ] = 10.00;
		$expected_prices['sale_price'][ $children[1] ] = 9.99;

		$this->assertEquals( $expected_prices, $product->get_variation_prices() );
		$this->assertEquals( 'UPDATED - Variation #2 of Dummy Variable CRUD Product', $variation_2->get_name() );

		$product->set_name( 'Renamed Variable Product' );
		$product->save();
		$this->assertEquals( 'Renamed Variable Product', $product->get_name() );
		$product->delete();
	}

	function test_varation_save_attributes() {
		// Create a variable product with a color attribute.
		$product = new WC_Product_Variable;

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

		// Now update some value unrelated to attributes.
		$variation = wc_get_product( $variation->get_id() );
		$variation->set_status( 'publish' );
		$variation->save();

		// Load up the updated variation and verify that the saved state is correct.
		$loaded_variation = wc_get_product( $variation->get_id() );

		$this->assertEquals( 'publish', $loaded_variation->get_status( 'edit' ) );
		$_attribute = $loaded_variation->get_attributes( 'edit' );
		$this->assertEquals( 'green', $_attribute['color'] );
	}
}
