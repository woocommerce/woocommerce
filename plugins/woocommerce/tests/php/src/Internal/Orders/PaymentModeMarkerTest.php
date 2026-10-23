<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Orders;

use Automattic\WooCommerce\Caches\OrderCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;
use Automattic\WooCommerce\Internal\Orders\PaymentModeMarker;
use WC_Helper_Order;
use WC_Order;
use WC_Payment_Gateway;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentModeMarker class.
 *
 * @covers \Automattic\WooCommerce\Internal\Orders\PaymentModeMarker
 */
class PaymentModeMarkerTest extends WC_Unit_Test_Case {

	/**
	 * Registered gateways before the test, which the base class doesn't restore.
	 *
	 * @var array
	 */
	private $payment_gateways_before_test = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->payment_gateways_before_test = WC()->payment_gateways()->payment_gateways;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			WC()->payment_gateways()->payment_gateways = $this->payment_gateways_before_test;
		}
	}

	/**
	 * @testdox Should save the mode the order's gateway saved, even when the gateway settings say otherwise.
	 *
	 * @testWith ["woocommerce_payments_card", "_wcpay_mode", "test", "test"]
	 *           ["woocommerce_payments", "_wcpay_mode", "prod", "live"]
	 *           ["ppcp-gateway", "_ppcp_paypal_payment_mode", "sandbox", "test"]
	 *           ["stripe_cc", "_wc_stripe_mode", "live", "live", true]
	 *           ["skyverge_gateway", "_wc_skyverge_gateway_environment", "production", "live"]
	 *
	 * @param string $gateway_id                Gateway ID.
	 * @param string $meta_key                  Order meta key the gateway writes.
	 * @param string $meta_value                Mode the gateway saved.
	 * @param string $expected_mode             Expected payment mode.
	 * @param bool   $is_payment_plugins_stripe Whether the gateway comes from Payment Plugins for Stripe.
	 */
	public function test_prefers_mode_saved_by_gateway( string $gateway_id, string $meta_key, string $meta_value, string $expected_mode, bool $is_payment_plugins_stripe = false ): void {
		$this->register_gateway( $gateway_id, PaymentModeMarker::MODE_LIVE === $expected_mode, $is_payment_plugins_stripe );
		$order = $this->create_order( $gateway_id );
		$order->update_meta_data( $meta_key, $meta_value );
		$order->save();

		$order->payment_complete();

		$this->assertSame( $expected_mode, $this->get_saved_mode( $order ), 'The mode saved by the gateway should win over its settings' );
	}

	/**
	 * @testdox Should ask the gateway provider for the mode when the gateway saved none on the order.
	 *
	 * @testWith [true, "test"]
	 *           [false, "live"]
	 *
	 * @param bool   $gateway_test_mode Whether the gateway settings are in test mode.
	 * @param string $expected_mode     Expected payment mode.
	 */
	public function test_falls_back_to_provider_test_mode( bool $gateway_test_mode, string $expected_mode ): void {
		// An official Stripe plugin ID, which shares the `stripe_` prefix with Payment Plugins for Stripe.
		$this->register_gateway( 'stripe_ideal', $gateway_test_mode );
		$order = $this->create_order( 'stripe_ideal' );
		// Modes other gateways saved during earlier payment attempts.
		$order->update_meta_data( '_wcpay_mode', $gateway_test_mode ? 'prod' : 'test' );
		$order->update_meta_data( '_wc_stripe_mode', $gateway_test_mode ? 'live' : 'test' );
		$order->save();

		$order->payment_complete();

		$this->assertSame( $expected_mode, $this->get_saved_mode( $order ), 'The mode should follow the gateway provider' );
	}

	/**
	 * @testdox Should not save a mode when the order's payment method isn't a registered gateway.
	 */
	public function test_skips_unregistered_gateway(): void {
		$order = $this->create_order( 'unregistered_gateway' );

		$order->payment_complete();

		$this->assertSame( '', $this->get_saved_mode( $order ), 'Orders without a registered gateway should not be marked' );
	}

	/**
	 * @testdox Should complete the payment when the mode can't be determined.
	 */
	public function test_failure_does_not_break_payment_complete(): void {
		$this->register_gateway( 'fake_gateway', true );
		$order     = $this->create_order( 'fake_gateway' );
		$providers = $this->createMock( PaymentsProviders::class );
		$providers->method( 'get_payment_gateway_provider_instance' )->willThrowException( new \RuntimeException( 'Provider failure' ) );
		wc_get_container()->replace( PaymentsProviders::class, $providers );

		$result = $order->payment_complete();

		$this->assertTrue( $result, 'payment_complete() should still succeed' );
		$this->assertTrue( $this->reload( $order )->has_status( OrderStatus::PROCESSING ), 'The order should be marked as paid' );
		$this->assertSame( '', $this->get_saved_mode( $order ), 'No mode should be saved' );
	}

	/**
	 * Register a fake gateway that stores its test mode in the `testmode` setting, like many gateways do.
	 *
	 * @param string $gateway_id                Gateway ID.
	 * @param bool   $test_mode                 Whether the gateway is in test mode.
	 * @param bool   $is_payment_plugins_stripe Whether to extend the Payment Plugins for Stripe base class.
	 */
	private function register_gateway( string $gateway_id, bool $test_mode, bool $is_payment_plugins_stripe = false ): void {
		if ( $is_payment_plugins_stripe ) {
			if ( ! class_exists( 'WC_Payment_Gateway_Stripe' ) ) {
				class_alias( get_class( new class() extends WC_Payment_Gateway {} ), 'WC_Payment_Gateway_Stripe' );
			}
			$gateway = new class() extends \WC_Payment_Gateway_Stripe {};
		} else {
			$gateway = new class() extends WC_Payment_Gateway {};
		}
		$gateway->id       = $gateway_id;
		$gateway->settings = array( 'testmode' => $test_mode ? 'yes' : 'no' );

		WC()->payment_gateways()->payment_gateways[] = $gateway;
	}

	/**
	 * Create a pending order paid with the given payment method.
	 *
	 * @param string $payment_method Payment method ID.
	 * @return WC_Order
	 */
	private function create_order( string $payment_method ): WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_payment_method( $payment_method );
		$order->save();

		return $order;
	}

	/**
	 * Load an order from the database, bypassing the order cache.
	 *
	 * @param WC_Order $order The order.
	 * @return WC_Order
	 */
	private function reload( WC_Order $order ): WC_Order {
		wc_get_container()->get( OrderCache::class )->remove( $order->get_id() );
		return wc_get_order( $order->get_id() );
	}

	/**
	 * Get the payment mode saved on the order.
	 *
	 * @param WC_Order $order The order.
	 * @return mixed
	 */
	private function get_saved_mode( WC_Order $order ) {
		return $this->reload( $order )->get_meta( PaymentModeMarker::META_KEY );
	}
}
