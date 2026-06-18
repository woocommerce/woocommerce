<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputesRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTransactionsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTransactionsRestController;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for native WooPayments money movement REST controllers.
 */
class WooPaymentsMoneyMovementRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * Recording API client.
	 *
	 * @var RecordingMoneyMovementApiClient
	 */
	private RecordingMoneyMovementApiClient $api_client;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client = new RecordingMoneyMovementApiClient();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wcpay_list_transactions_request' );
		remove_all_filters( 'wcpay_list_disputes_request' );
		parent::tearDown();
	}

	/**
	 * @testdox Transactions routes register only when native owns runtime.
	 */
	public function test_transactions_routes_register_only_when_native_owns_runtime(): void {
		$controller = $this->create_transactions_controller( true );
		$controller->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/payments/transactions', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/transactions/summary', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/transactions/search', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/transactions/(?P<transaction_id>\\w+)', $routes );

		$controller = $this->create_transactions_controller( false );
		$controller->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $controller, 'register_routes' ) ) );
	}

	/**
	 * @testdox Transactions list preserves reference query names and the legacy request filter.
	 */
	public function test_transactions_list_preserves_filter_contract(): void {
		$this->create_transactions_controller( true )->register_routes();

		add_filter(
			'wcpay_list_transactions_request',
			static function ( WooPaymentsTransactionsListRequest $request ): WooPaymentsTransactionsListRequest {
				$request->set_page_size( 50 );
				$request->set_deposit_id( 'po_filtered' );

				return $request;
			}
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions' );
		$request->set_query_params(
			array(
				'page'              => '2',
				'pagesize'          => '25',
				'store_currency_is' => 'usd',
				'type_is_in'        => array( 'charge', 'refund' ),
				'search'            => array( 'Ada' ),
				'ignored'           => 'drop-me',
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
				'type_is_in'        => array( 'charge', 'refund' ),
				'store_currency_is' => 'usd',
				'search'            => array( 'Ada' ),
				'deposit_id'        => 'po_filtered',
			),
			$this->api_client->last_call['query']
		);
	}

	/**
	 * @testdox Transactions routes require manage_woocommerce before API calls.
	 */
	public function test_transactions_routes_require_manage_woocommerce(): void {
		$this->create_transactions_controller( true )->register_routes();
		wp_set_current_user( 0 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/transactions' ) );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
		$this->assertSame( array(), $this->api_client->last_call );
	}

	/**
	 * @testdox Fraud outcome routes fail closed until native support exists.
	 */
	public function test_fraud_outcome_routes_fail_closed(): void {
		$this->create_transactions_controller( true )->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes' ) );

		$this->assertSame( 501, $response->get_status() );
		$this->assertSame( 'wcpay_native_fraud_outcomes_unavailable', $response->get_data()['code'] );
	}

	/**
	 * @testdox Disputes list maps reference REST filters to platform filter names.
	 */
	public function test_disputes_list_maps_reference_filters(): void {
		$this->create_disputes_controller( true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/disputes' );
		$request->set_query_params(
			array(
				'page'              => '1',
				'pagesize'          => '25',
				'store_currency_is' => 'usd',
				'date_before'       => '2026-06-18',
				'status_is'         => 'needs_response',
				'ignored'           => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_disputes', $this->api_client->last_call['method'] );
		$this->assertSame(
			array(
				'page'           => 1,
				'pagesize'       => 25,
				'sort'           => 'created',
				'direction'      => 'desc',
				'limit'          => 100,
				'currency_is'    => 'usd',
				'created_before' => '2026-06-18',
				'status_is'      => 'needs_response',
			),
			$this->api_client->last_call['filters']
		);
	}

	/**
	 * @testdox Dispute update and close forward preserved payloads.
	 */
	public function test_dispute_update_and_close_forward_payloads(): void {
		$this->create_disputes_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test' );
		$request->set_body_params(
			array(
				'evidence' => array( 'customer_name' => 'Ada' ),
				'submit'   => 'true',
				'metadata' => array( 'order_id' => 123 ),
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'update_dispute', $this->api_client->last_call['method'] );
		$this->assertSame( 'dp_test', $this->api_client->last_call['dispute_id'] );
		$this->assertSame( array( 'customer_name' => 'Ada' ), $this->api_client->last_call['evidence'] );
		$this->assertTrue( $this->api_client->last_call['submit'] );
		$this->assertSame( array( 'order_id' => 123 ), $this->api_client->last_call['metadata'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test/close' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'close_dispute', $this->api_client->last_call['method'] );
		$this->assertSame( 'dp_test', $this->api_client->last_call['dispute_id'] );
	}

	/**
	 * @testdox API exceptions carry their HTTP status into REST responses.
	 */
	public function test_api_exceptions_preserve_status(): void {
		$this->create_disputes_controller( true )->register_routes();
		$this->api_client->exception = new WooPaymentsApiException( 'Missing dispute.', 'resource_missing', 404 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/disputes/dp_missing' ) );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'resource_missing', $response->get_data()['code'] );
	}

	/**
	 * Create a transactions controller.
	 *
	 * @param bool $native_register Whether native should own routes.
	 * @return WooPaymentsTransactionsRestController
	 */
	private function create_transactions_controller( bool $native_register ): WooPaymentsTransactionsRestController {
		$controller = new WooPaymentsTransactionsRestController();
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client );

		return $controller;
	}

	/**
	 * Create a disputes controller.
	 *
	 * @param bool $native_register Whether native should own routes.
	 * @return WooPaymentsDisputesRestController
	 */
	private function create_disputes_controller( bool $native_register ): WooPaymentsDisputesRestController {
		$controller = new WooPaymentsDisputesRestController();
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client );

		return $controller;
	}

	/**
	 * Create a runtime arbiter stub.
	 *
	 * @param bool $native_register Whether native should own routes.
	 * @return NativePaymentsRuntimeArbiter
	 */
	private function create_arbiter( bool $native_register ): NativePaymentsRuntimeArbiter {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		return $arbiter;
	}
}
