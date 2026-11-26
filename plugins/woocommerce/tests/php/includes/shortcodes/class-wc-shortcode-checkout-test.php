<?php
/**
 * Tests for WC_Shortcode_Checkout.
 *
 * @package WooCommerce\Tests\Shortcodes
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

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
		WC()->session->set( 'new_customer', null );

		foreach ( $this->original_gateway_enabled as $gateway_id => $enabled ) {
			WC()->payment_gateways()->payment_gateways()[ $gateway_id ]->enabled = $enabled;
		}
		$this->original_gateway_enabled = array();

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
		$gateway_order_filter = function ( $gateways ) use ( $gateway_id ) {
			return isset( $gateways[ $gateway_id ] ) ? array( $gateway_id => $gateways[ $gateway_id ] ) + $gateways : $gateways;
		};

		add_filter( 'woocommerce_available_payment_gateways', $gateway_order_filter );
	}

	/**
	 * Render the pay page for an order, as the shopper following its payment link.
	 *
	 * @param WC_Order $order         Order to pay for.
	 * @param bool     $pay_for_order True for the payment link's pay form, false for the receipt step after checkout.
	 * @return string Rendered markup.
	 */
	private function render_pay_page( WC_Order $order, bool $pay_for_order = true ): string {
		global $wp;

		$wp->query_vars['order-pay'] = $order->get_id();
		$_GET['key']                 = $order->get_order_key();
		if ( $pay_for_order ) {
			$_GET['pay_for_order'] = 'true';
		}

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
	 * Create a guest order with the checkout-draft status, which block checkout uses before an order is placed.
	 *
	 * @return WC_Order
	 */
	private function create_checkout_draft_order(): WC_Order {
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( OrderStatus::CHECKOUT_DRAFT );
		$order->save();

		$this->assertTrue( $order->needs_payment(), 'Baseline: needs_payment() is still true for a draft, so the pay page has to refuse it explicitly.' );

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
	 * @testdox The order-pay endpoint should reject refund IDs without a fatal error.
	 */
	public function test_order_pay_rejects_refund_id(): void {
		global $wp;

		$order  = WC_Helper_Order::create_order( 0 );
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 1,
			)
		);
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );

		wp_set_current_user( 0 );
		$wp->query_vars['order-pay'] = $refund->get_id();

		ob_start();
		try {
			WC_Shortcode_Checkout::output( array() );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$this->assertStringContainsString(
			'Sorry, this order is invalid and cannot be paid for.',
			$output,
			'Refund IDs should show the invalid-order notice.'
		);
	}

	/**
	 * @testdox The pay page refuses a checkout-draft order and offers no payment form for it.
	 */
	public function test_order_pay_refuses_checkout_draft_order(): void {
		$output = $this->render_pay_page( $this->create_checkout_draft_order() );

		$this->assertStringContainsString( 'it cannot be paid for', $output, 'The pay page must tell the shopper a draft cannot be paid for.' );
		$this->assertStringNotContainsString( 'name="woocommerce_pay"', $output, 'No payment form may be offered for a draft.' );
	}

	/**
	 * @testdox The order receipt step after checkout shows the receipt for a pending order and refuses a checkout-draft.
	 * @testWith ["pending", true]
	 *           ["checkout-draft", false]
	 *
	 * @param string $status        Order status.
	 * @param bool   $shows_receipt Whether the receipt should render.
	 */
	public function test_order_receipt_is_shown_only_for_payable_orders( string $status, bool $shows_receipt ): void {
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( $status );
		$order->save();
		$this->assertTrue( $order->needs_payment(), 'Baseline: needs_payment() is true for both, so only the draft check can tell them apart.' );

		$output = $this->render_pay_page( $order, false );

		$this->assertSame( $shows_receipt, str_contains( $output, 'class="order_details"' ), $shows_receipt ? 'A pending order must show its receipt.' : 'No order receipt may be shown for a draft.' );
		$this->assertSame( ! $shows_receipt, str_contains( $output, 'it cannot be paid for' ), 'Only a draft may get the cannot-be-paid notice.' );
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
	 * Render the order received page for an order placed an hour ago, after the grace period.
	 *
	 * @param WC_Order $order Order to show.
	 * @return string Rendered markup.
	 */
	private function render_old_order_received_page( WC_Order $order ): string {
		global $wp;

		$order->set_date_created( time() - HOUR_IN_SECONDS );
		$order->save();

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = $order->get_order_key();

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox After the grace period, a guest order asks for the billing email, and the right email shows the order.
	 */
	public function test_order_received_unlocks_guest_order_with_billing_email(): void {
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		$order = WC_Helper_Order::create_order( 0 );
		WC()->session->set( 'customer', array( 'email' => $order->get_billing_email() ) );

		$locked = $this->render_old_order_received_page( $order );

		$_POST['check_submission'] = wp_create_nonce( 'wc_verify_email' );
		$_POST['email']            = $order->get_billing_email();
		$unlocked                  = $this->render_old_order_received_page( $order );
		unset( $_POST['check_submission'], $_POST['email'] );
		WC()->session->set( 'customer', null );
		delete_option( 'woocommerce_enable_guest_checkout' );

		$this->assertStringContainsString( 'check_submission', $locked, 'The session email alone should not unlock the order.' );
		$this->assertStringNotContainsString( 'woocommerce-order-overview', $locked );
		$this->assertStringContainsString( 'woocommerce-order-overview', $unlocked, 'The billing email should unlock the order.' );
	}

	/**
	 * @testdox A wrong email, a missing nonce, or an admin visitor does not unlock a guest order after the grace period.
	 *
	 * @testWith ["wrong", true, false]
	 *           ["right", false, false]
	 *           ["none", false, true]
	 *
	 * @param string $email      Which email to submit: the billing email, a wrong one, or none.
	 * @param bool   $with_nonce Whether to submit a valid nonce.
	 * @param bool   $as_admin   Whether an administrator visits the page.
	 */
	public function test_order_received_keeps_guest_order_locked( string $email, bool $with_nonce, bool $as_admin ): void {
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		$order = WC_Helper_Order::create_order( 0 );

		if ( $as_admin ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		}
		if ( 'none' !== $email ) {
			$_POST['email'] = 'right' === $email ? $order->get_billing_email() : 'someone-else@example.com';
		}
		if ( $with_nonce ) {
			$_POST['check_submission'] = wp_create_nonce( 'wc_verify_email' );
		}

		$output = $this->render_old_order_received_page( $order );
		unset( $_POST['check_submission'], $_POST['email'] );
		wp_set_current_user( 0 );
		delete_option( 'woocommerce_enable_guest_checkout' );

		$this->assertStringNotContainsString( 'woocommerce-order-overview', $output );
		$this->assertStringContainsString( 'check_submission', $output, 'The email form should show again.' );
	}

	/**
	 * @testdox The billing email does not unlock a guest order while guest checkout is off.
	 */
	public function test_order_received_billing_email_needs_guest_checkout(): void {
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		$order                     = WC_Helper_Order::create_order( 0 );
		$_POST['check_submission'] = wp_create_nonce( 'wc_verify_email' );
		$_POST['email']            = $order->get_billing_email();

		$output = $this->render_old_order_received_page( $order );
		unset( $_POST['check_submission'], $_POST['email'] );
		delete_option( 'woocommerce_enable_guest_checkout' );

		$this->assertStringNotContainsString( 'woocommerce-order-overview', $output );
	}

	/**
	 * @testdox A guest order after the grace period shows no details and no email form while guest checkout is off.
	 */
	public function test_order_received_hides_guest_order_without_guest_checkout(): void {
		update_option( 'woocommerce_enable_guest_checkout', 'no' );

		$output = $this->render_old_order_received_page( WC_Helper_Order::create_order( 0 ) );
		delete_option( 'woocommerce_enable_guest_checkout' );

		$this->assertStringNotContainsString( 'woocommerce-order-overview', $output );
		$this->assertStringNotContainsString( 'check_submission', $output );
		$this->assertStringNotContainsString( 'Please log in to your account to view this order.', $output );
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
	 * @testdox The receipt should respect the cart clearing decision made before rendering.
	 *
	 * @testWith [true, true, null, 0]
	 *           [false, true, null, 1]
	 *           [true, false, null, 1]
	 *           [false, false, null, 1]
	 *           [true, true, false, 1]
	 *           [false, true, true, 0]
	 *
	 * @param bool      $matching_cart Whether the order belongs to the current cart.
	 * @param bool      $valid_key     Whether the receipt has a valid order key.
	 * @param bool|null $override      Optional cart clearing filter override.
	 * @param int       $expected      Expected cart quantity after rendering.
	 */
	public function test_order_received_respects_cart_clearing_decision( bool $matching_cart, bool $valid_key, ?bool $override, int $expected ): void {
		global $wp;

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( OrderStatus::PROCESSING );
		$order->set_cart_hash( $matching_cart ? WC()->cart->get_cart_hash() : 'previous-cart-hash' );
		$order->save();

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = $valid_key ? $order->get_order_key() : 'invalid-order-key';

		if ( null !== $override ) {
			add_filter(
				'woocommerce_should_clear_cart_after_payment',
				static function () use ( $override ) {
					return $override;
				}
			);
		}

		// Cart clearing runs on template_redirect, before the checkout shortcode renders.
		wc_clear_cart_after_payment();
		$this->assertSame( $expected, WC()->cart->get_cart_contents_count(), 'The pre-render cart clearing decision should match the requested scenario.' );

		ob_start();
		try {
			WC_Shortcode_Checkout::output( array() );
		} finally {
			ob_end_clean();
		}

		$this->assertSame( $expected, WC()->cart->get_cart_contents_count(), 'Rendering a receipt must not override the earlier cart clearing decision.' );
	}

	/**
	 * @testdox The receipt should retain the awaiting-payment cart clearing behavior.
	 *
	 * @testWith ["processing", 0]
	 *           ["pending", 1]
	 *           ["failed", 1]
	 *           ["cancelled", 1]
	 *
	 * @param string $status   Awaiting order status.
	 * @param int    $expected Expected cart quantity after rendering.
	 */
	public function test_order_received_respects_awaiting_payment_cart( string $status, int $expected ): void {
		global $wp;

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->calculate_totals();

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( $status );
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->save();
		WC()->session->set( 'order_awaiting_payment', $order->get_id() );

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = 'invalid-order-key';

		wc_clear_cart_after_payment();

		ob_start();
		try {
			WC_Shortcode_Checkout::output( array() );
		} finally {
			ob_end_clean();
		}

		$this->assertSame( $expected, WC()->cart->get_cart_contents_count(), 'Only an awaiting order that went through should clear its matching cart.' );
		$this->assertNull( WC()->session->get( 'order_awaiting_payment' ), 'The receipt should still reset the awaiting-payment session.' );
	}

	/**
	 * @testdox The order received page shows the order when the account was created in another session, such as an express checkout on a product page.
	 */
	public function test_order_received_shows_order_for_new_customer_created_in_another_session(): void {
		global $wp;

		wp_set_current_user( 0 );
		$new_customer_session = wc_get_container()->get( \Automattic\WooCommerce\Internal\Customers\NewCustomerSession::class );
		$customer_id          = $new_customer_session->create_customer( 'thankyou-other-session@example.com' );
		$order                = WC_Helper_Order::create_order( $customer_id );
		$new_customer_session->mark_order_created_customer( $order, $customer_id );
		$order->save();
		// The order received page loads in a session that did not create the account.
		WC()->session->set( 'new_customer', null );

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = $order->get_order_key();

		ob_start();
		WC_Shortcode_Checkout::output( array() );
		$output = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'Please log in to your account to view this order.', $output, 'The shopper has no password yet, so a login prompt is a dead end.' );
		$this->assertStringContainsString( 'woocommerce-thankyou-order-details', $output, 'The order details should be shown.' );
	}

	/**
	 * @testdox The pay page shows the payment form to a logged out shopper whose account was created at checkout.
	 */
	public function test_order_pay_shows_payment_form_for_new_customer(): void {
		wp_set_current_user( 0 );
		$this->enable_gateways( array( WC_Gateway_BACS::ID ) );
		$new_customer_session = wc_get_container()->get( \Automattic\WooCommerce\Internal\Customers\NewCustomerSession::class );
		$customer_id          = $new_customer_session->create_customer( 'pay-new@example.com' );
		$order                = WC_Helper_Order::create_order( $customer_id );
		$new_customer_session->mark_order_created_customer( $order, $customer_id );
		$order->save();

		$output = $this->render_pay_page( $order );

		$this->assertStringNotContainsString( 'Please log in to your account below to continue to the payment form.', $output );
		$this->assertStringContainsString( 'id="order_review"', $output, 'The payment form should be shown.' );
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
	 * @testdox The pay page should prefer the merchant-assigned method over a default payment method callback that returns nothing.
	 */
	public function test_order_pay_prefers_merchant_assigned_gateway_over_the_default_payment_method_callback(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'admin', WC_Gateway_Cheque::ID );
		add_filter( 'woocommerce_default_payment_method', '__return_empty_string' );

		$output = $this->render_pay_page( $order );

		$this->assertSame( array( WC_Gateway_Cheque::ID ), $this->get_checked_gateways( $output ) );
		$this->assertStringContainsString( 'data-preselected="yes"', $output, 'The list should tell checkout.js a method is selected.' );
	}

	/**
	 * @testdox The pay page should leave nothing selected for an order the shopper placed when the default payment method callback returns nothing.
	 */
	public function test_order_pay_leaves_nothing_selected_when_the_default_payment_method_callback_returns_nothing(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'checkout', WC_Gateway_Cheque::ID );
		add_filter( 'woocommerce_default_payment_method', '__return_empty_string' );

		$output = $this->render_pay_page( $order );

		$this->assertSame( array(), $this->get_checked_gateways( $output ) );
		$this->assertStringContainsString( 'data-preselected="no"', $output, 'The list should tell checkout.js not to select the first method.' );
		$this->assertStringContainsString( 'id="payment_method_' . WC_Gateway_BACS::ID . '"', $output, 'The gateways should still be offered.' );
	}

	/**
	 * @testdox The pay form template should print the preselection it is handed, and say a method is preselected when its loader doesn't pass the value.
	 * @testWith [{"has_chosen_gateway": false}, "no"]
	 *           [{"has_chosen_gateway": true}, "yes"]
	 *           [{}, "yes"]
	 *
	 * @param array  $extra_args Template arguments on top of the order, the gateways and the button text.
	 * @param string $expected   Expected value of the data-preselected attribute.
	 */
	public function test_pay_form_template_prints_the_preselection_it_is_handed( array $extra_args, string $expected ): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order    = $this->create_pending_order( 'checkout', WC_Gateway_Cheque::ID );
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		// One gateway is current only in the "no" case, so the template can't get any case right by working the value out itself.
		if ( 'no' === $expected ) {
			$gateways[ WC_Gateway_BACS::ID ]->set_current();
		}

		ob_start();
		wc_get_template(
			'checkout/form-pay.php',
			array(
				'order'              => $order,
				'available_gateways' => $gateways,
				'order_button_text'  => 'Pay for order',
			) + $extra_args
		);
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'data-preselected="' . $expected . '"', $output );
	}

	/**
	 * @testdox The pay page should pre-select the method the default payment method callback picks for an order the shopper placed.
	 */
	public function test_order_pay_preselects_the_gateway_the_default_payment_method_callback_picks(): void {
		$this->enable_gateways( array( WC_Gateway_BACS::ID, WC_Gateway_Cheque::ID ) );
		$order = $this->create_pending_order( 'checkout', WC_Gateway_Cheque::ID );
		$this->put_gateway_first( WC_Gateway_Cheque::ID );
		add_filter(
			'woocommerce_default_payment_method',
			function () {
				return WC_Gateway_BACS::ID;
			}
		);

		$output = $this->render_pay_page( $order );

		$this->assertSame( array( WC_Gateway_BACS::ID ), $this->get_checked_gateways( $output ) );
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
