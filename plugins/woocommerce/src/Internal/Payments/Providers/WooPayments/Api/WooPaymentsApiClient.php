<?php
/**
 * WooPaymentsApiClient class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use WP_Error;

/**
 * Minimal native WooPayments API client for provider-owned WooPayments operations.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsApiClient {

	/**
	 * Request timeout.
	 */
	private const REQUEST_TIMEOUT_SECONDS = 70;

	/**
	 * HTTP client.
	 *
	 * @var WooPaymentsHttpClient
	 */
	private WooPaymentsHttpClient $http_client;

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
	 * @param WooPaymentsHttpClient     $http_client     Native WPCOM transport.
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 */
	final public function init( WooPaymentsHttpClient $http_client, WooPaymentsAccountService $account_service ): void {
		$this->http_client     = $http_client;
		$this->account_service = $account_service;
	}

	/**
	 * Tell whether the transport is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->http_client->is_connected() && null !== $this->http_client->get_blog_id();
	}

	/**
	 * Create a refund.
	 *
	 * @param string      $charge_id        Charge ID.
	 * @param int|null    $amount           Minor-unit amount.
	 * @param string|null $reason           Merchant reason.
	 * @param string      $source           Refund source identifier.
	 * @param string      $idempotency_key  Deterministic idempotency key.
	 * @return array<string,mixed>
	 */
	public function refund_charge( string $charge_id, ?int $amount, ?string $reason, string $source, string $idempotency_key ): array {
		$params = array(
			'idempotency_key' => $idempotency_key,
			'metadata'        => array(
				'refund_source' => $source,
			),
		);

		if ( null !== $amount ) {
			$params['amount'] = $amount;
		}

		if ( in_array( $reason, array( 'duplicate', 'fraudulent', 'requested_by_customer' ), true ) ) {
			$params['reason'] = $reason;
		}

		if ( null !== $reason && '' !== $reason ) {
			$params['metadata']['merchant_refund_reason'] = $reason;
		}

		return $this->request( $params, 'refunds/' . $charge_id, 'POST' );
	}

	/**
	 * Capture a payment intention.
	 *
	 * @param string              $intent_id          Intent ID.
	 * @param int                 $amount_to_capture  Minor-unit capture amount.
	 * @param array<string,mixed> $metadata           Intent metadata.
	 * @return array<string,mixed>
	 */
	public function capture_intention( string $intent_id, int $amount_to_capture, array $metadata = array() ): array {
		$params = array(
			'amount_to_capture' => $amount_to_capture,
			'metadata'          => $metadata,
		);

		return $this->request( $params, 'intentions/' . $intent_id . '/capture', 'POST' );
	}

	/**
	 * Cancel a payment intention.
	 *
	 * @param string $intent_id Intent ID.
	 * @return array<string,mixed>
	 */
	public function cancel_intention( string $intent_id ): array {
		return $this->request( array(), 'intentions/' . $intent_id . '/cancel', 'POST' );
	}

	/**
	 * Create a WooPayments customer.
	 *
	 * @param array<string,mixed> $customer_data Customer payload.
	 * @return string
	 */
	public function create_customer( array $customer_data ): string {
		$result = $this->request( $customer_data, 'customers', 'POST' );

		return isset( $result['id'] ) ? (string) $result['id'] : '';
	}

	/**
	 * Update a WooPayments customer.
	 *
	 * @param string              $customer_id   Customer ID.
	 * @param array<string,mixed> $customer_data Customer update payload.
	 * @return void
	 * @throws WooPaymentsApiException When the customer ID is missing.
	 */
	public function update_customer( string $customer_id, array $customer_data = array() ): void {
		if ( '' === trim( $customer_id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Customer ID is required.', 'woocommerce' ), 'wcpay_mandatory_customer_id_missing', 400 );
		}

		$this->request( $customer_data, 'customers/' . $customer_id, 'POST' );
	}

	/**
	 * Create and confirm a positive-amount WooPayments PaymentIntent.
	 *
	 * @param array<string,mixed> $request_data     Intent payload.
	 * @param string              $idempotency_key  Deterministic idempotency key.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request shape is invalid.
	 */
	public function create_and_confirm_payment_intention( array $request_data, string $idempotency_key ): array {
		$has_payment_method     = isset( $request_data['payment_method'] ) && '' !== trim( (string) $request_data['payment_method'] );
		$has_confirmation_token = isset( $request_data['confirmation_token'] ) && '' !== trim( (string) $request_data['confirmation_token'] );

		if ( $has_payment_method === $has_confirmation_token ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'A WooPayments charge requires exactly one payment credential.', 'woocommerce' ), 'wcpay_invalid_payment_credential', 400 );
		}

		$request_data['confirm']         = true;
		$request_data['capture_method']  = $request_data['capture_method'] ?? 'automatic';
		$request_data['idempotency_key'] = $idempotency_key;

		return $this->request( $request_data, 'intentions', 'POST' );
	}

	/**
	 * Create and confirm a WooPayments SetupIntent.
	 *
	 * @param array<string,mixed> $request_data    SetupIntent payload.
	 * @param string              $idempotency_key Deterministic idempotency key.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request shape is invalid.
	 */
	public function create_and_confirm_setup_intention( array $request_data, string $idempotency_key ): array {
		$has_payment_method     = isset( $request_data['payment_method'] ) && '' !== trim( (string) $request_data['payment_method'] );
		$has_confirmation_token = isset( $request_data['confirmation_token'] ) && '' !== trim( (string) $request_data['confirmation_token'] );

		if ( $has_payment_method === $has_confirmation_token ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'A WooPayments setup intent requires exactly one payment credential.', 'woocommerce' ), 'wcpay_invalid_payment_credential', 400 );
		}

		$request_data['confirm']              = true;
		$request_data['payment_method_types'] = $request_data['payment_method_types'] ?? array( 'card' );
		$request_data['idempotency_key']      = $idempotency_key;

		return $this->request( $request_data, 'setup_intents', 'POST' );
	}

	/**
	 * Create an unconfirmed WooPayments SetupIntent.
	 *
	 * @param array<string,mixed> $request_data    SetupIntent payload.
	 * @param string              $idempotency_key Optional idempotency key.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function create_setup_intention( array $request_data, string $idempotency_key = '' ): array {
		$request_data['confirm']              = false;
		$request_data['payment_method_types'] = $request_data['payment_method_types'] ?? array( 'card' );

		if ( '' !== $idempotency_key ) {
			$request_data['idempotency_key'] = $idempotency_key;
		}

		return $this->request( $request_data, 'setup_intents', 'POST' );
	}

	/**
	 * Retrieve a WooPayments PaymentIntent.
	 *
	 * @param string $intent_id Intent ID.
	 * @return array<string,mixed>
	 */
	public function get_payment_intention( string $intent_id ): array {
		return $this->request( array(), 'intentions/' . rawurlencode( $intent_id ), 'GET' );
	}

	/**
	 * Retrieve a WooPayments SetupIntent.
	 *
	 * @param string $setup_intent_id SetupIntent ID.
	 * @return array<string,mixed>
	 */
	public function get_setup_intention( string $setup_intent_id ): array {
		return $this->request( array(), 'setup_intents/' . rawurlencode( $setup_intent_id ), 'GET' );
	}

	/**
	 * Send a request through the provider transport.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @param string              $api    API path.
	 * @param string              $method HTTP method.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	private function request( array $params, string $api, string $method ): array {
		if ( ! $this->is_available() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Site is not connected to WordPress.com.', 'woocommerce' ), 'wcpay_wpcom_not_connected', 409 );
		}

		$params = wp_parse_args(
			$params,
			array(
				'test_mode' => $this->account_service->is_test_mode_enabled(),
			)
		);

		/**
		 * Filters the WooPayments native request parameters before transport dispatch.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $params Request parameters.
		 * @param string              $api    API path.
		 * @param string              $method HTTP method.
		 */
		$params = apply_filters( 'wcpay_api_request_params', $params, $api, $method );

		$headers = array(
			'Content-Type' => 'application/json; charset=utf-8',
			'User-Agent'   => $this->get_user_agent(),
		);

		if ( isset( $params['idempotency_key'] ) ) {
			$headers['Idempotency-Key'] = (string) $params['idempotency_key'];
			unset( $params['idempotency_key'] );
		}

		/**
		 * Filters the WooPayments native request headers before transport dispatch.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,string> $headers Request headers.
		 */
		$headers = apply_filters( 'wcpay_api_request_headers', $headers );
		$body    = wp_json_encode( $params );

		if ( false === $body ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Unable to encode the WooPayments request body.', 'woocommerce' ), 'wcpay_client_unable_to_encode_json' );
		}

		$site_id  = $this->http_client->get_blog_id();
		$response = $this->http_client->request(
			$method,
			sprintf( '/sites/%d/wcpay/%s', (int) $site_id, $api ),
			$headers,
			$body,
			self::REQUEST_TIMEOUT_SECONDS
		);

		if ( $response instanceof WP_Error ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Provider error is transported as structured application data, not rendered HTML.
			throw new WooPaymentsApiException( $response->get_error_message(), (string) $response->get_error_code() );
		}

		$response_code       = (int) wp_remote_retrieve_response_code( $response );
		$response_body       = wp_remote_retrieve_body( $response );
		$content_type_header = wp_remote_retrieve_header( $response, 'content-type' );
		$content_type        = is_array( $content_type_header ) ? implode( ',', $content_type_header ) : (string) $content_type_header;
		$is_json             = false !== strpos( strtolower( $content_type ), 'application/json' );
		$decoded_body        = json_decode( $response_body, true );

		if ( null === $decoded_body && '' !== $response_body && $is_json ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Unable to decode response from WooPayments.', 'woocommerce' ), 'wcpay_unparseable_or_null_body', $response_code );
		}

		if ( 400 <= $response_code ) {
			$this->throw_api_error( is_array( $decoded_body ) ? $decoded_body : array(), $response_code );
		}

		return is_array( $decoded_body ) ? $decoded_body : array();
	}

	/**
	 * Throw a normalized API exception from a decoded response body.
	 *
	 * @param array<string,mixed> $response_body Decoded response body.
	 * @param int                 $response_code HTTP status code.
	 * @throws WooPaymentsApiException Always.
	 */
	private function throw_api_error( array $response_body, int $response_code ): void {
		$error_code    = 'wcpay_client_error_code_missing';
		$error_message = __( 'Server error. Please try again.', 'woocommerce' );

		if ( isset( $response_body['error'] ) && is_array( $response_body['error'] ) ) {
			$error_code    = isset( $response_body['error']['code'] ) ? (string) $response_body['error']['code'] : $error_code;
			$error_message = isset( $response_body['error']['message'] ) ? (string) $response_body['error']['message'] : $error_message;
		} elseif ( isset( $response_body['code'] ) ) {
			$error_code    = (string) $response_body['code'];
			$error_message = isset( $response_body['message'] ) ? (string) $response_body['message'] : $error_message;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Provider error is transported as structured application data, not rendered HTML.
		throw new WooPaymentsApiException(
			sprintf(
				/* translators: %s: provider error message. */
				__( 'Error: %s', 'woocommerce' ),
				$error_message
			),
			$error_code,
			$response_code
		);
		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
	}

	/**
	 * Build the provider user-agent string.
	 *
	 * @return string
	 */
	private function get_user_agent(): string {
		$version = defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown';

		return 'WooPaymentsNative/woocommerce/' . $version;
	}
}
