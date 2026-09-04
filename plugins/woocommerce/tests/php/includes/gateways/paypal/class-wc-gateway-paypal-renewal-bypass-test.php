<?php
declare( strict_types = 1 );

/**
 * Test the session‑less payment_complete bypass.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/62761
 */
class WC_Gateway_Paypal_Renewal_Bypass_Test extends WC_Unit_Test_Case {

/**
 * Exploit: an order created via 'checkout' without a valid user session
 * must NOT transition to 'processing' when payment_complete() is called
 * by an external webhook (IPN/PDT). Currently it does, which is the bug.
 */
public function test_payment_complete_via_checkout_without_session_is_blocked(): void {
    // Arrange: create a pending order, mark it as a checkout order.
    $order = WC_Helper_Order::create_order();
    $order->set_created_via( 'checkout' );
    $order->set_status( 'pending' );
    $order->save();

    // Ensure there is no active user session (simulate webhook).
    WC()->session = null;

    // Act: the external payment verification (IPN) calls this.
    $order->payment_complete( 'test_txn_id' );

    // Refresh from database.
    $order = wc_get_order( $order->get_id() );

    // Assert: the status must NOT be 'processing' – the guardrail should
    // have blocked the transition because the order came from a front‑end
    // flow but no session exists.
    $this->assertNotEquals(
        'processing',
        $order->get_status(),
        'Order status was erroneously set to processing for a session‑less checkout order.'
        );
        $this->assertContains(
            $order->get_status(),
            array( 'pending', 'failed', 'on-hold' ),
            'Order status should remain in a non‑completed state.'
        );
    }

    /**
     * Legitimate renewal: the fix must still allow automated subscription
     * payments (created_via = 'subscription') to complete normally.
     */
    public function test_payment_complete_for_subscription_orders_is_allowed(): void {
    // Arrange: order created by the subscriptions engine, no session needed.
        $order = WC_Helper_Order::create_order();
        $order->set_created_via( 'subscription' );
        $order->set_status( 'pending' );
        $order->save();

        WC()->session = null; // Subscriptions also run session‑less.

        $order->payment_complete( 'sub_txn_id' );
        $order = wc_get_order( $order->get_id() );

        // This should pass even after the guardrail is in place.
        $this->assertEquals(
        'processing',
        $order->get_status(),
        'Subscription renewal order must proceed to processing.'
        );
    }

    /**
	 * Test that a standard frontend checkout order WITH a valid session proceeds normally.
	 */
	public function test_payment_complete_via_checkout_with_valid_session_is_allowed() {
		$order = WC_Helper_Order::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( 'pending' );
		$order->save();

		// Mock a valid session object to satisfy the guardrail's stricter validation.
		$mock_session = new class {
			public function has_session() {
				return true;
			}
			public function set( $key, $value ) {}
			public function get( $key, $default = null ) {
				// Return dummy customer data to satisfy the populated session check.
				if ( 'customer' === $key ) {
					return array( 'id' => 1, 'email' => 'test@example.com' );
				}
				return $default;
			}
			public function __call( $name, $arguments ) {
				return null;
			}
		};
		
		WC()->session = $mock_session;

		$order->payment_complete();

		$this->assertEquals( 'processing', $order->get_status(), 'Checkout order with a valid session must proceed to processing.' );
	
        // Clean up global state to prevent harness pollution
		WC()->session = null; 
    }

	/**
	 * Edge Case A: The "Ghost" Session.
	 * The session object is instantiated, but contains no actual session data.
	 */
	public function test_payment_complete_blocked_when_ghost_session_present() {
		// Clear global state pollution before creating the order.
		WC()->session = null; 

		$order = WC_Helper_Order::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( 'pending' );
		$order->save();

		// Mock a ghost session. We add get() and __call() to prevent WooCommerce core 
		// shipping/tax hooks from crashing if they probe the session during state transitions.
		$ghost_session = new class {
			public function has_session() {
				return false;
			}
			public function set( $key, $value ) {}
			public function get( $key, $default = null ) {
				return $default;
			}
			public function __call( $name, $arguments ) {
				return null;
			}
		};
		WC()->session = $ghost_session;

		$result = $order->payment_complete();

		$this->assertFalse( $result, 'Ghost session must be treated as no session.' );
		$this->assertEquals( 'pending', $order->get_status(), 'Order status must not advance with a ghost session.' );
		
		// Clean up
		WC()->session = null;
	}

	/**
	 * Edge Case D: Replay Attack on Pre-Completed Order.
	 * Guardrail must block session-less calls even if the order is already processing,
	 * acting as a shield before the try/catch block executes.
	 */
	public function test_payment_complete_blocked_on_replay_attack_for_completed_order() {
		// Clear global state pollution.
		WC()->session = null;

		$order = WC_Helper_Order::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( 'processing' ); // Already paid!
		$order->save();

		// WC()->session remains null, simulating IPN replay.
		$result = $order->payment_complete();

		$this->assertFalse( $result, 'Replay attacks must be blocked regardless of current status.' );
		$this->assertEquals( 'processing', $order->get_status(), 'Status must remain unchanged.' );
	}

    /**
	 * Edge Case B: Store API (Blocks) Checkout Exemption.
	 * Proves that REST_REQUEST context bypasses the session requirement.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_payment_complete_allowed_in_rest_api_context_without_session() {
		define( 'REST_REQUEST', true );

		$order = WC_Helper_Order::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( 'pending' );
		$order->save();

		WC()->session = null;

		$result = $order->payment_complete();

		$this->assertTrue( $result, 'REST API context must bypass the session guardrail.' );
		$this->assertEquals( 'processing', $order->get_status() );
	}

	/**
	 * Edge Case C: WP-CLI Exemption.
	 * Proves that CLI batch scripts bypass the session requirement.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_payment_complete_allowed_in_wp_cli_context_without_session() {
		define( 'WP_CLI', true );

		$order = WC_Helper_Order::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( 'pending' );
		$order->save();

		WC()->session = null;

		$result = $order->payment_complete();

		$this->assertTrue( $result, 'WP-CLI context must bypass the session guardrail.' );
		$this->assertEquals( 'processing', $order->get_status() );
	}

	/**
	 * Edge Case E: The Gateway Escape Hatch.
	 * Proves that a gateway which has performed its own cryptographic verification
	 * can bypass the session guardrail by hooking into the documented filter.
	 */
	public function test_payment_complete_allowed_via_filter_escape_hatch() {
		// Clear any global state pollution.
		WC()->session = null;

		$order = WC_Helper_Order::create_order();
		$order->set_created_via( 'checkout' );
		$order->set_status( 'pending' );
		$order->save();

		// Simulate a gateway that has verified the webhook and opts into the bypass.
		add_filter( 'woocommerce_allow_sessionless_payment_complete', '__return_true' );

		$result = $order->payment_complete();

		// Clean up the filter immediately to prevent test harness pollution.
		remove_filter( 'woocommerce_allow_sessionless_payment_complete', '__return_true' );
		WC()->session = null;

		$this->assertTrue( $result, 'Gateway escape hatch must allow session-less transition.' );
		$this->assertEquals( 'processing', $order->get_status(), 'Order must advance when filter returns true.' );
	}
}
