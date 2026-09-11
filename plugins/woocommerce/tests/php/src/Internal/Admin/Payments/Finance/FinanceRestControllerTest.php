<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataException;
use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataQuery;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceDataSchemas;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceRestController;
use Automattic\WooCommerce\Internal\Admin\Payments\Finance\FinanceService;
use Automattic\WooCommerce\Tests\Internal\Admin\Payments\Finance\Mocks\FakeLogger;
use PHPUnit\Framework\MockObject\MockObject;
use WC_Unit_Test_Case;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Tests for the FinanceRestController class.
 */
class FinanceRestControllerTest extends WC_Unit_Test_Case {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	private const ENDPOINT = '/wc-admin/payments/finance';

	/**
	 * The System Under Test.
	 *
	 * @var FinanceRestController
	 */
	private FinanceRestController $sut;

	/**
	 * The mocked finance service.
	 *
	 * @var MockObject|FinanceService
	 */
	private $mock_service;

	/**
	 * REST server used by the controller tests.
	 *
	 * @var WP_REST_Server
	 */
	private WP_REST_Server $server;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->mock_service = $this->getMockBuilder( FinanceService::class )->getMock();

		$this->sut = new FinanceRestController();
		$this->sut->init( $this->mock_service, new FinanceDataSchemas() );
		$this->server = $this->create_rest_server_with_routes(
			array(
				function () {
					$this->sut->register_routes( true );
				},
			),
			true
		);
	}

	/**
	 * Tear down the REST server.
	 */
	public function tearDown(): void {
		$this->clear_rest_server();
		parent::tearDown();
	}

	/**
	 * @testdox Should register the providers, balance and payouts routes as GET only.
	 */
	public function test_registers_readable_routes(): void {
		$routes = $this->server->get_routes( 'wc-admin' );

		foreach ( array( '/providers', '/providers/(?P<gateway_id>[a-zA-Z0-9_-]+)/balance', '/providers/(?P<gateway_id>[a-zA-Z0-9_-]+)/payouts' ) as $route ) {
			$this->assertArrayHasKey( self::ENDPOINT . $route, $routes );
			$this->assertSame( array( 'GET' => true ), $routes[ self::ENDPOINT . $route ][0]['methods'], "$route should only accept GET." );
		}
	}

	/**
	 * @testdox Should return the providers payload from the service.
	 */
	public function test_get_providers_returns_service_payload(): void {
		$payload = array( 'providers' => array( array( 'provider_id' => 'mock' ) ) );
		$this->mock_service->expects( $this->once() )
			->method( 'get_providers' )
			->willReturn( $payload );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $payload, $response->get_data() );
	}

	/**
	 * @testdox Should refuse users without the manage_woocommerce capability.
	 */
	public function test_refuses_users_without_capability(): void {
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => false );
		add_filter( 'user_has_cap', $filter_callback );
		$this->mock_service->expects( $this->never() )
			->method( 'get_providers' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' ) );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_view', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should pass the gateway id from the path to the balances service method.
	 */
	public function test_get_balances_passes_gateway_id(): void {
		$envelope = array(
			'schema_version' => 1,
			'items'          => array(),
			'has_more'       => false,
			'next_cursor'    => null,
			'prev_cursor'    => null,
		);
		$this->mock_service->expects( $this->once() )
			->method( 'get_balances' )
			->with( 'mock-gw_1' )
			->willReturn( $envelope );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ENDPOINT . '/providers/mock-gw_1/balance' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $envelope, $response->get_data() );
	}

	/**
	 * @testdox Should build the payouts query from the next_cursor, prev_cursor and per_page parameters.
	 */
	public function test_get_payouts_builds_query_from_parameters(): void {
		$this->mock_service->expects( $this->once() )
			->method( 'get_payouts' )
			->with(
				'mock',
				$this->callback( fn( FinanceDataQuery $query ) => 'to-next' === $query->get_next_cursor() && 'to-prev' === $query->get_prev_cursor() && 5 === $query->get_per_page() )
			)
			->willReturn( array( 'items' => array() ) );
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers/mock/payouts' );
		$request->set_query_params(
			array(
				'next_cursor' => 'to-next',
				'prev_cursor' => 'to-prev',
				'per_page'    => '5',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * @testdox Should default the payouts query to the first page with the default page size.
	 */
	public function test_get_payouts_defaults_query(): void {
		$this->mock_service->expects( $this->once() )
			->method( 'get_payouts' )
			->with(
				'mock',
				$this->callback( fn( FinanceDataQuery $query ) => null === $query->get_next_cursor() && null === $query->get_prev_cursor() && FinanceDataQuery::DEFAULT_PER_PAGE === $query->get_per_page() )
			)
			->willReturn( array( 'items' => array() ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ENDPOINT . '/providers/mock/payouts' ) );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * @testdox Should reject a per_page value outside 1-100 or not an integer.
	 *
	 * @testWith ["0"]
	 *           ["101"]
	 *           ["abc"]
	 *
	 * @param string $per_page The per page.
	 */
	public function test_get_payouts_rejects_invalid_per_page( string $per_page ): void {
		$this->mock_service->expects( $this->never() )->method( 'get_payouts' );
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers/mock/payouts' );
		$request->set_query_params( array( 'per_page' => $per_page ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should reject a cursor longer than 2048 characters.
	 *
	 * @testWith ["next_cursor"]
	 *           ["prev_cursor"]
	 *
	 * @param string $parameter The cursor parameter.
	 */
	public function test_get_payouts_rejects_overlong_cursor( string $parameter ): void {
		$this->mock_service->expects( $this->never() )->method( 'get_payouts' );
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/providers/mock/payouts' );
		$request->set_query_params( array( $parameter => str_repeat( 'a', 2049 ) ) );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should turn a finance data exception into a prefixed error with its status.
	 */
	public function test_maps_finance_data_exception_to_error_response(): void {
		$this->mock_service->method( 'get_balances' )->willThrowException( new FinanceDataException( 'No provider.', 'provider_not_found', 404 ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ENDPOINT . '/providers/mock/balance' ) );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_payments_finance_provider_not_found', $response->get_data()['code'] );
		$this->assertSame( 'No provider.', $response->get_data()['message'] );
	}

	/**
	 * @testdox Should report unexpected exceptions as a logged internal error.
	 */
	public function test_reports_unexpected_exceptions_as_internal_error(): void {
		$logger = new FakeLogger();
		add_filter( 'woocommerce_logging_class', fn() => $logger );
		$this->mock_service->method( 'get_providers' )->willThrowException( new \RuntimeException( 'boom' ) );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ENDPOINT . '/providers' ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_internal_error', $response->get_data()['code'] );
		$this->assertCount( 1, $logger->get_messages( 'error' ) );
	}
}
