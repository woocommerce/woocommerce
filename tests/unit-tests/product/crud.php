<?php
/**
 * CRUD Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.7.0
 */
class WC_Tests_Product_CRUD extends WC_Unit_Test_Case {

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
	 * Test deleting a product.
	 *
	 * @since 2.7.0
	 */
	function test_product_delete() {
		$product = WC_Helper_Product::create_external_product();
		$product->delete();
		$this->assertEquals( 0, $product->get_id() );
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
