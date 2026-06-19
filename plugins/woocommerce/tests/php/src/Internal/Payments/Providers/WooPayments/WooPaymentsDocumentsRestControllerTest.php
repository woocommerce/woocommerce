<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDocumentsRestController;
use WC_REST_Unit_Test_Case;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the native WooPayments Documents REST controller.
 */
class WooPaymentsDocumentsRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsDocumentsRestController
	 */
	private WooPaymentsDocumentsRestController $sut;

	/**
	 * Recording API client.
	 *
	 * @var RecordingDocumentsApiClient
	 */
	private RecordingDocumentsApiClient $api_client;

	/**
	 * Captured Tracks URL.
	 *
	 * @var string
	 */
	private string $captured_tracks_url = '';

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client = new RecordingDocumentsApiClient();
		$this->sut        = $this->create_controller( true, true, false );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
		remove_filter( 'rest_pre_serve_request', array( $this->sut, 'serve_raw_document_response' ) );
		remove_all_filters( 'wcpay_list_documents_request' );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'wp_doing_ajax' );
		$this->captured_tracks_url = '';
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Documents and VAT routes are registered only when native owns runtime and Documents are enabled.
	 */
	public function test_registers_routes_when_native_owns_runtime_and_documents_are_enabled(): void {
		$this->sut->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		foreach ( $this->get_expected_routes() as $route => $methods ) {
			$this->assertArrayHasKey( $route, $routes );
			foreach ( $methods as $method ) {
				$this->assertRouteHasMethod( $routes[ $route ], $method );
			}
		}
	}

	/**
	 * @testdox Documents routes are not registered when native runtime is inactive.
	 */
	public function test_registers_no_routes_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false, true, false );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox Documents routes are not registered when the account is not document eligible.
	 */
	public function test_registers_no_routes_when_documents_are_disabled(): void {
		$this->sut = $this->create_controller( true, false, false );

		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox Documents routes require manage_woocommerce before calling the platform API.
	 */
	public function test_routes_require_manage_woocommerce(): void {
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/documents' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertNull( $this->api_client->last_documents_query );
	}

	/**
	 * @testdox Documents list forwards only preserved platform query parameters.
	 */
	public function test_get_documents_forwards_allow_listed_query_parameters(): void {
		$this->sut->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/documents' );
		$request->set_query_params(
			array(
				'page'         => '2',
				'pagesize'     => '50',
				'sort'         => 'date',
				'direction'    => 'desc',
				'match'        => 'all',
				'date_before'  => '2026-06-18',
				'date_after'   => '2026-06-01',
				'date_between' => '2026-06-01...2026-06-18',
				'type_is'      => 'vat_invoice',
				'type_is_not'  => 'statement',
				'ignored'      => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'page'         => '2',
				'pagesize'     => '50',
				'sort'         => 'date',
				'direction'    => 'desc',
				'match'        => 'all',
				'date_before'  => '2026-06-18',
				'date_after'   => '2026-06-01',
				'date_between' => '2026-06-01...2026-06-18',
				'type_is'      => 'vat_invoice',
				'type_is_not'  => 'statement',
			),
			$this->api_client->last_documents_query
		);
		$this->assertSame( $this->api_client->documents_response, $response->get_data() );
	}

	/**
	 * @testdox Documents list leaves the preserved request-object filter to the API client boundary.
	 */
	public function test_get_documents_does_not_apply_legacy_request_filter_in_controller(): void {
		$filter_count = 0;
		add_filter(
			'wcpay_list_documents_request',
			static function ( $request ) use ( &$filter_count ) {
				++$filter_count;

				return $request;
			}
		);
		$this->sut->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/documents' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $filter_count, 'The API client applies the legacy documents filter exactly once.' );
	}

	/**
	 * @testdox Documents summary forwards filters only.
	 */
	public function test_get_documents_summary_forwards_filter_only_parameters(): void {
		$this->sut->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/documents/summary' );
		$request->set_query_params(
			array(
				'page'         => '2',
				'pagesize'     => '50',
				'sort'         => 'date',
				'direction'    => 'asc',
				'match'        => 'all',
				'date_before'  => '2026-06-18',
				'date_after'   => '2026-06-01',
				'date_between' => array( '2026-06-01', '2026-06-18' ),
				'type_is'      => 'vat_invoice',
				'type_is_not'  => 'statement',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'match'        => 'all',
				'date_before'  => '2026-06-18',
				'date_after'   => '2026-06-01',
				'date_between' => array( '2026-06-01', '2026-06-18' ),
				'type_is'      => 'vat_invoice',
				'type_is_not'  => 'statement',
			),
			$this->api_client->last_documents_summary_query
		);
	}

	/**
	 * @testdox Invalid document IDs are rejected before calling the platform API.
	 */
	public function test_get_document_rejects_invalid_document_id(): void {
		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/documents/../doc_123' );
		$request->set_param( 'document_id', '../doc_123' );

		$response = $this->sut->get_document( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_route_validation_failure', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertNull( $this->api_client->last_document_id );
	}

	/**
	 * @testdox Document download streams upstream status, headers, and body without JSON wrapping.
	 */
	public function test_get_document_returns_raw_download_response_semantics(): void {
		$this->api_client->document_response = array(
			'response' => array(
				'code'    => 201,
				'message' => 'Created',
			),
			'headers'  => array(
				'content-type'        => 'application/pdf',
				'content-disposition' => 'attachment; filename="invoice.pdf"',
			),
			'body'     => '%PDF invoice',
		);
		$request                             = new WP_REST_Request( 'GET', '/wc/v3/payments/documents/vat_invoice-123' );
		$request->set_param( 'document_id', 'vat_invoice-123' );

		$response = $this->sut->get_document( $request );

		$this->assertInstanceOf( WP_HTTP_Response::class, $response );
		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( '%PDF invoice', $response->get_data() );
		$this->assertSame( 'application/pdf', $response->get_headers()['Content-Type'] );
		$this->assertSame( 'attachment; filename="invoice.pdf"', $response->get_headers()['Content-Disposition'] );
		$this->assertSame( 'vat_invoice-123', $this->api_client->last_document_id );

		ob_start();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$served        = apply_filters( 'rest_pre_serve_request', false, $response, $request, $this->server );
		$response_body = ob_get_clean();

		$this->assertTrue( $served );
		$this->assertSame( '%PDF invoice', $response_body );
	}

	/**
	 * @testdox Malformed raw document responses fail closed before streaming or tracking.
	 */
	public function test_get_document_rejects_raw_responses_without_valid_http_status(): void {
		$this->api_client->document_response = array(
			'response' => array(
				'code'    => 0,
				'message' => '',
			),
			'headers'  => array(),
			'body'     => 'transport failure body',
		);
		$request                             = new WP_REST_Request( 'GET', '/wc/v3/payments/documents/vat_invoice-123' );
		$request->set_param( 'document_id', 'vat_invoice-123' );

		$response = $this->sut->get_document( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_api_error', $response->get_error_code() );
		$this->assertSame( 'Unable to retrieve document.', $response->get_error_message() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
		$this->assertFalse( has_filter( 'rest_pre_serve_request', array( $this->sut, 'serve_raw_document_response' ) ) );
		$this->assertSame( '', $this->captured_tracks_url );
	}

	/**
	 * @testdox Successful document downloads record the preserved Tracks event.
	 */
	public function test_get_document_records_tracks_event_after_successful_download(): void {
		if ( ! function_exists( 'wc_admin_record_tracks_event' ) && defined( 'WC_ABSPATH' ) ) {
			require_once WC_ABSPATH . 'includes/react-admin/core-functions.php';
		}
		update_option( 'woocommerce_allow_tracking', 'yes' );
		if ( ! post_type_exists( 'product' ) ) {
			register_post_type( 'product' );
		}
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'pre_http_request',
			function ( $preempt, $parsed_args, $url ) {
				$this->captured_tracks_url = $url;

				return array(
					'headers'  => array(),
					'body'     => '',
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			},
			10,
			3
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/documents/vat_invoice-123' );
		$request->set_param( 'document_id', 'vat_invoice-123' );

		$this->sut->get_document( $request );

		$this->assertNotSame( '', $this->captured_tracks_url );
		parse_str( (string) wp_parse_url( $this->captured_tracks_url, PHP_URL_QUERY ), $pixel_args );
		$this->assertSame( 'wcadmin_wcpay_document_downloaded', $pixel_args['_en'] );
		$this->assertSame( 'vat_invoice-123', $pixel_args['document_id'] );
		$this->assertSame( 'live', $pixel_args['mode'] );
	}

	/**
	 * @testdox VAT validation forwards the decoded VAT route parameter.
	 */
	public function test_validate_vat_forwards_vat_number(): void {
		$this->sut->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/vat/RO123456' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'RO123456', $this->api_client->last_vat_number );
		$this->assertSame( $this->api_client->validate_vat_response, $response->get_data() );
	}

	/**
	 * @testdox VAT save requires company name and address.
	 */
	public function test_save_vat_requires_name_and_address(): void {
		$this->sut->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/vat' );
		$request->set_body_params( array( 'vat_number' => 'RO123456' ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertNull( $this->api_client->last_saved_vat_details );
	}

	/**
	 * @testdox VAT save forwards optional VAT number with company name and address.
	 */
	public function test_save_vat_forwards_company_details(): void {
		$this->sut->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/vat' );
		$request->set_body_params(
			array(
				'vat_number' => 'RO123456',
				'name'       => 'ACME SRL',
				'address'    => '1 Market Street',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'vat_number' => 'RO123456',
				'name'       => 'ACME SRL',
				'address'    => '1 Market Street',
			),
			$this->api_client->last_saved_vat_details
		);
		$this->assertSame( $this->api_client->save_vat_response, $response->get_data() );
	}

	/**
	 * @testdox API exceptions are converted to REST errors with their status.
	 */
	public function test_api_exceptions_preserve_legacy_error_envelope(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Provider unavailable.', 'provider_unavailable', 503 );

		$response = $this->sut->get_documents( new WP_REST_Request( 'GET', '/wc/v3/payments/documents' ) );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'provider_unavailable', $response->get_error_code() );
		$this->assertSame( 'Provider unavailable.', $response->get_error_message() );
		$this->assertSame( 503, $response->get_error_data()['status'] );
	}

	/**
	 * @testdox API exceptions without a valid HTTP status become REST 500 errors.
	 */
	public function test_api_exceptions_without_valid_http_status_become_internal_errors(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Transport failed.', 'wcpay_server_request_error', 0 );

		$response = $this->sut->get_documents( new WP_REST_Request( 'GET', '/wc/v3/payments/documents' ) );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_server_request_error', $response->get_error_code() );
		$this->assertSame( 'Transport failed.', $response->get_error_message() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
	}

	/**
	 * @testdox API exceptions with non-error HTTP statuses become REST 500 errors.
	 *
	 * @dataProvider api_exception_non_error_status_provider
	 *
	 * @param int $http_code Exception HTTP code.
	 */
	public function test_api_exceptions_with_non_error_http_statuses_become_internal_errors( int $http_code ): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Malformed response.', 'wcpay_unparseable_or_null_body', $http_code );

		$response = $this->sut->get_documents( new WP_REST_Request( 'GET', '/wc/v3/payments/documents' ) );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_unparseable_or_null_body', $response->get_error_code() );
		$this->assertSame( 'Malformed response.', $response->get_error_message() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
	}

	/**
	 * Get non-error HTTP statuses that should never be exposed as REST errors.
	 *
	 * @return array<string,array{int}>
	 */
	public function api_exception_non_error_status_provider(): array {
		return array(
			'success'     => array( 200 ),
			'redirect'    => array( 302 ),
			'out-of-band' => array( 700 ),
		);
	}

	/**
	 * Create a native Documents REST controller.
	 *
	 * @param bool $native_register        Whether native should own route registration.
	 * @param bool $documents_enabled      Whether Documents are enabled for the account.
	 * @param bool $test_mode              Whether WooPayments should run in test mode.
	 * @return WooPaymentsDocumentsRestController
	 */
	private function create_controller( bool $native_register, bool $documents_enabled, bool $test_mode ): WooPaymentsDocumentsRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'is_documents_enabled' ) )
			->getMock();
		$account_service->method( 'is_documents_enabled' )->willReturn( $documents_enabled );
		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );

		$controller = new WooPaymentsDocumentsRestController();
		$controller->init( $arbiter, $this->api_client, $account_service );

		return $controller;
	}

	/**
	 * Assert a route handler supports a method.
	 *
	 * @param array<int,array<string,mixed>> $route_handlers Route handlers.
	 * @param string                         $method         Method constant.
	 */
	private function assertRouteHasMethod( array $route_handlers, string $method ): void {
		$methods = array_map( 'trim', explode( ',', $method ) );

		foreach ( $route_handlers as $handler ) {
			$route_methods = isset( $handler['methods'] ) && is_array( $handler['methods'] ) ? $handler['methods'] : array();
			$missing       = array_filter(
				$methods,
				static function ( string $method_name ) use ( $route_methods ): bool {
					return ! isset( $route_methods[ $method_name ] ) || true !== $route_methods[ $method_name ];
				}
			);

			if ( empty( $missing ) ) {
				return;
			}
		}

		$this->fail( 'Route does not accept method ' . $method . '.' );
	}

	/**
	 * Get expected route methods.
	 *
	 * @return array<string,string[]>
	 */
	private function get_expected_routes(): array {
		return array(
			'/wc/v3/payments/documents'         => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/documents/summary' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/documents/(?P<document_id>[\w-]+)' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/vat/(?P<vat_number>[\w\.\%]+)' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/vat'               => array( WP_REST_Server::EDITABLE ),
		);
	}
}
