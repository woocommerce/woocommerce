<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputesRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFraudOutcomeTransactionsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsMoneyMovementOrderService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderDataService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTransactionsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsTransactionsRestController;
use RuntimeException;
use WC_Order;
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
		remove_all_filters( 'wcpay_list_fraud_outcome_transactions_request' );
		remove_all_filters( 'wcpay_list_fraud_outcome_transactions_summary_request' );
		remove_all_filters( 'wcpay_get_fraud_outcome_transactions_search_autocomplete_request' );
		remove_all_filters( 'wcpay_get_fraud_outcome_transactions_export_request' );
		remove_all_filters( 'woocommerce_logging_class' );
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
	 * @testdox Transactions list maps order search tokens to charge IDs and adds legacy order context.
	 */
	public function test_transactions_list_maps_order_search_and_enriches_response(): void {
		$order = $this->create_order_with_charge( 'ch_order', 'pi_order' );

		$this->api_client->response = array(
			'data' => array(
				array(
					'id'        => 'txn_order',
					'charge_id' => 'ch_order',
				),
				array(
					'id'        => 'txn_missing',
					'charge_id' => 'ch_missing',
				),
			),
		);
		$this->create_transactions_controller( true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions' );
		$request->set_query_params(
			array(
				'search' => array( __( 'Order #', 'woocommerce' ) . $order->get_id() ),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'ch_order' ), $this->api_client->last_call['query']['search'] );
		$this->assertSame( $order->get_order_number(), $data['data'][0]['order']['number'] );
		$this->assertSame( 'pi_order', $data['data'][0]['payment_intent_id'] );
		$this->assertArrayNotHasKey( 'order', $data['data'][1] );
	}

	/**
	 * @testdox Transactions summary and export use the preserved list filter normalization.
	 */
	public function test_transactions_summary_and_export_normalize_reference_filters(): void {
		$this->create_transactions_controller( true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/summary' );
		$request->set_query_params(
			array(
				'page'              => '1',
				'store_currency_is' => 'eur',
				'date_after'        => '2026-06-18 10:00:00',
				'user_timezone'     => 'UTC',
				'ignored'           => 'drop-me',
				'deposit_id'        => 'po_test',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_transactions_summary', $this->api_client->last_call['method'] );
		$this->assertArrayHasKey( 'date_after', $this->api_client->last_call['filters'] );
		$this->assertSame( 'eur', $this->api_client->last_call['filters']['store_currency_is'] );
		$this->assertArrayNotHasKey( 'ignored', $this->api_client->last_call['filters'] );
		$this->assertSame( 'po_test', $this->api_client->last_call['deposit_id'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/transactions/download' );
		$request->set_body_params(
			array(
				'store_currency_is' => 'usd',
				'type_is_in'        => array( 'charge', 'refund' ),
				'user_email'        => 'merchant@example.com',
				'locale'            => 'en_US',
				'ignored'           => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_transactions_export', $this->api_client->last_call['method'] );
		$this->assertSame( 'usd', $this->api_client->last_call['filters']['store_currency_is'] );
		$this->assertSame( array( 'charge', 'refund' ), $this->api_client->last_call['filters']['type_is_in'] );
		$this->assertArrayNotHasKey( 'ignored', $this->api_client->last_call['filters'] );
		$this->assertSame( 'merchant@example.com', $this->api_client->last_call['user_email'] );
		$this->assertSame( 'en_US', $this->api_client->last_call['locale'] );
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
	 * @testdox Fraud outcome routes reject requests without a valid status.
	 */
	public function test_fraud_outcome_routes_reject_missing_status(): void {
		$this->create_transactions_controller( true )->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'invalid_fraud_outcome_status', $response->get_data()['code'] );
	}

	/**
	 * @testdox Fraud outcome routes preserve legacy local order-derived data.
	 */
	public function test_fraud_outcome_routes_preserve_legacy_order_data(): void {
		$order = $this->create_order_with_charge( 'ch_review', 'pi_order_meta' );
		$order->set_total( 10.50 );
		$order->update_meta_data( '_intention_status', 'requires_capture' );
		$order->update_meta_data( '_wcpay_fraud_meta_box_type', 'review' );
		$order->save();

		$this->api_client->response = array(
			array(
				'order_id'          => $order->get_id(),
				'payment_intent_id' => 'pi_platform',
				'created'           => 123,
			),
		);
		$this->create_transactions_controller( true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes' );
		$request->set_query_params( array( 'status' => 'review' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_fraud_outcomes', $this->api_client->last_call['method'] );
		$this->assertSame( 'review', $this->api_client->last_call['query']['status'] );
		$this->assertSame( $order->get_id(), $data['data'][0]['order_id'] );
		$this->assertSame( 1050, $data['data'][0]['amount'] );
		$this->assertSame( 'USD', $data['data'][0]['currency'] );
		$this->assertSame( 'pi_platform', $data['data'][0]['payment_intent']['id'] );
		$this->assertSame( 'requires_capture', $data['data'][0]['payment_intent']['status'] );
		$this->assertArrayNotHasKey( 'manual_review', $data['data'][0] );

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes/summary' );
		$request->set_query_params( array( 'status' => 'review' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1, $response->get_data()['count'] );
		$this->assertSame( 1050, $response->get_data()['total'] );
		$this->assertSame( array( 'usd' ), $response->get_data()['currencies'] );

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes/search' );
		$request->set_query_params(
			array(
				'status'      => 'review',
				'search_term' => (string) $order->get_id(),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'order-' . $order->get_id(), $response->get_data()[0]['key'] );
		$this->assertSame( __( 'Order #', 'woocommerce' ) . $order->get_id(), $response->get_data()[0]['label'] );

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes/download' );
		$request->set_query_params( array( 'status' => 'review' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $order->get_id(), $response->get_data()['data'][0]['order_id'] );
	}

	/**
	 * @testdox Fraud outcome routes preserve legacy request hooks and cap page size.
	 */
	public function test_fraud_outcome_routes_preserve_legacy_request_hooks_and_cap_page_size(): void {
		$this->create_transactions_controller( true )->register_routes();
		$hooks_seen = array();
		$hooks      = array(
			'wcpay_list_fraud_outcome_transactions_request'                  => '/wc/v3/payments/transactions/fraud-outcomes',
			'wcpay_list_fraud_outcome_transactions_summary_request'          => '/wc/v3/payments/transactions/fraud-outcomes/summary',
			'wcpay_get_fraud_outcome_transactions_search_autocomplete_request' => '/wc/v3/payments/transactions/fraud-outcomes/search',
			'wcpay_get_fraud_outcome_transactions_export_request'            => '/wc/v3/payments/transactions/fraud-outcomes/download',
		);

		foreach ( array_keys( $hooks ) as $hook ) {
			add_filter(
				$hook,
				static function ( WooPaymentsFraudOutcomeTransactionsListRequest $request ) use ( &$hooks_seen, $hook ): WooPaymentsFraudOutcomeTransactionsListRequest {
					$hooks_seen[] = $hook;
					$request->set_page_size( 999 );
					$request->set_search( array( 'Ada' ) );

					return $request;
				}
			);
		}

		foreach ( $hooks as $hook => $route ) {
			$request = new WP_REST_Request( 'GET', $route );
			$request->set_query_params( array( 'status' => 'allow' ) );

			$response = $this->server->dispatch( $request );

			$this->assertSame( 200, $response->get_status(), $hook . ' route should remain successful' );
			$this->assertSame( $hook, end( $hooks_seen ) );
			$this->assertSame( 100, $this->api_client->last_call['query']['pagesize'] );
			$this->assertSame( array( 'Ada' ), $this->api_client->last_call['query']['search'] );
		}
	}

	/**
	 * @testdox Fraud outcome enrichment truncates oversized platform responses and logs the degraded path.
	 */
	public function test_fraud_outcome_routes_cap_local_enrichment_rows(): void {
		$order_service = new class() extends WooPaymentsMoneyMovementOrderService {
			/**
			 * Number of rows received by local formatting.
			 *
			 * @var int
			 */
			public int $row_count = 0;

			/**
			 * Format raw fraud outcome platform rows with local WooCommerce order context.
			 *
			 * @param array<string|int,mixed> $response Platform response.
			 * @param array<string,mixed>     $params   Request params.
			 * @return array<int,array<string,mixed>>
			 */
			public function format_fraud_outcome_transactions( array $response, array $params ): array {
				$rows            = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : $response;
				$this->row_count = count( $rows );

				return array();
			}
		};
		$this->create_transactions_controller( true, $order_service )->register_routes();
		$logger = $this->create_recording_logger();
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ): object {
				return $logger;
			}
		);
		$this->api_client->response = array(
			'data' => array_fill( 0, 1001, array( 'order_id' => 1 ) ),
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/transactions/fraud-outcomes' );
		$request->set_query_params( array( 'status' => 'allow' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 1000, $order_service->row_count );
		$this->assertSame( 'warning', $logger->entries[0]['level'] );
		$this->assertSame( 'woopayments-fraud-outcomes', $logger->entries[0]['context']['source'] );
		$this->assertSame( 1001, $logger->entries[0]['context']['rows'] );
		$this->assertSame( 1000, $logger->entries[0]['context']['max_rows'] );
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
	 * @testdox Disputes list adds legacy compact order context and keeps missing orders explicit.
	 */
	public function test_disputes_list_enriches_order_context(): void {
		$order = $this->create_order_with_charge( 'ch_dispute', 'pi_dispute' );

		$this->api_client->response = array(
			'data' => array(
				array(
					'dispute_id' => 'dp_order',
					'charge_id'  => 'ch_dispute',
				),
				array(
					'dispute_id' => 'dp_missing',
					'charge_id'  => 'ch_missing',
				),
			),
		);
		$this->create_disputes_controller( true )->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/disputes' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $order->get_order_number(), $data['data'][0]['order']['number'] );
		$this->assertNull( $data['data'][1]['order'] );
	}

	/**
	 * @testdox Disputes summary and export map reference REST filters to platform filter names.
	 */
	public function test_disputes_summary_and_export_map_reference_filters(): void {
		$this->create_disputes_controller( true )->register_routes();

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/disputes/summary' );
		$request->set_query_params(
			array(
				'store_currency_is' => 'gbp',
				'date_between'      => array( '2026-06-01', '2026-06-18' ),
				'status_is_not'     => 'won',
				'ignored'           => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_disputes_summary', $this->api_client->last_call['method'] );
		$this->assertSame( 'gbp', $this->api_client->last_call['filters']['currency_is'] );
		$this->assertSame( array( '2026-06-01', '2026-06-18' ), $this->api_client->last_call['filters']['created_between'] );
		$this->assertSame( 'won', $this->api_client->last_call['filters']['status_is_not'] );
		$this->assertArrayNotHasKey( 'ignored', $this->api_client->last_call['filters'] );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/download' );
		$request->set_body_params(
			array(
				'store_currency_is' => 'usd',
				'date_before'       => '2026-06-18',
				'user_email'        => 'merchant@example.com',
				'locale'            => 'en_US',
				'ignored'           => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_disputes_export', $this->api_client->last_call['method'] );
		$this->assertSame( 'usd', $this->api_client->last_call['filters']['currency_is'] );
		$this->assertSame( '2026-06-18', $this->api_client->last_call['filters']['created_before'] );
		$this->assertArrayNotHasKey( 'ignored', $this->api_client->last_call['filters'] );
		$this->assertSame( 'merchant@example.com', $this->api_client->last_call['user_email'] );
		$this->assertSame( 'en_US', $this->api_client->last_call['locale'] );
	}

	/**
	 * @testdox Dispute update and close forward preserved payloads.
	 */
	public function test_dispute_update_and_close_forward_payloads(): void {
		$this->create_disputes_controller( true )->register_routes();
		$order = $this->create_order_with_charge( 'ch_dispute', 'pi_dispute' );

		$this->api_client->response = array(
			'id'     => 'dp_test',
			'charge' => array(
				'id'              => 'ch_dispute',
				'billing_details' => array(
					'address' => array(
						'line1'       => '1 Main Street',
						'city'        => 'San Francisco',
						'state'       => 'CA',
						'postal_code' => '94105',
						'country'     => 'US',
					),
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test' );
		$request->set_body_params(
			array(
				'evidence' => array( 'customer_name' => 'Ada' ),
				'submit'   => 'true',
				'metadata' => array( 'order_id' => 123 ),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'update_dispute', $this->api_client->last_call['method'] );
		$this->assertSame( 'dp_test', $this->api_client->last_call['dispute_id'] );
		$this->assertSame( array( 'customer_name' => 'Ada' ), $this->api_client->last_call['evidence'] );
		$this->assertTrue( $this->api_client->last_call['submit'] );
		$this->assertSame( array( 'order_id' => 123 ), $this->api_client->last_call['metadata'] );
		$this->assertSame( $order->get_id(), $data['order']['id'] );
		$this->assertStringContainsString( '1 Main Street', $data['charge']['billing_details']['formatted_address'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test/close' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'close_dispute', $this->api_client->last_call['method'] );
		$this->assertSame( 'dp_test', $this->api_client->last_call['dispute_id'] );
		$this->assertSame( $order->get_id(), $data['order']['id'] );
	}

	/**
	 * @testdox Dispute update forwards draft evidence clearing fields unchanged.
	 */
	public function test_dispute_update_forwards_draft_evidence_clearing_fields(): void {
		$this->create_disputes_controller( true )->register_routes();

		$this->api_client->response = array(
			'id' => 'dp_test',
		);

		$evidence = array(
			'receipt'                  => '',
			'customer_communication'   => '',
			'shipping_carrier'         => '',
			'shipping_tracking_number' => '',
			'product_description'      => 'Physical goods shipped to the customer.',
		);
		$metadata = array(
			'__product_type' => 'physical_product',
		);
		$request  = new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test' );
		$request->set_body_params(
			array(
				'evidence' => $evidence,
				'submit'   => 'false',
				'metadata' => $metadata,
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'update_dispute', $this->api_client->last_call['method'] );
		$this->assertSame( $evidence, $this->api_client->last_call['evidence'] );
		$this->assertFalse( $this->api_client->last_call['submit'] );
		$this->assertSame( $metadata, $this->api_client->last_call['metadata'] );
	}

	/**
	 * @testdox Dispute detail adds legacy detail order context and formatted charge addresses.
	 */
	public function test_dispute_detail_enriches_order_and_charge_address(): void {
		$order = $this->create_order_with_charge( 'ch_detail', 'pi_detail' );

		$this->api_client->response = array(
			'id'     => 'dp_detail',
			'charge' => array(
				'id'              => 'ch_detail',
				'billing_details' => array(
					'address' => array(
						'line1'       => '2 Market Street',
						'city'        => 'San Francisco',
						'state'       => 'CA',
						'postal_code' => '94105',
						'country'     => 'US',
					),
				),
			),
		);
		$this->create_disputes_controller( true )->register_routes();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/disputes/dp_detail' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $order->get_id(), $data['order']['id'] );
		$this->assertSame( 'physical_product', $data['order']['suggested_product_type'] );
		$this->assertStringContainsString( '2 Market Street', $data['charge']['billing_details']['formatted_address'] );
	}

	/**
	 * @testdox Dispute update logs a safe audit trail on success.
	 */
	public function test_dispute_update_logs_success_audit_trail(): void {
		$this->create_disputes_controller( true )->register_routes();
		$logger = $this->create_recording_logger();
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ): object {
				return $logger;
			}
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test' );
		$request->set_body_params(
			array(
				'evidence' => array( 'customer_name' => 'Ada' ),
				'submit'   => 'true',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $logger->entries );
		$this->assertSame( 'info', $logger->entries[0]['level'] );
		$this->assertSame( 'woopayments-disputes', $logger->entries[0]['context']['source'] );
		$this->assertSame( 'update', $logger->entries[0]['context']['action'] );
		$this->assertSame( 'dp_test', $logger->entries[0]['context']['dispute_id'] );
		$this->assertTrue( $logger->entries[0]['context']['submit'] );
		$this->assertSame( get_current_user_id(), $logger->entries[0]['context']['user_id'] );
		$this->assertSame( 'info', $logger->entries[1]['level'] );
		$this->assertStringContainsString( 'completed', $logger->entries[1]['message'] );
	}

	/**
	 * @testdox Dispute update does not hide a completed platform mutation when local enrichment fails.
	 */
	public function test_dispute_update_returns_platform_response_when_completed_action_enrichment_fails(): void {
		$order_service = new class() extends WooPaymentsMoneyMovementOrderService {
			/**
			 * Add order context and formatted charge address to a dispute response.
			 *
			 * @param array<string,mixed> $dispute Platform dispute.
			 * @return array<string,mixed>
			 */
			public function enrich_dispute_response( array $dispute ): array {
				if ( is_array( $dispute ) ) {
					throw new RuntimeException( 'enrichment failed' );
				}

				return $dispute;
			}
		};
		$this->create_disputes_controller( true, $order_service )->register_routes();
		$logger = $this->create_recording_logger();
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ): object {
				return $logger;
			}
		);
		$this->api_client->response = array( 'id' => 'dp_test' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test' );
		$request->set_body_params( array( 'submit' => 'false' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'dp_test', $data['id'] );
		$this->assertNull( $data['order'] );
		$this->assertCount( 3, $logger->entries );
		$this->assertStringContainsString( 'completed', $logger->entries[1]['message'] );
		$this->assertSame( 'warning', $logger->entries[2]['level'] );
		$this->assertStringContainsString( 'local order context enrichment failed', $logger->entries[2]['message'] );
		$this->assertSame( RuntimeException::class, $logger->entries[2]['context']['exception'] );
	}

	/**
	 * @testdox Dispute close logs safe upstream failure context.
	 */
	public function test_dispute_close_logs_failure_audit_trail(): void {
		$this->create_disputes_controller( true )->register_routes();
		$logger = $this->create_recording_logger();
		add_filter(
			'woocommerce_logging_class',
			static function () use ( $logger ): object {
				return $logger;
			}
		);
		$this->api_client->exception = new WooPaymentsApiException( 'Ambiguous dispute failure.', 'ambiguous_failure', 504 );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test/close' ) );

		$this->assertSame( 504, $response->get_status() );
		$this->assertCount( 2, $logger->entries );
		$this->assertSame( 'error', $logger->entries[1]['level'] );
		$this->assertStringContainsString( 'failed', $logger->entries[1]['message'] );
		$this->assertSame( 'woopayments-disputes', $logger->entries[1]['context']['source'] );
		$this->assertSame( 'close', $logger->entries[1]['context']['action'] );
		$this->assertSame( 'dp_test', $logger->entries[1]['context']['dispute_id'] );
		$this->assertSame( 'ambiguous_failure', $logger->entries[1]['context']['api_code'] );
		$this->assertSame( 504, $logger->entries[1]['context']['http_status'] );
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
	 * @param bool                                      $native_register Whether native should own routes.
	 * @param WooPaymentsMoneyMovementOrderService|null $order_service   Optional order service.
	 * @return WooPaymentsTransactionsRestController
	 */
	private function create_transactions_controller( bool $native_register, ?WooPaymentsMoneyMovementOrderService $order_service = null ): WooPaymentsTransactionsRestController {
		$controller = new WooPaymentsTransactionsRestController();
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client, $order_service ?? $this->create_order_service() );

		return $controller;
	}

	/**
	 * Create a disputes controller.
	 *
	 * @param bool                                      $native_register Whether native should own routes.
	 * @param WooPaymentsMoneyMovementOrderService|null $order_service   Optional order service.
	 * @return WooPaymentsDisputesRestController
	 */
	private function create_disputes_controller( bool $native_register, ?WooPaymentsMoneyMovementOrderService $order_service = null ): WooPaymentsDisputesRestController {
		$controller = new WooPaymentsDisputesRestController();
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client, $order_service ?? $this->create_order_service() );

		return $controller;
	}

	/**
	 * Create an order enrichment service.
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
	 * @param string $intent_id Intent ID.
	 * @return WC_Order
	 */
	private function create_order_with_charge( string $charge_id, string $intent_id ): WC_Order {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );

		$order->set_currency( 'USD' );
		$order->set_billing_first_name( 'Ada' );
		$order->set_billing_last_name( 'Lovelace' );
		$order->set_billing_email( 'ada@example.com' );
		$order->set_customer_ip_address( '127.0.0.1' );
		$order->update_meta_data( '_charge_id', $charge_id );
		$order->update_meta_data( '_intent_id', $intent_id );
		$order->save();

		return $order;
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
}
