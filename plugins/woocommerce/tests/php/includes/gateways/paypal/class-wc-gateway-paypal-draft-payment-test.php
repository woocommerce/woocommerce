<?php
/**
 * Tests that PayPal Standard IPN and PDT notifications do not pay a checkout-draft order.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Class WC_Gateway_Paypal_Draft_Payment_Test.
 */
class WC_Gateway_Paypal_Draft_Payment_Test extends \WC_Unit_Test_Case {

	/**
	 * Order statuses an IPN can arrive for, and whether a "completed" notification should pay the order.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public function provider_ipn_target_statuses(): array {
		return array(
			'pending order is paid'           => array( OrderStatus::PENDING, true ),
			'checkout-draft order is refused' => array( OrderStatus::CHECKOUT_DRAFT, false ),
		);
	}

	/**
	 * @testdox A completed PayPal IPN pays a pending order but is refused for a checkout-draft, which has not passed checkout validation.
	 *
	 * @dataProvider provider_ipn_target_statuses
	 *
	 * @param string $status      Order status when the IPN arrives.
	 * @param bool   $expect_paid Whether the order should reach a paid status.
	 */
	public function test_completed_ipn_pays_pending_order_but_not_checkout_draft( string $status, bool $expect_paid ): void {
		$receiver_email = 'merchant@example.com';

		$order = WC_Helper_Order::create_order( 0 );
		$order->set_status( $status );
		$order->save();

		$this->assertGreaterThan( 0, (float) $order->get_total(), 'Baseline: the order must have a payable total.' );
		$this->assertTrue( $order->needs_payment(), 'Baseline: needs_payment() is true for both statuses, so only the draft guard can tell them apart.' );

		$posted = array(
			'txn_type'       => 'web_accept',
			'mc_currency'    => $order->get_currency(),
			'mc_gross'       => number_format( (float) $order->get_total(), 2, '.', '' ),
			'receiver_email' => $receiver_email,
			'payment_status' => 'completed',
			'txn_id'         => 'TEST-' . $order->get_id(),
			'custom'         => wp_json_encode(
				array(
					'order_id'  => $order->get_id(),
					'order_key' => $order->get_order_key(),
				)
			),
		);

		// valid_response() is the entry point a VERIFIED IPN reaches, after the network check in check_response().
		( new WC_Gateway_Paypal_IPN_Handler( true, $receiver_email ) )->valid_response( $posted );

		$order = wc_get_order( $order->get_id() );

		if ( $expect_paid ) {
			$this->assertTrue( $order->has_status( wc_get_is_paid_statuses() ), 'A verified completed IPN must pay a pending order.' );
		} else {
			$this->assertTrue( $order->has_status( OrderStatus::CHECKOUT_DRAFT ), 'A verified completed IPN must not move a checkout-draft order off that status.' );
			$this->assertFalse( $order->has_status( wc_get_is_paid_statuses() ), 'A checkout-draft order must not be paid through an IPN.' );
		}
	}
}
