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

	private const FRAUD_OUTCOME_MAX_PAGE_SIZE = 100;

	private const FRAUD_OUTCOME_MAX_LOCAL_ROWS = 1000;

	private const FRAUD_OUTCOME_LOG_SOURCE = 'woopayments-fraud-outcomes';

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

	private const FRAUD_OUTCOME_QUERY_PARAMS = array(
		'page'              => true,
		'pagesize'          => true,
		'sort'              => true,
		'direction'         => true,
		'status'            => true,
		'search'            => true,
		'search_term'       => true,
		'additional_status' => true,
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
	 * WooPayments local order context service.
	 *
	 * @var WooPaymentsMoneyMovementOrderService
	 */
	private WooPaymentsMoneyMovementOrderService $order_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter         $arbiter       Runtime owner arbiter.
	 * @param WooPaymentsApiClient                 $api_client    Native WooPayments API client.
	 * @param WooPaymentsMoneyMovementOrderService $order_service Local order context service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client, WooPaymentsMoneyMovementOrderService $order_service ): void {
		$this->arbiter       = $arbiter;
		$this->api_client    = $api_client;
		$this->order_service = $order_service;
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

		register_rest_route( self::NAMESPACE, '/payments/transactions/fraud-outcomes', $this->get_readable_route( 'get_fraud_outcome_transactions' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/fraud-outcomes/summary', $this->get_readable_route( 'get_fraud_outcome_transactions_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/fraud-outcomes/search', $this->get_readable_route( 'get_fraud_outcome_transactions_search_autocomplete' ) );
		register_rest_route( self::NAMESPACE, '/payments/transactions/fraud-outcomes/download', $this->get_readable_route( 'get_fraud_outcome_transactions_export' ) );

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
			return new WP_REST_Response(
				$this->order_service->enrich_transactions_list_response(
					$this->api_client->get_transactions( $this->get_filtered_transactions_list_params( $request ) )
				)
			);
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
					$this->get_filtered_transactions_list_params( $request ),
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
			return new WP_REST_Response(
				$this->order_service->enrich_transaction_detail_response(
					$this->api_client->get_transaction( (string) $request->get_param( 'transaction_id' ) )
				)
			);
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
	 * Get fraud outcome transactions.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fraud_outcome_transactions( WP_REST_Request $request ) {
		try {
			$params       = $this->get_fraud_outcome_params( $request, 'wcpay_list_fraud_outcome_transactions_request' );
			$transactions = $this->get_formatted_fraud_outcome_transactions( $params );

			return new WP_REST_Response(
				array(
					'data' => $this->order_service->paginate_fraud_outcome_transactions( $transactions, $params ),
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get fraud outcome transactions summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fraud_outcome_transactions_summary( WP_REST_Request $request ) {
		try {
			$transactions = $this->get_formatted_fraud_outcome_transactions(
				$this->get_fraud_outcome_params( $request, 'wcpay_list_fraud_outcome_transactions_summary_request' )
			);

			return new WP_REST_Response( $this->order_service->summarize_fraud_outcome_transactions( $transactions ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get fraud outcome transactions search autocomplete results.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fraud_outcome_transactions_search_autocomplete( WP_REST_Request $request ) {
		try {
			$params       = $this->get_fraud_outcome_params( $request, 'wcpay_get_fraud_outcome_transactions_search_autocomplete_request' );
			$transactions = $this->get_formatted_fraud_outcome_transactions( $params );
			$search_term  = isset( $params['search_term'] ) && is_scalar( $params['search_term'] ) ? (string) $params['search_term'] : '';

			return new WP_REST_Response( $this->order_service->get_fraud_outcome_transactions_search_autocomplete( $transactions, $search_term ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get fraud outcome transactions export.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fraud_outcome_transactions_export( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				array(
					'data' => $this->get_formatted_fraud_outcome_transactions(
						$this->get_fraud_outcome_params( $request, 'wcpay_get_fraud_outcome_transactions_export_request' )
					),
				)
			);
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
					$this->get_filtered_transactions_list_params( $request ),
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

		return $this->order_service->map_transaction_search_params(
			is_array( $params ) ? array_intersect_key( $params, self::LIST_QUERY_PARAMS ) : $transactions_request->get_params()
		);
	}

	/**
	 * Get formatted fraud outcome transactions.
	 *
	 * @param array<string,mixed> $params Request params.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_formatted_fraud_outcome_transactions( array $params ): array {
		return $this->order_service->format_fraud_outcome_transactions(
			$this->limit_fraud_outcome_response_rows( $this->api_client->get_fraud_outcomes( $params ) ),
			$params
		);
	}

	/**
	 * Get fraud outcome request params.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param string          $hook    Legacy request hook.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_fraud_outcome_params( WP_REST_Request $request, string $hook ): array {
		WooPaymentsFraudOutcomeTransactionsListRequest::register_legacy_alias();
		$fraud_outcome_request = WooPaymentsFraudOutcomeTransactionsListRequest::from_rest_request( $request );

		/**
		 * Allows the WooPayments fraud outcome request to be modified before it is sent to the platform.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsFraudOutcomeTransactionsListRequest $fraud_outcome_request Native fraud outcome request.
		 */
		$filtered_request = apply_filters( $hook, $fraud_outcome_request );

		if ( ! is_object( $filtered_request ) || ! method_exists( $filtered_request, 'get_params' ) ) {
			$params = $fraud_outcome_request->get_params();
		} else {
			$params = $filtered_request->get_params();
			$params = is_array( $params ) ? $params : $fraud_outcome_request->get_params();
		}

		$params = array_filter(
			array_intersect_key( $params, self::FRAUD_OUTCOME_QUERY_PARAMS ),
			static function ( $value ): bool {
				return null !== $value;
			}
		);

		$params['page']      = isset( $params['page'] ) ? max( 1, (int) $params['page'] ) : 1;
		$params['pagesize']  = isset( $params['pagesize'] ) ? max( 1, min( self::FRAUD_OUTCOME_MAX_PAGE_SIZE, (int) $params['pagesize'] ) ) : 25;
		$params['sort']      = isset( $params['sort'] ) ? (string) $params['sort'] : 'date';
		$params['direction'] = isset( $params['direction'] ) ? (string) $params['direction'] : 'desc';

		if ( isset( $params['search'] ) && ! is_array( $params['search'] ) ) {
			$params['search'] = array( $params['search'] );
		}

		return $params;
	}

	/**
	 * Limit fraud outcome rows before local order enrichment.
	 *
	 * @param array<string|int,mixed> $response Platform response.
	 * @return array<string|int,mixed>
	 */
	private function limit_fraud_outcome_response_rows( array $response ): array {
		$is_wrapped = isset( $response['data'] ) && is_array( $response['data'] );
		$rows       = $is_wrapped ? $response['data'] : $response;

		if ( count( $rows ) <= self::FRAUD_OUTCOME_MAX_LOCAL_ROWS ) {
			return $response;
		}

		wc_get_logger()->warning(
			__( 'WooPayments fraud outcome response truncated before local order enrichment.', 'woocommerce' ),
			array(
				'source'   => self::FRAUD_OUTCOME_LOG_SOURCE,
				'rows'     => count( $rows ),
				'max_rows' => self::FRAUD_OUTCOME_MAX_LOCAL_ROWS,
			)
		);

		$rows = array_slice( $rows, 0, self::FRAUD_OUTCOME_MAX_LOCAL_ROWS );

		if ( $is_wrapped ) {
			$response['data'] = $rows;

			return $response;
		}

		return $rows;
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
