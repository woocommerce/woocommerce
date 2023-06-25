<?php
/**
 * Data Store Tests: Tests WC_Products's WC_Data_Store.
 *
 * @package WooCommerce\Tests\Product
 * @since 3.0.0
 */

/**
 * Class WC_Tests_Product_Data_Store
 */
class WC_Tests_Product_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Make sure the default product store loads.
	 *
	 * @since 3.0.0
	 * @group core-only
	 */
	public function test_product_store_loads() {
		$product_store = new WC_Data_Store( 'product' );
		$this->assertTrue( is_callable( array( $product_store, 'read' ) ) );
		$this->assertEquals( 'WC_Product_Data_Store_CPT', $product_store->get_current_class_name() );
	}

	/**
	 * Test creating a new product.
	 *
	 * @since 3.0.0
	 */
	public function test_product_create() {
		$product = new WC_Product();
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
	 * @since 3.0.0
	 */
	public function test_product_read() {
		$product = WC_Helper_Product::create_simple_product();
		$product = new WC_Product( $product->get_id() );

		$this->assertEquals( '10', $product->get_regular_price() );
	}

	/**
	 * Test updating a product.
	 *
	 * @since 3.0.0
	 */
	public function test_product_update() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( '10', $product->get_regular_price() );

		$product->set_regular_price( 15 );
		$product->save();

		// Reread from database.
		$product = new WC_Product( $product->get_id() );

		$this->assertEquals( '15', $product->get_regular_price() );
	}

	/**
	 * Test trashing a product.
	 *
	 * @since 3.0.0
	 */
	public function test_product_trash() {
		$product = WC_Helper_Product::create_simple_product();
		$product->delete();
		$this->assertEquals( 'trash', $product->get_status() );
	}

	/**
	 * Test deleting a product.
	 *
	 * @since 3.0.0
	 */
	public function test_product_delete() {
		$product = WC_Helper_Product::create_simple_product();
		$product->delete( true );
		$this->assertEquals( 0, $product->get_id() );
	}

	/**
	 * Test creating a new grouped product.
	 *
	 * @since 3.0.0
	 */
	public function test_grouped_product_create() {
		$simple_product = WC_Helper_Product::create_simple_product();
		$product        = new WC_Product_Grouped();
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
	 * @since 3.0.0
	 */
	public function test_grouped_product_read() {
		$product      = WC_Helper_Product::create_grouped_product();
		$read_product = new WC_Product_Grouped( $product->get_id() );
		$this->assertEquals( 'Dummy Grouped Product', $read_product->get_name() );
		$this->assertEquals( 2, count( $read_product->get_children() ) );
	}

	/**
	 * Test updating an grouped product.
	 *
	 * @since 3.0.0
	 */
	public function test_grouped_product_update() {
		$product        = WC_Helper_Product::create_grouped_product();
		$simple_product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( 'Dummy Grouped Product', $product->get_name() );
		$this->assertEquals( 2, count( $product->get_children() ) );
		$children   = $product->get_children();
		$children[] = $simple_product->get_id();
		$product->set_children( $children );
		$product->set_name( 'Dummy Grouped Product 2' );
		$product->save();
		// Reread from database.
		$product = new WC_Product_Grouped( $product->get_id() );
		$this->assertEquals( 3, count( $product->get_children() ) );
		$this->assertEquals( 'Dummy Grouped Product 2', $product->get_name() );
	}

	/**
	 * Test creating a new external product.
	 *
	 * @since 3.0.0
	 */
	public function test_external_product_create() {
		$product = new WC_Product_External();
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
	 * @since 3.0.0
	 */
	public function test_external_product_read() {
		$product = WC_Helper_Product::create_external_product();
		$product = new WC_Product_External( $product->get_id() );

		$this->assertEquals( 'Buy external product', $product->get_button_text() );
		$this->assertEquals( '10', $product->get_regular_price() );
	}

	/**
	 * Test updating an external product. Make sure both our external
	 * product data and the main product data are written to and present.
	 *
	 * @since 3.0.0
	 */
	public function test_external_product_update() {
		$product = WC_Helper_Product::create_external_product();

		$this->assertEquals( 'Buy external product', $product->get_button_text() );
		$this->assertEquals( '10', $product->get_regular_price() );

		$product->set_button_text( 'Buy my external product' );
		$product->set_regular_price( 15 );
		$product->save();

		// Reread from database.
		$product = new WC_Product_External( $product->get_id() );

		$this->assertEquals( 'Buy my external product', $product->get_button_text() );
		$this->assertEquals( '15', $product->get_regular_price() );
	}

	/**
	 * Test reading a variable product.
	 *
	 * @since 3.0.0
	 */
	public function test_variable_read() {
		$product  = WC_Helper_Product::create_variation_product();
		$children = $product->get_children();

		// Test sale prices too.
		$child = wc_get_product( $children[0] );
		$child->set_sale_price( 8 );
		$child->save();

		$product = new WC_Product_Variable( $product->get_id() );

		$this->assertEquals( 6, count( $product->get_children() ) );

		$expected_prices['price'][ $children[0] ] = 8.00;
		$expected_prices['price'][ $children[1] ] = 15.00;
		$expected_prices['price'][ $children[2] ] = 16.00;
		$expected_prices['price'][ $children[3] ] = 17.00;
		$expected_prices['price'][ $children[4] ] = 18.00;
		$expected_prices['price'][ $children[5] ] = 19.00;

		$expected_prices['regular_price'][ $children[0] ] = 10.00;
		$expected_prices['regular_price'][ $children[1] ] = 15.00;
		$expected_prices['regular_price'][ $children[2] ] = 16.00;
		$expected_prices['regular_price'][ $children[3] ] = 17.00;
		$expected_prices['regular_price'][ $children[4] ] = 18.00;
		$expected_prices['regular_price'][ $children[5] ] = 19.00;

		$expected_prices['sale_price'][ $children[0] ] = 8.00;
		$expected_prices['sale_price'][ $children[1] ] = 15.00;
		$expected_prices['sale_price'][ $children[2] ] = 16.00;
		$expected_prices['sale_price'][ $children[3] ] = 17.00;
		$expected_prices['sale_price'][ $children[4] ] = 18.00;
		$expected_prices['sale_price'][ $children[5] ] = 19.00;

		$this->assertEquals( $expected_prices, $product->get_variation_prices() );

		$expected_attributes = array(
			'pa_size'   => array( 'small', 'large', 'huge' ),
			'pa_colour' => array(
				0 => 'red',
				2 => 'blue',
			),
			'pa_number' => array( '0', '1', '2' ),
		);
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );
	}

	/**
	 * Test variable and variations.
	 *
	 * @since 3.0.0
	 */
	public function test_variables_and_variations() {
		$product = new WC_Product_Variable();
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

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_regular_price( 10 );
		$variation->set_sku( 'CRUD DUMMY SKU VARIABLE GREEN' );
		$variation->set_manage_stock( 'no' );
		$variation->set_downloadable( 'no' );
		$variation->set_virtual( 'no' );
		$variation->set_stock_status( 'instock' );
		$variation->set_attributes( array( 'pa_color' => 'green' ) );
		$variation->save();

		$this->assertEquals( 'CRUD DUMMY SKU VARIABLE GREEN', $variation->get_sku() );
		$this->assertEquals( 10, $variation->get_price() );

		$product  = new WC_Product_Variable( $product->get_id() );
		$children = $product->get_children();
		$this->assertEquals( $variation->get_id(), $children[0] );

		$expected_attributes = array( 'pa_color' => array( 'green' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );

		$variation_2 = new WC_Product_Variation();
		$variation_2->set_parent_id( $product->get_id() );
		$variation_2->set_regular_price( 10 );
		$variation_2->set_sku( 'CRUD DUMMY SKU VARIABLE RED' );
		$variation_2->set_manage_stock( 'no' );
		$variation_2->set_downloadable( 'no' );
		$variation_2->set_virtual( 'no' );
		$variation_2->set_stock_status( 'instock' );
		$variation_2->set_attributes( array( 'pa_color' => 'red' ) );
		$variation_2->save();

		$this->assertEquals( 'CRUD DUMMY SKU VARIABLE RED', $variation_2->get_sku() );
		$this->assertEquals( 10, $variation_2->get_price() );

		$product  = new WC_Product_Variable( $product->get_id() );
		$children = $product->get_children();
		$this->assertEquals( $variation_2->get_id(), $children[1] );
		$this->assertEquals( 2, count( $children ) );

		$expected_attributes = array( 'pa_color' => array( 'green', 'red' ) );
		$this->assertEquals( $expected_attributes, $product->get_variation_attributes() );

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

		$product->set_name( 'Renamed Variable Product' );
		$product->save();
		$this->assertEquals( 'Renamed Variable Product', $product->get_name() );
	}

	/**
	 * Tests saving variation attribute via set_attribute.
	 */
	public function test_variation_save_attributes() {
		// Create a variable product with a color attribute.
		$product = new WC_Product_Variable();

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( 0 );
		$attribute->set_name( 'color' );
		$attribute->set_options( explode( WC_DELIMITER, 'green | red' ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product->set_attributes( array( $attribute ) );
		$product->save();

		// Create a new variation with the color 'green'.
		$variation = new WC_Product_Variation();
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

	/**
	 * Tests for set_default_attributes and get_default_attributes.
	 */
	public function test_save_default_attributes() {

		// Create a variable product with sold individually.
		$product = new WC_Product_Variable();
		$options = array( '1', '0', 'true', 'false' );

		$attribute_1 = new WC_Product_Attribute();
		$attribute_1->set_name( 'sample-attribute-0' );
		$attribute_1->set_visible( true );
		$attribute_1->set_variation( true );
		$attribute_1->set_options( $options );
		$attribute_2 = new WC_Product_Attribute();
		$attribute_2->set_name( 'sample-attribute-1' );
		$attribute_2->set_visible( true );
		$attribute_2->set_variation( true );
		$attribute_2->set_options( $options );
		$attribute_3 = new WC_Product_Attribute();
		$attribute_3->set_name( 'sample-attribute-2' );
		$attribute_3->set_visible( true );
		$attribute_3->set_variation( true );
		$attribute_3->set_options( $options );
		$attribute_4 = new WC_Product_Attribute();
		$attribute_4->set_name( 'sample-attribute-3' );
		$attribute_4->set_visible( true );
		$attribute_4->set_variation( true );
		$attribute_4->set_options( $options );
		$attribute_5 = new WC_Product_Attribute();
		$attribute_5->set_name( 'sample-attribute-4' );
		$attribute_5->set_visible( true );
		$attribute_5->set_variation( true );
		$attribute_5->set_options( $options );
		$attribute_6 = new WC_Product_Attribute();
		$attribute_6->set_name( 'sample-attribute-5' );
		$attribute_6->set_visible( true );
		$attribute_6->set_variation( true );
		$attribute_6->set_options( $options );
		$attribute_7 = new WC_Product_Attribute();
		$attribute_7->set_name( 'sample-attribute-6' );
		$attribute_7->set_visible( true );
		$attribute_7->set_variation( true );
		$attribute_7->set_options( $options );
		$attribute_8 = new WC_Product_Attribute();
		$attribute_8->set_name( 'sample-attribute-7' );
		$attribute_8->set_visible( true );
		$attribute_8->set_variation( true );
		$attribute_8->set_options( $options );
		$attribute_9 = new WC_Product_Attribute();
		$attribute_9->set_name( 'sample-attribute-8' );
		$attribute_9->set_visible( true );
		$attribute_9->set_variation( true );
		$attribute_9->set_options( $options );
		$attribute_10 = new WC_Product_Attribute();
		$attribute_10->set_name( 'sample-attribute-9' );
		$attribute_10->set_visible( true );
		$attribute_10->set_variation( true );
		$attribute_10->set_options( $options );

		$product->set_attributes( array( $attribute_1, $attribute_2, $attribute_3, $attribute_4, $attribute_5, $attribute_6, $attribute_7, $attribute_8, $attribute_9, $attribute_10 ) );
		$product->save();

		// Save with a set of FALSE equivalents and some values we expect to come through as true.  We should see
		// string types with a value of '0' making it through filtration.
		$test_object           = new stdClass();
		$test_object->property = '12345';
		$product->set_default_attributes(
			array(
				'sample-attribute-0' => 0,
				'sample-attribute-1' => false,
				'sample-attribute-2' => '',
				'sample-attribute-3' => null,
				'sample-attribute-4' => '0',
				'sample-attribute-5' => 1,
				'sample-attribute-6' => 'true',
				'sample-attribute-7' => 'false',
				'sample-attribute-8' => array( 'exists' => 'false' ),
				'sample-attribute-9' => $test_object,
			)
		);
		$product->save();
		$product_id = $product->get_id();

		// Revive the product from the database and analyze results.
		$product            = wc_get_product( $product_id );
		$default_attributes = $product->get_default_attributes();
		$this->assertEquals(
			array(
				'sample-attribute-4' => '0',
				'sample-attribute-5' => '1',
				'sample-attribute-6' => 'true',
				'sample-attribute-7' => 'false',
			),
			$default_attributes
		);
	}

	/**
	 * Tests Product Variable data store child_has_dimension function when variation has a dimension (width).
	 */
	public function test_variable_child_has_dimensions() {
		$product = new WC_Product_Variable();
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_width( 10 );
		$variation->save();

		$product = wc_get_product( $product->get_id() );

		$store = WC_Data_Store::load( 'product-variable' );

		$this->assertTrue( $store->child_has_dimensions( $product ) );
	}

	/**
	 * Tests Product Variable data store child_has_dimension function when variation has no dimensions.
	 */
	public function test_variable_child_has_dimensions_no_dimensions() {
		$product = new WC_Product_Variable();
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->save();

		$product = wc_get_product( $product->get_id() );

		$store = WC_Data_Store::load( 'product-variable' );

		$this->assertFalse( $store->child_has_dimensions( $product ) );
	}

	/**
	 * Tests Product data store get_on_sale_products function.
	 */
	public function test_get_on_sale_products() {
		$product_store = WC_Data_Store::load( 'product' );

		$sale_product = WC_Helper_Product::create_simple_product();
		$sale_product->set_sale_price( 3.49 );
		$sale_product->set_regular_price( 3.99 );
		$sale_product->set_price( $sale_product->get_sale_price() );
		$sale_product->save();

		$not_sale_product = WC_Helper_Product::create_simple_product();
		$not_sale_product->set_regular_price( 4.00 );
		$not_sale_product->set_price( $not_sale_product->get_regular_price() );
		$not_sale_product->save();

		$future_sale_product = WC_Helper_Product::create_simple_product();
		$future_sale_product->set_date_on_sale_from( 'tomorrow' );
		$future_sale_product->set_regular_price( 6.49 );
		$future_sale_product->set_sale_price( 5.99 );
		$future_sale_product->set_price( $future_sale_product->get_regular_price() );
		$future_sale_product->save();

		$variable_draft_product = WC_Helper_Product::create_variation_product();
		$variable_draft_product->set_status( 'draft' );
		$variable_draft_product->save();
		$children                     = $variable_draft_product->get_children();
		$variable_draft_product_child = wc_get_product( $children[0] );
		$variable_draft_product_child->set_sale_price( 8 );
		$variable_draft_product_child->save();

		$sale_products    = $product_store->get_on_sale_products();
		$sale_product_ids = wp_list_pluck( $sale_products, 'id' );
		$sale_product_ids = array_map( 'absint', $sale_product_ids );

		$this->assertContains( $sale_product->get_id(), $sale_product_ids );
		$this->assertNotContains( $not_sale_product->get_id(), $sale_product_ids );
		$this->assertNotContains( $future_sale_product->get_id(), $sale_product_ids );
		$this->assertNotContains( $variable_draft_product->get_id(), $sale_product_ids );
		$this->assertNotContains( $variable_draft_product_child->get_id(), $sale_product_ids );
	}

	/**
	 * Tests automatic generation of variation name.
	 */
	public function test_generate_product_title() {
		$product = new WC_Product();
		$product->set_name( 'Test Product' );
		$product->save();

		$one_attribute_variation = new WC_Product_Variation();
		$one_attribute_variation->set_parent_id( $product->get_id() );
		$one_attribute_variation->set_attributes( array( 'color' => 'Green' ) );
		$one_attribute_variation->save();

		$two_attribute_variation = new WC_Product_Variation();
		$two_attribute_variation->set_parent_id( $product->get_id() );
		$two_attribute_variation->set_attributes(
			array(
				'color' => 'Green',
				'size'  => 'Large',
			)
		);
		$two_attribute_variation->save();

		$multiword_attribute_variation = new WC_Product_Variation();
		$multiword_attribute_variation->set_parent_id( $product->get_id() );
		$multiword_attribute_variation->set_attributes(
			array(
				'color'          => 'Green',
				'mounting-plate' => 'galaxy-s6',
				'support'        => 'one-year',
			)
		);
		$multiword_attribute_variation->save();

		// Check the one attribute variation title.
		$this->assertEquals( 'Test Product - Green', $one_attribute_variation->get_name() );

		// Check the two attribute variation title.
		$this->assertEquals( 'Test Product - Green, Large', $two_attribute_variation->get_name() );

		// Check the variation with a multiword attribute name.
		$this->assertEquals( 'Test Product', $multiword_attribute_variation->get_name() );
	}

	/**
	 * Tests disabling of automatic generation of variation name.
	 */
	public function test_generate_product_title_disable() {
		add_filter( 'woocommerce_product_variation_title_include_attributes', '__return_false' );

		$product = new WC_Product();
		$product->set_name( 'Test Product' );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'color' => 'green' ) );
		$variation->save();

		$loaded_variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( 'Test Product', $loaded_variation->get_name() );
	}

	/**
	 * Tests automatic generation of variation name for variation with no attributes.
	 */
	public function test_generate_product_title_no_attributes() {
		$product = new WC_Product();
		$product->set_name( 'Test Product' );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array() );
		$variation->save();

		$loaded_variation = wc_get_product( $variation->get_id() );
		$this->assertEquals( 'Test Product', $loaded_variation->get_name() );
	}

	/**
	 * Tests Product variation attribute_summary prop and its update on data store read.
	 *
	 * @since 3.6.0
	 * @return void
	 */
	public function test_generate_variation_attribute_summary() {
		$product = new WC_Product();
		$product->set_name( 'Test Product' );

		$product->save();

		$one_attribute_variation = new WC_Product_Variation();
		$one_attribute_variation->set_parent_id( $product->get_id() );
		$one_attribute_variation->set_attributes( array( 'color' => 'Green' ) );
		$one_attribute_variation->save();

		$two_attribute_variation = new WC_Product_Variation();
		$two_attribute_variation->set_parent_id( $product->get_id() );
		$two_attribute_variation->set_attributes(
			array(
				'color' => 'Green',
				'size'  => 'Large',
			)
		);
		$two_attribute_variation->save();

		$multiword_attribute_variation = new WC_Product_Variation();
		$multiword_attribute_variation->set_parent_id( $product->get_id() );
		$multiword_attribute_variation->set_attributes(
			array(
				'color'          => 'Green',
				'mounting-plate' => 'galaxy-s6',
				'support'        => 'one-year',
			)
		);
		$multiword_attribute_variation->save();

		// Check the one attribute variation title.
		$this->assertEquals( 'color: Green', $one_attribute_variation->get_attribute_summary() );

		// Check the two attribute variation title.
		$this->assertEquals( 'color: Green, size: Large', $two_attribute_variation->get_attribute_summary() );

		// Check the variation with a multiword attribute name.
		$this->assertEquals( 'color: Green, mounting-plate: galaxy-s6, support: one-year', $multiword_attribute_variation->get_attribute_summary() );

		// Add attributes to parent so that they are loaded correctly for variation.
		$attribute_1 = new WC_Product_Attribute();
		$attribute_1->set_name( 'color' );
		$attribute_1->set_options( array( 'Green', 'Blue' ) );
		$attribute_1->set_position( '0' );
		$attribute_1->set_visible( 1 );
		$attribute_1->set_variation( 1 );

		$attribute_2 = new WC_Product_Attribute();
		$attribute_2->set_name( 'size' );
		$attribute_2->set_options( array( 'Large', 'Not so Large' ) );
		$attribute_2->set_position( '1' );
		$attribute_2->set_visible( 1 );
		$attribute_2->set_variation( 1 );

		$attributes = array(
			$attribute_1,
			$attribute_2,
		);

		$product->set_attributes( $attributes );
		$product->save();

		$two_attribute_variation->set_attributes(
			array(
				'color' => 'Blue',
				'size'  => 'Not so Large',
			)
		);
		$two_attribute_variation->save();

		// Remove the record from the db to simulate existing variation with invalid excerpt set.
		$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, array( 'post_excerpt' => '_EMPTY_' ), array( 'ID' => $two_attribute_variation->get_id() ) );

		// Read on new instance should get correct value.
		$two_attribute_variation_2 = new WC_Product_Variation( $two_attribute_variation->get_id() );
		$this->assertEquals( 'color: Blue, size: Not so Large', $two_attribute_variation_2->get_attribute_summary() );

	}

	/**
	 * Test to make sure meta can still be set while hooked using save_post.
	 * https://github.com/woocommerce/woocommerce/issues/13960
	 *
	 * @since 3.0.1
	 */
	public function test_product_meta_save_post() {
		$product = new WC_Product();
		$product->set_name( 'Test Product' );
		$product->save();
		update_post_meta( $product->get_id(), '_test2', 'default' ); // this is the value we don't want to get back.

		// This takes place of WC_Meta_Box do_action( 'woocommerce_admin_process_product_object ' ) just adding simple meta.
		$product->update_meta_data( '_test', 'hello' );
		$product->set_name( 'Test Product_' );

		add_action( 'save_post', array( 'WC_Helper_Product', 'save_post_test_update_meta_data_direct' ), 11 );
		$product->save();

		$test  = get_post_meta( $product->get_id(), '_test', true );
		$test2 = get_post_meta( $product->get_id(), '_test2', true );

		$this->assertEquals( 'hello', $test );
		$this->assertEquals( 'world', $test2 ); // this would be 'default' without the force meta refresh in WC_Product_Data_Store::update().
		$this->assertEquals( 'world', $product->get_meta( '_test2' ) );
		$this->assertEquals( 'Test Product_', $product->get_name() );

		remove_action( 'save_post', array( 'WC_Helper_Product', 'save_post_test_update_meta_data_direct' ) );
	}

	/**
	 * Test Product search functionality.
	 *
	 * @return void
	 */
	public function test_search_products() {
		// Create some products to search for.
		$product = new WC_Product();
		$product->set_regular_price( 42 );
		$product->set_name( 'Blue widget' );
		$product->set_sku( 'blue-widget-1' );
		$product->set_description( "You bet I'm agitated! I may be surrounded by insanity, but I am not insane. I suggest you drop it, Mr. Data. Not if I weaken first. Earl Grey tea, watercress sandwiches... and Bularian canapés? Are you up for promotion? and attack the Romulans. Yesterday I did not know how to eat gagh." );
		$product->save();

		$product2 = new WC_Product();
		$product2->set_regular_price( 42 );
		$product2->set_name( 'Red widget' );
		$product2->set_sku( 'red-widget-1' );
		$product2->set_description( "and attack the Romulans. Fear is the true enemy, the only enemy. The game's not big enough unless it scares you a little. What? We're not at all alike! Now, how the hell do we defeat an enemy that knows us better than we know ourselves? Mr. Worf, you do remember how to fire phasers?" );
		$product2->save();

		$product3 = new WC_Product();
		$product3->set_regular_price( 42 );
		$product3->set_name( 'Green widget' );
		$product3->set_sku( 'green-widget-1' );
		$product3->set_description( 'For an android with no feelings, he sure managed to evoke them in others. Then maybe you should consider this: if anything happens to them, Starfleet is going to want a full investigation. We have a saboteur aboard.' );
		$product3->save();

		$product4 = new WC_Product();
		$product4->set_regular_price( 42 );
		$product4->set_name( 'Another green widget' );
		$product4->set_sku( 'green-widget-2' );
		$product4->set_description( 'For an android with no feelings, he sure managed to evoke them in others. Then maybe you should consider this: if anything happens to them, Starfleet is going to want a full investigation. We have a saboteur aboard.' );
		$product4->save();

		$data_store = WC_Data_Store::load( 'product' );

		// Search some things :).
		$results = $data_store->search_products( 'green', '', true, true );
		$this->assertNotContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertContains( $product3->get_id(), $results );
		$this->assertContains( $product4->get_id(), $results );

		$results = $data_store->search_products( 'blue-widget-1', '', true, true );
		$this->assertContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertNotContains( $product3->get_id(), $results );
		$this->assertNotContains( $product4->get_id(), $results );

		$results = $data_store->search_products( 'blue-widget-1 OR green-widget', '', true, true );
		$this->assertContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertContains( $product3->get_id(), $results );
		$this->assertContains( $product4->get_id(), $results );

		$results = $data_store->search_products( 'blue-widget-1     OR       green-widget', '', true, true );
		$this->assertContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertContains( $product3->get_id(), $results );
		$this->assertContains( $product4->get_id(), $results );

		$results = $data_store->search_products( '"green widget"', '', true, true );
		$this->assertNotContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertContains( $product3->get_id(), $results );
		$this->assertContains( $product4->get_id(), $results );

		$results = $data_store->search_products( 'Another widget', '', true, true );
		$this->assertNotContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertNotContains( $product3->get_id(), $results );
		$this->assertContains( $product4->get_id(), $results );

		$results = $data_store->search_products( '"Fear is the true enemy"', '', true, true );
		$this->assertNotContains( $product->get_id(), $results );
		$this->assertContains( $product2->get_id(), $results );
		$this->assertNotContains( $product3->get_id(), $results );
		$this->assertNotContains( $product4->get_id(), $results );

		$results = $data_store->search_products( 'green', '', true, true, 1 );
		$this->assertEquals( 1, count( array_diff( $results, array( 0 ) ) ) );

		$results = $data_store->search_products( 'green', '', true, true, null, array( $product3->get_id() ) );
		$this->assertNotContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertContains( $product3->get_id(), $results );
		$this->assertNotContains( $product4->get_id(), $results );

		$results = $data_store->search_products( 'green', '', true, true, null, null, array( $product3->get_id() ) );
		$this->assertNotContains( $product->get_id(), $results );
		$this->assertNotContains( $product2->get_id(), $results );
		$this->assertNotContains( $product3->get_id(), $results );
		$this->assertContains( $product4->get_id(), $results );
	}

	/**
	 * Test WC_Product_Data_Store_CPT::create_all_product_variations
	 */
	public function test_variable_create_all_product_variations() {
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Variable Product' );

		$attribute_1 = new WC_Product_Attribute();
		$attribute_1->set_name( 'color' );
		$attribute_1->set_visible( true );
		$attribute_1->set_variation( true );
		$attribute_1->set_options( array( 'red', 'green', 'blue' ) );

		$attribute_2 = new WC_Product_Attribute();
		$attribute_2->set_name( 'size' );
		$attribute_2->set_visible( true );
		$attribute_2->set_variation( true );
		$attribute_2->set_options( array( 'small', 'medium', 'large' ) );

		$attribute_3 = new WC_Product_Attribute();
		$attribute_3->set_name( 'pattern' );
		$attribute_3->set_visible( true );
		$attribute_3->set_variation( true );
		$attribute_3->set_options( array( 'striped', 'polka-dot', 'plain' ) );

		$attributes = array(
			$attribute_1,
			$attribute_2,
			$attribute_3,
		);

		$product->set_attributes( $attributes );
		$product_id = $product->save();

		// Test all variations get linked.
		$data_store = WC_Data_Store::load( 'product' );
		$count      = $data_store->create_all_product_variations( wc_get_product( $product_id ) );
		$this->assertEquals( 27, $count );

		// Test duplicates are not created.
		$count = $data_store->create_all_product_variations( wc_get_product( $product_id ) );
		$this->assertEquals( 0, $count );
	}

	/**
	 * Test WC_Product_Data_Store_CPT::create_all_product_variations
	 */
	public function test_variable_create_all_product_variations_limits() {
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Variable Product' );

		$attribute_1 = new WC_Product_Attribute();
		$attribute_1->set_name( 'color' );
		$attribute_1->set_visible( true );
		$attribute_1->set_variation( true );
		$attribute_1->set_options( array( 'red', 'green', 'blue' ) );

		$attribute_2 = new WC_Product_Attribute();
		$attribute_2->set_name( 'size' );
		$attribute_2->set_visible( true );
		$attribute_2->set_variation( true );
		$attribute_2->set_options( array( 'small', 'medium', 'large' ) );

		$attribute_3 = new WC_Product_Attribute();
		$attribute_3->set_name( 'pattern' );
		$attribute_3->set_visible( true );
		$attribute_3->set_variation( true );
		$attribute_3->set_options( array( 'striped', 'polka-dot', 'plain' ) );

		$attributes = array(
			$attribute_1,
			$attribute_2,
			$attribute_3,
		);

		$product->set_attributes( $attributes );
		$product_id = $product->save();

		// Test creation with a limit of 10.
		$data_store = WC_Data_Store::load( 'product' );
		$count      = $data_store->create_all_product_variations( wc_get_product( $product_id ), 10 );
		$this->assertEquals( 10, $count );

		$count = $data_store->create_all_product_variations( wc_get_product( $product_id ), 10 );
		$this->assertEquals( 10, $count );

		$count = $data_store->create_all_product_variations( wc_get_product( $product_id ), 10 );
		$this->assertEquals( 7, $count );
	}

	/**
	 * Test find_matching_product_variation.
	 *
	 * @return void
	 */
	public function test_find_matching_product_variation() {
		$product    = WC_Helper_Product::create_variation_product();
		$data_store = WC_Data_Store::load( 'product' );
		$children   = $product->get_children();

		$match = $data_store->find_matching_product_variation( $product, array() );
		$this->assertEquals( 0, $match );

		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size' => 'small',
			)
		);
		$this->assertEquals( $children[0], $match );

		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size' => 'large',
			)
		);
		$this->assertEquals( $children[1], $match );

		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'small',
				'attribute_pa_colour' => '',
			)
		);
		$this->assertEquals( $children[0], $match );

		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'large',
				'attribute_pa_colour' => '',
			)
		);
		$this->assertEquals( $children[1], $match );

		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'small',
				'attribute_pa_colour' => 'red',
			)
		);
		$this->assertEquals( $children[0], $match );

		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'large',
				'attribute_pa_colour' => 'blue',
			)
		);
		$this->assertEquals( $children[1], $match );

		// Test against non matching colour, should still return first attribute.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'small',
				'attribute_pa_colour' => 'pink',
			)
		);
		$this->assertEquals( $children[0], $match );

		// Test against non matching colour, should still return first attribute.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'large',
				'attribute_pa_colour' => 'pink',
			)
		);
		$this->assertEquals( $children[1], $match );

		// Test non expected matches.
		// If second attribute in variation is any and you omit the first attribute it should not match anything.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_colour' => 'blue',
			)
		);
		$this->assertEquals( 0, $match );

		// If second attribute in variation is any and you pass a blank in the first attribute it should not match anything.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => '',
				'attribute_pa_colour' => 'red',
			)
		);
		$this->assertEquals( 0, $match );

		// Passing blanks as both attributes should not match anything.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => '',
				'attribute_pa_colour' => '',
			)
		);
		$this->assertEquals( 0, $match );

		// Passing an empty array should not match anything.
		$match = $data_store->find_matching_product_variation(
			$product,
			array()
		);
		$this->assertEquals( 0, $match );

		// Test numeric attribute values.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'red',
				'attribute_pa_number' => '2',
			)
		);

		$this->assertEquals( $children[3], $match );

		// Test numeric attribute value 0.
		$match = $data_store->find_matching_product_variation(
			$product,
			array(
				'attribute_pa_size'   => 'huge',
				'attribute_pa_colour' => 'red',
				'attribute_pa_number' => '0',
			)
		);

		$this->assertEquals( $children[2], $match );
	}
}
