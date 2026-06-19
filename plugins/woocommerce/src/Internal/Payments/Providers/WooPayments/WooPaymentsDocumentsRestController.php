<?php
/**
 * WooPaymentsDocumentsRestController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPayments Documents REST controller.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsDocumentsRestController implements RegisterHooksInterface {

	private const NAMESPACE = 'wc/v3';

	private const LIST_QUERY_PARAMS = array(
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
	);

	private const SUMMARY_QUERY_PARAMS = array(
		'match'        => true,
		'date_before'  => true,
		'date_after'   => true,
		'date_between' => true,
		'type_is'      => true,
		'type_is_not'  => true,
	);

	/**
	 * Raw document response currently being served through rest_pre_serve_request.
	 *
	 * @var WP_HTTP_Response|null
	 */
	private ?WP_HTTP_Response $raw_document_response = null;

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
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsApiClient         $api_client      Native WooPayments API client.
	 * @param WooPaymentsAccountService    $account_service WooPayments account service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsApiClient $api_client, WooPaymentsAccountService $account_service ): void {
		$this->arbiter         = $arbiter;
		$this->api_client      = $api_client;
		$this->account_service = $account_service;
	}

	/**
	 * Register REST hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() || ! $this->account_service->is_documents_enabled() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register WooPayments-compatible Documents and VAT routes.
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/payments/documents', $this->get_readable_route( 'get_documents' ) );
		register_rest_route( self::NAMESPACE, '/payments/documents/summary', $this->get_readable_route( 'get_documents_summary' ) );
		register_rest_route( self::NAMESPACE, '/payments/documents/(?P<document_id>[\w-]+)', $this->get_readable_route( 'get_document' ) );
		register_rest_route( self::NAMESPACE, '/payments/vat/(?P<vat_number>[\w\.\%]+)', $this->get_readable_route( 'validate_vat' ) );
		register_rest_route(
			self::NAMESPACE,
			'/payments/vat',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'args'                => array(
					'vat_number' => array(
						'type'     => 'string',
						'required' => false,
					),
					'name'       => array(
						'type'     => 'string',
						'required' => true,
					),
					'address'    => array(
						'type'     => 'string',
						'required' => true,
					),
				),
				'callback'            => array( $this, 'save_vat_details' ),
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
	 * Get documents.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_documents( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_documents( $this->get_allowed_params( $request, self::LIST_QUERY_PARAMS ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get documents summary.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_documents_summary( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->get_documents_summary( $this->get_allowed_params( $request, self::SUMMARY_QUERY_PARAMS ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Get a document download response.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_HTTP_Response|WP_Error
	 */
	public function get_document( WP_REST_Request $request ) {
		$document_id = (string) $request->get_param( 'document_id' );
		if ( '' === $document_id || ! preg_match( '/^[\w-]+$/', $document_id ) ) {
			return new WP_Error( 'wcpay_route_validation_failure', __( 'Route param validation failed.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		try {
			$raw_response = $this->api_client->get_document( $document_id );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}

		$status              = (int) wp_remote_retrieve_response_code( $raw_response );
		$content_type        = $this->get_raw_response_header( $raw_response, 'content-type' );
		$content_disposition = $this->get_raw_response_header( $raw_response, 'content-disposition' );
		$body                = wp_remote_retrieve_body( $raw_response );
		$headers             = array();

		if ( $status <= 0 ) {
			return new WP_Error(
				'wcpay_api_error',
				__( 'Unable to retrieve document.', 'woocommerce' ),
				array( 'status' => 500 )
			);
		}

		if ( '' !== $content_type ) {
			$headers['Content-Type'] = $content_type;
		}

		if ( '' !== $content_disposition ) {
			$headers['Content-Disposition'] = $content_disposition;
		}

		$this->record_document_download( $document_id );

		$response                    = new WP_HTTP_Response(
			$body,
			$status,
			$headers
		);
		$this->raw_document_response = $response;

		if ( false === has_filter( 'rest_pre_serve_request', array( $this, 'serve_raw_document_response' ) ) ) {
			add_filter( 'rest_pre_serve_request', array( $this, 'serve_raw_document_response' ), 10, 2 );
		}

		return $response;
	}

	/**
	 * Serve raw document bytes without REST JSON serialization.
	 *
	 * @param bool             $served   Whether the response was already served.
	 * @param WP_HTTP_Response $response REST response.
	 * @return bool
	 */
	public function serve_raw_document_response( bool $served, WP_HTTP_Response $response ): bool {
		if ( $response !== $this->raw_document_response ) {
			return $served;
		}

		$this->raw_document_response = null;
		$body                        = $response->get_data();

		if ( is_scalar( $body ) ) {
			echo (string) $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Document bytes are intentionally streamed as the response body.
		}

		return true;
	}

	/**
	 * Validate VAT number.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_vat( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response( $this->api_client->validate_vat( sanitize_text_field( (string) $request->get_param( 'vat_number' ) ) ) );
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
	}

	/**
	 * Save VAT details.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_vat_details( WP_REST_Request $request ) {
		try {
			return new WP_REST_Response(
				$this->api_client->save_vat_details(
					$this->get_optional_string_param( $request, 'vat_number' ),
					(string) $request->get_param( 'name' ),
					(string) $request->get_param( 'address' )
				)
			);
		} catch ( WooPaymentsApiException $exception ) {
			return $this->api_exception_to_wp_error( $exception );
		}
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
	 * Extract allowed params from the request without normalizing platform-facing names.
	 *
	 * @param WP_REST_Request    $request Request.
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
	 * Get a scalar header value from a raw WordPress HTTP response.
	 *
	 * @param array<string,mixed> $raw_response Raw HTTP response.
	 * @param string              $header       Header name.
	 * @return string
	 */
	private function get_raw_response_header( array $raw_response, string $header ): string {
		$value = wp_remote_retrieve_header( $raw_response, $header );

		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Record the preserved document download Tracks event.
	 *
	 * @param string $document_id Document ID.
	 */
	private function record_document_download( string $document_id ): void {
		if ( ! function_exists( 'wc_admin_record_tracks_event' ) ) {
			return;
		}

		wc_admin_record_tracks_event(
			'wcpay_document_downloaded',
			array(
				'document_id' => $document_id,
				'mode'        => $this->account_service->is_test_mode_enabled() ? 'test' : 'live',
			)
		);
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
