<?php
/**
 * CRUD Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.7.0
 */
class WC_Tests_Product_CRUD extends WC_Unit_Test_Case {

	/**
	 * Test creating a new product.
	 *
	 * @since 2.7.0
	 */
	 function test_product_create() {
		 $product = new WC_Product;
		 $product->set_regular_price( 42 );
		 $product->set_name( 'My Product' );
		 $product->create();

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
	 * Test deleting a product.
	 *
	 * @since 2.7.0
	 */
	function test_product_delete() {
		$product = WC_Helper_Product::create_simple_product();
		$product->delete();
		$this->assertEquals( 0, $product->get_id() );
	}

	/**
	 * Test product setters and getters
	 * @todo needs tests for tags, categories, and attributes
	 * @since 2.7.0
	 */
	 public function test_product_getters_and_setters() {
		 $getters_and_setters = array(
			 'name'               => 'Test',
			 'slug'               => 'test',
			 'status'             => 'publish',
			 'catalog_visibility' => 'search',
			 'featured'           => false,
			 'description'        => 'Hello world',
			 'short_description'  => 'hello',
			 'sku'                => 'TEST SKU',
			 'regular_price'      => 15.00,
			 'sale_price'         => 10.00,
			 'date_on_sale_from'  => '1475798400',
			 'date_on_sale_to'    => '1477267200',
			 'total_sales'        => 20,
			 'tax_status'         => 'none',
			 'tax_class'          => '',
			 'manage_stock'       => true,
			 'stock_quantity'     => 10,
			 'stock_status'       => 'instock',
			 'backorders'         => 'notify',
			 'sold_individually'  => false,
			 'weight'             => 100,
			 'length'             => 10,
			 'width'              => 10,
			 'height'             => 10,
			 'upsell_ids'         => array( 2, 3 ),
			 'cross_sell_ids'     => array( 4, 5 ),
			 'parent_id'          => 0,
			 'reviews_allowed'    => true,
			 'default_attributes' => array(),
			 'purchase_note'      => 'A note',
			 'menu_order'         => 2,
		 );
		 $product = new WC_Product;
		  foreach ( $getters_and_setters as $function => $value ) {
			 $product->{"set_{$function}"}( $value );
		 }
		 $product->create();
		 $product = new WC_Product_Simple( $product->get_id() );
		 foreach ( $getters_and_setters as $function => $value ) {
			$this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		}
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
		 $product->create();

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
	 * Test external product setters and getters
	 *
	 * @since 2.7.0
	 */
	 public function test_external_product_getters_and_setters() {
		 $time = time();
		 $getters_and_setters = array(
			 'button_text' => 'Test Button Text',
			 'product_url' => 'http://wordpress.org',
		 );
		 $product = new WC_Product_External;
		  foreach ( $getters_and_setters as $function => $value ) {
			 $product->{"set_{$function}"}( $value );
			 $this->assertEquals( $value, $product->{"get_{$function}"}(), $function );
		 }
	 }
}
