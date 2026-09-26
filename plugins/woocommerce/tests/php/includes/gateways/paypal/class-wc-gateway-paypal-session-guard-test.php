<?php
/**
 * Session-less payment completion guardrail tests for PayPal Standard.
 *
 * @package WooCommerce\Tests\Gateways
 */

/**
 * Class WC_Gateway_Paypal_Session_Guard_Test
 *
 * Regression tests for the session guardrail added for issue #62761 and the
 * PayPal Standard opt-in that keeps verified IPN/PDT flows working.
 *
 * The guardrail blocks session-less payment_complete() calls for frontend
 * checkout orders, but PayPal Standard completes payment session-less via
 * IPN/PDT after verifying the transaction with PayPal's servers. The bundled
 * gateway opts those orders in via the
 * `woocommerce_allow_sessionless_payment_complete` filter.
 */
class WC_Gateway_Paypal_Session_Guard_Test extends WC_Unit_Test_Case {

	/**
	 * The original session handler, restored after each test.
	 *
	 * @var WC_Session|null
	 */
	private $original_session;

	/**
	 * Set up: simulate a server-to-server (session-less) request context.
	 */
	public function setUp(): void {
		parent::setUp();

		// Instantiate the gateway so it registers its session-less opt-in
		// filter, mirroring a live request where the gateway (and its IPN/PDT
		// listeners) are loaded.
		new WC_Gateway_Paypal();

		// Quarantine: nullify the session to simulate an IPN/PDT request,
		// which carries no frontend session cookie.
		$this->original_session = WC()->session;
		WC()->session           = null;
	}

	/**
	 * Tear down: restore the session so later tests are unaffected.
	 */
	public function tearDown(): void {
		WC()->session = $this->original_session;
		parent::tearDown();
	}

	/**
	 * Build a pending frontend-checkout order for the given payment method.
	 *
	 * @param string $payment_method Gateway ID, e.g. 'paypal' or 'bacs'.
	 * @return WC_Order
	 */
	private function create_checkout_order( string $payment_method ): WC_Order {
		$product = WC_Helper_Product::create_simple_product();

		$order = wc_create_order( array( 'status' => 'pending' ) );
		$order->add_product( $product, 1 );
		$order->set_payment_method( $payment_method );
		$order->set_created_via( 'checkout' );
		$order->save();

		return $order;
	}

	/**
	 * Positive path: a verified PayPal Standard IPN completes payment
	 * session-less. The gateway's opt-in filter must bypass the guardrail.
	 */
	public function test_sessionless_payment_complete_is_allowed_for_paypal() {
		$order = $this->create_checkout_order( 'paypal' );

		$result = $order->payment_complete( 'PAYPAL_TXN_123' );

		$this->assertTrue( $result, 'payment_complete() should succeed for a session-less PayPal IPN.' );
		$this->assertTrue( $order->has_status( 'processing' ), 'A physical-goods checkout order should transition to processing.' );
		$this->assertSame( 'PAYPAL_TXN_123', $order->get_transaction_id(), 'The IPN transaction ID should be recorded.' );
	}

	/**
	 * Negative path: a non-PayPal session-less checkout completion is blocked
	 * and logged, proving the guardrail still holds the line.
	 */
	public function test_sessionless_payment_complete_is_blocked_for_non_paypal() {
		$order = $this->create_checkout_order( 'bacs' );

		$result = $order->payment_complete( 'FAKE_TXN_456' );

		$this->assertFalse( $result, 'payment_complete() should fail for a session-less non-PayPal request.' );
		$this->assertTrue( $order->has_status( 'pending' ), 'The order must remain pending.' );
		$this->assertEmpty( $order->get_transaction_id(), 'No transaction ID may be recorded.' );

		$notes   = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$content = implode( ' ', wp_list_pluck( $notes, 'content' ) );
		$this->assertStringContainsString( 'Payment blocked: session-less call', $content, 'The guardrail should log the blocked attempt.' );
	}
}