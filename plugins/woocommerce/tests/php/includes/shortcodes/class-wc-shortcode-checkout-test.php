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
	 * Gateway `enabled` values before the test switched them, keyed by gateway ID.
	 *
	 * @var array<string, string>
	 */
	private $original_gateway_enabled = array();

	/**
	 * Billing props of `WC()->customer` before the test, since the pay page copies the order's address onto it.
	 *
	 * @var array<string, string>
	 */
	private $original_customer_billing = array();

	/**
	 * Callback currently reordering the available gateways, if any.
	 *
	 * @var callable|null
	 */
	private $gateway_order_filter;

	/**
	 * Remember the shared customer state this test may change.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_customer_billing = array(
			'billing_country'  => WC()->customer->get_billing_country(),
			'billing_state'    => WC()->customer->get_billing_state(),
			'billing_postcode' => WC()->customer->get_billing_postcode(),
		);
	}

	/**
	 * Restore the request, query, session, customer and gateway state touched by this test.
	 */
	public function tearDown(): void {
		global $wp;

		unset( $_GET['key'] );
		unset( $_GET['pay_for_order'] );
		unset( $wp->query_vars['order-received'] );
		unset( $wp->query_vars['order-pay'] );

		WC()->customer->set_props( $this->original_customer_billing );
		WC()->customer->save();

		foreach ( $this->original_gateway_enabled as $gateway_id => $enabled ) {
			WC()->payment_gateways()->payment_gateways()[ $gateway_id ]->enabled = $enabled;
		}
		$this->original_gateway_enabled = array();

		if ( $this->gateway_order_filter ) {
			remove_filter( 'woocommerce_available_payment_gateways', $this->gateway_order_filter );
			$this->gateway_order_filter = null;
		}

		// The gateway objects are shared singletons, so clear the selection on all of them, not only those this test enabled.
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			$gateway->chosen = false;
		}

		if ( isset( WC()->session ) ) {
			WC()->session->set( 'chosen_payment_method', null );
		}

		parent::tearDown();
	}

	/**
	 * Enable the given gateways for the test, remembering their previous state.
	 *
	 * @param string[] $gateway_ids Gateway IDs to enable.
	 */
	private function enable_gateways( array $gateway_ids ): void {
		foreach ( $gateway_ids as $gateway_id ) {
			$gateway                                       = WC()->payment_gateways()->payment_gateways()[ $gateway_id ];
			$this->original_gateway_enabled[ $gateway_id ] = $gateway->enabled;
			$gateway->enabled                              = 'yes';
		}
	}

	/**
	 * Move a gateway to the front of the available list, so the default selection would land on it.
	 *
	 * @param string $gateway_id Gateway ID to list first.
	 */
	private function put_gateway_first( string $gateway_id ): void {
		$this->gateway_order_filter = function ( $gateways ) use ( $gateway_id ) {
			return isset( $gateways[ $gateway_id ] ) ? array( $gateway_id => $gateways[ $gateway_id ] ) + $gateways : $gateways;
		};

		add_filter( 'woocommerce_available_payment_gateways', $this->gateway_order_filter );
	}

	/**
	 * Render the pay page for an order, as the shopper following its payment link.
	 *
	 * @param WC_Order $order Order to pay for.
	 * @return string Rendered markup.
	 */
	private function render_pay_page( WC_Order $order ): string {
		global $wp;

		$wp->query_vars['order-pay'] = $order->get_id();
		$_GET['pay_for_order']       = 'true';
		$_GET['key']                 = $order->get_order_key();

		ob_start();
		WC_Shortcode_Checkout::output( array() );

		return (string) ob_get_clean();
	}

	/**
	 * Create a pending guest order with the given origin and payment method.
	 *
	 * @param string $created_via    Value for the order's `created_via` prop.
	 * @param string $payment_method Gateway ID to assign.
	 * @return WC_Order
	 */
	private function create_pending_order( string $created_via, string $payment_method ): WC_Order {
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_created_via( $created_via );
		$order->set_payment_method( $payment_method );
		$order->save();

		return $order;
	}

	/**
	 * Get the IDs of the payment method radios that are checked on rendered pay page markup.
	 *
	 * @param string $output Rendered pay page.
	 * @return string[]
	 */
	private function get_checked_gateways( string $output ): array {
		preg_match_all( '/id="payment_method_([^"]+)"[^>]*\/>/', $output, $radios, PREG_SET_ORDER );
		$checked = array();
		foreach ( $radios as $radio ) {
			if ( false !== strpos( $radio[0], 'checked=' ) ) {
				$checked[] = $radio[1];
			}
		}

		return $checked;
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

	/**
	 * @testdox The pay page should pre-select the method a merchant assigned to an admin-created order, keeping the others available.
	 */
	public function test_order_pay_preselects_merchant_assigned_gateway_for_admin_orders(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'admin', WC_Gateway_Cheque::ID );

		$output = $this->render_pay_page( $order );

		$this->assertSame( array( WC_Gateway_Cheque::ID ), $this->get_checked_gateways( $output ) );
		$this->assertStringContainsString( 'id="payment_method_' . WC_Gateway_BACS::ID . '"', $output, 'Other enabled gateways should still be offered.' );
	}

	/**
	 * @testdox The pay page should prefer the merchant-assigned method over the one chosen in the shopper's session.
	 */
	public function test_order_pay_prefers_merchant_assigned_gateway_over_session_choice(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'admin', WC_Gateway_Cheque::ID );

		// List the assigned gateway first and pick a different one in the session, so only the session choice could steal the selection.
		$this->put_gateway_first( WC_Gateway_Cheque::ID );
		WC()->session->set( 'chosen_payment_method', WC_Gateway_BACS::ID );

		$output = $this->render_pay_page( $order );

		$this->assertSame( array( WC_Gateway_Cheque::ID ), $this->get_checked_gateways( $output ) );
	}

	/**
	 * @testdox The pay page should clear a selection made by third-party code, so only the merchant-assigned method is expanded.
	 */
	public function test_order_pay_clears_gateway_chosen_by_third_party_code(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'admin', WC_Gateway_Cheque::ID );
		WC()->payment_gateways()->payment_gateways()[ WC_Gateway_BACS::ID ]->set_current();

		$output = $this->render_pay_page( $order );

		$this->assertSame( array( WC_Gateway_Cheque::ID ), $this->get_checked_gateways( $output ) );
	}

	/**
	 * @testdox The pay page should keep the default selection when the order was not created in admin.
	 */
	public function test_order_pay_keeps_default_gateway_for_checkout_orders(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'checkout', WC_Gateway_Cheque::ID );

		$output  = $this->render_pay_page( $order );
		$checked = $this->get_checked_gateways( $output );

		$this->assertCount( 1, $checked, 'Exactly one gateway should be pre-selected on the pay page.' );
		$this->assertNotContains( WC_Gateway_Cheque::ID, $checked, 'The assigned method should not override the default for orders the shopper placed.' );
	}

	/**
	 * @testdox The pay page should fall back to the default selection when the assigned method is no longer available.
	 */
	public function test_order_pay_falls_back_when_assigned_gateway_is_unavailable(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_COD::ID ) );
		$order = $this->create_pending_order( 'admin', WC_Gateway_Cheque::ID );

		$output  = $this->render_pay_page( $order );
		$checked = $this->get_checked_gateways( $output );

		$this->assertCount( 1, $checked, 'Exactly one gateway should be pre-selected on the pay page.' );
		$this->assertNotContains( WC_Gateway_Cheque::ID, $checked );
		$this->assertStringNotContainsString( 'id="payment_method_' . WC_Gateway_Cheque::ID . '"', $output );
	}
}
