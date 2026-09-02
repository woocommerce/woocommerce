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
	 * Restore the request and query state touched by these tests.
	 */
	public function tearDown(): void {
		global $wp;

		unset( $_GET['key'] );
		unset( $wp->query_vars['order-received'] );

		parent::tearDown();
	}

	/**
	 * Render the order received page for an order with the supplied `key` request value.
	 *
	 * @param int   $order_id  Order to request.
	 * @param mixed $order_key Value to place in `$_GET['key']`.
	 * @return string The rendered markup.
	 */
	private function render_order_received( int $order_id, $order_key ): string {
		global $wp;

		$wp->query_vars['order-received'] = $order_id;
		$_GET['key']                      = $order_key;

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		return (string) ob_get_clean();
	}

	/**
	 * An array `key` must be treated as absent rather than reaching hash_equals().
	 */
	public function test_order_received_treats_array_order_key_as_absent() {
		$order = WC_Helper_Order::create_order( 0 );

		$output = $this->render_order_received( $order->get_id(), array( $order->get_order_key() ) );

		$this->assertStringNotContainsString( 'woocommerce-thankyou-order-details', $output );
		$this->assertStringNotContainsString( (string) $order->get_order_number(), $output );
	}

	/**
	 * A valid string `key` still renders the order details.
	 */
	public function test_order_received_renders_order_for_valid_key() {
		$order = WC_Helper_Order::create_order( 0 );

		$output = $this->render_order_received( $order->get_id(), $order->get_order_key() );

		$this->assertStringContainsString( 'woocommerce-thankyou-order-details', $output );
		$this->assertStringContainsString( (string) $order->get_order_number(), $output );
	}
}
