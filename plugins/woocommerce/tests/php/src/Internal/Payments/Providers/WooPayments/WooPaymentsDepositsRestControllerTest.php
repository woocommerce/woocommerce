<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDepositsRestController;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the native WooPayments deposits REST controller.
 */
class WooPaymentsDepositsRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsDepositsRestController
	 */
	private WooPaymentsDepositsRestController $sut;

	/**
	 * Recording API client.
	 *
	 * @var RecordingDepositsApiClient
	 */
	private RecordingDepositsApiClient $api_client;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client = new RecordingDepositsApiClient();
		$this->sut        = $this->create_controller( true );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
		remove_all_filters( 'wcpay_list_deposits_request' );
		remove_all_filters( 'woocommerce_logging_class' );
		parent::tearDown();
	}

	/**
	 * @testdox Deposits routes are registered under wc/v3 when native owns runtime.
	 */
	public function test_registers_deposits_routes_when_native_owns_runtime(): void {
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
	 * @testdox Deposits routes are not registered when native does not own runtime.
	 */
	public function test_registers_no_routes_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox Deposits routes require manage_woocommerce before calling the platform API.
	 */
	public function test_routes_require_manage_woocommerce(): void {
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertNull( $this->api_client->last_deposits_query );
	}

	/**
	 * @testdox Overview route returns the raw payout overview envelope.
	 */
	public function test_get_deposits_overview_returns_raw_envelope(): void {
		$this->api_client->deposits_overview_response = array(
			'balance' => array(
				'available' => array(
					array(
						'amount'   => 1000,
						'currency' => 'usd',
					),
				),
			),
			'account' => array(
				'default_currency' => 'usd',
			),
		);

		$response = $this->sut->get_deposits_overview( new WP_REST_Request( 'GET', '/wc/v3/payments/deposits/overview-all' ) );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( $this->api_client->deposits_overview_response, $response->get_data() );
	}

	/**
	 * @testdox Deposits list forwards only preserved platform query parameters.
	 */
	public function test_get_deposits_forwards_allow_listed_query_parameters(): void {
		$this->sut->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits' );
		$request->set_query_params(
			array(
				'page'              => '2',
				'pagesize'          => '25',
				'sort'              => 'date',
				'direction'         => 'desc',
				'match'             => 'all',
				'store_currency_is' => 'usd',
				'date_before'       => '2026-06-18',
				'date_after'        => '2026-06-01',
				'date_between'      => '2026-06-01...2026-06-18',
				'status_is'         => 'paid',
				'status_is_not'     => 'failed',
				'ignored'           => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'page'              => 2,
				'pagesize'          => 25,
				'sort'              => 'date',
				'direction'         => 'desc',
				'limit'             => 100,
				'match'             => 'all',
				'store_currency_is' => 'usd',
				'date_before'       => '2026-06-18',
				'date_after'        => '2026-06-01',
				'date_between'      => array( '2026-06-01...2026-06-18' ),
				'status_is'         => 'paid',
				'status_is_not'     => 'failed',
			),
			$this->api_client->last_deposits_query
		);
		$this->assertSame( $this->api_client->deposits_response, $response->get_data() );
	}

	/**
	 * @testdox Deposits list preserves the legacy request filter contract.
	 */
	public function test_get_deposits_preserves_legacy_request_filter_contract(): void {
		$this->sut->register_routes();
		$observed_page_size = null;
		$observed_api       = null;

		add_filter(
			'wcpay_list_deposits_request',
			static function ( \WCPay\Core\Server\Request $request ): \WCPay\Core\Server\Request {
				return $request;
			},
			8
		);
		add_filter(
			'wcpay_list_deposits_request',
			static function ( \WCPay\Core\Server\Request\Paginated $request ): \WCPay\Core\Server\Request\Paginated {
				return $request;
			},
			9
		);
			add_filter(
				'wcpay_list_deposits_request',
				static function ( \WCPay\Core\Server\Request\List_Deposits $request ) use ( &$observed_api, &$observed_page_size ): \WCPay\Core\Server\Request\List_Deposits {
					$observed_api       = $request->get_api();
					$observed_page_size = $request->get_param( 'pagesize' );
					$request->set_page_size( 50 );
					$request->set_date_after( '2026-06-01 00:00:00' );
					$request->set_date_before( '2026-06-18 23:59:59' );
					$request->set_date_between( array( '2026-06-01 00:00:00', '2026-06-18 23:59:59' ) );
					$request->set_status_is( 'failed' );
					$request->set_param( 'status_is_not', 'canceled' );

					return $request;
				},
				10
			);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits' );
		$request->set_query_params(
			array(
				'page'      => '2',
				'pagesize'  => '25',
				'status_is' => 'paid',
			)
		);

		$response = $this->server->dispatch( $request );

			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( 'deposits', $observed_api );
			$this->assertSame( 25, $observed_page_size );
			$this->assertSame(
				array(
					'page'          => 2,
					'pagesize'      => 50,
					'sort'          => 'created',
					'direction'     => 'desc',
					'limit'         => 100,
					'status_is'     => 'failed',
					'date_after'    => '2026-06-01 00:00:00',
					'date_before'   => '2026-06-18 23:59:59',
					'date_between'  => array( '2026-06-01 00:00:00', '2026-06-18 23:59:59' ),
					'status_is_not' => 'canceled',
				),
				$this->api_client->last_deposits_query
			);
	}

	/**
	 * @testdox Deposits summary forwards only preserved filter parameters.
	 */
	public function test_get_deposits_summary_forwards_filter_parameters(): void {
		$this->sut->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits/summary' );
		$request->set_query_params(
			array(
				'page'              => '2',
				'pagesize'          => '25',
				'sort'              => 'date',
				'direction'         => 'desc',
				'match'             => 'all',
				'store_currency_is' => 'usd',
				'status_is'         => 'paid',
				'ignored'           => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'match'             => 'all',
				'store_currency_is' => 'usd',
				'status_is'         => 'paid',
			),
			$this->api_client->last_deposits_summary_query
		);
	}

	/**
	 * @testdox Deposit detail forwards the route resource ID.
	 */
	public function test_get_deposit_forwards_route_identifier(): void {
		$this->sut->register_routes();
		$this->api_client->deposit_response = array(
			'id'     => 'po_test',
			'amount' => 1234,
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits/po_test' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'po_test', $this->api_client->last_deposit_id );
		$this->assertSame( $this->api_client->deposit_response, $response->get_data() );
	}

	/**
	 * @testdox Deposits export forwards filters, email, and locale.
	 */
	public function test_get_deposits_export_forwards_filters_email_and_locale(): void {
		$this->sut->register_routes();
		$this->api_client->deposits_export_response = array(
			'exported_deposits' => 42,
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/deposits/download' );
		$request->set_body_params(
			array(
				'user_email' => 'merchant@example.com',
				'locale'     => 'en_US',
			)
		);
		$request->set_query_params(
			array(
				'store_currency_is' => 'usd',
				'status_is'         => 'paid',
				'page'              => '2',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'store_currency_is' => 'usd',
				'status_is'         => 'paid',
			),
			$this->api_client->last_export_query
		);
		$this->assertSame( 'merchant@example.com', $this->api_client->last_export_user_email );
		$this->assertSame( 'en_US', $this->api_client->last_export_locale );
		$this->assertSame( $this->api_client->deposits_export_response, $response->get_data() );
	}

	/**
	 * @testdox Export polling forwards the export ID.
	 */
	public function test_get_export_url_forwards_route_identifier(): void {
		$this->sut->register_routes();
		$this->api_client->payouts_export_url_response = array(
			'url' => 'https://example.com/export.csv',
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits/download/poexp_test' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'poexp_test', $this->api_client->last_export_id );
		$this->assertSame( $this->api_client->payouts_export_url_response, $response->get_data() );
	}

	/**
	 * @testdox Manual payout forwards type and currency.
	 */
	public function test_manual_deposit_forwards_type_and_currency(): void {
		$this->sut->register_routes();
		$this->api_client->manual_deposit_response = array(
			'id'       => 'po_instant',
			'type'     => 'instant',
			'currency' => 'usd',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/deposits' );
		$request->set_body_params(
			array(
				'type'     => 'instant',
				'currency' => 'usd',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame(
			array(
				'type'     => 'instant',
				'currency' => 'usd',
			),
			$this->api_client->last_manual_deposit
		);
		$this->assertSame( $this->api_client->manual_deposit_response, $response->get_data() );
	}

	/**
	 * @testdox Manual payout requires type and currency.
	 */
	public function test_manual_deposit_requires_type_and_currency(): void {
		$this->sut->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/deposits' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'wcpay_missing_manual_deposit_data', $response->get_data()['code'] );
		$this->assertNull( $this->api_client->last_manual_deposit );
	}

	/**
	 * @testdox API exceptions preserve the legacy deposits REST error envelope.
	 */
	public function test_api_exceptions_preserve_legacy_error_envelope(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Missing payout.', 'resource_missing', 404 );

		$response = $this->sut->get_deposit( $this->create_detail_request( 'po_missing' ) );

		$this->assertSame( 'resource_missing', $response->get_error_code() );
		$this->assertSame( 'Missing payout.', $response->get_error_message() );
		$this->assertNull( $response->get_error_data() );
	}

	/**
	 * @testdox Manual payout logs a safe audit trail on success.
	 */
	public function test_manual_deposit_logs_success_audit_trail(): void {
		$this->sut->register_routes();
		$logger = $this->create_recording_logger();
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ): object {
				return $logger;
			}
		);
		$this->api_client->manual_deposit_response = array( 'id' => 'po_manual' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/deposits' );
		$request->set_body_params(
			array(
				'type'     => 'instant',
				'currency' => 'usd',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $logger->entries );
		$this->assertSame( 'info', $logger->entries[0]['level'] );
		$this->assertSame( 'woopayments-payouts', $logger->entries[0]['context']['source'] );
		$this->assertSame( 'instant', $logger->entries[0]['context']['type'] );
		$this->assertSame( 'usd', $logger->entries[0]['context']['currency'] );
		$this->assertSame( 'info', $logger->entries[1]['level'] );
		$this->assertStringContainsString( 'completed', $logger->entries[1]['message'] );
	}

	/**
	 * @testdox Manual payout logs safe upstream failure context.
	 */
	public function test_manual_deposit_logs_failure_audit_trail(): void {
		$this->sut->register_routes();
		$logger = $this->create_recording_logger();
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ): object {
				return $logger;
			}
		);
		$this->api_client->exception = new WooPaymentsApiException( 'Ambiguous payout failure.', 'ambiguous_failure', 504 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/deposits' );
		$request->set_body_params(
			array(
				'type'     => 'instant',
				'currency' => 'usd',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 500, $response->get_status() );
		$this->assertCount( 2, $logger->entries );
		$this->assertSame( 'error', $logger->entries[1]['level'] );
		$this->assertStringContainsString( 'failed', $logger->entries[1]['message'] );
		$this->assertSame( 'woopayments-payouts', $logger->entries[1]['context']['source'] );
		$this->assertSame( 'instant', $logger->entries[1]['context']['type'] );
		$this->assertSame( 'usd', $logger->entries[1]['context']['currency'] );
		$this->assertSame( 'ambiguous_failure', $logger->entries[1]['context']['api_code'] );
		$this->assertSame( 504, $logger->entries[1]['context']['http_status'] );
	}

	/**
	 * Create a native deposits REST controller.
	 *
	 * @param bool $native_register Whether native should own route registration.
	 * @return WooPaymentsDepositsRestController
	 */
	private function create_controller( bool $native_register ): WooPaymentsDepositsRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$controller = new WooPaymentsDepositsRestController();
		$controller->init( $arbiter, $this->api_client );

		return $controller;
	}

	/**
	 * Create a detail request with route params.
	 *
	 * @param string $deposit_id Deposit ID.
	 * @return WP_REST_Request
	 */
	private function create_detail_request( string $deposit_id ): WP_REST_Request {
		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/deposits/' . $deposit_id );
		$request->set_param( 'deposit_id', $deposit_id );

		return $request;
	}

	/**
	 * Create a recording logger test double.
	 *
	 * @return object
	 */
	private function create_recording_logger(): object {
		return new class() implements \WC_Logger_Interface {
			/**
			 * Logged entries.
			 *
			 * @var array<int,array{level:string,message:string,context:array<string,mixed>}>
			 */
			public array $entries = array();

			/**
			 * Add a log entry.
			 *
			 * @param string $handle  File handle.
			 * @param string $message Log message.
			 * @param string $level   Log level.
			 * @return bool
			 */
			public function add( $handle, $message, $level = \WC_Log_Levels::NOTICE ) {
				$this->record( $level, $message, array( 'source' => $handle ) );

				return true;
			}

			/**
			 * Add a log entry.
			 *
			 * @param string              $level   Log level.
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function log( $level, $message, $context = array() ) {
				$this->record( $level, $message, $context );
			}

			/**
			 * Record an emergency log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function emergency( $message, $context = array() ) {
				$this->record( 'emergency', $message, $context );
			}

			/**
			 * Record an alert log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function alert( $message, $context = array() ) {
				$this->record( 'alert', $message, $context );
			}

			/**
			 * Record a critical log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function critical( $message, $context = array() ) {
				$this->record( 'critical', $message, $context );
			}

			/**
			 * Record an info log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function info( $message, $context = array() ) {
				$this->record( 'info', $message, $context );
			}

			/**
			 * Record an error log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function error( $message, $context = array() ) {
				$this->record( 'error', $message, $context );
			}

			/**
			 * Record a warning log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function warning( $message, $context = array() ) {
				$this->record( 'warning', $message, $context );
			}

			/**
			 * Record a notice log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function notice( $message, $context = array() ) {
				$this->record( 'notice', $message, $context );
			}

			/**
			 * Record a debug log entry.
			 *
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			public function debug( $message, $context = array() ) {
				$this->record( 'debug', $message, $context );
			}

			/**
			 * Record a log entry.
			 *
			 * @param string              $level   Log level.
			 * @param string              $message Log message.
			 * @param array<string,mixed> $context Log context.
			 */
			private function record( string $level, string $message, array $context ): void {
				$this->entries[] = array(
					'level'   => $level,
					'message' => $message,
					'context' => $context,
				);
			}
		};
	}

	/**
	 * Assert a route handler supports a method.
	 *
	 * @param array<int,array<string,mixed>> $route_handlers Route handlers.
	 * @param string                         $method         Method constant.
	 */
	private function assertRouteHasMethod( array $route_handlers, string $method ): void {
		foreach ( $route_handlers as $handler ) {
			if ( isset( $handler['methods'][ $method ] ) && true === $handler['methods'][ $method ] ) {
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
			'/wc/v3/payments/deposits'              => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/deposits/summary'      => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/deposits/overview-all' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/deposits/download'     => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/deposits/download/(?P<export_id>.*)' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/deposits/(?P<deposit_id>[\w]+)' => array( WP_REST_Server::READABLE ),
		);
	}
}
