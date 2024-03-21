<?php
/**
 * Unit tests for the WC_Order_Item_Product class.
 *
 * @package WooCommerce\Tests\Order_Items
 * @since 3.2.0
 */

/**
 * Order Item Product unit tests.
 */
class WC_Tests_Order_Item_Product extends WC_Unit_Test_Case {

	/**
	 * Test generic setters and getters for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_generic_setters_getters() {
		$simple_product = new WC_Product_Simple();
		$simple_product->save();

		$variation_product = new WC_Product_Variation();
		$variation_product->save();

		$product_item = new WC_Order_Item_Product();

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

		$product_item->set_total( '' );
		$this->assertEquals( '0.00', $product_item->get_total() );

		$product_item->set_subtotal_tax( '0.50' );
		$this->assertEquals( '0.50', $product_item->get_subtotal_tax() );

		$product_item->set_total_tax( '0.30' );
		$this->assertEquals( '0.30', $product_item->get_total_tax() );
	}

	/**
	 * Test get item shipping total
	 */
	public function test_get_item_shipping_total() {
		$order    = WC_Helper_Order::create_order_with_fees_and_shipping();
		$order_id = $order->get_id();

		array_values( $order->get_items( 'shipping' ) )[0]->set_total( '10.17' );
		$order->save();

		$order = wc_get_order( $order_id );
		$this->assertEquals( '10.17', $order->get_line_total( array_values( $order->get_items( 'shipping' ) )[0], true ) );

		array_values( $order->get_items( 'shipping' ) )[0]->set_total( '' );
		$order->save();

		$order = wc_get_order( $order_id );
		$this->assertEquals( '0.00', $order->get_line_total( array_values( $order->get_items( 'shipping' ) )[0], true ) );
	}

	/**
	 * Test set_taxes and get_taxes for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_set_get_taxes() {
		$product_item = new WC_Order_Item_Product();

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
		$simple_product = new WC_Product_Simple();
		$simple_product->set_name( 'Test Simple' );
		$simple_product->set_tax_class( 'reduced-rate' );
		$simple_product->save();

		$parent_product = new WC_Product_Variable();
		$parent_product->set_name( 'Test Parent' );
		$parent_product->save();

		$variation_product = new WC_Product_Variation();
		$variation_product->set_name( 'Test Variation' );
		$variation_product->set_parent_id( $parent_product->get_id() );
		$variation_product->set_attributes( array( 'color' => 'Green' ) );
		$variation_product->save();

		// Simple product.
		$product_item = new WC_Order_Item_Product();
		$product_item->set_product( $simple_product );
		$this->assertEquals( 'Test Simple', $product_item->get_name() );
		$this->assertEquals( $simple_product->get_id(), $product_item->get_product_id() );
		$this->assertEquals( 0, $product_item->get_variation_id() );
		$this->assertEquals( 'reduced-rate', $product_item->get_tax_class() );

		$retrieved = $product_item->get_product();
		$this->assertEquals( $simple_product->get_id(), $retrieved->get_id() );

		// Variation product.
		$product_item = new WC_Order_Item_Product();
		$product_item->set_product( $variation_product );
		$this->assertEquals( 'Test Parent - Green', $product_item->get_name() );
		$this->assertEquals( $parent_product->get_id(), $product_item->get_product_id() );
		$this->assertEquals( $variation_product->get_id(), $product_item->get_variation_id() );
		$this->assertEquals( '', $product_item->get_tax_class() );
		$this->assertEquals( 'Green', $product_item->get_meta( 'color' ) );
		$retrieved = $product_item->get_product();
		$this->assertEquals( $variation_product->get_id(), $retrieved->get_id() );
	}

	/**
	 * Test get_item_download_url method for WC_Order_Item_Product.
	 *
	 * @since 3.2.0
	 */
	public function test_get_item_download_url() {
		$product = new WC_Product_Simple();
		$product->save();

		$order = new WC_Order();
		$order->set_billing_email( 'test@woo.com' );
		$order->save();

		$product_item = new WC_Order_Item_Product();
		$product_item->set_product( $product );
		$product_item->set_order_id( $order->get_id() );

		$expected_regex = '/download_file=.*&order=wc_order_.*&email=test%40woo.com&key=100/';
		$this->assertMatchesRegularExpression( $expected_regex, $product_item->get_item_download_url( 100 ) );
	}

