<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\PaymentProcessingService;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAuthorizationsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAuthorizationsRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputeCacheService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDisputesRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsFraudOutcomeTransactionsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsMoneyMovementOrderService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderDataService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsPaymentDetailsRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
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
	 * Test refund gateway initializer callback.
	 *
	 * @var callable|null
	 */
	private $refund_gateway_initializer = null;

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
		remove_all_filters( 'wcpay_list_authorizations_request' );
		remove_all_filters( 'wcpay_list_disputes_request' );
		remove_all_filters( 'wcpay_list_fraud_outcome_transactions_request' );
		remove_all_filters( 'wcpay_list_fraud_outcome_transactions_summary_request' );
		remove_all_filters( 'wcpay_get_fraud_outcome_transactions_search_autocomplete_request' );
		remove_all_filters( 'wcpay_get_fraud_outcome_transactions_export_request' );
		remove_all_filters( 'woocommerce_logging_class' );
		delete_option( 'wcpay_dispute_status_counts_cache' );
		delete_option( 'wcpay_test_dispute_status_counts_cache' );
		delete_option( 'wcpay_active_dispute_cache' );
		if ( null !== $this->refund_gateway_initializer ) {
			remove_action( 'wc_payment_gateways_initialized', $this->refund_gateway_initializer, 100 );
			$this->refund_gateway_initializer = null;
		}
		if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
			WC()->payment_gateways()->payment_gateways = array();
			WC()->payment_gateways()->init();
		}
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
	 * @testdox Authorization routes register only when native owns runtime.
	 */
	public function test_authorization_routes_register_only_when_native_owns_runtime(): void {
		$controller = $this->create_authorizations_controller( true );
		$controller->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/payments/authorizations', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/authorizations/summary', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/authorizations/(?P<payment_intent_id>\\w+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/orders/(?P<order_id>\\w+)/capture_authorization', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/orders/(?P<order_id>\\w+)/cancel_authorization', $routes );

		$controller = $this->create_authorizations_controller( false );
		$controller->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $controller, 'register_routes' ) ) );
	}

	/**
	 * @testdox Authorizations list preserves reference query names and the legacy request filter.
	 */
	public function test_authorizations_list_preserves_filter_contract(): void {
		$this->create_authorizations_controller( true )->register_routes();
		$observed_request = null;

		add_filter(
			'wcpay_list_authorizations_request',
			static function ( \WCPay\Core\Server\Request\List_Authorizations $request ) use ( &$observed_request ): \WCPay\Core\Server\Request\List_Authorizations {
				$observed_request = $request;
				$request->set_page_size( 50 );
				$request->set_param( 'customer_email_is', 'ada@example.com' );

				return $request;
			}
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/authorizations' );
		$request->set_query_params(
			array(
				'page'                => '2',
				'pagesize'            => '25',
				'sort'                => 'capture_by',
				'direction'           => 'asc',
				'order_id'            => '123',
				'customer_email'      => 'grace@example.com',
				'payment_method_type' => 'card',
				'loan_id_is'          => 'drop-me',
				'store_currency_is'   => 'drop-me',
				'ignored'             => 'drop-me',
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertInstanceOf( WooPaymentsAuthorizationsListRequest::class, $observed_request );
		$this->assertSame( 'authorizations', $observed_request->get_api() );
		$this->assertSame( 'get_authorizations', $this->api_client->last_call['method'] );
		$this->assertSame(
			array(
				'page'              => 2,
				'pagesize'          => 50,
				'sort'              => 'created',
				'direction'         => 'asc',
				'limit'             => 100,
				'order_id_is'       => '123',
				'customer_email_is' => 'ada@example.com',
				'source_is'         => 'card',
			),
			$this->api_client->last_call['query']
		);
	}

	/**
	 * @testdox Authorization detail and summary routes proxy the compatible API methods.
	 */
	public function test_authorization_detail_and_summary_routes_proxy_api_methods(): void {
		$this->create_authorizations_controller( true )->register_routes();

		$this->api_client->response = array(
			'payment_intent_id' => 'pi_auth',
			'captured'          => false,
		);

		$detail_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/authorizations/pi_auth' ) );

		$this->assertSame( 200, $detail_response->get_status() );
		$this->assertSame( 'get_authorization', $this->api_client->last_call['method'] );
		$this->assertSame( 'pi_auth', $this->api_client->last_call['payment_intent_id'] );

		$this->api_client->response = array(
			'count' => 2,
			'total' => 1000,
		);

		$summary_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/payments/authorizations/summary' ) );

		$this->assertSame( 200, $summary_response->get_status() );
		$this->assertSame( 'get_authorizations_summary', $this->api_client->last_call['method'] );
		$this->assertSame( 2, $summary_response->get_data()['count'] );
	}

	/**
	 * @testdox Authorization actions validate order state before delegating to native payment processing.
	 */
	public function test_authorization_actions_validate_order_state_before_processing(): void {
		$processing_service = new class() extends PaymentProcessingService {
			/**
			 * Capture call count.
			 *
			 * @var int
			 */
			public int $capture_calls = 0;

			/**
			 * Capture a payment.
			 *
			 * @param PaymentContext   $context  Payment context.
			 * @param ProviderContract $provider Payment provider.
			 * @return PaymentOutcome
			 */
			public function capture( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
				++$this->capture_calls;

				return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_auth' );
			}
		};

		$this->create_authorizations_controller( true, $processing_service )->register_routes();

		$missing_response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/orders/999999/capture_authorization' ) );

		$this->assertSame( 404, $missing_response->get_status() );
		$this->assertSame( 'wcpay_missing_order', $missing_response->get_data()['code'] );
		$this->assertSame( 0, $processing_service->capture_calls );

		$order   = $this->create_authorized_order( 'pi_order' );
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_authorization' );
		$request->set_body_params( array( 'payment_intent_id' => 'pi_other' ) );

		$mismatch_response = $this->server->dispatch( $request );

		$this->assertSame( 409, $mismatch_response->get_status() );
		$this->assertSame( 'wcpay_intent_order_mismatch', $mismatch_response->get_data()['code'] );
		$this->assertSame( 0, $processing_service->capture_calls );
	}

	/**
	 * @testdox Authorization actions reject live intents that belong to another order.
	 */
	public function test_authorization_actions_reject_live_intent_order_mismatch(): void {
		$processing_service = new class() extends PaymentProcessingService {
			/**
			 * Capture call count.
			 *
			 * @var int
			 */
			public int $capture_calls = 0;

			/**
			 * Capture a payment.
			 *
			 * @param PaymentContext   $context  Payment context.
			 * @param ProviderContract $provider Payment provider.
			 * @return PaymentOutcome
			 */
			public function capture( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
				++$this->capture_calls;

				return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_auth' );
			}
		};

		$this->create_authorizations_controller( true, $processing_service )->register_routes();
		$order                      = $this->create_authorized_order( 'pi_auth' );
		$this->api_client->response = array(
			'id'       => 'pi_auth',
			'status'   => 'requires_capture',
			'metadata' => array(
				'order_id' => (string) ( $order->get_id() + 1 ),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_authorization' );
		$request->set_body_params( array( 'payment_intent_id' => 'pi_auth' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'wcpay_intent_order_mismatch', $response->get_data()['code'] );
		$this->assertSame( 'get_payment_intention', $this->api_client->last_call['method'] );
		$this->assertSame( 0, $processing_service->capture_calls );
	}

	/**
	 * @testdox Authorization actions reject stale live intent statuses.
	 */
	public function test_authorization_actions_reject_stale_live_intent_status(): void {
		$processing_service = new class() extends PaymentProcessingService {
			/**
			 * Cancel call count.
			 *
			 * @var int
			 */
			public int $cancel_calls = 0;

			/**
			 * Cancel a payment.
			 *
			 * @param PaymentContext   $context  Payment context.
			 * @param ProviderContract $provider Payment provider.
			 * @return PaymentOutcome
			 */
			public function cancel( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
				++$this->cancel_calls;

				return new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, 'pi_auth' );
			}
		};

		$this->create_authorizations_controller( true, $processing_service )->register_routes();
		$order                      = $this->create_authorized_order( 'pi_auth' );
		$this->api_client->response = array(
			'id'       => 'pi_auth',
			'status'   => 'succeeded',
			'metadata' => array(
				'order_id' => (string) $order->get_id(),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/cancel_authorization' );
		$request->set_body_params( array( 'payment_intent_id' => 'pi_auth' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'wcpay_payment_uncapturable', $response->get_data()['code'] );
		$this->assertSame( 'get_payment_intention', $this->api_client->last_call['method'] );
		$this->assertSame( 0, $processing_service->cancel_calls );
	}

	/**
	 * @testdox Capture and cancel authorization actions delegate through native payment processing.
	 */
	public function test_authorization_actions_delegate_through_native_processing(): void {
		$processing_service = new class() extends PaymentProcessingService {
			/**
			 * Last capture context.
			 *
			 * @var PaymentContext|null
			 */
			public ?PaymentContext $last_capture_context = null;

			/**
			 * Last cancel context.
			 *
			 * @var PaymentContext|null
			 */
			public ?PaymentContext $last_cancel_context = null;

			/**
			 * Capture a payment.
			 *
			 * @param PaymentContext   $context  Payment context.
			 * @param ProviderContract $provider Payment provider.
			 * @return PaymentOutcome
			 */
			public function capture( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
				$this->last_capture_context = $context;

				return new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_auth' );
			}

			/**
			 * Cancel a payment.
			 *
			 * @param PaymentContext   $context  Payment context.
			 * @param ProviderContract $provider Payment provider.
			 * @return PaymentOutcome
			 */
			public function cancel( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
				$this->last_cancel_context = $context;

				return new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, 'pi_auth' );
			}
		};

		$this->create_authorizations_controller( true, $processing_service )->register_routes();
		$order                      = $this->create_authorized_order( 'pi_auth' );
		$this->api_client->response = array(
			'id'       => 'pi_auth',
			'status'   => 'requires_capture',
			'metadata' => array(
				'order_id' => (string) $order->get_id(),
			),
		);

		$capture_request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_authorization' );
		$capture_request->set_body_params( array( 'payment_intent_id' => 'pi_auth' ) );
		$capture_response = $this->server->dispatch( $capture_request );

		$this->assertSame( 200, $capture_response->get_status() );
		$this->assertSame( 'succeeded', $capture_response->get_data()['status'] );
		$this->assertSame( 'pi_auth', $capture_response->get_data()['id'] );
		$this->assertInstanceOf( PaymentContext::class, $processing_service->last_capture_context );

		$cancel_request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/cancel_authorization' );
		$cancel_request->set_body_params( array( 'payment_intent_id' => 'pi_auth' ) );
		$cancel_response = $this->server->dispatch( $cancel_request );

		$this->assertSame( 200, $cancel_response->get_status() );
		$this->assertSame( 'canceled', $cancel_response->get_data()['status'] );
		$this->assertSame( 'pi_auth', $cancel_response->get_data()['id'] );
		$this->assertInstanceOf( PaymentContext::class, $processing_service->last_cancel_context );
	}

	/**
	 * @testdox Payment detail routes register only when native owns runtime.
	 */
	public function test_payment_detail_routes_register_only_when_native_owns_runtime(): void {
		$controller = $this->create_payment_details_controller( true );
		$controller->register();
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );

		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/payments/charges/(?P<charge_id>\\w+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/payment_intents/(?P<payment_intent_id>\\w+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/timeline/(?P<intention_id>\\w+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/payments/refund', $routes );

		$controller = $this->create_payment_details_controller( false );
		$controller->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $controller, 'register_routes' ) ) );
	}

	/**
	 * @testdox Payment detail charge route proxies the charge ID.
	 */
	public function test_payment_detail_charge_route_proxies_charge_id(): void {
		$order = $this->create_order_with_charge( 'ch_test', 'pi_test' );

		$this->api_client->response = array(
			'id'                  => 'ch_test',
			'balance_transaction' => array( 'id' => 'txn_test' ),
		);
		$this->create_payment_details_controller( true )->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/charges/ch_test' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_charge', $this->api_client->last_call['method'] );
		$this->assertSame( 'ch_test', $this->api_client->last_call['charge_id'] );
		$this->assertSame( 'txn_test', $data['balance_transaction']['id'] );
		$this->assertSame( $order->get_id(), $data['order']['id'] );
	}

	/**
	 * @testdox Payment detail payment intent route proxies the intent ID.
	 */
	public function test_payment_detail_intent_route_proxies_intent_id(): void {
		$order = $this->create_order_with_charge( 'ch_test', 'pi_test' );

		$this->api_client->response = array(
			'id'      => 'pi_test',
			'charges' => array(
				'data' => array(
					array(
						'id'                  => 'ch_test',
						'balance_transaction' => array( 'id' => 'txn_test' ),
					),
				),
			),
		);
		$this->create_payment_details_controller( true )->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/payment_intents/pi_test' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_payment_intention', $this->api_client->last_call['method'] );
		$this->assertSame( 'pi_test', $this->api_client->last_call['intent_id'] );
		$this->assertSame( 'txn_test', $data['charges']['data'][0]['balance_transaction']['id'] );
		$this->assertSame( $order->get_id(), $data['order']['id'] );
		$this->assertSame( $order->get_id(), $data['charges']['data'][0]['order']['id'] );
	}

	/**
	 * @testdox Payment detail timeline route proxies the intention ID.
	 */
	public function test_payment_detail_timeline_route_proxies_intention_id(): void {
		$this->api_client->response = array(
			'data' => array(
				array(
					'type'    => 'captured',
					'message' => 'Payment captured.',
				),
			),
		);
		$this->create_payment_details_controller( true )->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/timeline/pi_test' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_timeline', $this->api_client->last_call['method'] );
		$this->assertSame( 'pi_test', $this->api_client->last_call['intention_id'] );
		$this->assertSame( 'captured', $data['data'][0]['type'] );
	}

	/**
	 * @testdox Payment detail timeline adds local manual fraud outcomes for review timelines.
	 */
	public function test_payment_detail_timeline_adds_manual_fraud_outcome(): void {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );
		$order->add_meta_data(
			'_wcpay_fraud_outcome_manual_entry',
			array(
				'type'     => 'fraud_outcome_manual_approve',
				'user'     => array(
					'id'       => 1,
					'username' => 'admin',
				),
				'action'   => 'approved',
				'datetime' => 1781712200,
			)
		);
		$order->save();

		$this->api_client->responses = array(
			'get_timeline'          => array(
				'data' => array(
					array(
						'type'     => 'fraud_outcome_review',
						'datetime' => 1781712000,
					),
					array(
						'type'     => 'captured',
						'datetime' => 1781711900,
					),
				),
			),
			'get_payment_intention' => array(
				'id'       => 'pi_review',
				'metadata' => array(
					'order_id' => (string) $order->get_id(),
				),
			),
		);
		$this->create_payment_details_controller( true )->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/timeline/pi_review' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'get_timeline', $this->api_client->calls[0]['method'] );
		$this->assertSame( 'pi_review', $this->api_client->calls[0]['intention_id'] );
		$this->assertSame( 'get_payment_intention', $this->api_client->calls[1]['method'] );
		$this->assertSame( 'pi_review', $this->api_client->calls[1]['intent_id'] );
		$this->assertSame(
			array( 'fraud_outcome_manual_approve', 'fraud_outcome_review', 'captured' ),
			wp_list_pluck( $data['data'], 'type' )
		);
		$this->assertSame( 'approved', $data['data'][0]['action'] );
	}

	/**
	 * @testdox Payment detail routes preserve API error status codes.
	 */
	public function test_payment_detail_routes_preserve_api_error_status_codes(): void {
		$this->api_client->exception = new WooPaymentsApiException( 'Charge not found.', 'wcpay_missing_charge', 404 );
		$this->create_payment_details_controller( true )->register_routes();

		$request  = new WP_REST_Request( 'GET', '/wc/v3/payments/charges/ch_missing' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'wcpay_missing_charge', $data['code'] );
	}

	/**
	 * @testdox Payment detail refund route creates an order-backed WooCommerce refund through the order gateway.
	 */
	public function test_payment_detail_refund_route_creates_order_backed_refund(): void {
		$gateway = $this->register_refund_gateway( true );
		$order   = $this->create_refundable_order_with_charge( '50.00', 'ch_order' );
		$this->create_payment_details_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/refund' );
		$request->set_body_params(
			array(
				'charge_id' => 'ch_order',
				'amount'    => 5000,
				'reason'    => 'requested_by_customer',
				'order_id'  => $order->get_id(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$refunds  = $order->get_refunds();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $refunds );
		$this->assertSame( 50.0, (float) $refunds[0]->get_amount() );
		$this->assertSame( 'requested_by_customer', $refunds[0]->get_reason() );
		$this->assertTrue( $refunds[0]->get_refunded_payment() );
		$this->assertSame( $refunds[0]->get_id(), $data['id'] );
		$this->assertSame( $order->get_id(), $data['order_id'] );
		$this->assertSame( '50.00', $data['amount'] );
		$this->assertSame(
			array(
				array(
					'order_id' => $order->get_id(),
					'amount'   => 50.0,
					'reason'   => 'requested_by_customer',
				),
			),
			$gateway->refund_calls
		);
	}

	/**
	 * @testdox Payment detail refund route rejects refunds without a WooCommerce order.
	 */
	public function test_payment_detail_refund_route_rejects_missing_order(): void {
		$this->create_payment_details_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/refund' );
		$request->set_body_params(
			array(
				'charge_id' => 'ch_order',
				'amount'    => 5000,
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'wcpay_refund_missing_order', $data['code'] );
	}

	/**
	 * @testdox Payment detail refund route rejects unknown orders with its own error code.
	 */
	public function test_payment_detail_refund_route_rejects_unknown_order(): void {
		$this->create_payment_details_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/refund' );
		$request->set_body_params(
			array(
				'charge_id' => 'ch_order',
				'amount'    => 5000,
				'order_id'  => 999999,
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'wcpay_refund_missing_order', $data['code'] );
	}

	/**
	 * @testdox Payment detail refund route rejects charge IDs that do not belong to the order.
	 */
	public function test_payment_detail_refund_route_rejects_charge_order_mismatch(): void {
		$this->register_refund_gateway( true );
		$order = $this->create_refundable_order_with_charge( '50.00', 'ch_order' );
		$this->create_payment_details_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/refund' );
		$request->set_body_params(
			array(
				'charge_id' => 'ch_other',
				'amount'    => 5000,
				'reason'    => 'requested_by_customer',
				'order_id'  => $order->get_id(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'wcpay_refund_charge_order_mismatch', $data['code'] );
		$this->assertCount( 0, $order->get_refunds() );
	}

	/**
	 * @testdox Payment detail refund route validates the requested amount before creating a refund.
	 */
	public function test_payment_detail_refund_route_rejects_invalid_amounts(): void {
		$this->register_refund_gateway( true );
		$order = $this->create_refundable_order_with_charge( '50.00', 'ch_order' );
		$this->create_payment_details_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/refund' );
		$request->set_body_params(
			array(
				'charge_id' => 'ch_order',
				'amount'    => 0,
				'order_id'  => $order->get_id(),
			)
		);

		$zero_response = $this->server->dispatch( $request );
		$zero_data     = $zero_response->get_data();

		$this->assertSame( 400, $zero_response->get_status() );
		$this->assertSame( 'wcpay_refund_invalid_amount', $zero_data['code'] );

		$request->set_body_params(
			array(
				'charge_id' => 'ch_order',
				'amount'    => 5100,
				'order_id'  => $order->get_id(),
			)
		);

		$too_large_response = $this->server->dispatch( $request );
		$too_large_data     = $too_large_response->get_data();

		$this->assertSame( 400, $too_large_response->get_status() );
		$this->assertSame( 'wcpay_refund_invalid_amount', $too_large_data['code'] );
		$this->assertCount( 0, $order->get_refunds() );
	}

	/**
	 * @testdox Payment detail refund route returns gateway failures without keeping a local refund row.
	 */
	public function test_payment_detail_refund_route_returns_gateway_failure(): void {
		$this->register_refund_gateway( new \WP_Error( 'gateway_failed', 'Gateway failed.' ) );
		$order = $this->create_refundable_order_with_charge( '50.00', 'ch_order' );
		$this->create_payment_details_controller( true )->register_routes();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/refund' );
		$request->set_body_params(
			array(
				'charge_id' => 'ch_order',
				'amount'    => 5000,
				'order_id'  => $order->get_id(),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'wcpay_refund_payment_failed', $data['code'] );
		$this->assertStringContainsString( 'Gateway failed.', $data['message'] );
		$this->assertCount( 0, $order->get_refunds() );
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
	 * @testdox Dispute close deletes stale dispute caches after the platform accepts the close.
	 */
	public function test_dispute_close_deletes_stale_dispute_caches_after_platform_success(): void {
		$this->create_disputes_controller( true )->register_routes();
		update_option( 'wcpay_dispute_status_counts_cache', array( 'data' => array( 'needs_response' => 1 ) ) );
		update_option( 'wcpay_test_dispute_status_counts_cache', array( 'data' => array( 'warning_needs_response' => 1 ) ) );
		update_option( 'wcpay_active_dispute_cache', array( 'id' => 'dp_test' ) );
		$this->api_client->response = array(
			'id'     => 'dp_test',
			'status' => 'lost',
		);

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test/close' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( get_option( 'wcpay_dispute_status_counts_cache' ) );
		$this->assertFalse( get_option( 'wcpay_test_dispute_status_counts_cache' ) );
		$this->assertFalse( get_option( 'wcpay_active_dispute_cache' ) );
	}

	/**
	 * @testdox Dispute close keeps dispute caches when the platform close fails.
	 */
	public function test_dispute_close_keeps_stale_dispute_caches_when_platform_fails(): void {
		$this->create_disputes_controller( true )->register_routes();
		update_option( 'wcpay_dispute_status_counts_cache', array( 'data' => array( 'needs_response' => 1 ) ) );
		$this->api_client->exception = new WooPaymentsApiException( 'Close failed.', 'close_failed', 400 );

		$response = $this->server->dispatch( new WP_REST_Request( 'POST', '/wc/v3/payments/disputes/dp_test/close' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame(
			array( 'data' => array( 'needs_response' => 1 ) ),
			get_option( 'wcpay_dispute_status_counts_cache' )
		);
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
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client, $order_service ?? $this->create_order_service(), new WooPaymentsDisputeCacheService() );

		return $controller;
	}

	/**
	 * Create an authorizations controller.
	 *
	 * @param bool                          $native_register    Whether native should own routes.
	 * @param PaymentProcessingService|null $processing_service Optional payment processing service.
	 * @return WooPaymentsAuthorizationsRestController
	 */
	private function create_authorizations_controller( bool $native_register, ?PaymentProcessingService $processing_service = null ): WooPaymentsAuthorizationsRestController {
		$controller = new WooPaymentsAuthorizationsRestController();
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client, $processing_service ?? new PaymentProcessingService(), new WooPaymentsProvider() );

		return $controller;
	}

	/**
	 * Create a payment details controller.
	 *
	 * @param bool $native_register Whether native should own routes.
	 * @return WooPaymentsPaymentDetailsRestController
	 */
	private function create_payment_details_controller( bool $native_register ): WooPaymentsPaymentDetailsRestController {
		$controller = new WooPaymentsPaymentDetailsRestController();
		$controller->init( $this->create_arbiter( $native_register ), $this->api_client, $this->create_order_service() );

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
	 * Create an authorized WooPayments order.
	 *
	 * @param string $intent_id Intent ID.
	 * @return WC_Order
	 */
	private function create_authorized_order( string $intent_id ): WC_Order {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );

		$order->set_total( 10 );
		$order->set_currency( 'USD' );
		$order->set_transaction_id( $intent_id );
		$order->update_meta_data( '_intent_id', $intent_id );
		$order->update_meta_data( '_intention_status', 'requires_capture' );
		$order->save();
		$order->update_status( 'on-hold' );

		return $order;
	}

	/**
	 * Create a refundable WooPayments order.
	 *
	 * @param string $total     Order total.
	 * @param string $charge_id WooPayments charge ID.
	 * @return WC_Order
	 */
	private function create_refundable_order_with_charge( string $total, string $charge_id ): WC_Order {
		$order = wc_create_order();
		$this->assertInstanceOf( WC_Order::class, $order );

		$order->set_total( $total );
		$order->set_currency( 'USD' );
		$order->set_payment_method( 'native_test_refund_gateway' );
		$order->update_meta_data( '_charge_id', $charge_id );
		$order->update_meta_data( '_intent_id', 'pi_order' );
		$order->save();

		return $order;
	}

	/**
	 * Register a local refundable gateway for refund route tests.
	 *
	 * @param bool|\WP_Error $refund_result Gateway refund result.
	 * @return \WC_Payment_Gateway&object{refund_calls: array<int,array<string,mixed>>}
	 */
	private function register_refund_gateway( $refund_result ): \WC_Payment_Gateway {
		$gateway = new class( $refund_result ) extends \WC_Payment_Gateway {
			/**
			 * Refund result.
			 *
			 * @var bool|\WP_Error
			 */
			private $refund_result;

			/**
			 * Recorded refund calls.
			 *
			 * @var array<int,array<string,mixed>>
			 */
			public array $refund_calls = array();

			/**
			 * Constructor.
			 *
			 * @param bool|\WP_Error $refund_result Refund result.
			 */
			public function __construct( $refund_result ) {
				$this->id            = 'native_test_refund_gateway';
				$this->method_title  = 'Native test refund gateway';
				$this->title         = 'Native test refund gateway';
				$this->supports      = array( 'refunds' );
				$this->refund_result = $refund_result;
			}

			/**
			 * Process refund.
			 *
			 * @param int        $order_id Order ID.
			 * @param float|null $amount   Amount.
			 * @param string     $reason   Reason.
			 * @return bool|\WP_Error
			 */
			public function process_refund( $order_id, $amount = null, $reason = '' ) {
				$this->refund_calls[] = array(
					'order_id' => (int) $order_id,
					'amount'   => null === $amount ? null : (float) $amount,
					'reason'   => (string) $reason,
				);

				return $this->refund_result;
			}
		};

		$this->refund_gateway_initializer = static function ( \WC_Payment_Gateways $wc_payment_gateways ) use ( $gateway ): void {
			$wc_payment_gateways->payment_gateways = array( $gateway );
		};

		add_action( 'wc_payment_gateways_initialized', $this->refund_gateway_initializer, 100 );

		WC()->payment_gateways()->payment_gateways = array();
		WC()->payment_gateways()->init();

		return $gateway;
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
