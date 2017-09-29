<?php

/**
 * Order Item Product Tests.
 * @package WooCommerce\Tests\Order_Items
 * @since 3.2.0
 */
class WC_Tests_Order_Item_Product extends WC_Unit_Test_Case {

	/**
	 * Test generic setters and getters for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_generic_setters_getters() {
		$simple_product = new WC_Product_Simple;
		$simple_product->save();

		$variation_product = new WC_Product_Variation;
		$variation_product->save();

		$product_item = new WC_Order_Item_Product;

		$product_item->set_quantity( 3 );
		$this->assertEquals( 3, $product_item->get_quantity() );

		$product_item->set_tax_class( 'reduced-rate' );
		$this->assertEquals( 'reduced-rate', $product_item->get_tax_class() );

		$product_item->set_product_id( $simple_product->get_id() );
		$this->assertEquals( $simple_product->get_id(), $product_item->get_product_id() );

		$product_item->set_variation_id( $variation_product->get_id() );
		$this->assertEquals( $variation_product->get_id(), $product_item->get_variation_id() );

		$product_item->set_subtotal( '12.00' );
		$this->assertEquals( '12.00', $product_item->get_subtotal() );

		$product_item->set_total( '10.00' );
		$this->assertEquals( '10.00', $product_item->get_total() );

		$product_item->set_subtotal_tax( '0.50' );
		$this->assertEquals( '0.50', $product_item->get_subtotal_tax() );

		$product_item->set_total_tax( '0.30' );
		$this->assertEquals( '0.30', $product_item->get_total_tax() );
	}

	/**
	 * Test set_taxes and get_taxes for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_taxes() {
		$product_item = new WC_Order_Item_Product;

		$taxes = array(
			'total'    => array( '10', '2.4' ),
			'subtotal' => array( '12', '3.1' ),
		);
		$product_item->set_taxes( $taxes );
		$this->assertEquals( $taxes, $product_item->get_taxes() );
		$this->assertEquals( '12.4', $product_item->get_total_tax() );
		$this->assertEquals( '15.1', $product_item->get_subtotal_tax() );
	}

	/**
	 * Test set_product and get_product for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_product() {
		$simple_product = new WC_Product_Simple;
		$simple_product->set_name( 'Test Simple' );
		$simple_product->set_tax_class( 'reduced-rate' );
		$simple_product->save();

		$parent_product = new WC_Product_Variable;
		$parent_product->set_name( 'Test Parent' );
		$parent_product->save();

		$variation_product = new WC_Product_Variation;
		$variation_product->set_name( 'Test Variation' );
		$variation_product->set_parent_id( $parent_product->get_id() );
		$variation_product->save();

		// Simple product.
		$product_item = new WC_Order_Item_Product;
		$product_item->set_product( $simple_product );
		$this->assertEquals( 'Test Simple', $product_item->get_name() );
		$this->assertEquals( $simple_product->get_id(), $product_item->get_product_id() );
		$this->assertEquals( 0, $product_item->get_variation_id() );
		$this->assertEquals( 'reduced-rate', $product_item->get_tax_class() );

		$retrieved = $product_item->get_product();
		$this->assertEquals( $simple_product->get_id(), $retrieved->get_id() );

		// Variation product.
		$product_item = new WC_Order_Item_Product;
		$product_item->set_product( $variation_product );
		$this->assertEquals( 'Test Parent', $product_item->get_name() );
		$this->assertEquals( $parent_product->get_id(), $product_item->get_product_id() );
		$this->assertEquals( $variation_product->get_id(), $product_item->get_variation_id() );
		$this->assertEquals( '', $product_item->get_tax_class() );

		$retrieved = $product_item->get_product();
		$this->assertEquals( $variation_product->get_id(), $retrieved->get_id() );
	}

	/**
	 * Test get_item_download_url method for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_get_item_download_url() {
		$product = new WC_Product_Simple;
		$product->save();

		$order = new WC_Order;
		$order->set_billing_email( 'test@woocommerce.com' );
		$order->save();

		$product_item = new WC_Order_Item_Product;
		$product_item->set_product( $product );
		$product_item->set_order_id( $order->get_id() );

		$expected_regex = '/download_file=.*&order=wc_order_.*&email=test%40woocommerce.com&key=100/';
		$this->assertRegexp( $expected_regex, $product_item->get_item_download_url( 100 ) );
	}
}
