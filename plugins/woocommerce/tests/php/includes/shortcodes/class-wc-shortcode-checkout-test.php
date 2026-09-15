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

	/**
	 * @testdox Pay page notices should render inside the shared notices wrapper.
	 */
	public function test_order_pay_prints_notices_inside_notices_wrapper(): void {
		global $wp;

		$order = WC_Helper_Order::create_order( 0 );

		$wp->query_vars['order-pay'] = $order->get_id();
		$_GET['pay_for_order']       = 'true';
		$_GET['key']                 = 'not-the-order-key';

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		$output = (string) ob_get_clean();

		$this->assertMatchesRegularExpression(
			'#<div class="woocommerce-notices-wrapper">\s*<(ul|div) class="[^"]*(woocommerce-error|is-error)[^"]*"[^>]*>.*Sorry, this order is invalid and cannot be paid for\..*</div>#s',
			$output,
			'The pay page error should be wrapped in the notices wrapper.'
		);
	}

	/**
	 * @testdox The order received login prompt should render inside the shared notices wrapper.
	 */
	public function test_order_received_prints_login_notice_inside_notices_wrapper(): void {
		global $wp;

		$customer = $this->factory->user->create( array( 'role' => 'customer' ) );
		$order    = WC_Helper_Order::create_order( $customer );
		wp_set_current_user( 0 );

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = $order->get_order_key();

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		$output = (string) ob_get_clean();

		$this->assertMatchesRegularExpression(
			'#<div class="woocommerce-notices-wrapper">\s*<div class="[^"]*(woocommerce-info|is-info)[^"]*"[^>]*>.*Please log in to your account to view this order\..*</div>#s',
			$output,
			'The order received login prompt should be wrapped in the notices wrapper.'
		);
	}
}
