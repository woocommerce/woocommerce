<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Payments\NativeWooPaymentsGateway;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the NativeWooPaymentsGateway class.
 */
class NativeWooPaymentsGatewayTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preserve the WooPayments gateway identity and settings option key.
	 */
	public function test_preserves_gateway_identity(): void {
		$gateway = wc_get_container()->get( NativeWooPaymentsGateway::class );

		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $gateway->id );
		$this->assertSame( 'woocommerce_woocommerce_payments_settings', $gateway->get_option_key() );
		$this->assertSame( 'Card', $gateway->title );
		$this->assertStringContainsString( '/assets/images/payment-methods/visa.svg', $gateway->get_icon() );
		$this->assertStringContainsString( 'alt="Visa"', $gateway->get_icon() );
		$this->assertStringContainsString( '/assets/images/payment-methods/mastercard.svg', $gateway->get_icon() );
		$this->assertStringContainsString( '+ 3', $gateway->get_icon() );
		$this->assertContains( 'refunds', $gateway->supports );
		$this->assertContains( PaymentGatewayFeature::TOKENIZATION, $gateway->supports );
		$this->assertNotContains( 'subscriptions', $gateway->supports );
		$this->assertNotContains( 'subscription_payment_method_change', $gateway->supports );
		$this->assertNotContains( 'subscription_payment_method_change_admin', $gateway->supports );
		$this->assertNotContains( 'subscription_payment_method_change_customer', $gateway->supports );
		$this->assertNotContains( PaymentGatewayFeature::ADD_PAYMENT_METHOD, $gateway->supports );
	}

	/**
	 * @testdox Should not translate gateway labels during construction before init.
	 */
	public function test_constructor_does_not_translate_gateway_labels_before_init(): void {
		global $wp_actions;

		$had_init_action_count = is_array( $wp_actions ) && array_key_exists( 'init', $wp_actions );
		$previous_init_count   = $had_init_action_count ? $wp_actions['init'] : null;
		$translated            = array();
		$filter                = static function ( $translation, $text, $domain ) use ( &$translated ) {
			if ( 'woocommerce' === $domain ) {
				$translated[] = $text;
			}

			return $translation;
		};

		unset( $wp_actions['init'] );
		add_filter( 'gettext', $filter, 10, 3 );

		try {
			new NativeWooPaymentsGateway();
		} finally {
			remove_filter( 'gettext', $filter, 10 );

			if ( $had_init_action_count ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate pre-init construction for the WP 6.7 textdomain guard.
				$wp_actions['init'] = $previous_init_count;
			} else {
				unset( $wp_actions['init'] );
			}
		}

		$this->assertSame( array(), $translated, 'Native gateway construction must not call translation APIs before init.' );
	}

	/**
	 * @testdox Should translate gateway labels from the init hook.
	 */
	public function test_translates_gateway_labels_from_init_hook(): void {
		global $wp_actions;

		$had_init_action_count = is_array( $wp_actions ) && array_key_exists( 'init', $wp_actions );
		$previous_init_count   = $had_init_action_count ? $wp_actions['init'] : null;
		$filter                = static function ( $translation, $text, $domain ) {
			return 'woocommerce' === $domain ? 'Translated: ' . $text : $translation;
		};

		unset( $wp_actions['init'] );
		add_filter( 'gettext', $filter, 10, 3 );

		try {
			$gateway = new NativeWooPaymentsGateway();

			$this->assertSame( 'Card', $gateway->title );
			$this->assertSame( 'WooPayments', $gateway->method_title );
			$this->assertSame( 'Accept payments with WooPayments.', $gateway->method_description );

			$gateway->handle_init();

			$this->assertSame( 'Translated: Card', $gateway->title );
			$this->assertSame( 'Translated: WooPayments', $gateway->method_title );
			$this->assertSame( 'Translated: Accept payments with WooPayments.', $gateway->method_description );
		} finally {
			remove_filter( 'gettext', $filter, 10 );

			if ( $had_init_action_count ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate pre-init construction for the WP 6.7 textdomain guard.
				$wp_actions['init'] = $previous_init_count;
			} else {
				unset( $wp_actions['init'] );
			}
		}
	}

	/**
	 * @testdox Should process payments through the native processing service.
	 */
	public function test_process_payment_delegates_to_processing_service(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_payment( $order->get_id() );

		$this->assertSame( 'success', $result['result'] );
		$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
		$this->assertSame( $order->get_id(), $service->last_checkout_context->get_order_id() );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $service->last_checkout_context->get_gateway_id() );
	}

	/**
	 * @testdox Should process refunds through the native processing service.
	 */
	public function test_process_refund_delegates_to_processing_service(): void {
		$order = $this->create_order();
		$order->update_meta_data( '_charge_id', 'ch_test' );
		$order->save();

		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_refund( $order->get_id(), 4.25, 'Adjustment' );

		$this->assertTrue( $result );
		$this->assertInstanceOf( PaymentContext::class, $service->last_refund_context );
		$this->assertSame(
			array(
				'amount' => 4.25,
				'reason' => 'Adjustment',
			),
			$service->last_refund_context->get_payment_data()
		);
	}

	/**
	 * @testdox Should only allow refunds for orders with a WooPayments charge.
	 */
	public function test_can_refund_order_requires_charge_id(): void {
		$order   = $this->create_order();
		$gateway = new NativeWooPaymentsGateway();

		$this->assertFalse( $gateway->can_refund_order( $order ) );

		$order->update_meta_data( '_charge_id', 'ch_test' );
		$order->save();

		$this->assertTrue( $gateway->can_refund_order( wc_get_order( $order->get_id() ) ) );
	}

	/**
	 * @testdox Should fail refunds that do not have a WooPayments charge.
	 */
	public function test_process_refund_fails_without_charge_id(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_refund( $order->get_id(), 4.25, 'Adjustment' );

		$this->assertWPError( $result );
		$this->assertSame( 'native_payment_refund_missing_charge', $result->get_error_code() );
		$this->assertNull( $service->last_refund_context );
	}

	/**
	 * @testdox Should resolve native dependencies when WooCommerce instantiates the gateway directly.
	 */
	public function test_process_payment_resolves_dependencies_without_explicit_init(): void {
		$order   = $this->create_order();
		$gateway = new NativeWooPaymentsGateway();

		$result = $gateway->process_payment( $order->get_id() );

		$this->assertSame(
			array(
				'result'         => 'fail',
				'redirect'       => '',
				'payment_method' => '',
			),
			$result
		);
	}

	/**
	 * @testdox Should delegate payment fields rendering to the checkout bridge.
	 */
	public function test_payment_fields_delegate_to_checkout_bridge(): void {
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$service = new RecordingPaymentProcessingService();
		$bridge  = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'render_payment_fields' ) )
			->getMock();
		$bridge
			->expects( $this->once() )
			->method( 'render_payment_fields' )
			->willReturnCallback(
				static function (): void {
					echo '<div id="wcpay-bridge-marker"></div>';
				}
			);

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider(), $bridge );

		ob_start();
		try {
			$gateway->payment_fields();
			$output = (string) ob_get_clean();
		} finally {
			remove_filter( 'woocommerce_is_checkout', '__return_true' );
		}

		$this->assertStringContainsString( 'wcpay-bridge-marker', $output );
		$this->assertStringContainsString( 'wc-woocommerce_payments-new-payment-method', $output );
		$this->assertStringContainsString( 'wc-woocommerce_payments-payment-token-new', $output );
	}

	/**
	 * @testdox Should resolve checkout bridge dependencies when payment fields are rendered directly.
	 */
	public function test_payment_fields_resolve_dependencies_without_explicit_init(): void {
		$gateway = new NativeWooPaymentsGateway();

		ob_start();
		$gateway->payment_fields();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'wcpay-core-checkout-form', $output );
	}

	/**
	 * Create an order for gateway tests.
	 *
	 * @return WC_Order
	 */
	private function create_order(): WC_Order {
		$order = wc_create_order();
		$order->set_total( '12.00' );
		$order->save();

		return $order;
	}
}
