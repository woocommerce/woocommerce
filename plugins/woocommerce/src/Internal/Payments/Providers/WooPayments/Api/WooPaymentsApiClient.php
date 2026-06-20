<?php
/**
 * WooPaymentsApiClient class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

use Automattic\Jetpack\Connection\Client as Jetpack_Connection_Client;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAuthorizationsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDocumentsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaginatedListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsReportingBalanceSummaryRequest;
use WP_Error;
use WP_REST_Request;

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
	 * Retry attempts after the initial request for idempotent transport failures.
	 */
	private const REQUEST_RETRIES_LIMIT = 3;

	/**
	 * Backoff between transport retries, in microseconds.
	 */
	private const REQUEST_RETRIES_BACKOFF_MICROSECONDS = 250;

	/**
	 * WooPayments V1 client capability version advertised to WPCOM.
	 */
	private const WCPAY_V1_CLIENT_CAPABILITY_VERSION = '10.8.0';

	/**
	 * Public WordPress.com API base preserved for compatibility filters.
	 */
	private const WPCOM_ENDPOINT_BASE = 'https://public-api.wordpress.com/wpcom/v2';

	/**
	 * WooPayments onboarding API path.
	 */
	private const ONBOARDING_API = 'onboarding';

	/**
	 * WooPayments accounts API path.
	 */
	private const ACCOUNTS_API = 'accounts';

	/**
	 * WooPayments account capabilities API path.
	 */
	private const CAPABILITIES_API = 'accounts/capabilities';

	/**
	 * WooPayments files API path.
	 */
	private const FILES_API = 'files';

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
	 * WooPayments reporting API path.
	 */
	/**
	 * WooPayments authorizations API path.
	 */
	private const AUTHORIZATIONS_API = 'authorizations';

	/**
	 * WooPayments disputes API path.
	 */
	private const DISPUTES_API = 'disputes';

	/**
	 * WooPayments fraud outcomes API path.
	 */
	private const FRAUD_OUTCOMES_API = 'fraud_outcomes';

	/**
	 * WooPayments fraud ruleset API path.
	 */
	private const FRAUD_RULESET_API = 'fraud_ruleset';

	/**
	 * WooPayments deposits API path. These endpoints back the merchant-facing payouts surfaces.
	 */
	private const DEPOSITS_API = 'deposits';

	/**
	 * WooPayments Documents API path.
	 */
	private const DOCUMENTS_API = 'documents';

	/**
	 * WooPayments VAT API path.
	 */
	private const VAT_API = 'vat';

	/**
	 * WooPayments Capital API path.
	 */
	private const CAPITAL_API = 'capital';

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
			'charge'          => $charge_id,
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

		return $this->request( $params, 'refunds', 'POST' );
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
	 * Retrieve visible WooPayments payment method promotions for the current store context.
	 *
	 * @param array<string,mixed> $store_context Store context parameters.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_pm_promotions( array $store_context ): array {
		$request = WooPaymentsGetPmPromotionsRequest::from_store_context( $store_context );

		return $this->request_with_legacy_request_filter(
			$request,
			'wcpay_get_pm_promotions_request'
		);
	}

	/**
	 * Activate a WooPayments payment method promotion.
	 *
	 * @param string $promotion_id Promotion ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the promotion ID or request is invalid.
	 */
	public function activate_pm_promotion( string $promotion_id ): array {
		$this->validate_pm_promotion_id( $promotion_id );
		$request = WooPaymentsActivatePmPromotionRequest::from_id( $promotion_id );

		return $this->request_with_legacy_request_filter(
			$request,
			'wcpay_activate_pm_promotion_request'
		);
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
	 * Retrieve a WooPayments dispute summary.
	 *
	 * @param string $dispute_id Dispute ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_dispute_summary( string $dispute_id ): array {
		$this->validate_route_resource_id( $dispute_id );

		return $this->request( array(), 'disputes/' . $dispute_id . '/summary', 'GET' );
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
	 * Retrieve WooPayments transactions.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_transactions( array $query = array() ): array {
		return $this->request( $query, self::TRANSACTIONS_API, 'GET' );
	}

	/**
	 * Retrieve WooPayments reporting balance summary.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_reporting_balance_summary( array $query = array() ): array {
		return $this->request_with_legacy_request_filter(
			WooPaymentsReportingBalanceSummaryRequest::from_params(
				array_intersect_key(
					$query,
					array(
						'date_start' => true,
						'date_end'   => true,
						'currency'   => true,
					)
				)
			),
			'wcpay_get_reporting_balance_summary_request'
		);
	}

	/**
	 * Retrieve WooPayments transactions summary.
	 *
	 * @param array<string,mixed> $filters    Summary filters.
	 * @param string|null         $deposit_id Optional payout/deposit ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_transactions_summary( array $filters = array(), ?string $deposit_id = null ): array {
		if ( null !== $deposit_id && '' !== $deposit_id ) {
			$filters['deposit_id'] = $deposit_id;
		}

		return $this->request( $filters, self::TRANSACTIONS_API . '/summary', 'GET' );
	}

	/**
	 * Retrieve the manual-capture authorization summary used by admin menu badges.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_authorizations_summary( array $query = array() ): array {
		return $this->request_with_legacy_filter(
			$query,
			self::AUTHORIZATIONS_API . '/summary',
			'GET',
			'wc_pay_get_authorizations_summary'
		);
	}

	/**
	 * Retrieve WooPayments uncaptured authorizations.
	 *
	 * @param array<string,mixed> $query                  Query params.
	 * @param bool                $preserve_legacy_filter Whether to apply the preserved legacy request hook.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_authorizations( array $query = array(), bool $preserve_legacy_filter = true ): array {
		if ( ! $preserve_legacy_filter ) {
			return $this->request( $query, self::AUTHORIZATIONS_API, 'GET' );
		}

		return $this->request_with_legacy_request_filter(
			WooPaymentsAuthorizationsListRequest::from_params( $query ),
			'wcpay_list_authorizations_request'
		);
	}

	/**
	 * Retrieve a WooPayments uncaptured authorization.
	 *
	 * @param string $payment_intent_id Payment intent ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_authorization( string $payment_intent_id ): array {
		$this->validate_route_resource_id( $payment_intent_id );

		return $this->request_with_legacy_filter(
			array(),
			self::AUTHORIZATIONS_API . '/' . $payment_intent_id,
			'GET',
			'wcpay_get_authorization_request'
		);
	}

	/**
	 * Initiate a WooPayments transactions export.
	 *
	 * @param array<string,mixed> $filters    Export filters.
	 * @param string              $user_email User email for the export.
	 * @param string|null         $deposit_id Optional payout/deposit ID.
	 * @param string|null         $locale     Site locale.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_transactions_export( array $filters = array(), string $user_email = '', ?string $deposit_id = null, ?string $locale = null ): array {
		if ( '' !== $user_email ) {
			$filters['user_email'] = $user_email;
		}

		if ( null !== $deposit_id && '' !== $deposit_id ) {
			$filters['deposit_id'] = $deposit_id;
		}

		if ( null !== $locale && '' !== $locale ) {
			$filters['locale'] = $locale;
		}

		return $this->request( $filters, self::TRANSACTIONS_API . '/download', 'POST' );
	}

	/**
	 * Retrieve a WooPayments transactions export URL.
	 *
	 * @param string $export_id Export ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_transactions_export_url( string $export_id ): array {
		$this->validate_route_export_id( $export_id );

		return $this->request( array(), self::TRANSACTIONS_API . '/download/' . $export_id, 'GET' );
	}

	/**
	 * Retrieve WooPayments transaction search autocomplete results.
	 *
	 * @param string $search_term Search term.
	 * @return array<int,array<string,string>>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_transactions_search_autocomplete( string $search_term ): array {
		$search_results = $this->request( array( 'search_term' => $search_term ), self::TRANSACTIONS_API . '/search', 'GET' );
		$results        = array_values(
			array_map(
				static function ( array $result ): array {
					$customer_name  = isset( $result['customer_name'] ) ? (string) $result['customer_name'] : '';
					$customer_email = isset( $result['customer_email'] ) ? (string) $result['customer_email'] : '';

					return array(
						'label' => trim( sprintf( '%s (%s)', $customer_name, $customer_email ) ),
					);
				},
				$search_results
			)
		);

		$order = wc_get_order( $search_term );
		if ( $order ) {
			$is_subscription = function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $order );
			array_unshift(
				$results,
				array(
					'label' => ( $is_subscription ? __( 'Subscription #', 'woocommerce' ) : __( 'Order #', 'woocommerce' ) ) . $search_term,
				)
			);
		}

		return $results;
	}

	/**
	 * Retrieve WooPayments fraud outcome transactions by status.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string|int,mixed>
	 * @throws WooPaymentsApiException When the fraud outcome status is invalid.
	 */
	public function get_fraud_outcomes( array $query = array() ): array {
		$status = isset( $query['status'] ) && is_scalar( $query['status'] ) ? (string) $query['status'] : '';
		unset( $query['status'] );

		if ( ! in_array( $status, array( 'allow', 'block', 'review' ), true ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Invalid fraud outcome status provided.', 'woocommerce' ), 'invalid_fraud_outcome_status', 400 );
		}

		return $this->request( $query, self::FRAUD_OUTCOMES_API . '/status/' . $status, 'GET' );
	}

	/**
	 * Get the latest fraud ruleset config for the connected account.
	 *
	 * @return array<string,mixed>
	 */
	public function get_latest_fraud_ruleset(): array {
		return $this->request( array(), self::FRAUD_RULESET_API, 'GET' );
	}

	/**
	 * Save fraud ruleset config for the connected account.
	 *
	 * @param array<int|string,mixed> $ruleset_config Ruleset config.
	 * @return array<string,mixed>
	 */
	public function save_fraud_ruleset( array $ruleset_config ): array {
		return $this->request(
			array(
				'ruleset_config' => $ruleset_config,
			),
			self::FRAUD_RULESET_API,
			'POST'
		);
	}

	/**
	 * Retrieve WooPayments disputes.
	 *
	 * @param array<string,mixed> $filters Query filters.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_disputes( array $filters = array() ): array {
		return $this->request( $filters, self::DISPUTES_API, 'GET' );
	}

	/**
	 * Retrieve WooPayments disputes summary.
	 *
	 * @param array<string,mixed> $filters Summary filters.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_disputes_summary( array $filters = array() ): array {
		return $this->request( array( '0' => $filters ), self::DISPUTES_API . '/summary', 'GET' );
	}

	/**
	 * Retrieve dispute status counts used by admin menu badges.
	 *
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_dispute_status_counts(): array {
		return $this->request_with_legacy_filter(
			array(),
			self::DISPUTES_API . '/status_counts',
			'GET',
			'wcpay_get_dispute_status_counts'
		);
	}

	/**
	 * Retrieve a WooPayments dispute.
	 *
	 * @param string $dispute_id Dispute ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_dispute( string $dispute_id ): array {
		$this->validate_route_resource_id( $dispute_id );

		return $this->request( array(), self::DISPUTES_API . '/' . $dispute_id, 'GET' );
	}

	/**
	 * Update a WooPayments dispute.
	 *
	 * @param string              $dispute_id Dispute ID.
	 * @param array<string,mixed> $evidence   Evidence payload.
	 * @param bool                $submit     Whether to submit the evidence.
	 * @param array<string,mixed> $metadata   Metadata payload.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function update_dispute( string $dispute_id, array $evidence, bool $submit, array $metadata ): array {
		$this->validate_route_resource_id( $dispute_id );

		$request = array(
			'evidence' => $evidence,
			'submit'   => $submit,
			'metadata' => $metadata,
		);

		$dispute_details = $this->get_dispute( $dispute_id );
		if ( isset( $dispute_details['reason'] ) && 'noncompliant' === $dispute_details['reason'] ) {
			$request['evidence']['enhanced_evidence'] = array_merge(
				isset( $request['evidence']['enhanced_evidence'] ) && is_array( $request['evidence']['enhanced_evidence'] ) ? $request['evidence']['enhanced_evidence'] : array(),
				array(
					'visa_compliance' => array(
						'fee_acknowledged' => 'true',
					),
				)
			);
		}

		return $this->request( $request, self::DISPUTES_API . '/' . $dispute_id, 'POST' );
	}

	/**
	 * Close a WooPayments dispute.
	 *
	 * @param string $dispute_id Dispute ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function close_dispute( string $dispute_id ): array {
		$this->validate_route_resource_id( $dispute_id );

		return $this->request( array(), self::DISPUTES_API . '/' . $dispute_id . '/close', 'POST' );
	}

	/**
	 * Initiate a WooPayments disputes export.
	 *
	 * @param array<string,mixed> $filters    Export filters.
	 * @param string              $user_email User email for the export.
	 * @param string|null         $locale     Site locale.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_disputes_export( array $filters = array(), string $user_email = '', ?string $locale = null ): array {
		if ( '' !== $user_email ) {
			$filters['user_email'] = $user_email;
		}

		if ( null !== $locale && '' !== $locale ) {
			$filters['locale'] = $locale;
		}

		return $this->request( $filters, self::DISPUTES_API . '/download', 'POST' );
	}

	/**
	 * Retrieve a WooPayments disputes export URL.
	 *
	 * @param string $export_id Export ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_disputes_export_url( string $export_id ): array {
		$this->validate_route_export_id( $export_id );

		return $this->request( array(), self::DISPUTES_API . '/download/' . $export_id, 'GET' );
	}

	/**
	 * Retrieve WooPayments payout overviews.
	 *
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_deposits_overview(): array {
		return $this->request( array(), self::DEPOSITS_API . '/overview-all', 'GET' );
	}

	/**
	 * Retrieve WooPayments payouts.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_deposits( array $query = array() ): array {
		return $this->request( $query, self::DEPOSITS_API, 'GET' );
	}

	/**
	 * Retrieve WooPayments payout summary.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_deposits_summary( array $query = array() ): array {
		return $this->request( $query, self::DEPOSITS_API . '/summary', 'GET' );
	}

	/**
	 * Retrieve a WooPayments payout detail.
	 *
	 * @param string $deposit_id Deposit ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_deposit( string $deposit_id ): array {
		$this->validate_route_resource_id( $deposit_id );

		return $this->request( array(), self::DEPOSITS_API . '/' . $deposit_id, 'GET' );
	}

	/**
	 * Initiate a WooPayments payout export.
	 *
	 * @param array<string,mixed> $filters    Export filters.
	 * @param string              $user_email User email for the export.
	 * @param string|null         $locale     Site locale.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_deposits_export( array $filters = array(), string $user_email = '', ?string $locale = null ): array {
		if ( '' !== $user_email ) {
			$filters['user_email'] = $user_email;
		}

		if ( null !== $locale && '' !== $locale ) {
			$filters['locale'] = $locale;
		}

		return $this->request( $filters, self::DEPOSITS_API . '/download', 'POST' );
	}

	/**
	 * Retrieve a WooPayments payout export URL.
	 *
	 * @param string $export_id Export ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_payouts_export_url( string $export_id ): array {
		$this->validate_route_export_id( $export_id );

		return $this->request( array(), self::DEPOSITS_API . '/download/' . $export_id, 'GET' );
	}

	/**
	 * Trigger a WooPayments manual payout.
	 *
	 * @param string $type     Payout type.
	 * @param string $currency Payout currency.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function manual_deposit( string $type, string $currency ): array {
		return $this->request(
			array(
				'type'     => $type,
				'currency' => $currency,
			),
			self::DEPOSITS_API,
			'POST'
		);
	}

	/**
	 * Retrieve WooPayments documents.
	 *
	 * @param array<string,mixed> $query Query params.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_documents( array $query = array() ): array {
		$query = array_intersect_key(
			$query,
			array(
				'page'         => true,
				'pagesize'     => true,
				'sort'         => true,
				'direction'    => true,
				'limit'        => true,
				'match'        => true,
				'date_before'  => true,
				'date_after'   => true,
				'date_between' => true,
				'type_is'      => true,
				'type_is_not'  => true,
			)
		);

		return $this->request_with_legacy_request_filter(
			WooPaymentsDocumentsListRequest::from_params( $query ),
			'wcpay_list_documents_request'
		);
	}

	/**
	 * Retrieve WooPayments documents summary.
	 *
	 * @param array<string,mixed> $query Query filters.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_documents_summary( array $query = array() ): array {
		$query = array_intersect_key(
			$query,
			array(
				'match'        => true,
				'date_before'  => true,
				'date_after'   => true,
				'date_between' => true,
				'type_is'      => true,
				'type_is_not'  => true,
			)
		);

		return $this->request( $query, self::DOCUMENTS_API . '/summary', 'GET' );
	}

	/**
	 * Retrieve a WooPayments document raw response.
	 *
	 * @param string $document_id Document ID.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	public function get_document( string $document_id ): array {
		$this->validate_document_id( $document_id );

		return $this->request( array(), self::DOCUMENTS_API . '/' . $document_id, 'GET', true, false, true, true, true );
	}

	/**
	 * Validate a VAT number.
	 *
	 * @param string $vat_number VAT number.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function validate_vat( string $vat_number ): array {
		return $this->request_with_legacy_filter(
			array(),
			self::VAT_API . '/' . $vat_number,
			'GET',
			'wcpay_validate_vat_request'
		);
	}

	/**
	 * Save VAT details.
	 *
	 * @param string|null $vat_number VAT number.
	 * @param string      $name       Name.
	 * @param string      $address    Address.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function save_vat_details( ?string $vat_number, string $name, string $address ): array {
		$params = array(
			'name'    => $name,
			'address' => $address,
		);

		if ( null !== $vat_number ) {
			$params['vat_number'] = $vat_number;
		}

		$response = $this->request( $params, self::VAT_API, 'POST' );
		$this->account_service->refresh_account_data();

		return $response;
	}

	/**
	 * Retrieve the WooPayments Capital active loan summary.
	 *
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_capital_active_loan_summary(): array {
		return $this->request_with_legacy_filter(
			array(),
			self::CAPITAL_API . '/active_loan_summary',
			'GET',
			'wcpay_get_active_loan_summary_request'
		);
	}

	/**
	 * Retrieve WooPayments Capital loans.
	 *
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_capital_loans(): array {
		return $this->request_with_legacy_filter(
			array(),
			self::CAPITAL_API . '/loans',
			'GET',
			'wcpay_get_loans_request'
		);
	}

	/**
	 * Create a WooPayments embedded account session.
	 *
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function create_embedded_account_session(): array {
		return $this->request( array(), self::ACCOUNTS_API . '/embedded/session', 'POST', true, true );
	}

	/**
	 * Create a WooPayments Capital account link.
	 *
	 * @param string $return_url  URL to return to after viewing the offer.
	 * @param string $refresh_url URL to use when the link expires or is invalid.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function create_capital_link( string $return_url, string $refresh_url ): array {
		$request = WooPaymentsGetAccountCapitalLinkRequest::from_urls( $return_url, $refresh_url );

		return $this->request_with_legacy_request_filter(
			$request,
			'wcpay_get_account_capital_link',
			true
		);
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
	 * @param string              $return_url     URL to redirect to at the end of the flow.
	 * @param array<string,mixed> $site_data      Site payload.
	 * @param array<string,mixed> $user_data      User payload.
	 * @param array<string,mixed> $account_data   Account payload.
	 * @param string[]            $actioned_notes Actioned note names.
	 * @param string|null         $referral_code  Referral code.
	 * @return array<string,mixed>
	 */
	public function initialize_onboarding( bool $live_account, string $return_url, array $site_data = array(), array $user_data = array(), array $account_data = array(), array $actioned_notes = array(), ?string $referral_code = null ): array {
		$request_args                  = $this->get_filtered_onboarding_request_args(
			array(
				'return_url'          => $return_url,
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
	 * Update connected account settings.
	 *
	 * @param array<string,mixed> $account_settings Account settings accepted by the platform accounts endpoint.
	 * @return array<string,mixed>
	 */
	public function update_account( array $account_settings ): array {
		if ( empty( $account_settings ) ) {
			return array();
		}

		return $this->request( $account_settings, self::ACCOUNTS_API, 'POST', true, true );
	}

	/**
	 * Request or unrequest a connected account capability.
	 *
	 * @param string $capability_id Capability ID.
	 * @param bool   $requested Whether the capability should be requested.
	 * @return array<string,mixed>
	 */
	public function request_capability( string $capability_id, bool $requested ): array {
		return $this->request(
			array(
				'capability_id' => $capability_id,
				'requested'     => $requested,
			),
			self::CAPABILITIES_API,
			'POST',
			true,
			true
		);
	}

	/**
	 * Retrieve the connected WooPayments account.
	 *
	 * @param string $woocommerce_store_id WooCommerce store ID.
	 * @return array<string,mixed>
	 */
	public function get_account( string $woocommerce_store_id = '' ): array {
		$params = array(
			'test_mode' => $this->account_service->is_test_mode_onboarding_enabled(),
		);

		if ( '' !== $woocommerce_store_id ) {
			$params['woocommerce_store_id'] = $woocommerce_store_id;
		}

		return $this->request(
			$params,
			self::ACCOUNTS_API,
			'GET'
		);
	}

	/**
	 * Upload a file through the native files endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the uploaded file is invalid or the request fails.
	 */
	public function upload_file( WP_REST_Request $request ): array {
		$file_params = $request->get_file_params();
		$file        = is_array( $file_params['file'] ?? null ) ? $file_params['file'] : array();

		if ( empty( $file ) || ! empty( $file['error'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Max file size exceeded.', 'woocommerce' ), 'wcpay_evidence_file_max_size', 400 );
		}

		$file_contents = file_get_contents( (string) ( $file['tmp_name'] ?? '' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local uploaded temp file.
		if ( false === $file_contents ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Unable to read the uploaded file.', 'woocommerce' ), 'wcpay_evidence_file_read_error', 400 );
		}

		try {
			return $this->request(
				array(
					'file'       => base64_encode( $file_contents ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoding uploaded file contents for the provider Files API.
					'file_name'  => isset( $file['name'] ) && is_scalar( $file['name'] ) ? (string) $file['name'] : '',
					'file_type'  => isset( $file['type'] ) && is_scalar( $file['type'] ) ? (string) $file['type'] : '',
					'purpose'    => (string) $request->get_param( 'purpose' ),
					'as_account' => (bool) $request->get_param( 'as_account' ),
				),
				self::FILES_API,
				'POST'
			);
		} catch ( WooPaymentsApiException $exception ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( $exception->getMessage(), 'wcpay_evidence_file_upload_error', $exception->get_http_code() );
		}
	}

	/**
	 * Retrieve provider file details.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether to fetch the file as the connected account.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_file( string $file_id, bool $as_account = true ): array {
		$this->validate_route_resource_id( $file_id );

		return $this->request(
			array( 'as_account' => $as_account ),
			self::FILES_API . '/' . $file_id,
			'GET'
		);
	}

	/**
	 * Retrieve provider file contents.
	 *
	 * @param string $file_id    Provider file ID.
	 * @param bool   $as_account Whether to fetch the file as the connected account.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	public function get_file_contents( string $file_id, bool $as_account = true ): array {
		$this->validate_route_resource_id( $file_id );

		return $this->request(
			array( 'as_account' => $as_account ),
			self::FILES_API . '/' . $file_id . '/contents',
			'GET'
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
	 * Validate a WooPayments export ID before path interpolation.
	 *
	 * Export routes accept opaque IDs from the platform and only exclude path separators and URL-encoded path bytes.
	 *
	 * @param string $id Export ID.
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	private function validate_route_export_id( string $id ): void {
		if ( '' === $id || ! preg_match( '/^[^\/\\\\%]+$/', $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Route param validation failed.', 'woocommerce' ), 'wcpay_route_validation_failure', 400 );
		}
	}

	/**
	 * Validate a WooPayments document ID before path interpolation.
	 *
	 * @param string $id Document ID.
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	private function validate_document_id( string $id ): void {
		if ( '' === $id || ! preg_match( '/^[\w-]+$/', $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Route param validation failed.', 'woocommerce' ), 'wcpay_route_validation_failure', 400 );
		}
	}

	/**
	 * Validate a payment method promotion ID before path interpolation.
	 *
	 * @param string $id Promotion ID.
	 * @throws WooPaymentsApiException When the route parameter is invalid.
	 */
	private function validate_pm_promotion_id( string $id ): void {
		if ( '' === $id || ! preg_match( '/^[A-Za-z0-9_-]+$/', $id ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Route param validation failed.', 'woocommerce' ), 'wcpay_route_validation_failure', 400 );
		}
	}

	/**
	 * Send a request through the provider transport.
	 *
	 * @param array<int|string,mixed> $params        Request params.
	 * @param string                  $api           API path.
	 * @param string                  $method        HTTP method.
	 * @param bool                    $is_site_scoped Whether to include the WPCOM site ID in the API path.
	 * @param bool                    $use_user_token Whether to sign with the connection-owner user token.
	 * @param bool                    $blocking      Whether to block for the transport response.
	 * @param bool                    $include_test_mode_param Whether to add test mode to request params.
	 * @param bool                    $return_raw_response Whether to return the raw transport response.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	private function request( array $params, string $api, string $method, bool $is_site_scoped = true, bool $use_user_token = false, bool $blocking = true, bool $include_test_mode_param = true, bool $return_raw_response = false ): array {
		if ( ! $this->is_available() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
			throw new WooPaymentsApiException( __( 'Site is not connected to WordPress.com.', 'woocommerce' ), 'wcpay_wpcom_not_connected', 409 );
		}

		if ( $include_test_mode_param ) {
			$params = wp_parse_args(
				$params,
				array(
					'test_mode' => $this->account_service->is_test_mode_enabled(),
				)
			);
		}

		/**
		 * Filters the WooPayments native request parameters before transport dispatch.
		 *
		 * @since 11.0.0
		 *
		 * @param array<int|string,mixed> $params Request parameters.
		 * @param string                  $api    API path.
		 * @param string                  $method HTTP method.
		 */
		$params = apply_filters( 'wcpay_api_request_params', $params, $api, $method );

		$headers = array(
			'Content-Type' => 'application/json; charset=utf-8',
			'User-Agent'   => $this->get_user_agent(),
		);

		$caller_idempotency_key = '';
		if ( isset( $params['idempotency_key'] ) ) {
			$caller_idempotency_key = (string) $params['idempotency_key'];
			unset( $params['idempotency_key'] );
		}

		if ( ! in_array( $method, array( 'GET', 'DELETE' ), true ) ) {
			$headers['Idempotency-Key'] = '' !== $caller_idempotency_key ? $caller_idempotency_key : wp_generate_uuid4();
		}

		/**
		 * Filters the WooPayments native request headers before transport dispatch.
		 *
		 * @since 11.0.0
		 *
		 * @param array<string,string> $headers Request headers.
		 */
		$headers    = apply_filters( 'wcpay_api_request_headers', $headers );
		$site_id    = $this->http_client->get_blog_id();
		$path       = $is_site_scoped
			? sprintf( '/sites/%d/wcpay/%s', (int) $site_id, $api )
			: sprintf( '/wcpay/%s', $api );
		$body       = null;
		$filter_url = $this->get_filter_request_url( $api, $is_site_scoped );

		if ( 'GET' === $method ) {
			$query_string = http_build_query( $params );
			$path        .= '?' . $query_string;
			$filter_url  .= '?' . $query_string;
		} else {
			$body = wp_json_encode( $params );

			if ( false === $body ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message is internal application state, not HTML output.
				throw new WooPaymentsApiException( __( 'Unable to encode the WooPayments request body.', 'woocommerce' ), 'wcpay_client_unable_to_encode_json' );
			}
		}

		$stop_trying_at = time() + self::REQUEST_TIMEOUT_SECONDS;
		$retries        = 0;
		$retries_limit  = $blocking && array_key_exists( 'Idempotency-Key', $headers ) ? self::REQUEST_RETRIES_LIMIT : 0;

		while ( true ) {
			$headers['X-Request-Initiated'] = (string) microtime( true );

			$response = $this->http_client->request(
				$method,
				$path,
				$headers,
				$body,
				self::REQUEST_TIMEOUT_SECONDS,
				$use_user_token,
				$blocking
			);

			/**
			 * Filters the WooPayments native response after transport dispatch.
			 *
			 * @since 11.0.0
			 *
			 * @param mixed  $response Transport response.
			 * @param string $method   HTTP method.
			 * @param string $url      Public WordPress.com API URL.
			 * @param string $api      WooPayments API path.
			 */
			$response      = apply_filters( 'wcpay_api_request_response', $response, $method, $filter_url, $api );
			$response_code = $response instanceof WP_Error ? 0 : (int) wp_remote_retrieve_response_code( $response );

			if ( ! $this->should_retry_request_response( $response, $response_code, $retries, $retries_limit, $stop_trying_at ) ) {
				break;
			}

			usleep( self::REQUEST_RETRIES_BACKOFF_MICROSECONDS * ( 2 ** $retries ) );
			++$retries;
		}

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

		if ( $return_raw_response && 400 > $response_code ) {
			return is_array( $response ) ? $response : array();
		}

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
	 * Send a request after applying a legacy WooPayments request-object filter.
	 *
	 * @param array<int|string,mixed> $params Request params.
	 * @param string                  $api    API path.
	 * @param string                  $method HTTP method.
	 * @param string                  $hook   Legacy WooPayments request filter hook.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	private function request_with_legacy_filter( array $params, string $api, string $method, string $hook ): array {
		WooPaymentsApiRequest::register_legacy_aliases();

		$request = WooPaymentsApiRequest::create( $params, $api, $method );

		return $this->request_with_legacy_request_filter( $request, $hook );
	}

	/**
	 * Send a concrete request after applying a legacy WooPayments request-object filter.
	 *
	 * @param WooPaymentsPaginatedListRequest $request Native request compatibility object.
	 * @param string                          $hook    Legacy WooPayments request filter hook.
	 * @param bool                            $include_test_mode_in_query Whether to add test mode to the API path query.
	 * @return array<string,mixed>
	 * @throws WooPaymentsApiException When the request fails.
	 */
	private function request_with_legacy_request_filter( WooPaymentsPaginatedListRequest $request, string $hook, bool $include_test_mode_in_query = false ): array {
		if ( $request instanceof WooPaymentsGetPmPromotionsRequest ) {
			WooPaymentsGetPmPromotionsRequest::register_legacy_aliases();
		} elseif ( $request instanceof WooPaymentsActivatePmPromotionRequest ) {
			WooPaymentsActivatePmPromotionRequest::register_legacy_aliases();
		} elseif ( $request instanceof WooPaymentsGetAccountCapitalLinkRequest ) {
			WooPaymentsGetAccountCapitalLinkRequest::register_legacy_aliases();
		} elseif ( $request instanceof WooPaymentsAuthorizationsListRequest ) {
			WooPaymentsAuthorizationsListRequest::register_legacy_alias();
		} elseif ( $request instanceof WooPaymentsDocumentsListRequest ) {
			WooPaymentsDocumentsListRequest::register_legacy_alias();
		} elseif ( $request instanceof WooPaymentsReportingBalanceSummaryRequest ) {
			WooPaymentsReportingBalanceSummaryRequest::register_legacy_alias();
		} else {
			WooPaymentsApiRequest::register_legacy_aliases();
		}

		/**
		 * Filters a WooPayments API request before native transport dispatch.
		 *
		 * This preserves legacy WooPayments request-object filters for provider APIs that had public request hooks before moving into core.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsPaginatedListRequest $request Native request compatibility object, aliased to legacy WooPayments request classes when the extension is absent.
		 */
		$filtered_request = apply_filters( $hook, $request );

		if ( ! $filtered_request instanceof WooPaymentsPaginatedListRequest ) {
			// translators: %s: WooPayments API request filter hook name.
			$message = sprintf( __( 'Invalid WooPayments request returned by %s.', 'woocommerce' ), $hook );
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages are not HTML output.
			throw new WooPaymentsApiException( $message, 'wcpay_invalid_filtered_request', 500 );
		}

		$filtered_params = $filtered_request->get_params();
		$filtered_api    = $filtered_request->get_api();
		$filtered_method = $filtered_request->get_method();

		if ( $include_test_mode_in_query ) {
			$filtered_api = add_query_arg(
				array(
					'test_mode' => $this->account_service->is_test_mode_enabled() ? '1' : '0',
				),
				$filtered_api
			);
		}

		return $this->request(
			$filtered_params,
			$filtered_api,
			$filtered_method,
			$filtered_request->is_site_specific(),
			$filtered_request->should_use_user_token(),
			true,
			! $include_test_mode_in_query
		);
	}

	/**
	 * Get the compatibility URL exposed to WooPayments API response filters.
	 *
	 * @param string $api            WooPayments API path.
	 * @param bool   $is_site_scoped Whether the request is site-scoped.
	 * @return string
	 */
	private function get_filter_request_url( string $api, bool $is_site_scoped ): string {
		$url = self::WPCOM_ENDPOINT_BASE;
		if ( $is_site_scoped ) {
			$url .= '/sites/%s';
		}

		return $url . '/' . self::ENDPOINT_REST_BASE . '/' . ltrim( $api, '/' );
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
	 * Tell whether the transport response should be retried.
	 *
	 * @param mixed $response       Transport response.
	 * @param int   $response_code  HTTP status code.
	 * @param int   $retries        Retry attempts already made.
	 * @param int   $retries_limit  Retry attempts limit.
	 * @param int   $stop_trying_at Epoch timestamp at which retrying must stop.
	 * @return bool
	 */
	private function should_retry_request_response( $response, int $response_code, int $retries, int $retries_limit, int $stop_trying_at ): bool {
		if ( $response_code || time() >= $stop_trying_at || $retries_limit === $retries ) {
			return false;
		}

		return $response instanceof WP_Error && $this->is_retryable_transport_error( $response );
	}

	/**
	 * Tell whether a transport error may recover on retry.
	 *
	 * @param WP_Error $error Transport error.
	 * @return bool
	 */
	private function is_retryable_transport_error( WP_Error $error ): bool {
		return in_array(
			(string) $error->get_error_code(),
			array(
				'http_request_failed',
				'http_request_not_executed',
			),
			true
		);
	}

	/**
	 * Build the provider user-agent string.
	 *
	 * @return string
	 */
	private function get_user_agent(): string {
		return 'WooCommerce Payments/' . self::WCPAY_V1_CLIENT_CAPABILITY_VERSION;
	}
}
