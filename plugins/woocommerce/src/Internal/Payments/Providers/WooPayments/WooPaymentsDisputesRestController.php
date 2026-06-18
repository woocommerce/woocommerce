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
			return new WP_REST_Response( $this->api_client->get_disputes( $this->get_filtered_disputes_list_params( $request ) ) );
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
			return new WP_REST_Response( $this->api_client->get_disputes_summary( $this->get_allowed_params( $request, self::LIST_QUERY_PARAMS ) ) );
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
			return new WP_REST_Response( $this->api_client->get_dispute( (string) $request->get_param( 'dispute_id' ) ) );
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
		try {
			$evidence = $request->get_param( 'evidence' );
			$metadata = $request->get_param( 'metadata' );

			return new WP_REST_Response(
				$this->api_client->update_dispute(
					(string) $request->get_param( 'dispute_id' ),
					is_array( $evidence ) ? $evidence : array(),
					wc_string_to_bool( $request->get_param( 'submit' ) ),
					is_array( $metadata ) ? $metadata : array()
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
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
		try {
			return new WP_REST_Response( $this->api_client->close_dispute( (string) $request->get_param( 'dispute_id' ) ) );
		} catch ( WooPaymentsApiException $exception ) {
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
					$this->get_allowed_params( $request, self::LIST_QUERY_PARAMS ),
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
