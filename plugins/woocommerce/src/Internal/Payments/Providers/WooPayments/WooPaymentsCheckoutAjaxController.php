<?php
/**
 * WooPaymentsCheckoutAjaxController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\NativeWooPaymentsGateway;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentLifecycleService;
use Automattic\WooCommerce\Internal\Payments\PaymentLifecycleEvent;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WC_Order;

/**
 * Native WooPayments checkout AJAX callbacks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCheckoutAjaxController implements RegisterHooksInterface {

	private const CREATE_SETUP_INTENT_RATE_LIMIT_SECONDS = 5;

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * Native WooPayments API client.
	 *
	 * @var WooPaymentsApiClient
	 */
	private WooPaymentsApiClient $api_client;

	/**
	 * WooPayments customer service.
	 *
	 * @var WooPaymentsCustomerService
	 */
	private WooPaymentsCustomerService $customer_service;

	/**
	 * Order lifecycle service.
	 *
	 * @var OrderPaymentLifecycleService
	 */
	private OrderPaymentLifecycleService $lifecycle_service;

	/**
	 * WooPayments token service.
	 *
	 * @var WooPaymentsTokenService
	 */
	private WooPaymentsTokenService $token_service;

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter           Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client        Native WooPayments API client.
	 * @param WooPaymentsCustomerService   $customer_service  WooPayments customer service.
	 * @param OrderPaymentLifecycleService $lifecycle_service Order lifecycle service.
	 * @param WooPaymentsTokenService      $token_service     WooPayments token service.
	 * @param WooPaymentsAccountService    $account_service   WooPayments account service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsApiClient $api_client,
		WooPaymentsCustomerService $customer_service,
		OrderPaymentLifecycleService $lifecycle_service,
		WooPaymentsTokenService $token_service,
		WooPaymentsAccountService $account_service
	): void {
		$this->arbiter           = $arbiter;
		$this->api_client        = $api_client;
		$this->customer_service  = $customer_service;
		$this->lifecycle_service = $lifecycle_service;
		$this->token_service     = $token_service;
		$this->account_service   = $account_service;
	}

	/**
	 * Register AJAX hooks.
	 */
	public function register() {
		if ( ! $this->can_handle_callbacks() ) {
			return;
		}

		if ( false === has_action( 'wp_ajax_update_order_status', array( $this, 'handle_update_order_status' ) ) ) {
			add_action( 'wp_ajax_update_order_status', array( $this, 'handle_update_order_status' ) );
		}

		if ( false === has_action( 'wp_ajax_nopriv_update_order_status', array( $this, 'handle_update_order_status' ) ) ) {
			add_action( 'wp_ajax_nopriv_update_order_status', array( $this, 'handle_update_order_status' ) );
		}

		if ( false === has_action( 'wp_ajax_create_setup_intent', array( $this, 'handle_create_setup_intent' ) ) ) {
			add_action( 'wp_ajax_create_setup_intent', array( $this, 'handle_create_setup_intent' ) );
		}
	}

	/**
	 * Handle the WooPayments-compatible order-status update action.
	 */
	public function handle_update_order_status(): void {
		$response    = $this->get_update_order_status_response( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$status_code = $this->extract_status_code( $response );

		wp_send_json( $response, $status_code );
	}

	/**
	 * Handle the WooPayments-compatible setup-intent creation action.
	 */
	public function handle_create_setup_intent(): void {
		$response    = $this->get_create_setup_intent_response( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$status_code = $this->extract_status_code( $response );
		$data        = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();

		if ( ! empty( $response['success'] ) ) {
			wp_send_json_success( $data, $status_code );
		}

		wp_send_json_error( $data, $status_code );
	}

	/**
	 * Build the WooPayments-compatible order-status response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,mixed>
	 */
	public function get_update_order_status_response( array $request ): array {
		if ( ! $this->can_handle_callbacks() ) {
			return $this->error_response( __( "We're not able to process this payment. Please try again later.", 'woocommerce' ), 409 );
		}

		if ( ! $this->is_nonce_valid( $request, 'wcpay_update_order_status_nonce' ) ) {
			return $this->error_response( __( "We're not able to process this payment. Please refresh the page and try again.", 'woocommerce' ), 403 );
		}

		$order_id = absint( $request['order_id'] ?? 0 );
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->error_response( __( "We're not able to process this payment. Please try again later.", 'woocommerce' ), 404 );
		}

		$intent_id = $this->get_request_string( $request, 'intent_id' );
		if ( '' === $intent_id || $intent_id !== (string) $order->get_meta( '_intent_id', true ) ) {
			$order->add_order_note( __( 'WooPayments intent verification failed after customer authentication.', 'woocommerce' ) );
			return $this->error_response( __( "We're not able to process this payment. Please try again later.", 'woocommerce' ), 409 );
		}

		try {
			$intent = 0.0 >= (float) $order->get_total()
				? $this->api_client->get_setup_intention( $intent_id )
				: $this->api_client->get_payment_intention( $intent_id );
			$status = isset( $intent['status'] ) ? (string) $intent['status'] : '';

			if ( $this->is_authorized_intent_status( $status ) ) {
				$token_save_error = $this->maybe_save_payment_method_for_order( $order, $intent, $request );
				if ( null !== $token_save_error ) {
					return $token_save_error;
				}
			}

			$event = $this->build_lifecycle_event_from_intent( $intent, $order );
			$this->lifecycle_service->apply( $order, $event );

			if ( ! $this->is_authorized_intent_status( $status ) ) {
				return $this->error_response( __( "We're not able to process this payment. Please try again later.", 'woocommerce' ), 409 );
			}

			return array(
				'return_url'  => $this->get_return_url( $order ),
				'status_code' => 200,
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->error_response( $exception->getMessage(), 502 );
		} catch ( Throwable $exception ) {
			return $this->error_response( __( "We're not able to process this payment. Please try again later.", 'woocommerce' ), 500 );
		}
	}

	/**
	 * Build the WooPayments-compatible setup-intent creation response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,mixed>
	 */
	public function get_create_setup_intent_response( array $request ): array {
		if ( ! $this->can_handle_callbacks() ) {
			return $this->json_error_response( __( "We're not able to add this payment method. Please try again later.", 'woocommerce' ), 409 );
		}

		if ( ! is_user_logged_in() ) {
			return $this->json_error_response( __( "We're not able to add this payment method. Please log in and try again.", 'woocommerce' ), 401 );
		}

		if ( ! $this->is_nonce_valid( $request, 'wcpay_create_setup_intent_nonce' ) ) {
			return $this->json_error_response( __( "We're not able to add this payment method. Please refresh the page and try again.", 'woocommerce' ), 403 );
		}

		$user_id        = get_current_user_id();
		$rate_limit_key = 'add_payment_method_' . $user_id;
		if ( \WC_Rate_Limiter::retried_too_soon( $rate_limit_key ) ) {
			return $this->json_error_response( __( 'You cannot add a new payment method so soon after the previous one. Please try again later.', 'woocommerce' ), 429 );
		}

		$payment_method_id = $this->get_request_string( $request, 'wcpay-payment-method' );
		if ( '' === $payment_method_id ) {
			return $this->json_error_response( __( "We're not able to add this payment method. Please try again later.", 'woocommerce' ), 400 );
		}

		try {
			\WC_Rate_Limiter::set_rate_limit( $rate_limit_key, self::CREATE_SETUP_INTENT_RATE_LIMIT_SECONDS );

			$result = $this->api_client->create_and_confirm_setup_intention(
				array(
					'customer'             => $this->customer_service->get_or_create_customer_id_for_user( $user_id ),
					'payment_method'       => $payment_method_id,
					'payment_method_types' => array( 'card' ),
				),
				'add_payment_method_' . $user_id . '_' . md5( $payment_method_id )
			);

			return array(
				'success'     => true,
				'data'        => array(
					'id'            => isset( $result['id'] ) ? (string) $result['id'] : '',
					'status'        => isset( $result['status'] ) ? (string) $result['status'] : '',
					'client_secret' => isset( $result['client_secret'] ) ? (string) $result['client_secret'] : '',
				),
				'status_code' => 200,
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->json_error_response( $exception->getMessage(), 502 );
		} catch ( Throwable $exception ) {
			return $this->json_error_response( __( "We're not able to add this payment method. Please try again later.", 'woocommerce' ), 500 );
		}
	}

	/**
	 * Tell whether native callbacks can be handled.
	 *
	 * @return bool
	 */
	private function can_handle_callbacks(): bool {
		return $this->arbiter->should_native_register() && $this->api_client->is_available();
	}

	/**
	 * Verify an AJAX nonce from request data.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @param string              $action  Nonce action.
	 * @return bool
	 */
	private function is_nonce_valid( array $request, string $action ): bool {
		$nonce = $this->get_request_string( $request, '_ajax_nonce' );

		return '' !== $nonce && (bool) wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Build a payment lifecycle event from a native intent response.
	 *
	 * @param array<string,mixed> $intent Native intent response.
	 * @param WC_Order            $order  Order being updated.
	 * @return PaymentLifecycleEvent
	 */
	private function build_lifecycle_event_from_intent( array $intent, WC_Order $order ): PaymentLifecycleEvent {
		$status    = isset( $intent['status'] ) ? (string) $intent['status'] : '';
		$intent_id = isset( $intent['id'] ) ? (string) $intent['id'] : '';
		$charge    = $this->get_latest_charge( $intent );
		$meta      = array(
			'_intent_id'             => $intent_id,
			'_intention_status'      => $status,
			'_wcpay_intent_currency' => isset( $intent['currency'] ) ? (string) $intent['currency'] : (string) $order->get_currency(),
			'_wcpay_mode'            => $this->account_service->get_mode(),
		);

		$payment_method_id = $this->get_result_payment_method_id( $intent );
		if ( '' !== $payment_method_id ) {
			$meta['_payment_method_id'] = $payment_method_id;
		}

		$customer_id = $this->get_result_customer_id( $intent );
		if ( '' !== $customer_id ) {
			$meta['_stripe_customer_id'] = $customer_id;
		}

		if ( isset( $charge['id'] ) ) {
			$meta['_charge_id'] = (string) $charge['id'];
		}

		if ( isset( $charge['balance_transaction']['id'] ) ) {
			$meta['_wcpay_payment_transaction_id'] = (string) $charge['balance_transaction']['id'];
		}

		if ( isset( $charge['outcome']['risk_level'] ) ) {
			$meta['_charge_risk_level'] = (string) $charge['outcome']['risk_level'];
		}

		return new PaymentLifecycleEvent(
			$this->map_intent_status_to_lifecycle_status( $status ),
			'' === $intent_id ? null : $intent_id,
			$meta
		);
	}

	/**
	 * Map a provider intent status to a lifecycle status.
	 *
	 * @param string $status Provider intent status.
	 * @return string
	 */
	private function map_intent_status_to_lifecycle_status( string $status ): string {
		switch ( $status ) {
			case 'succeeded':
				return PaymentLifecycleEvent::STATUS_COMPLETED;

			case 'requires_capture':
				return PaymentLifecycleEvent::STATUS_AUTHORIZED;

			case 'processing':
			case 'requires_action':
			case 'requires_confirmation':
				return PaymentLifecycleEvent::STATUS_STARTED;

			case 'canceled':
				return PaymentLifecycleEvent::STATUS_CANCELED;
		}

		return PaymentLifecycleEvent::STATUS_FAILED;
	}

	/**
	 * Tell whether an intent status should be treated as authorized.
	 *
	 * @param string $status Intent status.
	 * @return bool
	 */
	private function is_authorized_intent_status( string $status ): bool {
		return in_array( $status, array( 'succeeded', 'requires_capture', 'processing' ), true );
	}

	/**
	 * Persist a requested payment method as a WooCommerce token before completing the order.
	 *
	 * @param WC_Order            $order   Order being updated.
	 * @param array<string,mixed> $intent  Native intent response.
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,mixed>|null Error response when token saving must block checkout.
	 */
	private function maybe_save_payment_method_for_order( WC_Order $order, array $intent, array $request ): ?array {
		$is_recurring     = $this->is_recurring_payment( $order );
		$should_save_card = $is_recurring || $this->should_save_payment_method( $request );
		if ( ! $should_save_card ) {
			return null;
		}

		$payment_method_id = $this->get_result_payment_method_id( $intent );
		$user_id           = $this->get_token_user_id( $order );
		if ( '' === $payment_method_id || 0 >= $user_id ) {
			return $is_recurring ? $this->recurring_token_save_error_response() : null;
		}

		try {
			$token = $this->token_service->get_or_create_card_token_for_user( $payment_method_id, $user_id );
			if ( $token instanceof \WC_Payment_Token ) {
				$this->token_service->attach_token_to_order( $order, $token );

				return null;
			}
		} catch ( Throwable $exception ) {
			$this->log_token_save_error( $payment_method_id, $exception );

			return $is_recurring ? $this->recurring_token_save_error_response() : null;
		}

		return $is_recurring ? $this->recurring_token_save_error_response() : null;
	}

	/**
	 * Tell whether the customer requested payment-method saving.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return bool
	 */
	private function should_save_payment_method( array $request ): bool {
		return 'true' === strtolower( $this->get_request_string( $request, 'should_save_payment_method' ) );
	}

	/**
	 * Tell whether this payment must be saved for a recurring order.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	private function is_recurring_payment( WC_Order $order ): bool {
		$is_recurring = false;
		if ( function_exists( 'wcs_order_contains_subscription' ) ) {
			$is_recurring = (bool) wcs_order_contains_subscription( $order->get_id() );
		}

		if ( ! $is_recurring && function_exists( 'wcs_order_contains_renewal' ) ) {
			$is_recurring = (bool) wcs_order_contains_renewal( $order->get_id() );
		}

		/**
		 * Filters whether a native WooPayments callback requires saved-token persistence.
		 *
		 * @since 11.0.0
		 *
		 * @param bool     $is_recurring Whether the order requires saved-token persistence.
		 * @param WC_Order $order        Order object.
		 */
		return (bool) apply_filters( 'woocommerce_native_woopayments_is_recurring_payment', $is_recurring, $order );
	}

	/**
	 * Get the user ID that should own a saved payment token.
	 *
	 * @param WC_Order $order Order object.
	 * @return int
	 */
	private function get_token_user_id( WC_Order $order ): int {
		$user_id = $order->get_user_id();

		return 0 < $user_id ? $user_id : get_current_user_id();
	}

	/**
	 * Log a token-save error.
	 *
	 * @param string    $payment_method_id Provider payment method ID.
	 * @param Throwable $exception         Exception thrown while saving the token.
	 */
	private function log_token_save_error( string $payment_method_id, Throwable $exception ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		wc_get_logger()->error(
			sprintf(
				'Failed to save native WooPayments payment method %1$s: %2$s',
				$payment_method_id,
				$exception->getMessage()
			),
			array( 'source' => 'payment-info' )
		);
	}

	/**
	 * Build the recurring token-save error response.
	 *
	 * @return array<string,mixed>
	 */
	private function recurring_token_save_error_response(): array {
		return $this->error_response( __( 'Unable to save payment method for subscription. Please try again or use a different payment method.', 'woocommerce' ), 409 );
	}

	/**
	 * Get the latest charge array from a PaymentIntent response.
	 *
	 * @param array<string,mixed> $intent Native intent response.
	 * @return array<string,mixed>
	 */
	private function get_latest_charge( array $intent ): array {
		$charges = isset( $intent['charges']['data'] ) && is_array( $intent['charges']['data'] ) ? $intent['charges']['data'] : array();
		$charge  = empty( $charges ) ? array() : end( $charges );

		return is_array( $charge ) ? $charge : array();
	}

	/**
	 * Get the payment method ID from an intent response.
	 *
	 * @param array<string,mixed> $intent Native intent response.
	 * @return string
	 */
	private function get_result_payment_method_id( array $intent ): string {
		if ( isset( $intent['payment_method'] ) && is_string( $intent['payment_method'] ) ) {
			return $intent['payment_method'];
		}

		if ( isset( $intent['payment_method'] ) && is_array( $intent['payment_method'] ) && isset( $intent['payment_method']['id'] ) ) {
			return (string) $intent['payment_method']['id'];
		}

		return '';
	}

	/**
	 * Get the customer ID from an intent response.
	 *
	 * @param array<string,mixed> $intent Native intent response.
	 * @return string
	 */
	private function get_result_customer_id( array $intent ): string {
		if ( isset( $intent['customer'] ) && is_string( $intent['customer'] ) ) {
			return $intent['customer'];
		}

		if ( isset( $intent['customer'] ) && is_array( $intent['customer'] ) && isset( $intent['customer']['id'] ) ) {
			return (string) $intent['customer']['id'];
		}

		return '';
	}

	/**
	 * Get the return URL for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function get_return_url( WC_Order $order ): string {
		$gateway = new NativeWooPaymentsGateway();

		return $gateway->get_return_url( $order );
	}

	/**
	 * Build a bare WooPayments order-status error response.
	 *
	 * @param string $message     Error message.
	 * @param int    $status_code HTTP status code.
	 * @return array<string,mixed>
	 */
	private function error_response( string $message, int $status_code ): array {
		return array(
			'error'       => array(
				'message' => $message,
			),
			'status_code' => $status_code,
		);
	}

	/**
	 * Build a WP JSON error response payload.
	 *
	 * @param string $message     Error message.
	 * @param int    $status_code HTTP status code.
	 * @return array<string,mixed>
	 */
	private function json_error_response( string $message, int $status_code ): array {
		return array(
			'success'     => false,
			'data'        => array(
				'error' => array(
					'message' => $message,
				),
			),
			'status_code' => $status_code,
		);
	}

	/**
	 * Extract and remove the internal status code from a response.
	 *
	 * @param array<string,mixed> $response Response payload.
	 * @return int
	 */
	private function extract_status_code( array &$response ): int {
		$status_code = isset( $response['status_code'] ) ? (int) $response['status_code'] : 200;
		unset( $response['status_code'] );

		return $status_code;
	}

	/**
	 * Read a sanitized request string.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @param string              $key     Request key.
	 * @return string
	 */
	private function get_request_string( array $request, string $key ): string {
		$value = $request[ $key ] ?? '';

		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}
}
