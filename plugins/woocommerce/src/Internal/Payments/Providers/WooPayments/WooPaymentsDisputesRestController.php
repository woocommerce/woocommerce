<?php
/**
 * WooPaymentsDisputesRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments disputes REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDisputesRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const LOG_SOURCE = 'woopayments-disputes';

	private const LIST_QUERY_PARAMS = array(
		'page'            => true,
		'pagesize'        => true,
		'sort'            => true,
		'direction'       => true,
		'match'           => true,
		'currency_is'     => true,
		'created_before'  => true,
		'created_after'   => true,
		'created_between' => true,
		'search'          => true,
		'status_is'       => true,
		'status_is_not'   => true,
		'limit'           => true,
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
	 * Dispute cache service.
	 *
	 * @var WooPaymentsDisputeCacheService
	 */
	private WooPaymentsDisputeCacheService $dispute_cache_service;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter         $arbiter               Runtime owner arbiter.
	 * @param WooPaymentsApiClient                 $api_client            Native WooPayments API client.
	 * @param WooPaymentsMoneyMovementOrderService $order_service         Local order context service.
	 * @param WooPaymentsDisputeCacheService       $dispute_cache_service Dispute cache service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client, WooPaymentsMoneyMovementOrderService $order_service, WooPaymentsDisputeCacheService $dispute_cache_service ): void {
		$this->arbiter               = $arbiter;
		$this->api_client            = $api_client;
		$this->order_service         = $order_service;
		$this->dispute_cache_service = $dispute_cache_service;
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
	 * Register WooPayments-compatible disputes routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/disputes', $this->get_readable_route( 'get_disputes' ) );
		register_rest_route( self::NAMESPACE, '/payments/disputes/download/(?P<export_id>[^/\\\\%]+)', $this->get_readable_route( 'get_export_url' ) );
		register_rest_route( self::NAMESPACE, '/payments/disputes/summary', $this->get_readable_route( 'get_disputes_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/disputes/download', $this->get_creatable_route( 'get_disputes_export' ) );
		register_rest_route(
			self::NAMESPACE,
			'/payments/disputes/(?P<dispute_id>\w+)',
			array(
				$this->get_readable_route( 'get_dispute' ),
				$this->get_creatable_route( 'update_dispute' ),
			)
		);
		register_rest_route( self::NAMESPACE, '/payments/disputes/(?P<dispute_id>\w+)/close', $this->get_creatable_route( 'close_dispute' ) );
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
	 * Get disputes.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_disputes( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->order_service->enrich_disputes_list_response(
					$this->api_client->get_disputes( $this->get_filtered_disputes_list_params( $request ) )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get disputes summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_disputes_summary( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_disputes_summary( $this->get_filtered_disputes_list_params( $request ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get dispute detail.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_dispute( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->order_service->enrich_dispute_response(
					$this->api_client->get_dispute( (string) $request->get_param( 'dispute_id' ) )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Update dispute evidence.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_dispute( WP_REST_Request $request ) {
		$dispute_id  = (string) $request->get_param( 'dispute_id' );
		$submit      = wc_string_to_bool( $request->get_param( 'submit' ) );
		$log_context = $this->get_dispute_action_log_context( 'update', $dispute_id, $submit );

		$this->log_dispute_action( 'info', __( 'WooPayments dispute update requested.', 'woocommerce' ), $log_context );

		try {
			$evidence = $request->get_param( 'evidence' );
			$metadata = $request->get_param( 'metadata' );

			$response = $this->api_client->update_dispute(
				$dispute_id,
				is_array( $evidence ) ? $evidence : array(),
				$submit,
				is_array( $metadata ) ? $metadata : array()
			);

			$this->log_dispute_action( 'info', __( 'WooPayments dispute update completed.', 'woocommerce' ), $log_context );

			return new WP_REST_Response( $this->enrich_dispute_response_after_completed_action( $response, $log_context ) );
		} catch ( WooPaymentsApiException $exception ) {
			$this->log_dispute_action(
				'error',
				__( 'WooPayments dispute update failed.', 'woocommerce' ),
				array_merge(
					$log_context,
					array(
						'api_code'    => $exception->get_error_code(),
						'http_status' => $exception->get_http_code(),
					)
				)
			);

			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Close dispute.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function close_dispute( WP_REST_Request $request ) {
		$dispute_id  = (string) $request->get_param( 'dispute_id' );
		$log_context = $this->get_dispute_action_log_context( 'close', $dispute_id );

		$this->log_dispute_action( 'info', __( 'WooPayments dispute close requested.', 'woocommerce' ), $log_context );

		try {
			$response = $this->api_client->close_dispute( $dispute_id );
			$this->dispute_cache_service->delete_dispute_caches();

			$this->log_dispute_action( 'info', __( 'WooPayments dispute close completed.', 'woocommerce' ), $log_context );

			return new WP_REST_Response( $this->enrich_dispute_response_after_completed_action( $response, $log_context ) );
		} catch ( WooPaymentsApiException $exception ) {
			$this->log_dispute_action(
				'error',
				__( 'WooPayments dispute close failed.', 'woocommerce' ),
				array_merge(
					$log_context,
					array(
						'api_code'    => $exception->get_error_code(),
						'http_status' => $exception->get_http_code(),
					)
				)
			);

			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Initiate disputes export.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_disputes_export( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->get_disputes_export(
					$this->get_filtered_disputes_list_params( $request ),
					(string) $request->get_param( 'user_email' ),
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
			return new WP_REST_Response( $this->api_client->get_disputes_export_url( (string) $request->get_param( 'export_id' ) ) );
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
	 * Get disputes list params after the preserved legacy request filter has run.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return array<string,mixed>
	 */
	private function get_filtered_disputes_list_params( WP_REST_Request $request ): array {
		WooPaymentsDisputesListRequest::register_legacy_alias();
		$disputes_request = WooPaymentsDisputesListRequest::from_rest_request( $request );

		/**
		 * Allows the WooPayments disputes list request to be modified before it is sent to the platform.
		 *
		 * @since 11.0.0
		 *
		 * @param WooPaymentsDisputesListRequest $disputes_request Native disputes list request.
		 */
		$filtered_request = apply_filters( 'wcpay_list_disputes_request', $disputes_request );

		if ( ! is_object( $filtered_request ) || ! method_exists( $filtered_request, 'get_params' ) ) {
			return $disputes_request->get_params();
		}

		$params = $filtered_request->get_params();

		return is_array( $params ) ? array_intersect_key( $params, self::LIST_QUERY_PARAMS ) : $disputes_request->get_params();
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
	 * Get a safe dispute action log context.
	 *
	 * @param string    $action     Action name.
	 * @param string    $dispute_id Dispute ID.
	 * @param bool|null $submit     Whether evidence was submitted.
	 * @return array<string,mixed>
	 */
	private function get_dispute_action_log_context( string $action, string $dispute_id, ?bool $submit = null ): array {
		$context = array(
			'source'     => self::LOG_SOURCE,
			'action'     => $action,
			'dispute_id' => $dispute_id,
			'user_id'    => get_current_user_id(),
		);

		if ( null !== $submit ) {
			$context['submit'] = $submit;
		}

		return $context;
	}

	/**
	 * Log a dispute action event.
	 *
	 * @param string              $level   Log level.
	 * @param string              $message Log message.
	 * @param array<string,mixed> $context Log context.
	 */
	private function log_dispute_action( string $level, string $message, array $context ): void {
		wc_get_logger()->log( $level, $message, $context );
	}

	/**
	 * Add local order context after a platform mutation has already completed.
	 *
	 * @param array<string,mixed> $response    Platform response.
	 * @param array<string,mixed> $log_context Safe dispute action log context.
	 * @return array<string,mixed>
	 */
	private function enrich_dispute_response_after_completed_action( array $response, array $log_context ): array {
		try {
			return $this->order_service->enrich_dispute_response( $response );
		} catch ( Throwable $exception ) {
			$this->log_dispute_action(
				'warning',
				__( 'WooPayments dispute action completed, but local order context enrichment failed.', 'woocommerce' ),
				array_merge(
					$log_context,
					array(
						'exception' => get_class( $exception ),
					)
				)
			);

			if ( ! array_key_exists( 'order', $response ) ) {
				$response['order'] = null;
			}

			return $response;
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
			$exception->getMessage(),
			array( 'status' => $exception->get_http_code() > 0 ? $exception->get_http_code() : 500 )
		);
	}
}
