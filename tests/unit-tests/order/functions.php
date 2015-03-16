<?php

namespace WooCommerce\Tests\Order;

/**
 * Class Functions
 * @package WooCommerce\Tests\Order
 * @since 2.3
 */
class Functions extends \WC_Unit_Test_Case {

	/**
	 * Test wc_get_order_statuses()
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
	 * Test wc_is_order_status()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_is_order_status() {

		$this->assertEquals( true,  wc_is_order_status( 'wc-pending' ) );
		$this->assertEquals( false, wc_is_order_status( 'wc-another-status' ) );
	}

	/**
	 * Test wc_get_order_status_name()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_status_name() {

		$this->assertEquals( _x( 'Pending Payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'wc-pending' ) );
		$this->assertEquals( _x( 'Pending Payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'pending' ) );
	}

	/**
	 * Test wc_ship_to_billing_address_only()
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
}
