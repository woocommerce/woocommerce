<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Shortcode_Checkout.
 *
 * @package WooCommerce\Tests\Shortcodes
 */

/**
 * Class WC_Shortcode_Checkout_Test.
 */
class WC_Shortcode_Checkout_Test extends WC_Unit_Test_Case {

	/**
	 * Restore the request and query state touched by this test.
	 */
	public function tearDown(): void {
		global $wp;

		unset( $_GET['key'] );
		unset( $_GET['pay_for_order'] );
		unset( $wp->query_vars['order-received'] );
		unset( $wp->query_vars['order-pay'] );

		parent::tearDown();
	}

	/**
	 * An array `key` must be treated as absent rather than reaching hash_equals().
	 */
	public function test_order_received_treats_array_order_key_as_absent() {
		global $wp;

		$order = WC_Helper_Order::create_order( 0 );

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = array( $order->get_order_key() );

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'woocommerce-thankyou-order-details', $output );
		$this->assertStringNotContainsString( (string) $order->get_order_number(), $output );
	}

	/**
	 * An array `key` on the pay-for-order page must be rejected rather than reaching hash_equals().
	 */
	public function test_order_pay_treats_array_order_key_as_invalid() {
		global $wp;

		$order = WC_Helper_Order::create_order( 0 );

		$wp->query_vars['order-pay'] = $order->get_id();
		$_GET['pay_for_order']       = 'true';
		$_GET['key']                 = array( $order->get_order_key() );

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Sorry, this order is invalid and cannot be paid for.', $output );
	}
}
