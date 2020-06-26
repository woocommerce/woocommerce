<?php
/**
 * Unit tests for gateways.
 *
 * @package WooCommerce\Tests\Gateways
 */

/**
 * Unit tests for gateways.
 */
class WC_Tests_Gateways extends WC_Unit_Test_Case {

	/**
	 * Test for supports() method.
	 */
	public function test_supports() {
		$gateway = new WC_Mock_Payment_Gateway();

		$this->assertTrue( $gateway->supports( 'products' ) );
		$this->assertFalse( $gateway->supports( 'made-up-feature' ) );
	}

	/**
	 * Test for supports() method.
	 */
	public function test_can_refund_order() {
		$gateway = new WC_Mock_Payment_Gateway();
		$order   = WC_Helper_Order::create_order();

		$order->set_payment_method( 'mock' );
		$order->set_transaction_id( '12345' );
		$order->save();

		$this->assertFalse( $gateway->can_refund_order( $order ) );

		$gateway->supports[] = 'refunds';

		$this->assertTrue( $gateway->can_refund_order( $order ) );
	}

	/**
	 * Test for PayPal supports() method.
	 */
	public function test_paypal_can_refund_order() {
		$gateway = new WC_Gateway_Paypal();
		$order   = WC_Helper_Order::create_order();

		$order->set_payment_method( 'paypal' );
		$order->set_transaction_id( '12345' );
		$order->save();

		// Refunds won't work without credentials.
		$this->assertFalse( $gateway->can_refund_order( $order ) );

		// Add API credentials.
		$settings = array(
			'testmode'              => 'yes',
			'sandbox_api_username'  => 'test',
			'sandbox_api_password'  => 'test',
			'sandbox_api_signature' => 'test',
		);
		update_option( 'woocommerce_paypal_settings ', $settings );
		$gateway = new WC_Gateway_Paypal();
		$this->assertTrue( $gateway->can_refund_order( $order ) );

		// Refund requires transaction ID.
		$order->set_transaction_id( '' );
		$order->save();
		$this->assertFalse( $gateway->can_refund_order( $order ) );
	}

	/**
	 * Test WC_Payment_Gateway::get_pay_button_id();
	 *
	 * @return void
	 */
	public function test_get_pay_button_id() {
		$gateway = new WC_Mock_Payment_Gateway();

		$this->assertEquals( $gateway->pay_button_id, $gateway->get_pay_button_id() );

		$gateway->pay_button_id = 'new-pay-button';

		$this->assertEquals( $gateway->pay_button_id, $gateway->get_pay_button_id() );
	}
}

