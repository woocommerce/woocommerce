<?php
/**
 * WooPaymentsDepositsRestController class file.
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
use Throwable;

/**
 * Native WooPayments deposits REST controller.
 *
 * These deposits-named endpoints back merchant-facing payouts surfaces and intentionally preserve the WooPayments API contract.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDepositsRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const LOG_SOURCE = 'woopayments-payouts';

	/**
	 * Query params accepted by the payout list endpoint.
	 */
	private const LIST_QUERY_PARAMS = array(
		'page'              => true,
		'pagesize'          => true,
		'sort'              => true,
		'direction'         => true,
		'match'             => true,
		'store_currency_is' => true,
		'date_before'       => true,
		'date_after'        => true,
		'date_between'      => true,
		'status_is'         => true,
		'status_is_not'     => true,
	);

	/**
	 * Query params accepted by the payout summary endpoint.
	 */
	private const SUMMARY_QUERY_PARAMS = array(
		'match'             => true,
		'store_currency_is' => true,
		'date_before'       => true,
		'date_after'        => true,
		'date_between'      => true,
		'status_is'         => true,
		'status_is_not'     => true,
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
	 * Register WooPayments-compatible deposits routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/payments/deposits',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_deposits' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'manual_deposit' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/deposits/download',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_deposits_export' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/deposits/download/(?P<export_id>.*)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_export_url' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/deposits/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_deposits_summary' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/deposits/overview-all',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_deposits_overview' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/payments/deposits/(?P<deposit_id>[\w]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_deposit' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
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
	 * Get payout overviews.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deposits_overview( WP_REST_Request $request ) {
		unset( $request );

		try {
			return new WP_REST_Response( $this->api_client->get_deposits_overview() );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get payouts.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deposits( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->get_deposits( $this->get_filtered_deposits_list_params( $request ) )
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get payout summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deposits_summary( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->get_deposits_summary( $this->get_allowed_params( $request, self::SUMMARY_QUERY_PARAMS ) )
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get payout detail.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deposit( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_deposit( (string) $request->get_param( 'deposit_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Initiate payout export.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_deposits_export( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->get_deposits_export(
					$this->get_allowed_params( $request, self::SUMMARY_QUERY_PARAMS ),
					(string) $request->get_param( 'user_email' ),
					$this->get_optional_string_param( $request, 'locale' )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get payout export URL.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_export_url( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_payouts_export_url( (string) $request->get_param( 'export_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Trigger manual payout.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function manual_deposit( WP_REST_Request $request ) {
		$type     = (string) $request->get_param( 'type' );
		$currency = (string) $request->get_param( 'currency' );

		if ( '' === $type || '' === $currency ) {
			return new WP_Error(
				'wcpay_missing_manual_deposit_data',
				__( 'Payout type and currency are required.', 'woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$log_context = $this->get_manual_deposit_log_context( $type, $currency );
		$this->log_manual_deposit( 'info', 'Manual payout initiation requested.', $log_context );

		try {
			$response = $this->api_client->manual_deposit( $type, $currency );
			$this->log_manual_deposit( 'info', 'Manual payout initiation completed.', $log_context );

			return new WP_REST_Response( $response );
		} catch ( WooPaymentsApiException $exception ) {
			$this->log_manual_deposit(
				'error',
				'Manual payout initiation failed.',
				array_merge(
					$log_context,
					array(
						'api_code'    => '' !== $exception->get_error_code() ? $exception->get_error_code() : 'wcpay_api_error',
						'http_status' => $exception->get_http_code(),
					)
				)
			);

			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get deposits list params after the preserved legacy request filter has run.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_filtered_deposits_list_params( WP_REST_Request $request ): array {
		WooPaymentsDepositsListRequest::register_legacy_alias();
		$deposits_request = WooPaymentsDepositsListRequest::from_rest_request( $request );

		/**
		 * Allows the WooPayments deposits list request to be modified before it is sent to the platform.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsDepositsListRequest $deposits_request Native deposits list request.
		 */
		$filtered_request = apply_filters( 'wcpay_list_deposits_request', $deposits_request );

		if ( ! is_object( $filtered_request ) || ! method_exists( $filtered_request, 'get_params' ) ) {
			return $deposits_request->get_params();
		}

		$params = $filtered_request->get_params();

		return is_array( $params ) ? array_intersect_key( $params, self::LIST_QUERY_PARAMS + array( 'limit' => true ) ) : $deposits_request->get_params();
	}

	/**
	 * Extract allowed params from the request without normalizing platform-facing names.
	 *
	 * @param WP_REST_Request   $request Request.
	 * @param array<string,bool> $allowed Allowed param map.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_allowed_params( WP_REST_Request $request, array $allowed ): array {
		$query = array_intersect_key( $request->get_params(), $allowed );

		return array_filter(
			$query,
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
	 * Get safe manual payout log context.
	 *
	 * @param string $type     Payout type.
	 * @param string $currency Payout currency.
	 * @return array<string,mixed>
	 */
	private function get_manual_deposit_log_context( string $type, string $currency ): array {
		return array(
			'source'   => self::LOG_SOURCE,
			'user_id'  => get_current_user_id(),
			'type'     => $type,
			'currency' => $currency,
		);
	}

	/**
	 * Log manual payout events without letting logger failures replace the REST response.
	 *
	 * @param string              $level   Log level.
	 * @param string              $message Log message.
	 * @param array<string,mixed> $context Log context.
	 */
	private function log_manual_deposit( string $level, string $message, array $context ): void {
		try {
			if ( 'error' === $level ) {
				wc_get_logger()->error( $message, $context );
				return;
			}

			wc_get_logger()->info( $message, $context );
		} catch ( Throwable $exception ) {
			unset( $exception );
		}
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
			$exception->getMessage()
		);
	}
}
