<?php
/**
 * WooPaymentsCheckoutAjaxController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Admin\Settings\Utils;
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

	private const ZERO_DECIMAL_CURRENCIES = array(
		'bif',
		'clp',
		'djf',
		'gnf',
		'jpy',
		'kmf',
		'krw',
		'mga',
		'pyg',
		'rwf',
		'vnd',
		'vuv',
		'xaf',
		'xof',
		'xpf',
	);

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
	 * WooPayments order data service.
	 *
	 * @var WooPaymentsOrderDataService|null
	 */
	private ?WooPaymentsOrderDataService $order_data_service = null;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter     $arbiter            Runtime owner arbiter.
	 * @param WooPaymentsApiClient             $api_client         Native WooPayments API client.
	 * @param WooPaymentsCustomerService       $customer_service   WooPayments customer service.
	 * @param OrderPaymentLifecycleService     $lifecycle_service  Order lifecycle service.
	 * @param WooPaymentsTokenService          $token_service      WooPayments token service.
	 * @param WooPaymentsAccountService        $account_service    WooPayments account service.
	 * @param WooPaymentsOrderDataService|null $order_data_service WooPayments order data service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsApiClient $api_client,
		WooPaymentsCustomerService $customer_service,
		OrderPaymentLifecycleService $lifecycle_service,
		WooPaymentsTokenService $token_service,
		WooPaymentsAccountService $account_service,
		?WooPaymentsOrderDataService $order_data_service = null
	): void {
		$this->arbiter            = $arbiter;
		$this->api_client         = $api_client;
		$this->customer_service   = $customer_service;
		$this->lifecycle_service  = $lifecycle_service;
		$this->token_service      = $token_service;
		$this->account_service    = $account_service;
		$this->order_data_service = $order_data_service;
	}

	/**
	 * Register AJAX hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
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

				$this->apply_payment_method_display_details( $order, $intent );
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

		$balance_transaction_id = $this->get_balance_transaction_id( $charge['balance_transaction'] ?? null );
		if ( '' !== $balance_transaction_id ) {
			$meta['_wcpay_payment_transaction_id'] = $balance_transaction_id;
		}

		if ( isset( $charge['outcome']['risk_level'] ) ) {
			$meta['_charge_risk_level'] = (string) $charge['outcome']['risk_level'];
		}

		if ( 'succeeded' === $status ) {
			$meta = array_merge( $meta, $this->get_completed_charge_order_meta( $intent, $charge, $order ) );
		}

		return new PaymentLifecycleEvent(
			$this->map_intent_status_to_lifecycle_status( $status ),
			'' === $intent_id ? null : $intent_id,
			$meta,
			array(),
			$this->get_lifecycle_note_from_intent( $intent, $order, $intent_id, $charge )
		);
	}

	/**
	 * Get the lifecycle note for a native intent response.
	 *
	 * @param array<string,mixed> $intent    Native intent response.
	 * @param WC_Order            $order     Order being updated.
	 * @param string              $intent_id Payment intent ID.
	 * @param array<string,mixed> $charge    Latest charge data.
	 * @return string|null
	 */
	private function get_lifecycle_note_from_intent( array $intent, WC_Order $order, string $intent_id, array $charge ): ?string {
		$status = isset( $intent['status'] ) ? (string) $intent['status'] : '';
		if ( 'succeeded' !== $status || 0 !== strpos( $intent_id, 'pi_' ) ) {
			return null;
		}

		$charge_id              = isset( $charge['id'] ) ? (string) $charge['id'] : '';
		$balance_transaction_id = $this->get_balance_transaction_id( $charge['balance_transaction'] ?? null );

		return $this->get_payment_success_note( $order, $intent_id, $charge_id, $balance_transaction_id );
	}

	/**
	 * Apply charge payment-method display details before order completion.
	 *
	 * @param WC_Order            $order  Order being updated.
	 * @param array<string,mixed> $intent Native intent response.
	 */
	private function apply_payment_method_display_details( WC_Order $order, array $intent ): void {
		$charge                 = $this->get_latest_charge( $intent );
		$payment_method_details = is_array( $charge['payment_method_details'] ?? null ) ? $charge['payment_method_details'] : array();
		$wallet_type            = $payment_method_details['card']['wallet']['type'] ?? null;

		if ( empty( $payment_method_details ) ) {
			return;
		}

		$encoded_payment_method_details = wp_json_encode( $payment_method_details );
		if ( false !== $encoded_payment_method_details ) {
			$order->update_meta_data( '_wcpay_payment_method_details', $encoded_payment_method_details );
		}

		if ( 'link' !== $wallet_type && isset( $payment_method_details['card']['last4'] ) ) {
			$order->update_meta_data( 'last4', (string) $payment_method_details['card']['last4'] );
			if ( isset( $payment_method_details['card']['brand'] ) ) {
				$order->update_meta_data( '_card_brand', (string) $payment_method_details['card']['brand'] );
			}
		}

		if ( is_string( $wallet_type ) && '' !== $wallet_type ) {
			$order->update_meta_data( '_wcpay_express_checkout_payment_method', $wallet_type );
		}

		$order->set_payment_method_title( $this->get_payment_method_title( $payment_method_details ) );
		$order->save();
	}

	/**
	 * Get the WooPayments order data service.
	 *
	 * @return WooPaymentsOrderDataService
	 */
	private function get_order_data_service(): WooPaymentsOrderDataService {
		if ( null === $this->order_data_service ) {
			$this->order_data_service = wc_get_container()->get( WooPaymentsOrderDataService::class );
		}

		return $this->order_data_service;
	}

	/**
	 * Get the human-readable payment method title from charge details.
	 *
	 * @param array<string,mixed> $payment_method_details Payment method details from the charge.
	 * @return string
	 */
	private function get_payment_method_title( array $payment_method_details ): string {
		$wallet_type = $payment_method_details['card']['wallet']['type'] ?? null;

		switch ( $wallet_type ) {
			case 'link':
				return __( 'Link', 'woocommerce' );

			case 'apple_pay':
				return __( 'Apple Pay', 'woocommerce' );

			case 'google_pay':
				return __( 'Google Pay', 'woocommerce' );
		}

		if ( 'card' === ( $payment_method_details['type'] ?? '' ) && isset( $payment_method_details['card'] ) && is_array( $payment_method_details['card'] ) ) {
			return $this->get_card_payment_method_title( $payment_method_details['card'] );
		}

		return __( 'Credit / Debit Cards', 'woocommerce' );
	}

	/**
	 * Get the human-readable card payment method title from charge details.
	 *
	 * @param array<string,mixed> $card_details Card details from the charge.
	 * @return string
	 */
	private function get_card_payment_method_title( array $card_details ): string {
		$funding_types = array(
			'credit'  => __( 'credit', 'woocommerce' ),
			'debit'   => __( 'debit', 'woocommerce' ),
			'prepaid' => __( 'prepaid', 'woocommerce' ),
			'unknown' => __( 'unknown', 'woocommerce' ),
		);

		$networks     = isset( $card_details['networks'] ) && is_array( $card_details['networks'] ) ? $card_details['networks'] : array();
		$available    = isset( $networks['available'] ) && is_array( $networks['available'] ) ? $networks['available'] : array();
		$card_network = $card_details['display_brand'] ?? $card_details['network'] ?? $networks['preferred'] ?? $available[0] ?? 'card';
		$card_network = str_replace( '_', ' ', (string) $card_network );
		$funding      = isset( $card_details['funding'] ) && isset( $funding_types[ (string) $card_details['funding'] ] )
			? $funding_types[ (string) $card_details['funding'] ]
			: $funding_types['unknown'];

		return sprintf(
			/* translators: %1$s: card brand, %2$s: card funding type. */
			__( '%1$s %2$s card', 'woocommerce' ),
			ucwords( $card_network ),
			$funding
		);
	}

	/**
	 * Get a WooPayments-compatible payment success order note.
	 *
	 * @param WC_Order $order     Order object.
	 * @param string   $intent_id Payment intent ID.
	 * @param string   $charge_id              Charge ID.
	 * @param string   $balance_transaction_id Balance transaction ID.
	 * @return string
	 */
	private function get_payment_success_note( WC_Order $order, string $intent_id, string $charge_id, string $balance_transaction_id = '' ): string {
		$formatted_amount = wc_price( (float) $order->get_total(), array( 'currency' => $order->get_currency() ) ) . ' ' . $order->get_currency();
		$transaction_id   = '' !== $intent_id ? $intent_id : $charge_id;
		$transaction_url  = $this->get_transaction_url( $intent_id, $charge_id, $balance_transaction_id );

		if ( 'test' === $this->account_service->get_mode() ) {
			return sprintf(
				$this->get_interpolated_note_text(
					/* translators: %1$s: charged amount, %2$s: WooPayments, %3$s: transaction ID. */
					__( 'A test payment of %1$s was processed using %2$s in <strong>test mode</strong> (<a>%3$s</a>). No real funds were collected.', 'woocommerce' ),
					array(
						'strong' => '<strong>',
						'a'      => '' !== $transaction_url ? '<a href="' . $transaction_url . '" target="_blank" rel="noopener noreferrer">' : '<code>',
					)
				),
				$formatted_amount,
				'WooPayments',
				$transaction_id
			);
		}

		return sprintf(
			$this->get_interpolated_note_text(
				/* translators: %1$s: charged amount, %2$s: WooPayments, %3$s: transaction ID, %4$s: transaction URL. */
				__( 'A payment of %1$s was <strong>successfully charged</strong> using %2$s (<a>%3$s</a>).', 'woocommerce' ),
				array(
					'strong' => '<strong>',
					'a'      => '' !== $transaction_url ? '<a href="%4$s" target="_blank" rel="noopener noreferrer">' : '<code>',
				)
			),
			$formatted_amount,
			'WooPayments',
			$transaction_id,
			$transaction_url
		);
	}

	/**
	 * Replace simple interpolation tags with stored note HTML.
	 *
	 * @param string               $text        Note text.
	 * @param array<string,string> $element_map Element replacements.
	 * @return string
	 */
	private function get_interpolated_note_text( string $text, array $element_map ): string {
		foreach ( $element_map as $tag => $opening_tag ) {
			$closing_tag = '</' . $tag . '>';
			if ( preg_match( '/^<(\w+)/', $opening_tag, $matches ) ) {
				$closing_tag = '</' . $matches[1] . '>';
			}

			$text = str_replace( '<' . $tag . '>', $opening_tag, $text );
			$text = str_replace( '</' . $tag . '>', $closing_tag, $text );
		}

		return $text;
	}

	/**
	 * Get the WooPayments transaction details URL.
	 *
	 * @param string $intent_id              Payment intent ID.
	 * @param string $charge_id              Charge ID.
	 * @param string $balance_transaction_id Balance transaction ID.
	 * @return string
	 */
	private function get_transaction_url( string $intent_id, string $charge_id, string $balance_transaction_id = '' ): string {
		if ( '' === $intent_id && '' === $charge_id && '' === $balance_transaction_id ) {
			return '';
		}

		if ( false !== strpos( $intent_id, 'seti_' ) ) {
			return '';
		}

		$params = array(
			'id' => '' !== $intent_id ? $intent_id : $charge_id,
		);

		return Utils::wc_payments_legacy_admin_url(
			'/payments/transactions/details',
			$params
		);
	}

	/**
	 * Get a balance transaction ID from a provider response field.
	 *
	 * @param mixed $balance_transaction Balance transaction response field.
	 * @return string
	 */
	private function get_balance_transaction_id( $balance_transaction ): string {
		if ( is_string( $balance_transaction ) ) {
			return $balance_transaction;
		}

		if ( is_array( $balance_transaction ) && isset( $balance_transaction['id'] ) ) {
			return (string) $balance_transaction['id'];
		}

		return '';
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
				$this->token_service->sync_related_subscriptions_payment_token( $order, $token, $payment_method_id, $this->get_result_customer_id( $intent ) );

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
	 * Get legacy-compatible order meta for a completed native charge.
	 *
	 * @param array<string,mixed> $intent Native PaymentIntent response.
	 * @param array<string,mixed> $charge Native Charge response.
	 * @param WC_Order            $order  Order being charged.
	 * @return array<string,string>
	 */
	private function get_completed_charge_order_meta( array $intent, array $charge, WC_Order $order ): array {
		$meta = array();

		$transaction_fee = $this->get_transaction_fee_from_charge( $intent, $charge );
		if ( '' !== $transaction_fee ) {
			$meta['_wcpay_transaction_fee'] = $transaction_fee;
		}

		$net = $this->get_net_from_charge( $intent, $charge, $transaction_fee );
		if ( '' !== $net ) {
			$meta['_wcpay_net'] = $net;
		}

		$meta = array_merge(
			$meta,
			$this->get_order_data_service()->get_settlement_exchange_rate_order_meta(
				$order,
				$charge,
				$this->account_service->get_account_default_currency()
			)
		);

		$meta = array_merge( $meta, $this->get_fraud_outcome_order_meta( $intent, $charge ) );

		return $meta;
	}

	/**
	 * Get WooPayments fraud-outcome order meta from provider metadata.
	 *
	 * @param array<string,mixed> $intent Native intent response.
	 * @param array<string,mixed> $charge Native Charge response.
	 * @return array<string,string>
	 */
	private function get_fraud_outcome_order_meta( array $intent, array $charge ): array {
		$metadata      = isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
		$fraud_outcome = isset( $metadata['fraud_outcome'] ) ? (string) $metadata['fraud_outcome'] : '';

		if ( '' === $fraud_outcome && $this->is_card_charge( $charge ) ) {
			$fraud_outcome = 'allow';
		}

		if ( ! in_array( $fraud_outcome, array( 'allow', 'block', 'review' ), true ) ) {
			return array();
		}

		return array(
			'_wcpay_fraud_outcome_status' => $fraud_outcome,
			'_wcpay_fraud_meta_box_type'  => 'allow',
		);
	}

	/**
	 * Tell whether a native charge was made with a card payment method.
	 *
	 * @param array<string,mixed> $charge Native Charge response.
	 * @return bool
	 */
	private function is_card_charge( array $charge ): bool {
		$payment_method_details = isset( $charge['payment_method_details'] ) && is_array( $charge['payment_method_details'] )
			? $charge['payment_method_details']
			: array();

		return 'card' === (string) ( $payment_method_details['type'] ?? '' );
	}

	/**
	 * Get the merchant transaction fee from a native charge.
	 *
	 * @param array<string,mixed> $intent Native PaymentIntent response.
	 * @param array<string,mixed> $charge Native Charge response.
	 * @return string
	 */
	private function get_transaction_fee_from_charge( array $intent, array $charge ): string {
		$fee_breakdown_v1 = $charge['fee_breakdown_v1'] ?? null;
		if ( is_array( $fee_breakdown_v1 ) && isset( $fee_breakdown_v1['totals']['fee']['amount'], $fee_breakdown_v1['totals']['fee']['currency'] ) ) {
			return (string) $this->interpret_stripe_amount( (int) $fee_breakdown_v1['totals']['fee']['amount'], (string) $fee_breakdown_v1['totals']['fee']['currency'] );
		}

		$application_fee_amount = $charge['application_fee_amount'] ?? null;
		$currency               = isset( $charge['currency'] ) ? (string) $charge['currency'] : (string) ( $intent['currency'] ?? '' );
		if ( null !== $application_fee_amount && '' !== $currency ) {
			return (string) $this->interpret_stripe_amount( (int) $application_fee_amount, $currency );
		}

		return '';
	}

	/**
	 * Get the merchant net amount from a native charge.
	 *
	 * @param array<string,mixed> $intent          Native PaymentIntent response.
	 * @param array<string,mixed> $charge          Native Charge response.
	 * @param string              $transaction_fee Transaction fee.
	 * @return string
	 */
	private function get_net_from_charge( array $intent, array $charge, string $transaction_fee ): string {
		$fee_breakdown_v1 = $charge['fee_breakdown_v1'] ?? null;
		if ( is_array( $fee_breakdown_v1 ) && isset( $fee_breakdown_v1['totals']['net']['amount'], $fee_breakdown_v1['totals']['net']['currency'] ) ) {
			return (string) $this->interpret_stripe_amount( (int) $fee_breakdown_v1['totals']['net']['amount'], (string) $fee_breakdown_v1['totals']['net']['currency'] );
		}

		$application_fee_amount = $charge['application_fee_amount'] ?? null;
		$charge_amount          = $charge['amount'] ?? $intent['amount'] ?? null;
		$currency               = isset( $charge['currency'] ) ? (string) $charge['currency'] : (string) ( $intent['currency'] ?? '' );
		if ( null !== $application_fee_amount && '' !== $transaction_fee && null !== $charge_amount && '' !== $currency ) {
			return (string) ( $this->interpret_stripe_amount( (int) $charge_amount, $currency ) - (float) $transaction_fee );
		}

		return '';
	}

	/**
	 * Interpret a Stripe integer amount for a currency.
	 *
	 * @param int    $amount   Stripe integer amount.
	 * @param string $currency Currency code.
	 * @return float
	 */
	private function interpret_stripe_amount( int $amount, string $currency ): float {
		return in_array( strtolower( $currency ), self::ZERO_DECIMAL_CURRENCIES, true ) ? (float) $amount : (float) $amount / 100;
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
