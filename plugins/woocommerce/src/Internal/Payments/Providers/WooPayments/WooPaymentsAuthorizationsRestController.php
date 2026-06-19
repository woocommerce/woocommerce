<?php
/**
 * WooPaymentsAuthorizationsRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\PaymentProcessingService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments uncaptured authorizations REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsAuthorizationsRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const LIST_QUERY_PARAMS = array(
		'page'                => true,
		'pagesize'            => true,
		'sort'                => true,
		'direction'           => true,
		'limit'               => true,
		'match'               => true,
		'order_id_is'         => true,
		'customer_email_is'   => true,
		'customer_country_is' => true,
		'risk_level_is'       => true,
		'source_is'           => true,
		'date_before'         => true,
		'date_after'          => true,
		'date_between'        => true,
		'from_date'           => true,
		'to_date'             => true,
		'search'              => true,
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
	 * Native payment processing service.
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
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter            Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client         Native WooPayments API client.
	 * @param PaymentProcessingService     $processing_service Native payment processing service.
	 * @param WooPaymentsProvider          $provider           WooPayments provider.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client, PaymentProcessingService $processing_service, WooPaymentsProvider $provider ): void {
		$this->arbiter            = $arbiter;
		$this->api_client         = $api_client;
		$this->processing_service = $processing_service;
		$this->provider           = $provider;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register WooPayments-compatible authorization routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/authorizations', $this->get_readable_route( 'get_authorizations' ) );
		register_rest_route( self::NAMESPACE, '/payments/authorizations/summary', $this->get_readable_route( 'get_authorizations_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/authorizations/(?P<payment_intent_id>\w+)', $this->get_readable_route( 'get_authorization' ) );
		register_rest_route( self::NAMESPACE, '/payments/orders/(?P<order_id>\w+)/capture_authorization', $this->get_creatable_route( 'capture_authorization' ) );
		register_rest_route( self::NAMESPACE, '/payments/orders/(?P<order_id>\w+)/cancel_authorization', $this->get_creatable_route( 'cancel_authorization' ) );
	}

	/**
	 * Check route permissions.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get authorizations.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_authorizations( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_authorizations( $this->get_filtered_list_params( $request ), false ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get authorizations summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_authorizations_summary( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_authorizations_summary( $this->get_filtered_list_params( $request ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get authorization detail.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_authorization( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_authorization( (string) $request->get_param( 'payment_intent_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Capture an authorization.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function capture_authorization( WP_REST_Request $request ) {
		return $this->handle_authorization_action( $request, 'capture' );
	}

	/**
	 * Cancel an authorization.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function cancel_authorization( WP_REST_Request $request ) {
		return $this->handle_authorization_action( $request, 'cancel' );
	}

	/**
	 * Handle capture/cancel authorization requests.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $action  Action name.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	private function handle_authorization_action( WP_REST_Request $request, string $action ) {
		$order_id          = absint( $request->get_param( 'order_id' ) );
		$payment_intent_id = (string) $request->get_param( 'payment_intent_id' );
		$order             = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return new WP_Error( 'wcpay_missing_order', __( 'Order not found', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( 0 < (float) $order->get_total_refunded() ) {
			return new WP_Error(
				'wcpay_refunded_order_uncapturable',
				'capture' === $action
					? __( 'Payment cannot be captured for partially or fully refunded orders.', 'woocommerce' )
					: __( 'Payment cannot be canceled for partially or fully refunded orders.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		if ( '' === $payment_intent_id || $payment_intent_id !== $this->get_order_intent_id( $order ) ) {
			return new WP_Error(
				'wcpay_intent_order_mismatch',
				'capture' === $action ? __( 'The payment cannot be captured', 'woocommerce' ) : __( 'The payment cannot be canceled', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$live_intent_validation = $this->validate_live_authorization_intent( $order, $payment_intent_id, $action );
		if ( is_wp_error( $live_intent_validation ) ) {
			return $live_intent_validation;
		}

		$this->add_fraud_outcome_manual_entry( $order, 'capture' === $action ? 'approve' : 'block' );

		$outcome = 'capture' === $action
			? $this->processing_service->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), $this->provider )
			: $this->processing_service->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), $this->provider );

		if ( ! $this->is_expected_action_outcome( $outcome, $action ) ) {
			return $this->authorization_action_error( $outcome, $action );
		}

		$order->save_meta_data();

		return new WP_REST_Response(
			array(
				'status' => 'capture' === $action ? 'succeeded' : 'canceled',
				'id'     => '' !== $outcome->get_provider_payment_id() ? $outcome->get_provider_payment_id() : $payment_intent_id,
			)
		);
	}

	/**
	 * Build a readable REST route definition.
	 *
	 * @param string $callback Callback method.
	 * @return array<string,mixed>
	 */
	private function get_readable_route( string $callback ): array {
		return array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, $callback ),
			'permission_callback' => array( $this, 'check_permission' ),
		);
	}

	/**
	 * Build a creatable REST route definition.
	 *
	 * @param string $callback Callback method.
	 * @return array<string,mixed>
	 */
	private function get_creatable_route( string $callback ): array {
		return array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, $callback ),
			'permission_callback' => array( $this, 'check_permission' ),
		);
	}

	/**
	 * Get authorizations list params after reference-compatible normalization.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_filtered_list_params( WP_REST_Request $request ): array {
		$params = array(
			'page'                => max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) ),
			'pagesize'            => max( 1, (int) ( $request->get_param( 'pagesize' ) ?? $request->get_param( 'per_page' ) ?? 25 ) ),
			'sort'                => $this->normalize_sort( (string) ( $request->get_param( 'sort' ) ?? $request->get_param( 'orderby' ) ?? 'created' ) ),
			'direction'           => $this->normalize_direction( (string) ( $request->get_param( 'direction' ) ?? $request->get_param( 'order' ) ?? 'desc' ) ),
			'limit'               => 100,
			'match'               => $request->get_param( 'match' ),
			'order_id_is'         => $request->get_param( 'order_id_is' ) ?? $request->get_param( 'order_id' ),
			'customer_email_is'   => $request->get_param( 'customer_email_is' ) ?? $request->get_param( 'customer_email' ),
			'customer_country_is' => $request->get_param( 'customer_country_is' ),
			'risk_level_is'       => $request->get_param( 'risk_level_is' ),
			'source_is'           => $request->get_param( 'source_is' ) ?? $request->get_param( 'payment_method_type' ),
			'date_before'         => $request->get_param( 'date_before' ),
			'date_after'          => $request->get_param( 'date_after' ),
			'date_between'        => $request->get_param( 'date_between' ),
			'search'              => $request->get_param( 'search' ),
		);

		$params = array_filter(
			array_intersect_key( $params, self::LIST_QUERY_PARAMS ),
			static function ( $value ): bool {
				return null !== $value && '' !== $value;
			}
		);

		return $this->apply_authorizations_request_filter( $params );
	}

	/**
	 * Apply the preserved authorizations request filter to REST list params.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @return array<string,mixed>
	 */
	private function apply_authorizations_request_filter( array $params ): array {
		WooPaymentsAuthorizationsListRequest::register_legacy_alias();
		$request = WooPaymentsAuthorizationsListRequest::from_params( $params );

		/**
		 * Allows the WooPayments authorizations list request to be modified before it is sent to the platform.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsAuthorizationsListRequest $request Native authorizations list request.
		 */
		$filtered_request = apply_filters( 'wcpay_list_authorizations_request', $request );

		if ( ! is_object( $filtered_request ) || ! method_exists( $filtered_request, 'get_params' ) ) {
			return $params;
		}

		$filtered_params = $filtered_request->get_params();

		return is_array( $filtered_params ) ? array_intersect_key( $filtered_params, self::LIST_QUERY_PARAMS ) : $params;
	}

	/**
	 * Normalize sort field.
	 *
	 * @param string $sort Sort field.
	 * @return string
	 */
	private function normalize_sort( string $sort ): string {
		return 'capture_by' === $sort ? 'created' : $sort;
	}

	/**
	 * Validate the live PaymentIntent still belongs to this order and remains capturable.
	 *
	 * @param WC_Order $order             Order.
	 * @param string   $payment_intent_id PaymentIntent ID.
	 * @param string   $action            Action name.
	 * @return true|WP_Error
	 */
	private function validate_live_authorization_intent( WC_Order $order, string $payment_intent_id, string $action ) {
		try {
			$intent = $this->api_client->get_payment_intention( $payment_intent_id );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}

		$metadata                 = isset( $intent['metadata'] ) && is_array( $intent['metadata'] ) ? $intent['metadata'] : array();
		$intent_meta_order_id_raw = $metadata['order_id'] ?? '';
		$intent_meta_order_id     = is_numeric( $intent_meta_order_id_raw ) ? absint( $intent_meta_order_id_raw ) : 0;

		if ( $intent_meta_order_id !== $order->get_id() ) {
			return new WP_Error(
				'wcpay_intent_order_mismatch',
				'capture' === $action ? __( 'The payment cannot be captured', 'woocommerce' ) : __( 'The payment cannot be canceled', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		$status = isset( $intent['status'] ) && is_scalar( $intent['status'] ) ? (string) $intent['status'] : '';
		if ( 'requires_capture' !== $status ) {
			return new WP_Error(
				'wcpay_payment_uncapturable',
				'capture' === $action ? __( 'The payment cannot be captured', 'woocommerce' ) : __( 'The payment cannot be canceled', 'woocommerce' ),
				array( 'status' => 409 )
			);
		}

		return true;
	}

	/**
	 * Normalize sort direction.
	 *
	 * @param string $direction Sort direction.
	 * @return string
	 */
	private function normalize_direction( string $direction ): string {
		return 'asc' === strtolower( $direction ) ? 'asc' : 'desc';
	}

	/**
	 * Get the PaymentIntent id stored on an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private function get_order_intent_id( WC_Order $order ): string {
		$intent_id = (string) $order->get_transaction_id();
		if ( '' === $intent_id ) {
			$intent_id = (string) $order->get_meta( '_intent_id', true );
		}

		return $intent_id;
	}

	/**
	 * Add a fraud outcome manual action entry to the order.
	 *
	 * @param WC_Order $order  Order.
	 * @param string   $action Action name.
	 */
	private function add_fraud_outcome_manual_entry( WC_Order $order, string $action ): void {
		$current_user = wp_get_current_user();
		$order->add_meta_data(
			'_wcpay_fraud_outcome_manual_entry',
			array(
				'type'     => 'fraud_outcome_manual_' . $action,
				'user'     => array(
					'id'       => $current_user->ID,
					'username' => $current_user->user_login,
				),
				'action'   => 'block' === $action ? 'blocked' : 'approved',
				'datetime' => time(),
			)
		);
	}

	/**
	 * Tell whether an outcome matches the requested action success state.
	 *
	 * @param PaymentOutcome $outcome Outcome.
	 * @param string         $action  Action name.
	 * @return bool
	 */
	private function is_expected_action_outcome( PaymentOutcome $outcome, string $action ): bool {
		return 'capture' === $action
			? PaymentOutcome::STATUS_COMPLETED === $outcome->get_status()
			: PaymentOutcome::STATUS_CANCELED === $outcome->get_status();
	}

	/**
	 * Convert a failed action outcome to a REST error.
	 *
	 * @param PaymentOutcome $outcome Outcome.
	 * @param string         $action  Action name.
	 * @return WP_Error
	 */
	private function authorization_action_error( PaymentOutcome $outcome, string $action ): WP_Error {
		$data          = $outcome->get_data();
		$error_code    = isset( $data['error_code'] ) && '' !== (string) $data['error_code'] ? (string) $data['error_code'] : ( 'capture' === $action ? 'wcpay_capture_error' : 'wcpay_cancel_error' );
		$error_message = isset( $data['error_message'] ) && '' !== (string) $data['error_message'] ? (string) $data['error_message'] : ( 'capture' === $action ? __( 'The payment capture failed to complete.', 'woocommerce' ) : __( 'The payment cancellation failed to complete.', 'woocommerce' ) );

		return new WP_Error(
			$error_code,
			$error_message,
			array(
				'status' => 502,
			)
		);
	}

	/**
	 * Convert a WooPayments API exception to a REST error.
	 *
	 * @param WooPaymentsApiException $exception Exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		$error_code = $exception->get_error_code();
		if ( '' === $error_code ) {
			$error_code = 'wcpay_api_error';
		}

		$http_code = $exception->get_http_code();
		if ( ! $http_code ) {
			$http_code = 400;
		}

		return new WP_Error(
			$error_code,
			$exception->getMessage(),
			array( 'status' => $http_code )
		);
	}
}
