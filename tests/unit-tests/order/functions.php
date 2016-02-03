<?php

namespace WooCommerce\Tests\Order;

/**
 * Class Functions.
 * @package WooCommerce\Tests\Order
 * @since 2.3
 */
class Functions extends \WC_Unit_Test_Case {

	/**
	 * Test wc_get_order_statuses().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_statuses() {

		$order_statuses = apply_filters( 'wc_order_statuses', array(
			'wc-pending'    => _x( 'Pending Payment', 'Order status', 'woocommerce' ),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
			'wc-on-hold'    => _x( 'On Hold', 'Order status', 'woocommerce' ),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
		) );

		$this->assertEquals( $order_statuses, wc_get_order_statuses() );
	}

	/**
	 * Test wc_is_order_status().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_is_order_status() {

		$this->assertEquals( true,  wc_is_order_status( 'wc-pending' ) );
		$this->assertEquals( false, wc_is_order_status( 'wc-another-status' ) );
	}

	/**
	 * Test wc_get_order_status_name().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_status_name() {

		$this->assertEquals( _x( 'Pending Payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'wc-pending' ) );
		$this->assertEquals( _x( 'Pending Payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'pending' ) );
	}

	/**
	 * Test wc_processing_order_count().
	 *
	 * @todo needs improvement when we have an orders helper
	 * @since 2.4
	 */
	public function test_wc_processing_order_count() {
		$this->assertEquals( 0, wc_processing_order_count() );
	}

	/**
	 * Test wc_orders_count().
	 *
	 * @todo needs improvement when we have an orders helper
	 * @since 2.4
	 */
	public function test_wc_orders_count() {
		foreach ( wc_get_order_statuses() as $status ) {
			$this->assertEquals( 0, wc_orders_count( $status ) );
		}

		// Invalid status returns 0
		$this->assertEquals( 0, wc_orders_count( 'unkown-status' ) );
	}

	/**
	 * Test wc_ship_to_billing_address_only().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_ship_to_billing_address_only() {

		$default = get_option( 'woocommerce_ship_to_destination' );

		update_option( 'woocommerce_ship_to_destination', 'shipping' );
		$this->assertEquals( false, wc_ship_to_billing_address_only() );

		update_option( 'woocommerce_ship_to_destination', 'billing_only' );
		$this->assertEquals( true, wc_ship_to_billing_address_only() );

		update_option( 'woocommerce_ship_to_destination', $default );
	}

	/**
	 * Test wc_get_order().
	 *
	 * @since 2.4.0
	 * @group test
	 */
	public function test_wc_get_order() {

		$order = \WC_Helper_Order::create_order();

		// Assert that $order is a WC_Order object
		$this->assertInstanceOf( 'WC_Order', $order );

		// Assert that wc_get_order() accepts a WC_Order object
		$this->assertInstanceOf( 'WC_Order', wc_get_order( $order ) );

		// Assert that wc_get_order() accepts a order post id.
		$this->assertInstanceOf( 'WC_Order', wc_get_order( $order->id ) );

		// Assert that a non-shop_order post returns false
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$this->assertFalse( wc_get_order( $post->ID ) );

		// Assert the return when $the_order args is false
		$this->assertFalse( wc_get_order( false ) );

		// Assert the return when $the_order args is a random (incorrect) id.
		$this->assertFalse( wc_get_order( 123456 ) );
	}
}
