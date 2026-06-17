<?php
/**
 * WooPaymentsApiClient class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
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
	 * WooPayments onboarding API path.
	 */
	private const ONBOARDING_API = 'onboarding';

	/**
	 * WooPayments accounts API path.
	 */
	private const ACCOUNTS_API = 'accounts';

	/**
	 * WooPayments API root.
	 */
	private const ENDPOINT_REST_BASE = 'wcpay';

	/**
	 * WooPayments recommended payment methods API path.
	 */
	private const RECOMMENDED_PAYMENT_METHODS = 'payment_methods/recommended';

	/**
	 * WooPayments payment methods API path.
	 */
	private const PAYMENT_METHODS_API = 'payment_methods';

	/**
	 * WooPayments timeline API path.
	 */
	private const TIMELINE_API = 'timeline';

	/**
	 * WooPayments store setup API path.
	 */
	private const STORE_SETUP_API = 'accounts/store_setup';

	/**
	 * WooPayments compatibility API path.
	 */
	private const COMPATIBILITY_API = 'compatibility';

	/**
	 * WooPayments tracking API path.
	 */
	private const TRACKING_API = 'tracking';

	/**
	 * WooPayments failed webhook events API path.
	 */
	private const WEBHOOK_FETCH_API = 'webhook/failed_events';

	/**
	 * WooPayments terminal connection-token API path.
	 */
	private const TERMINAL_CONNECTION_TOKENS_API = 'terminal/connection_tokens';

	/**
	 * WooPayments terminal locations API path.
	 */
	private const TERMINAL_LOCATIONS_API = 'terminal/locations';

	/**
	 * WooPayments terminal readers API path.
	 */
	private const TERMINAL_READERS_API = 'terminal/readers';

	/**
	 * WooPayments reader charge summary API path.
	 */
	private const READERS_CHARGE_SUMMARY_API = 'reader-charges/summary';

	/**
	 * WooPayments transactions API path.
	 */
	private const TRANSACTIONS_API = 'transactions';

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

		$request_data['confirm']         = 'true';
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

		$request_data['confirm']              = 'true';
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
		$request_data['confirm']              = 'false';
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
	 * Retrieve a WooPayments payment method.
	 *
	 * @param string $payment_method_id Payment method ID.
	 * @return array<string,mixed>
	 */
	public function get_payment_method( string $payment_method_id ): array {
		return $this->request( array(), self::PAYMENT_METHODS_API . '/' . rawurlencode( $payment_method_id ), 'GET' );
	}

	/**
	 * Update a WooPayments payment method.
	 *
	 * @param string              $payment_method_id   Payment method ID.
	 * @param array<string,mixed> $payment_method_data Payment method update payload.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function update_payment_method( string $payment_method_id, array $payment_method_data = array() ): array {
		$this->validate_route_resource_id( $payment_method_id );

		return $this->request( $payment_method_data, self::PAYMENT_METHODS_API . '/' . $payment_method_id, 'POST' );
	}

	/**
	 * Retrieve the WooPayments timeline for an intent or order identifier.
	 *
	 * @param string $id Payment intent ID or order ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_timeline( string $id ): array {
		$this->validate_route_resource_id( $id );

		return $this->request( array(), self::TIMELINE_API . '/' . $id, 'GET' );
	}

	/**
	 * Send the current store setup snapshot.
	 *
	 * @param array<string,mixed> $store_setup Store setup snapshot.
	 * @return array<string,mixed>
	 */
	public function send_store_setup( array $store_setup ): array {
		return $this->request(
			array(
				'snapshot'  => $store_setup,
				'test_mode' => $this->account_service->is_test_mode_onboarding_enabled(),
			),
			self::STORE_SETUP_API,
			'POST',
			true,
			false,
			false
		);
	}

	/**
	 * Send WooPayments compatibility data.
	 *
	 * @param array<string,mixed> $compatibility_data Compatibility payload.
	 * @return array<string,mixed>
	 */
	public function update_compatibility_data( array $compatibility_data ): array {
		return $this->request(
			array(
				'compatibility_data' => $compatibility_data,
			),
			self::COMPATIBILITY_API,
			'POST'
		);
	}

	/**
	 * Track a WooPayments order creation or update event.
	 *
	 * @param array<string,mixed> $order_data Order payload.
	 * @param bool                $update     Whether this is an update event.
	 * @return array<string,mixed>
	 */
	public function track_order( array $order_data, bool $update = false ): array {
		return $this->request(
			array(
				'order_data' => $order_data,
				'update'     => $update,
			),
			self::TRACKING_API . '/order',
			'POST'
		);
	}

	/**
	 * Retrieve failed webhook events for replay.
	 *
	 * @return array<string,mixed>
	 */
	public function get_failed_webhook_events(): array {
		return $this->request( array(), self::WEBHOOK_FETCH_API, 'POST' );
	}

	/**
	 * Create a terminal connection token.
	 *
	 * @return array<string,mixed>
	 */
	public function create_terminal_connection_token(): array {
		return $this->request( array(), self::TERMINAL_CONNECTION_TOKENS_API, 'POST' );
	}

	/**
	 * Create a terminal payment intent.
	 *
	 * @param array<string,mixed> $request_data Terminal PaymentIntent payload.
	 * @return array<string,mixed>
	 */
	public function create_terminal_payment_intention( array $request_data ): array {
		return $this->request( $request_data, 'intentions', 'POST' );
	}

	/**
	 * Prepare a terminal payment intent for reader collection.
	 *
	 * @param string $intent_id Intent ID.
	 * @param int    $order_id  Order ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function prepare_terminal_payment( string $intent_id, int $order_id ): array {
		$this->validate_route_resource_id( $intent_id );

		return $this->request(
			array(
				'order_id' => $order_id,
			),
			'intentions/' . $intent_id . '/prepare_terminal_payment',
			'POST'
		);
	}

	/**
	 * Retrieve terminal readers.
	 *
	 * @return array<string,mixed>
	 */
	public function get_terminal_readers(): array {
		return $this->request( array(), self::TERMINAL_READERS_API, 'GET' );
	}

	/**
	 * Register a terminal reader.
	 *
	 * @param string                   $location          Terminal location ID.
	 * @param string                   $registration_code Reader registration code.
	 * @param string|null              $label             Optional reader label.
	 * @param array<string,mixed>|null $metadata          Optional reader metadata.
	 * @return array<string,mixed>
	 */
	public function register_terminal_reader( string $location, string $registration_code, ?string $label = null, ?array $metadata = null ): array {
		$params = array(
			'location'          => $location,
			'registration_code' => $registration_code,
		);

		if ( null !== $label ) {
			$params['label'] = $label;
		}

		if ( null !== $metadata ) {
			$params['metadata'] = $metadata;
		}

		return $this->request( $params, self::TERMINAL_READERS_API, 'POST' );
	}

	/**
	 * Retrieve reader charge summary data.
	 *
	 * @param string      $charge_date    Charge date in Y-m-d format.
	 * @param string|null $transaction_id Optional transaction ID.
	 * @return array<string,mixed>
	 */
	public function get_readers_charge_summary( string $charge_date, ?string $transaction_id = null ): array {
		$params = array(
			'charge_date' => $charge_date,
		);

		if ( null !== $transaction_id && '' !== $transaction_id ) {
			$params['transaction_id'] = $transaction_id;
		}

		return $this->request( $params, self::READERS_CHARGE_SUMMARY_API, 'GET' );
	}

	/**
	 * Retrieve terminal locations.
	 *
	 * @return array<string,mixed>
	 */
	public function get_terminal_locations(): array {
		return $this->request( array(), self::TERMINAL_LOCATIONS_API, 'GET' );
	}

	/**
	 * Retrieve one terminal location.
	 *
	 * @param string $location_id Location ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_terminal_location( string $location_id ): array {
		$this->validate_route_resource_id( $location_id );

		return $this->request( array(), self::TERMINAL_LOCATIONS_API . '/' . $location_id, 'GET' );
	}

	/**
	 * Create a terminal location.
	 *
	 * @param string              $display_name Location display name.
	 * @param array<string,mixed> $address      Location address.
	 * @param array<string,mixed> $metadata     Location metadata.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request is missing required address fields.
	 */
	public function create_terminal_location( string $display_name, array $address, array $metadata = array() ): array {
		if ( empty( $address['country'] ) || empty( $address['line1'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Address country and line1 are required.', 'woocommerce' ), 'wcpay_invalid_terminal_location_request', 400 );
		}

		return $this->request(
			array(
				'display_name' => $display_name,
				'address'      => $address,
				'metadata'     => $metadata,
			),
			self::TERMINAL_LOCATIONS_API,
			'POST'
		);
	}

	/**
	 * Update a terminal location.
	 *
	 * @param string                   $location_id  Location ID.
	 * @param string|null              $display_name Optional display name.
	 * @param array<string,mixed>|null $address      Optional address.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function update_terminal_location( string $location_id, ?string $display_name = null, ?array $address = null ): array {
		$this->validate_route_resource_id( $location_id );

		$params = array();
		if ( null !== $display_name ) {
			$params['display_name'] = $display_name;
		}

		if ( null !== $address ) {
			$params['address'] = $address;
		}

		return $this->request( $params, self::TERMINAL_LOCATIONS_API . '/' . $location_id, 'POST' );
	}

	/**
	 * Delete a terminal location.
	 *
	 * @param string $location_id Location ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function delete_terminal_location( string $location_id ): array {
		$this->validate_route_resource_id( $location_id );

		return $this->request( array(), self::TERMINAL_LOCATIONS_API . '/' . $location_id, 'DELETE' );
	}

	/**
	 * Retrieve a WooPayments charge.
	 *
	 * @param string $charge_id Charge ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_charge( string $charge_id ): array {
		$this->validate_route_resource_id( $charge_id );

		return $this->request( array(), 'charges/' . $charge_id, 'GET' );
	}

	/**
	 * Retrieve a WooPayments transaction.
	 *
	 * @param string $transaction_id Transaction ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_transaction( string $transaction_id ): array {
		$this->validate_route_resource_id( $transaction_id );

		return $this->request( array(), self::TRANSACTIONS_API . '/' . $transaction_id, 'GET' );
	}

	/**
	 * Retrieve recommended payment methods for onboarding.
	 *
	 * This route is intentionally not sent through the signed provider request path because recommendations are used
	 * before onboarding has connected a store/account.
	 *
	 * @param string $country_code Business location country code.
	 * @param string $locale       User locale.
	 * @return array<int,array<string,mixed>>
	 * @throws WooPaymentsApiException When the public request fails.
	 */
	public function get_recommended_payment_methods( string $country_code, string $locale = '' ): array {
		$request_args = Jetpack_Connection_Client::validate_args_for_wpcom_json_api_request(
			self::ENDPOINT_REST_BASE . '/' . self::RECOMMENDED_PAYMENT_METHODS,
			'2',
			array(),
			'wpcom'
		);
		$url          = add_query_arg(
			array(
				'country_code' => $country_code,
				'locale'       => $locale,
			),
			$request_args['url']
		);

		/**
		 * Filters WooPayments public API request headers.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,string> $headers Request headers.
		 */
		$headers = apply_filters(
			'wcpay_api_request_headers',
			array(
				'Content-type' => 'application/json; charset=utf-8',
			)
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers'    => $headers,
				'user-agent' => $this->get_user_agent(),
				'timeout'    => self::REQUEST_TIMEOUT_SECONDS,
				'sslverify'  => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException(
				sprintf(
					/* translators: %1$s: original error message. */
					__( 'Http request failed. Reason: %1$s', 'woocommerce' ),
					$response->get_error_message()
				),
				'wcpay_http_request_failed',
				500
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$decoded_body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $decoded_body ) ? $decoded_body : array();
	}

	/**
	 * Retrieve onboarding field data.
	 *
	 * @param string $locale User locale.
	 * @return array<string,mixed>
	 */
	public function get_onboarding_fields_data( string $locale = '' ): array {
		return $this->request(
			array(
				'locale' => $locale,
			),
			self::ONBOARDING_API . '/fields_data',
			'GET',
			false,
			true
		);
	}

	/**
	 * Initialize the non-embedded onboarding flow.
	 *
	 * @param bool                $live_account   Whether to create a live account.
	 * @param array<string,mixed> $site_data      Site payload.
	 * @param array<string,mixed> $user_data      User payload.
	 * @param array<string,mixed> $account_data   Account payload.
	 * @param string[]            $actioned_notes Actioned note names.
	 * @param string|null         $referral_code  Referral code.
	 * @return array<string,mixed>
	 */
	public function initialize_onboarding( bool $live_account, array $site_data = array(), array $user_data = array(), array $account_data = array(), array $actioned_notes = array(), ?string $referral_code = null ): array {
		$request_args                  = $this->get_filtered_onboarding_request_args(
			array(
				'site_data'           => $site_data,
				'user_data'           => $user_data,
				'account_data'        => $account_data,
				'actioned_notes'      => $actioned_notes,
				'create_live_account' => $live_account,
			)
		);
		$request_args['referral_code'] = $referral_code;

		return $this->request(
			$request_args,
			self::ONBOARDING_API . '/init',
			'POST',
			true,
			true
		);
	}

	/**
	 * Initialize the embedded KYC onboarding flow.
	 *
	 * @param bool                $live_account   Whether to create a live account.
	 * @param array<string,mixed> $site_data      Site payload.
	 * @param array<string,mixed> $user_data      User payload.
	 * @param array<string,mixed> $account_data   Account payload.
	 * @param string[]            $actioned_notes Actioned note names.
	 * @param string|null         $referral_code  Referral code.
	 * @return array<string,mixed>
	 */
	public function initialize_onboarding_embedded_kyc( bool $live_account, array $site_data = array(), array $user_data = array(), array $account_data = array(), array $actioned_notes = array(), ?string $referral_code = null ): array {
		$request_args                  = $this->get_filtered_onboarding_request_args(
			array(
				'site_data'           => $site_data,
				'user_data'           => $user_data,
				'account_data'        => $account_data,
				'actioned_notes'      => $actioned_notes,
				'create_live_account' => $live_account,
			)
		);
		$request_args['referral_code'] = $referral_code;

		return $this->request(
			$request_args,
			self::ONBOARDING_API . '/embedded',
			'POST',
			true,
			true
		);
	}

	/**
	 * Finalize the embedded KYC onboarding flow.
	 *
	 * @param string   $locale         User locale.
	 * @param string   $source         Onboarding source.
	 * @param string[] $actioned_notes Actioned note names.
	 * @return array<string,mixed>
	 */
	public function finalize_onboarding_embedded_kyc( string $locale, string $source, array $actioned_notes = array() ): array {
		return $this->request(
			array(
				'locale'         => $locale,
				'source'         => $source,
				'actioned_notes' => $actioned_notes,
			),
			self::ONBOARDING_API . '/embedded/finalize',
			'POST',
			true,
			true
		);
	}

	/**
	 * Delete the connected WooPayments account.
	 *
	 * @param bool $test_mode Whether to delete a test-mode account.
	 * @return array<string,mixed>
	 */
	public function delete_account( bool $test_mode = false ): array {
		return $this->request(
			array(
				'test_mode' => $test_mode,
			),
			self::ACCOUNTS_API . '/delete',
			'POST',
			true,
			true
		);
	}

	/**
	 * Apply WooPayments onboarding payload filters preserved from the plugin path.
	 *
	 * @param array<string,mixed> $request_args Onboarding request payload.
	 * @return array<string,mixed>
	 */
	private function get_filtered_onboarding_request_args( array $request_args ): array {
		/**
		 * Filters WooPayments onboarding request args.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,mixed> $request_args Onboarding request payload.
		 */
		$filtered_args = apply_filters( 'wc_payments_get_onboarding_data_args', $request_args );

		return is_array( $filtered_args ) ? $filtered_args : $request_args;
	}

	/**
	 * Validate a WooPayments route resource ID before path interpolation.
	 *
	 * @param string $id Resource ID.
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	private function validate_route_resource_id( string $id ): void {
		if ( ! preg_match( '/^\w+$/', $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Route param validation failed.', 'woocommerce' ), 'wcpay_route_validation_failure', 400 );
		}
	}

	/**
	 * Send a request through the provider transport.
	 *
	 * @param array<string,mixed> $params         Request params.
	 * @param string              $api            API path.
	 * @param string              $method         HTTP method.
	 * @param bool                $is_site_scoped Whether to include the WPCOM site ID in the API path.
	 * @param bool                $use_user_token Whether to sign with the connection-owner user token.
	 * @param bool                $blocking       Whether to block for the transport response.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	private function request( array $params, string $api, string $method, bool $is_site_scoped = true, bool $use_user_token = false, bool $blocking = true ): array {
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
		$site_id = $this->http_client->get_blog_id();
		$path    = $is_site_scoped
			? sprintf( '/sites/%d/wcpay/%s', (int) $site_id, $api )
			: sprintf( '/wcpay/%s', $api );
		$body    = null;

		if ( 'GET' === $method ) {
			$path .= '?' . http_build_query( $params );
		} else {
			$body = wp_json_encode( $params );

			if ( false === $body ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
				throw new WooPaymentsApiException( __( 'Unable to encode the WooPayments request body.', 'woocommerce' ), 'wcpay_client_unable_to_encode_json' );
			}
		}

		$response = $this->http_client->request(
			$method,
			$path,
			$headers,
			$body,
			self::REQUEST_TIMEOUT_SECONDS,
			$use_user_token,
			$blocking
		);

		if ( ! $blocking ) {
			return array();
		}

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
		$version = defined( 'WC_VERSION' ) ? preg_replace( '/-dev$/', '', WC_VERSION ) : 'unknown';
		$version = is_string( $version ) ? $version : 'unknown';

		return 'WooCommerce Payments/' . $version;
	}
}
