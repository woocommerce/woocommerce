<?php

/**
 * Order coupon tests.
 * @package WooCommerce\Tests\Orders
 */
class WC_Tests_Order_Coupons extends WC_Unit_Test_Case {

	/**
	 * Track ids.
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Setup an order.
	 */
	public function setUp() {
		$this->objects = array();

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'base' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_regular_price', '1000' );
		update_post_meta( $product->get_id(), '_price', '1000' );
		$product = wc_get_product( $product->get_id() );

		$coupon = new WC_Coupon;
		$coupon->set_code( 'test-coupon-1' );
		$coupon->set_amount( 1 );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->save();

		$coupon2 = new WC_Coupon;
		$coupon2->set_code( 'test-coupon-2' );
		$coupon2->set_amount( 20 );
		$coupon2->set_discount_type( 'percent' );
		$coupon2->save();

		$order = wc_create_order( array(
			'status'        => 'pending',
			'customer_id'   => 1,
			'customer_note' => '',
			'total'         => '',
		) );

		// Add order products
		$item = new WC_Order_Item_Product();
		$item->set_props( array(
			'product'  => $product,
			'quantity' => 1,
			'subtotal' => 909.09, // Ex tax.
			'total'    => 726.36,
		) );
		$item->save();
		$order->add_item( $item );

		$item = new WC_Order_Item_Coupon();
		$item->set_props( array(
			'code'         => 'test-coupon-1',
			'discount'     => 1.00,
			'discount_tax' => 0.01,
		) );
		$item->save();
		$order->add_item( $item );

		$item = new WC_Order_Item_Coupon();
		$item->set_props( array(
			'code'         => 'this-is-a-virtal-coupon',
			'discount'     => 181.82,
			'discount_tax' => 18.18,
		) );
		$item->save();
		$order->add_item( $item );

		$this->objects['coupons'][]      = $coupon;
		$this->objects['coupons'][]      = $coupon2;
		$this->objects['products'][]     = $product;
		$this->objects['order']          = $order;
		$this->objects['tax_rate_ids'][] = WC_Tax::_insert_tax_rate( array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		) );

		$order->calculate_totals( true );
		$order->save();
	}

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		foreach ( $this->objects['coupons'] + $this->objects['products'] as $object ) {
			$object->delete( true );
		}

		$this->objects['order']->delete( true );

		foreach ( $this->objects['tax_rate_ids'] as $tax_rate_id ) {
			WC_Tax::_delete_tax_rate( $tax_rate_id );
		}

		$this->objects = array();
	}

	/**
	 * Test: get_type
	 */
	function test_remove_coupon_from_order() {
		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		// Check it's expected.
		$this->assertEquals( 'shop_order', $order->get_type() );
		$this->assertEquals( '799', $order->get_total(), $order->get_total() );

		// Remove the virtual coupon. Total should be 999.
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '999', $order->get_total(), $order->get_total() );

		// Remove the other coupon. Total should be 1000.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '1000', $order->get_total(), $order->get_total() );

		// Reset.
		$this->tearDown();
		$this->setUp();

		// Do the above tests in reverse.
		$order->remove_coupon( 'test-coupon-1' );
		$this->assertEquals( '800', $order->get_total(), $order->get_total() );
		$order->remove_coupon( 'this-is-a-virtal-coupon' );
		$this->assertEquals( '1000', $order->get_total(), $order->get_total() );

		// Reset.
		$this->tearDown();
		$this->setUp();
	}

	/**
	 * Test: get_type
	 */
	function test_add_coupon_to_order() {
		$order_id = $this->objects['order']->get_id();
		$order    = wc_get_order( $order_id );

		$order->apply_coupon( 'test-coupon-2' );
		$this->assertEquals( '639.2', $order->get_total(), $order->get_total() );

		// Reset.
		$this->tearDown();
		$this->setUp();
	}
}