	/**
	 * Test the get_formatted_meta_data method.
	 *
	 * @since 3.3.0
	 */
	public function test_get_formatted_meta_data() {
		$parent_product = new WC_Product_Variable();
		$parent_product->set_name( 'Test Parent' );
		$parent_product->save();

		$variation_product = new WC_Product_Variation();
		$variation_product->set_name( 'Test Variation' );
		$variation_product->set_parent_id( $parent_product->get_id() );
		$variation_product->set_attributes(
			array(
				'color' => 'Green',
				'size'  => 'Large',
			)
		);
		$variation_product->save();

		$product_item = new WC_Order_Item_Product();
		$product_item->set_product( $variation_product );
		$product_item->add_meta_data( 'testkey', 'testval', true );
		$product_item->save();

		// Test with show_all on.
		$formatted          = $product_item->get_formatted_meta_data( '_', true );
		$formatted_as_array = array();
		foreach ( $formatted as $f ) {
			$formatted_as_array[] = (array) $f;
		}
		$this->assertEquals(
			array(
				array(
					'key'           => 'color',
					'value'         => 'Green',
					'display_key'   => 'color',
					'display_value' => "<p>Green</p>\n",
				),
				array(
					'key'           => 'size',
					'value'         => 'Large',
					'display_key'   => 'size',
					'display_value' => "<p>Large</p>\n",
				),
				array(
					'key'           => 'testkey',
					'value'         => 'testval',
					'display_key'   => 'testkey',
					'display_value' => "<p>testval</p>\n",
				),
			),
			$formatted_as_array
		);

		// Test with show_all off.
		$formatted          = $product_item->get_formatted_meta_data( '_', false );
		$formatted_as_array = array();
		foreach ( $formatted as $f ) {
			$formatted_as_array[] = (array) $f;
		}
		$this->assertEquals(
			array(
				array(
					'key'           => 'testkey',
					'value'         => 'testval',
					'display_key'   => 'testkey',
					'display_value' => "<p>testval</p>\n",
				),
			),
			$formatted_as_array
		);

		// Test with an exclude prefix. Should exclude everything since they're either in the title or in the exclude prefix.
		$formatted = $product_item->get_formatted_meta_data( 'test', false );
		$this->assertEmpty( $formatted );
	}

	/**
	 * Test the get_formatted_meta_data method.
	 *
	 * @since x.x.x
	 */
	public function test_get_all_formatted_meta_data() {
		$parent_product = new WC_Product_Variable();
		$parent_product->set_name( 'Test Parent' );
		$parent_product->save();

		$variation_product = new WC_Product_Variation();
		$variation_product->set_name( 'Test Variation' );
		$variation_product->set_parent_id( $parent_product->get_id() );
		$variation_product->set_attributes(
			array(
				'color' => 'Green',
				'size'  => 'Large',
			)
		);
		$variation_product->save();

		$product_item = new WC_Order_Item_Product();
		$product_item->set_product( $variation_product );
		$product_item->add_meta_data( 'testkey', 'testval', true );
		$product_item->save();

		// Test with show_all set to default.
		$formatted          = $product_item->get_all_formatted_meta_data( '_' );
		$formatted_as_array = array();
		foreach ( $formatted as $f ) {
			$formatted_as_array[] = (array) $f;
		}
		$this->assertEquals(
			array(
				array(
					'key'           => 'color',
					'value'         => 'Green',
					'display_key'   => 'color',
					'display_value' => "<p>Green</p>\n",
				),
				array(
					'key'           => 'size',
					'value'         => 'Large',
					'display_key'   => 'size',
					'display_value' => "<p>Large</p>\n",
				),
				array(
					'key'           => 'testkey',
					'value'         => 'testval',
					'display_key'   => 'testkey',
					'display_value' => "<p>testval</p>\n",
				),
			),
			$formatted_as_array
		);

		// Test with show_all off.
		$formatted          = $product_item->get_all_formatted_meta_data( '_', false );
		$formatted_as_array = array();
		foreach ( $formatted as $f ) {
			$formatted_as_array[] = (array) $f;
		}
		$this->assertEquals(
			array(
				array(
					'key'           => 'testkey',
					'value'         => 'testval',
					'display_key'   => 'testkey',
					'display_value' => "<p>testval</p>\n",
				),
			),
			$formatted_as_array
		);

		// Test with an exclude prefix. Should exclude everything since they're either in the title or in the exclude prefix.
		$formatted = $product_item->get_all_formatted_meta_data( 'test', false );
		$this->assertEmpty( $formatted );
	}

