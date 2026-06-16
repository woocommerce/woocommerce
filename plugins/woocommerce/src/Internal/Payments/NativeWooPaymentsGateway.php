<?php
/**
 * NativeWooPaymentsGateway class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use WC_Order;
use WC_Payment_Gateway_CC;
use WP_Error;

/**
 * Native WooPayments payment gateway shell.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class NativeWooPaymentsGateway extends WC_Payment_Gateway_CC {

	/**
	 * Payment processing service.
	 *
	 * @var PaymentProcessingService
	 */
	private PaymentProcessingService $processing_service;

	/**
	 * WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * WooPayments checkout bridge.
	 *
	 * @var WooPaymentsCheckoutBridge
	 */
	private WooPaymentsCheckoutBridge $checkout_bridge;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = OrderPaymentStore::GATEWAY_ID;
		$this->method_title       = __( 'WooPayments', 'woocommerce' );
		$this->method_description = __( 'Accept payments with WooPayments.', 'woocommerce' );
		$this->has_fields         = true;
		$this->supports           = array(
			'products',
			'refunds',
		);

		$this->init_settings();
	}

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param PaymentProcessingService       $processing_service Payment processing service.
	 * @param WooPaymentsProvider            $provider           WooPayments provider.
	 * @param WooPaymentsCheckoutBridge|null $checkout_bridge    Optional checkout bridge.
	 */
	final public function init( PaymentProcessingService $processing_service, WooPaymentsProvider $provider, ?WooPaymentsCheckoutBridge $checkout_bridge = null ): void {
		$this->processing_service = $processing_service;
		$this->provider           = $provider;

		if ( null !== $checkout_bridge ) {
			$this->checkout_bridge = $checkout_bridge;
		}
	}

	/**
	 * Render WooPayments payment fields.
	 *
	 * @return void
	 */
	public function payment_fields() {
		$this->get_checkout_bridge()->render_payment_fields();
	}

	/**
	 * Process payment for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return array<string,string>
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return array(
				'result'         => 'fail',
				'redirect'       => '',
				'payment_method' => '',
			);
		}

		return $this->get_processing_service()->process_checkout(
			PaymentContext::for_checkout(
				$order,
				$this->id,
				$this->get_request_payment_method_id(),
				$this->get_checkout_payment_data(),
				$this->get_checkout_provider_data()
			),
			$this->get_provider()
		);
	}

	/**
	 * Process refund for an order.
	 *
	 * @param int        $order_id Order ID.
	 * @param float|null $amount   Refund amount.
	 * @param string     $reason   Refund reason.
	 * @return bool|\WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$refund_amount = null === $amount ? 0.0 : (float) $amount;
		if ( '0.00' !== sprintf( '%0.2f', $refund_amount ) && ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'native_payment_refund_missing_charge', __( 'This order does not have a WooPayments charge to refund.', 'woocommerce' ) );
		}

		return $this->get_processing_service()->process_refund(
			PaymentContext::for_refund( $order, $this->id, $refund_amount, (string) $reason ),
			$this->get_provider()
		);
	}

	/**
	 * Tell whether an order can be refunded through WooPayments.
	 *
	 * @param WC_Order|mixed $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order instanceof WC_Order
			&& $this->supports( PaymentGatewayFeature::REFUNDS )
			&& '' !== (string) $order->get_meta( '_charge_id', true );
	}

	/**
	 * Get the payment processing service.
	 *
	 * @return PaymentProcessingService
	 */
	private function get_processing_service(): PaymentProcessingService {
		if ( ! isset( $this->processing_service ) ) {
			$this->processing_service = wc_get_container()->get( PaymentProcessingService::class );
		}

		return $this->processing_service;
	}

	/**
	 * Get the WooPayments provider.
	 *
	 * @return WooPaymentsProvider
	 */
	private function get_provider(): WooPaymentsProvider {
		if ( ! isset( $this->provider ) ) {
			$this->provider = wc_get_container()->get( WooPaymentsProvider::class );
		}

		return $this->provider;
	}

	/**
	 * Get the WooPayments checkout bridge.
	 *
	 * @return WooPaymentsCheckoutBridge
	 */
	private function get_checkout_bridge(): WooPaymentsCheckoutBridge {
		if ( ! isset( $this->checkout_bridge ) ) {
			$this->checkout_bridge = wc_get_container()->get( WooPaymentsCheckoutBridge::class );
		}

		return $this->checkout_bridge;
	}

	/**
	 * Read the submitted provider payment method ID.
	 *
	 * @return string
	 */
	private function get_request_payment_method_id(): string {
		foreach ( array( 'wcpay-confirmation-token', 'wcpay-payment-method', 'wcpay-payment-method-sepa' ) as $key ) {
			if ( empty( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			return $this->sanitize_post_string( $key );
		}

		return '';
	}

	/**
	 * Get generic checkout payment data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_checkout_payment_data(): array {
		$token_key = 'wc-' . $this->id . '-payment-token';

		return array(
			'payment_token'       => $this->sanitize_post_string( $token_key ),
			'save_payment_method' => ! empty( $_POST[ 'wc-' . $this->id . '-new-payment-method' ] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Get WooPayments-scoped checkout provider data.
	 *
	 * @return array<string,mixed>
	 */
	private function get_checkout_provider_data(): array {
		$cvc_key = 'wc-' . $this->id . '-payment-cvc-confirmation';

		return array(
			'cvc_confirmation'          => $this->sanitize_post_string( $cvc_key ),
			'fingerprint'               => $this->sanitize_post_string( 'wcpay-fingerprint' ),
			'payment_method_error'      => $this->sanitize_post_string( 'wcpay-payment-method-error-message' ),
			'payment_method_error_code' => $this->sanitize_post_string( 'wcpay-payment-method-error-code' ),
			'is_woopay'                 => ! empty( $_POST['is_woopay'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}

	/**
	 * Safely read a string from the POST payload.
	 *
	 * @param string $key POST key.
	 * @return string
	 */
	private function sanitize_post_string( string $key ): string {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return '';
		}

		$value = wc_clean( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		return is_string( $value ) ? $value : '';
	}
}
