<?php
/**
 * Products Factory Tests
 * @package WooCommerce\Tests\Product
 * @since 3.0.0
 */
class WC_Tests_Product_Factory extends WC_Unit_Test_Case {

	/**
	 * Test getting product type.
	 *
	 * @since 3.0.0
	 */
	function test_get_product_type() {
		$simple   = WC_Helper_Product::create_simple_product();
		$external = WC_Helper_Product::create_external_product();
		$grouped  = WC_Helper_Product::create_grouped_product();
		$variable = WC_Helper_Product::create_variation_product();
		$children = $variable->get_children();
		$child_id = $children[0];

		$this->assertEquals( 'simple', WC()->product_factory->get_product_type( $simple->get_id() ) );
		$this->assertEquals( 'external', WC()->product_factory->get_product_type( $external->get_id() ) );
		$this->assertEquals( 'grouped', WC()->product_factory->get_product_type( $grouped->get_id() ) );
		$this->assertEquals( 'variable', WC()->product_factory->get_product_type( $variable->get_id() ) );
		$this->assertEquals( 'variation', WC()->product_factory->get_product_type( $child_id ) );

		$simple->delete( true );
		$external->delete( true );
		$grouped->delete( true );
		$variable->delete( true );
	}

	/**
	 * Test the helper method that returns a class name for a specific product type.
	 *
	 * @since 3.0.0
	 */
	function test_get_classname_from_product_type() {
		$this->assertEquals( 'WC_Product_Grouped', WC()->product_factory->get_classname_from_product_type( 'grouped' ) );
		$this->assertEquals( 'WC_Product_Simple', WC()->product_factory->get_classname_from_product_type( 'simple' ) );
		$this->assertEquals( 'WC_Product_Variable', WC()->product_factory->get_classname_from_product_type( 'variable' ) );
		$this->assertEquals( 'WC_Product_Variation', WC()->product_factory->get_classname_from_product_type( 'variation' ) );
		$this->assertEquals( 'WC_Product_External', WC()->product_factory->get_classname_from_product_type( 'external' ) );
	}

	/**
	 * Tests getting a product using the factory.
	 *
	 * @since 3.0.0
	 */
	function test_get_product() {
		$test_product = WC_Helper_Product::create_simple_product();
		$get_product  = WC()->product_factory->get_product( $test_product->get_id() );
		$this->assertEquals( $test_product->get_data(), $get_product->get_data() );
	}

	/**
	 * Tests that an incorrect product returns false.
	 *
	 * @since 3.0.0
	 */
	function test_get_invalid_product_returns_false() {
		$product = WC()->product_factory->get_product( 50000 );
		$this->assertFalse( $product );
	}

}