	/**
	 * Test the Array Access methods.
	 *
	 * @since 3.3.0
	 */
	public function test_arrayaccess() {
		$item = new WC_Order_Item_Product();

		// Test line_subtotal.
		$this->assertTrue( isset( $item['line_subtotal'] ) );
		$item->set_subtotal( 50 );
		$this->assertEquals( 50, $item->get_subtotal() );
		$this->assertEquals( $item->get_subtotal(), $item['line_subtotal'] );

		// Test line_subtotal_tax.
		$this->assertTrue( isset( $item['line_subtotal_tax'] ) );
		$item->set_subtotal_tax( 5 );
		$this->assertEquals( 5, $item->get_subtotal_tax() );
		$this->assertEquals( $item->get_subtotal_tax(), $item['line_subtotal_tax'] );

		// Test line_total.
		$this->assertTrue( isset( $item['line_total'] ) );
		$item->set_total( 55 );
		$this->assertEquals( 55, $item->get_total() );
		$this->assertEquals( $item->get_total(), $item['line_total'] );

		// Test line_tax.
		$this->assertTrue( isset( $item['line_tax'] ) );
		$item->set_total_tax( 5 );
		$this->assertEquals( 5, $item->get_total_tax() );
		$this->assertEquals( $item->get_total_tax(), $item['line_tax'] );

		// Test line_tax_data.
		$this->assertTrue( isset( $item['line_tax_data'] ) );
		$item->set_taxes(
			array(
				'total'    => array( 5 ),
				'subtotal' => array( 5 ),
			)
		);
		$this->assertEquals(
			array(
				'total'    => array( 5 ),
				'subtotal' => array( 5 ),
			),
			$item->get_taxes()
		);
		$this->assertEquals( $item->get_taxes(), $item['line_tax_data'] );

		// Test qty.
		$this->assertTrue( isset( $item['qty'] ) );
		$item->set_quantity( 150 );
		$this->assertEquals( 150, $item->get_quantity() );
		$this->assertEquals( $item->get_quantity(), $item['qty'] );

		// Test item_meta_array.
		$this->assertTrue( isset( $item['item_meta_array'] ) );
		$item->update_meta_data( 'test', 'val', 0 );
		$this->assertInstanceOf( 'WC_Meta_Data', current( $item->get_meta_data() ) );
		$this->assertEquals( current( $item->get_meta_data() ), $item['item_meta_array'][''] );
		unset( $item['item_meta_array'] );
		$this->assertEquals( array(), $item->get_meta_data() );

		// Test default.
		$this->assertFalse( $item->meta_exists( 'foo' ) );
		$item->add_meta_data( 'foo', 'bar' );
		$this->assertEquals( 'bar', $item->get_meta( 'foo' ) );
	}
}
