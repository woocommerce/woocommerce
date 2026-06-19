<?php
/**
 * WooPaymentsReportsRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use DateTime;
use DateTimeZone;
use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments Reports REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsReportsRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const DEFAULT_FEE_BEARING_TYPES = array(
		'charge',
		'payment',
		'payment_failure_refund',
		'payment_refund',
		'refund',
		'refund_failure',
		'dispute',
		'dispute_reversal',
		'fee_refund',
		'network_costs',
	);

	private const FEES_TRANSACTION_PARAMS = array(
		'page'              => true,
		'pagesize'          => true,
		'sort'              => true,
		'direction'         => true,
		'limit'             => true,
		'source_is'         => true,
		'type_is_in'        => true,
		'order_id_is'       => true,
		'deposit_id'        => true,
		'date_before'       => true,
		'date_after'        => true,
		'date_between'      => true,
		'match'             => true,
		'search'            => true,
		'user_timezone'     => true,
		'transaction_id_is' => true,
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
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

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
	 * @param NativePaymentsRuntimeArbiter         $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsApiClient                 $api_client      Native WooPayments API client.
	 * @param WooPaymentsAccountService            $account_service WooPayments account service.
	 * @param WooPaymentsMoneyMovementOrderService $order_service  Local order context service.
	 */
	final public function init(
		NativePaymentsRuntimeArbiter $arbiter,
		WooPaymentsApiClient $api_client,
		WooPaymentsAccountService $account_service,
		WooPaymentsMoneyMovementOrderService $order_service
	): void {
		$this->arbiter         = $arbiter;
		$this->api_client      = $api_client;
		$this->account_service = $account_service;
		$this->order_service   = $order_service;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() || ! $this->account_service->is_reports_enabled() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register WooPayments-compatible Reports routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/reports/balance', $this->get_readable_route( 'get_balance_summary', $this->get_balance_args() ) );
		register_rest_route( self::NAMESPACE, '/payments/reports/fees', $this->get_readable_route( 'get_fees' ) );
		register_rest_route( self::NAMESPACE, '/payments/reports/fees/summary', $this->get_readable_route( 'get_fees_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/reports/fees/download', $this->get_creatable_route( 'get_fees_export' ) );
		register_rest_route( self::NAMESPACE, '/payments/reports/fees/download/(?P<export_id>[^/\\\\%]+)', $this->get_readable_route( 'get_export_url' ) );
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
	 * Get Balance report summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_balance_summary( WP_REST_Request $request ) {
		$currency = strtolower( $this->get_scalar_param( $request, 'currency' ) );
		if ( ! WooPaymentsReportingBalanceSummaryRequest::is_valid_currency_code( $currency ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Currency must be an ISO 4217 three-letter code.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		try {
			return new WP_REST_Response(
				$this->api_client->get_reporting_balance_summary(
					array(
						'date_start' => $this->get_scalar_param( $request, 'date_start' ),
						'date_end'   => $this->get_scalar_param( $request, 'date_end' ),
						'currency'   => $currency,
					)
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get Fees report rows.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fees( WP_REST_Request $request ) {
		try {
			$response = $this->api_client->get_transactions( $this->get_filtered_fees_list_params( $request ) );
			$rows     = $response;

			if ( isset( $response['data'] ) ) {
				$rows = is_array( $response['data'] ) ? $response['data'] : array();
			}

			return new WP_REST_Response( $this->prepare_fees_rows( $rows ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get Fees report summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fees_summary( WP_REST_Request $request ) {
		try {
			$filters    = $this->get_fees_filters( $request );
			$deposit_id = isset( $filters['deposit_id'] ) && is_scalar( $filters['deposit_id'] ) ? (string) $filters['deposit_id'] : null;
			unset( $filters['deposit_id'] );

			return new WP_REST_Response( $this->api_client->get_transactions_summary( $filters, $deposit_id ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Start a Fees CSV export.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_fees_export( WP_REST_Request $request ) {
		try {
			$filters    = $this->get_fees_filters( $request );
			$deposit_id = isset( $filters['deposit_id'] ) && is_scalar( $filters['deposit_id'] ) ? (string) $filters['deposit_id'] : null;
			unset( $filters['deposit_id'] );

			return new WP_REST_Response(
				$this->api_client->get_transactions_export(
					$filters,
					$this->get_scalar_param( $request, 'user_email' ),
					$deposit_id,
					$this->get_optional_string_param( $request, 'locale' )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get Fees export URL.
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
	 * Build a readable REST route definition.
	 *
	 * @param string              $callback Callback method.
	 * @param array<string,mixed> $args     Route args.
	 * @return array<string,mixed>
	 */
	private function get_readable_route( string $callback, array $args = array() ): array {
		return array(
			'methods'             => WP_REST_Server::READABLE,
			'args'                => $args,
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
	 * Get Balance route args.
	 *
	 * @return array<string,mixed>
	 */
	private function get_balance_args(): array {
		return array(
			'date_start' => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'date_end'   => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'currency'   => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => static function ( $currency ): string {
					return strtolower( sanitize_text_field( (string) $currency ) );
				},
				'validate_callback' => static function ( $currency ) {
					if ( WooPaymentsReportingBalanceSummaryRequest::is_valid_currency_code( $currency ) ) {
						return true;
					}

					return new WP_Error( 'rest_invalid_param', __( 'Currency must be an ISO 4217 three-letter code.', 'woocommerce' ) );
				},
			),
		);
	}

	/**
	 * Get filtered Fees list params after the preserved transactions request filter has run.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_filtered_fees_list_params( WP_REST_Request $request ): array {
		WooPaymentsTransactionsListRequest::register_legacy_alias();
		$fees_request = new WooPaymentsTransactionsListRequest();
		foreach ( $this->get_fees_list_params( $request ) as $key => $value ) {
			$fees_request->set_param( (string) $key, $value );
		}

		/**
		 * Allows the WooPayments transactions list request to be modified before it is sent to the platform.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsTransactionsListRequest $fees_request Native transactions list request for the Fees report.
		 */
		$filtered_request = apply_filters( 'wcpay_list_transactions_request', $fees_request );
		$params           = is_object( $filtered_request ) && method_exists( $filtered_request, 'get_params' )
			? $filtered_request->get_params()
			: $fees_request->get_params();

		$params = $this->filter_empty_params( is_array( $params ) ? array_intersect_key( $params, self::FEES_TRANSACTION_PARAMS ) : $fees_request->get_params() );

		return $this->order_service->map_transaction_search_params( $params );
	}

	/**
	 * Get Fees list params.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_fees_list_params( WP_REST_Request $request ): array {
		return array_merge(
			array(
				'page'      => max( 1, (int) ( $request->get_param( 'page' ) ?? 1 ) ),
				'pagesize'  => max( 1, (int) ( $request->get_param( 'per_page' ) ?? 25 ) ),
				'sort'      => $this->get_scalar_param( $request, 'sort', 'date' ),
				'direction' => $this->get_scalar_param( $request, 'direction', 'desc' ),
				'limit'     => 100,
			),
			$this->get_fees_filters( $request )
		);
	}

	/**
	 * Get Fees transaction filters.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_fees_filters( WP_REST_Request $request ): array {
		$search             = $this->normalize_string_list( $request->get_param( 'search' ) );
		$identifier_filters = $this->get_identifier_filters_from_search( $search );
		$type               = $this->normalize_string_list( $request->get_param( 'type' ) );
		$user_timezone      = $this->get_optional_string_param( $request, 'user_timezone' );
		$date_between       = $this->normalize_date_filters_by_timezone(
			$this->normalize_string_list( $request->get_param( 'date_between' ) ),
			$user_timezone
		);

		$filters = array(
			'source_is'     => $this->get_optional_string_param( $request, 'payment_method_type' ),
			'type_is_in'    => $type ?? self::DEFAULT_FEE_BEARING_TYPES,
			'order_id_is'   => $this->get_optional_string_param( $request, 'order_id' ),
			'deposit_id'    => $this->get_optional_string_param( $request, 'deposit_id' ),
			'date_before'   => $this->format_transaction_date_by_timezone( $this->get_optional_string_param( $request, 'date_before' ), $user_timezone ),
			'date_after'    => $this->format_transaction_date_by_timezone( $this->get_optional_string_param( $request, 'date_after' ), $user_timezone ),
			'date_between'  => $date_between,
			'match'         => $this->get_optional_string_param( $request, 'match' ),
			'search'        => empty( $identifier_filters ) ? $search : null,
			'user_timezone' => $user_timezone,
		);

		$filters = $this->filter_empty_params( array_merge( $filters, $identifier_filters ) );

		return $this->order_service->map_transaction_search_params( $filters );
	}

	/**
	 * Normalize date filters to match the user's timezone offset.
	 *
	 * @param string[]|null $dates         Date filters.
	 * @param string|null   $user_timezone User timezone offset.
	 * @return string[]|null
	 */
	private function normalize_date_filters_by_timezone( ?array $dates, ?string $user_timezone ): ?array {
		if ( null === $dates ) {
			return null;
		}

		return array_map(
			function ( string $date ) use ( $user_timezone ): string {
				return $this->format_transaction_date_by_timezone( $date, $user_timezone ) ?? $date;
			},
			$dates
		);
	}

	/**
	 * Shift a transaction date filter by the difference between the store and user timezones.
	 *
	 * @param string|null $transaction_date Transaction date.
	 * @param string|null $user_timezone    User timezone offset.
	 * @return string|null
	 */
	private function format_transaction_date_by_timezone( ?string $transaction_date, ?string $user_timezone ): ?string {
		if ( null === $transaction_date || null === $user_timezone ) {
			return $transaction_date;
		}

		try {
			$store_time = new DateTime( $transaction_date );
			$store_time->setTimezone( new DateTimeZone( wp_timezone_string() ) );

			$user_time = new DateTime( $transaction_date );
			$user_time->setTimezone( new DateTimeZone( $user_timezone ) );

			$time_difference = ( strtotime( $user_time->format( 'Y-m-d H:i:s' ) ) - strtotime( $store_time->format( 'Y-m-d H:i:s' ) ) ) / 60;
			$formatted_date  = new DateTime( $transaction_date );
			date_modify( $formatted_date, $time_difference . 'minutes' );

			return $formatted_date->format( 'Y-m-d H:i:s' );
		} catch ( Exception $exception ) {
			return $transaction_date;
		}
	}

	/**
	 * Prepare Fees rows for REST output.
	 *
	 * @param array<int|string,mixed> $rows Rows.
	 * @return array<int,array<string,mixed>>
	 */
	private function prepare_fees_rows( array $rows ): array {
		$prepared = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$prepared[] = array(
				'transaction_id'       => $row['transaction_id'] ?? '',
				'date'                 => $row['date'] ?? '',
				'payment_id'           => $row['payment_intent_id'] ?? '',
				'channel'              => $row['channel'] ?? '',
				'payment_method'       => array(
					'type' => $row['source'] ?? '',
				),
				'type'                 => $row['type'] ?? '',
				'transaction_currency' => $row['customer_currency'] ?? '',
				'amount'               => $row['amount'] ?? 0,
				'exchange_rate'        => $row['exchange_rate'] ?? null,
				'deposit_currency'     => $row['currency'] ?? '',
				'fees'                 => $row['fees'] ?? 0,
				'net_amount'           => $row['net'] ?? 0,
				'order_id'             => $row['order_id'] ?? null,
				'risk_level'           => $row['risk_level'] ?? null,
				'deposit_date'         => $row['available_on'] ?? null,
				'deposit_id'           => $row['deposit_id'] ?? null,
				'deposit_status'       => $row['deposit_status'] ?? null,
			);
		}

		return $prepared;
	}

	/**
	 * Get identifier filters from a single search term.
	 *
	 * @param string[]|null $search Search terms.
	 * @return array<string,string>
	 */
	private function get_identifier_filters_from_search( ?array $search ): array {
		if ( ! is_array( $search ) || 1 !== count( $search ) ) {
			return array();
		}

		$term = reset( $search );
		if ( ! is_string( $term ) ) {
			return array();
		}

		if ( 1 === preg_match( '/^po_\w+$/', $term ) ) {
			return array( 'deposit_id' => $term );
		}

		if ( 1 === preg_match( '/^txn_\w+$/', $term ) ) {
			return array( 'transaction_id_is' => $term );
		}

		return array();
	}

	/**
	 * Normalize a scalar or array value to a sanitized string list.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]|null
	 */
	private function normalize_string_list( $value ): ?array {
		if ( null === $value || '' === $value ) {
			return null;
		}

		$values     = is_array( $value ) ? $value : array( $value );
		$normalized = array();
		foreach ( $values as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}

			$item = sanitize_text_field( (string) $item );
			if ( '' !== $item ) {
				$normalized[] = $item;
			}
		}

		return empty( $normalized ) ? null : $normalized;
	}

	/**
	 * Remove empty params while preserving zero-like values.
	 *
	 * @param array<string,mixed> $params Params.
	 * @return array<string,mixed>
	 */
	private function filter_empty_params( array $params ): array {
		return array_filter(
			$params,
			static function ( $value ): bool {
				return null !== $value && '' !== $value && array() !== $value;
			}
		);
	}

	/**
	 * Get a scalar request param.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @param string          $name     Param name.
	 * @param string          $fallback Fallback.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return string
	 */
	private function get_scalar_param( WP_REST_Request $request, string $name, string $fallback = '' ): string {
		$value = $request->get_param( $name );

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $fallback;
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
		$value = $this->get_scalar_param( $request, $name );

		return '' === $value ? null : $value;
	}

	/**
	 * Convert an API exception to a REST error.
	 *
	 * @param WooPaymentsApiException $exception API exception.
	 * @return WP_Error
	 */
	private function api_exception_to_wp_error( WooPaymentsApiException $exception ): WP_Error {
		$http_code = $exception->get_http_code();
		$status    = $http_code >= 400 && $http_code <= 599 ? $http_code : 500;

		return new WP_Error(
			'' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_api_error',
			$exception->getMessage(),
			array( 'status' => $status )
		);
	}
}
