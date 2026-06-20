<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsMoneyMovementOrderService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderDataService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsReportsRestController;
use WC_Order;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the native WooPayments Reports REST controller.
 */
class WooPaymentsReportsRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Recording API client.
	 *
	 * @var RecordingReportsApiClient
	 */
	private RecordingReportsApiClient $api_client;

	/**
	 * Original site timezone.
	 *
	 * @var string
	 */
	private string $original_timezone_string;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client               = new RecordingReportsApiClient();
		$this->original_timezone_string = (string) get_option( 'timezone_string' );
		update_option( 'timezone_string', 'UTC' );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wcpay_get_reporting_balance_summary_request' );
		remove_all_filters( 'wcpay_list_transactions_request' );
		update_option( 'timezone_string', $this->original_timezone_string );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * @testdox Reports routes are registered only when native owns runtime and Reports are enabled.
	 */
	public function test_registers_routes_when_native_owns_runtime_and_reports_are_enabled(): void {
		$controller = $this->create_controller( true, true );
		$controller->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();

		foreach ( $this->get_expected_routes() as $route => $methods ) {
			$this->assertArrayHasKey( $route, $routes );
			foreach ( $methods as $method ) {
				$this->assertRouteHasMethod( $routes[ $route ], $method );
			}
		}

		$controller = $this->create_controller( false, true );
		$controller->register();
		$this->assertFalse( has_action( 'rest_api_init', array( $controller, 'register_routes' ) ) );

		$controller = $this->create_controller( true, false );
		$controller->register();
		$this->assertFalse( has_action( 'rest_api_init', array( $controller, 'register_routes' ) ) );
	}

	/**
	 * @testdox Reports routes require manage_woocommerce before calling the platform API.
	 */
	public function test_routes_require_manage_woocommerce(): void {
		$this->create_controller( true, true )->register_routes();
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/balance' );
		$request->set_query_params(
			array(
				'date_start' => '2026-06-01T00:00:00Z',
				'date_end'   => '2026-06-19T23:59:59Z',
				'currency'   => 'usd',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( array(), $this->api_client->last_call );
	}

	/**
	 * @testdox Balance report forwards date range and lowercase currency to the reporting endpoint.
	 */
	public function test_balance_report_forwards_sanitized_query(): void {
		$this->create_controller( true, true )->register_routes();
		$this->api_client->response = array(
			'currency'                         => 'usd',
			'starting_balance'                 => array(
				'amount' => 1000,
				'count'  => 1,
			),
			'reader_fees'                      => array(
				'amount' => -150,
				'count'  => 1,
			),
			'net_balance_change_in_the_period' => array(
				'amount' => 850,
			),
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/balance' );
		$request->set_query_params(
			array(
				'date_start' => '2026-06-01T00:00:00Z',
				'date_end'   => '2026-06-19T23:59:59Z',
				'currency'   => 'USD',
				'ignored'    => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_reporting_balance_summary', $this->api_client->last_call['method'] );
		$this->assertSame(
			array(
				'date_start' => '2026-06-01T00:00:00Z',
				'date_end'   => '2026-06-19T23:59:59Z',
				'currency'   => 'usd',
			),
			$this->api_client->last_call['query']
		);
		$this->assertSame( $this->api_client->response, $response->get_data() );
		$this->assertSame( -150, $response->get_data()['reader_fees']['amount'] );
		$this->assertSame( 850, $response->get_data()['net_balance_change_in_the_period']['amount'] );
	}

	/**
	 * @testdox Balance report rejects invalid currency before calling the platform API.
	 */
	public function test_balance_report_rejects_invalid_currency(): void {
		$this->create_controller( true, true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/balance' );
		$request->set_query_params(
			array(
				'date_start' => '2026-06-01T00:00:00Z',
				'date_end'   => '2026-06-19T23:59:59Z',
				'currency'   => 'usd1',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( array(), $this->api_client->last_call );
	}

	/**
	 * @testdox Fees list maps Reports filters to transaction filters and strips customer data from rows.
	 */
	public function test_fees_list_maps_filters_and_strips_customer_data(): void {
		$this->create_controller( true, true )->register_routes();
		$this->api_client->response = array(
			'data' => array(
				array(
					'transaction_id'    => 'txn_123',
					'date'              => '2026-06-19T10:00:00Z',
					'payment_intent_id' => 'pi_123',
					'channel'           => 'woocommerce',
					'source'            => 'card',
					'type'              => 'charge',
					'customer_currency' => 'usd',
					'amount'            => 2000,
					'exchange_rate'     => 1,
					'currency'          => 'usd',
					'fees'              => 59,
					'customer'          => array(
						'email' => 'ada@example.com',
					),
					'customer_name'     => 'Ada Lovelace',
					'customer_email'    => 'ada@example.com',
					'customer_country'  => 'US',
					'net'               => 1941,
					'order_id'          => 123,
					'risk_level'        => 'normal',
					'available_on'      => '2026-06-21T00:00:00Z',
					'deposit_id'        => 'po_123',
					'deposit_status'    => 'paid',
				),
			),
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees' );
		$request->set_query_params(
			array(
				'page'                => '2',
				'per_page'            => '50',
				'sort'                => 'date',
				'direction'           => 'desc',
				'date_between'        => array( '2026-06-01T00:00:00Z', '2026-06-19T23:59:59Z' ),
				'payment_method_type' => 'card',
				'type'                => array( 'charge', 'refund' ),
				'search'              => array( 'txn_123' ),
				'user_timezone'       => '+03:00',
				'ignored'             => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_transactions', $this->api_client->last_call['method'] );
		$this->assertSame(
			array(
				'page'              => 2,
				'pagesize'          => 50,
				'sort'              => 'date',
				'direction'         => 'desc',
				'limit'             => 100,
				'source_is'         => 'card',
				'type_is_in'        => array( 'charge', 'refund' ),
				'date_between'      => array( '2026-06-01 03:00:00', '2026-06-20 02:59:59' ),
				'user_timezone'     => '+03:00',
				'transaction_id_is' => 'txn_123',
			),
			$this->api_client->last_call['query']
		);
		$this->assertSame( 'txn_123', $response->get_data()[0]['transaction_id'] );
		$this->assertSame( array( 'type' => 'card' ), $response->get_data()[0]['payment_method'] );
		$this->assertArrayNotHasKey( 'customer', $response->get_data()[0] );
	}

	/**
	 * @testdox Fees list defaults to fee-bearing transaction types and maps payout search to deposit ID.
	 */
	public function test_fees_list_defaults_types_and_maps_payout_search(): void {
		$this->create_controller( true, true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees' );
		$request->set_query_params(
			array(
				'search' => array( 'po_123' ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'po_123', $this->api_client->last_call['query']['deposit_id'] );
		$this->assertSame(
			array(
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
			),
			$this->api_client->last_call['query']['type_is_in']
		);
	}

	/**
	 * @testdox Fees list, summary, and export map legacy order search tokens to charge IDs.
	 */
	public function test_fees_routes_map_order_search_tokens_to_charge_ids(): void {
		$order = $this->create_order_with_charge( 'ch_reports_order' );
		$this->create_controller( true, true )->register_routes();

		$list_request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees' );
		$list_request->set_query_params(
			array(
				'search' => array( __( 'Order #', 'woocommerce' ) . $order->get_id() ),
			)
		);
		$this->server->dispatch( $list_request );

		$this->assertSame( array( 'ch_reports_order' ), $this->api_client->last_call['query']['search'] );

		$summary_request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees/summary' );
		$summary_request->set_query_params(
			array(
				'search' => array( __( 'Order #', 'woocommerce' ) . $order->get_id() ),
			)
		);
		$this->server->dispatch( $summary_request );

		$this->assertSame( array( 'ch_reports_order' ), $this->api_client->last_call['filters']['search'] );

		$export_request = new WP_REST_Request( 'POST', '/wc/v3/payments/reports/fees/download' );
		$export_request->set_query_params(
			array(
				'search' => array( __( 'Order #', 'woocommerce' ) . $order->get_id() ),
			)
		);
		$this->server->dispatch( $export_request );

		$this->assertSame( array( 'ch_reports_order' ), $this->api_client->last_call['filters']['search'] );
	}

	/**
	 * @testdox Fees summary and export routes proxy the existing transactions summary and export APIs.
	 */
	public function test_fees_summary_and_export_routes_proxy_transactions_apis(): void {
		$this->create_controller( true, true )->register_routes();

		$summary_request = new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees/summary' );
		$summary_request->set_query_params(
			array(
				'deposit_id'    => 'po_123',
				'date_after'    => '2026-06-01T00:00:00Z',
				'type'          => array( 'charge' ),
				'user_timezone' => '+03:00',
			)
		);
		$this->server->dispatch( $summary_request );

		$this->assertSame( 'get_transactions_summary', $this->api_client->last_call['method'] );
		$this->assertSame(
			array(
				'type_is_in'    => array( 'charge' ),
				'date_after'    => '2026-06-01 03:00:00',
				'user_timezone' => '+03:00',
			),
			$this->api_client->last_call['filters']
		);
		$this->assertSame( 'po_123', $this->api_client->last_call['deposit_id'] );

		$export_request = new WP_REST_Request( 'POST', '/wc/v3/payments/reports/fees/download' );
		$export_request->set_query_params(
			array(
				'deposit_id'    => 'po_123',
				'date_before'   => '2026-06-19T23:59:59Z',
				'type'          => array( 'refund' ),
				'user_timezone' => '+03:00',
				'user_email'    => 'merchant@example.com',
				'locale'        => 'en_US',
			)
		);
		$this->server->dispatch( $export_request );

		$this->assertSame( 'get_transactions_export', $this->api_client->last_call['method'] );
		$this->assertSame(
			array(
				'type_is_in'    => array( 'refund' ),
				'date_before'   => '2026-06-20 02:59:59',
				'user_timezone' => '+03:00',
			),
			$this->api_client->last_call['filters']
		);
		$this->assertSame( 'po_123', $this->api_client->last_call['deposit_id'] );
		$this->assertSame( 'merchant@example.com', $this->api_client->last_call['user_email'] );
		$this->assertSame( 'en_US', $this->api_client->last_call['locale'] );

		$this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees/download/export-123' ) );

		$this->assertSame( 'get_transactions_export_url', $this->api_client->last_call['method'] );
		$this->assertSame( 'export-123', $this->api_client->last_call['export_id'] );
	}

	/**
	 * @testdox Reports routes preserve platform exception status codes.
	 */
	public function test_reports_routes_preserve_platform_exception_status(): void {
		$this->create_controller( true, true )->register_routes();
		$this->api_client->exception = new WooPaymentsApiException( 'Provider unavailable.', 'wcpay_provider_unavailable', 503 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/reports/fees' ) );

		$this->assertSame( 503, $response->get_status() );
		$this->assertSame( 'wcpay_provider_unavailable', $response->get_data()['code'] );
	}

	/**
	 * Create a native Reports REST controller.
	 *
	 * @param bool $native_register Whether native should own route registration.
	 * @param bool $reports_enabled Whether Reports are enabled.
	 * @return WooPaymentsReportsRestController
	 */
	private function create_controller( bool $native_register, bool $reports_enabled ): WooPaymentsReportsRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_reports_enabled' ) )
			->getMock();
		$account_service->method( 'is_reports_enabled' )->willReturn( $reports_enabled );

		$controller = new WooPaymentsReportsRestController();
		$controller->init( $arbiter, $this->api_client, $account_service, $this->create_order_service() );

		return $controller;
	}

	/**
	 * Create an order search mapping service.
	 *
	 * @return WooPaymentsMoneyMovementOrderService
	 */
	private function create_order_service(): WooPaymentsMoneyMovementOrderService {
		$service = new WooPaymentsMoneyMovementOrderService();
		$service->init( new WooPaymentsOrderDataService() );

		return $service;
	}

	/**
	 * Create an order with WooPayments charge metadata.
	 *
	 * @param string $charge_id Charge ID.
	 * @return WC_Order
	 */
	private function create_order_with_charge( string $charge_id ): WC_Order {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );

		$order->set_currency( 'USD' );
		$order->update_meta_data( '_charge_id', $charge_id );
		$order->save();

		return $order;
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
			'/wc/v3/payments/reports/balance'       => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/reports/fees'          => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/reports/fees/summary'  => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/reports/fees/download' => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/reports/fees/download/(?P<export_id>[^/\\\\%]+)' => array( WP_REST_Server::READABLE ),
		);
	}
}
