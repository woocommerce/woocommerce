<?php
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
}
