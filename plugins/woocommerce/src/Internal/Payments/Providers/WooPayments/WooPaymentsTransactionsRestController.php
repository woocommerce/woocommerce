<?php
/**
 * WooPaymentsTransactionsRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments transactions REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsTransactionsRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const LIST_QUERY_PARAMS = array(
		'page'                     => true,
		'pagesize'                 => true,
		'sort'                     => true,
		'direction'                => true,
		'match'                    => true,
		'date_before'              => true,
		'date_after'               => true,
		'date_between'             => true,
		'type_is'                  => true,
		'type_is_not'              => true,
		'type_is_in'               => true,
		'source_device_is'         => true,
		'source_device_is_not'     => true,
		'channel_is'               => true,
		'channel_is_not'           => true,
		'customer_country_is'      => true,
		'customer_country_is_not'  => true,
		'risk_level_is'            => true,
		'risk_level_is_not'        => true,
		'store_currency_is'        => true,
		'customer_currency_is'     => true,
		'customer_currency_is_not' => true,
		'source_is'                => true,
		'source_is_not'            => true,
		'loan_id_is'               => true,
		'search'                   => true,
		'deposit_id'               => true,
		'limit'                    => true,
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
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter    Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client Native WooPayments API client.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client ): void {
		$this->arbiter    = $arbiter;
		$this->api_client = $api_client;
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
	 * Register WooPayments-compatible transactions routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/transactions', $this->get_readable_route( 'get_transactions' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/download', $this->get_creatable_route( 'get_transactions_export' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/download/(?P<export_id>[^/\\\\%]+)', $this->get_readable_route( 'get_export_url' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/summary', $this->get_readable_route( 'get_transactions_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/search', $this->get_readable_route( 'get_transactions_search_autocomplete' ) );

		foreach ( array( '/payments/transactions/fraud-outcomes', '/payments/transactions/fraud-outcomes/summary', '/payments/transactions/fraud-outcomes/search', '/payments/transactions/fraud-outcomes/download' ) as $route ) {
			register_rest_route( self::NAMESPACE, $route, $this->get_readable_route( 'fraud_outcomes_unavailable' ) );
		}

		register_rest_route( self::NAMESPACE, '/payments/transactions/(?P<transaction_id>\w+)', $this->get_readable_route( 'get_transaction' ) );
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
	 * Get transactions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_transactions( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_transactions( $this->get_filtered_transactions_list_params( $request ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get transactions summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_transactions_summary( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->get_transactions_summary(
					$this->get_allowed_params( $request, self::LIST_QUERY_PARAMS ),
					$this->get_optional_string_param( $request, 'deposit_id' )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get transaction detail.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_transaction( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_transaction( (string) $request->get_param( 'transaction_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Search transactions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_transactions_search_autocomplete( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_transactions_search_autocomplete( (string) $request->get_param( 'search_term' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Initiate transactions export.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_transactions_export( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->get_transactions_export(
					$this->get_allowed_params( $request, self::LIST_QUERY_PARAMS ),
					(string) $request->get_param( 'user_email' ),
					$this->get_optional_string_param( $request, 'deposit_id' ),
					$this->get_optional_string_param( $request, 'locale' )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get export URL.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_export_url( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_transactions_export_url( (string) $request->get_param( 'export_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Explicitly fail closed for fraud-outcome routes not yet implemented natively.
	 *
	 * @return WP_Error
	 */
	public function fraud_outcomes_unavailable(): WP_Error {
		return new WP_Error(
			'wcpay_native_fraud_outcomes_unavailable',
			__( 'Fraud outcome transaction routes are not available in the native WooPayments admin surface yet.', 'woocommerce' ),
			array( 'status' => 501 )
		);
	}

	/**
	 * Get a readable route definition.
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
	 * Get a creatable route definition.
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
	 * Get transactions list params after the preserved legacy request filter has run.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_filtered_transactions_list_params( WP_REST_Request $request ): array {
		WooPaymentsTransactionsListRequest::register_legacy_alias();
		$transactions_request = WooPaymentsTransactionsListRequest::from_rest_request( $request );

		/**
		 * Allows the WooPayments transactions list request to be modified before it is sent to the platform.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsTransactionsListRequest $transactions_request Native transactions list request.
		 */
		$filtered_request = apply_filters( 'wcpay_list_transactions_request', $transactions_request );

		if ( ! is_object( $filtered_request ) || ! method_exists( $filtered_request, 'get_params' ) ) {
			return $transactions_request->get_params();
		}

		$params = $filtered_request->get_params();

		return is_array( $params ) ? array_intersect_key( $params, self::LIST_QUERY_PARAMS ) : $transactions_request->get_params();
	}

	/**
	 * Extract allowed params from the request without normalizing platform-facing names.
	 *
	 * @param WP_REST_Request    $request Request.
	 * @param array<string,bool> $allowed Allowed param map.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_allowed_params( WP_REST_Request $request, array $allowed ): array {
		return array_filter(
			array_intersect_key( $request->get_params(), $allowed ),
			static function ( $value ): bool {
				return null !== $value;
			}
		);
	}

	/**
	 * Get an optional string param.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $name    Param name.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return string|null
	 */
	private function get_optional_string_param( WP_REST_Request $request, string $name ): ?string {
		$value = $request->get_param( $name );

		return null === $value ? null : (string) $value;
	}

	/**
	 * Convert an API exception to a REST error.
	 *
	 * @param WooPaymentsApiException $exception API exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		return new WP_Error(
			'' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_api_error',
			$exception->getMessage(),
			array( 'status' => $exception->get_http_code() > 0 ? $exception->get_http_code() : 500 )
		);
	}
}
