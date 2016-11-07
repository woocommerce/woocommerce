<?php
/**
 * Products Data Store Tests
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
	 * Test product store read for a simple product.
	 *
	 * @since 2.7.0
	 */
	function test_product_store_read_simple() {
		$simple		= WC_Helper_Product::create_simple_product();
		$product	   = new WC_Product_Simple( $simple->get_id() );

		$this->assertEquals( 'Dummy Product', $product->get_name() );
		$this->assertEquals( '10', $product->get_regular_price() );
	}

	/**
	 * Test product store read for an external product.
	 *
	 * @since 2.7.0
	 */
	function test_product_store_read_external() {
		$external	  = WC_Helper_Product::create_external_product();
		$product	   = new WC_Product_External( $external->get_id() );

		$this->assertEquals( 'Dummy External Product', $product->get_name() );
		$this->assertEquals( '10', $product->get_regular_price() );
		$this->assertEquals( 'http://woocommerce.com', $product->get_product_url() );
		$this->assertEquals( 'Buy external product', $product->get_button_text() );
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
		 $this->assertEquals( '42', $product->get_regular_price() );
		 $this->assertEquals( 'My Product', $product->get_name() );
		 $read_product = new WC_Product_Simple( $product->get_id() );
		 $this->assertEquals( '42', $read_product->get_regular_price() );
		 $this->assertEquals( 'My Product', $read_product->get_name() );
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
		$this->assertEquals( 'My Grouped Product', $product->get_name() );
		$this->assertEquals( array( $simple_product->get_id() ), $product->get_children() );
		$read_product = new WC_Product_Grouped( $product->get_id() );
		$this->assertEquals( 'My Grouped Product', $read_product->get_name() );
		$this->assertEquals( array( $simple_product->get_id() ), $read_product->get_children() );

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

		 $this->assertEquals( '42', $product->get_regular_price() );
		 $this->assertEquals( 'Test CRUD', $product->get_button_text() );
		 $this->assertEquals( 'http://automattic.com', $product->get_product_url() );
		 $this->assertEquals( 'My External Product', $product->get_name() );

		 $read_product = new WC_Product_External( $product->get_id() );

		 $this->assertEquals( '42', $read_product->get_regular_price() );
		 $this->assertEquals( 'Test CRUD', $read_product->get_button_text() );
		 $this->assertEquals( 'http://automattic.com', $read_product->get_product_url() );
		 $this->assertEquals( 'My External Product', $read_product->get_name() );
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
	 * Test updating an grouped product.
	 *
	 * @since 2.7.0
	 */
	function test_grouped_product_update() {
		$product        = WC_Helper_Product::create_grouped_product();
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

}
