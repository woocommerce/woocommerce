<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCustomerService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsMobileRestController;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsOrderDataService;
use WC_Helper_Order;
use WC_REST_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Tests for the native WooPayments mobile and IPP REST controller.
 */
class WooPaymentsMobileRestControllerTest extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WooPaymentsMobileRestController
	 */
	private $sut;

	/**
	 * Recording API client.
	 *
	 * @var RecordingTerminalApiClient
	 */
	private RecordingTerminalApiClient $api_client;

	/**
	 * Mocked WooPayments gateway settings.
	 *
	 * @var array<string,mixed>
	 */
	private array $gateway_settings = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_client       = new RecordingTerminalApiClient();
		$this->gateway_settings = array();
		$this->sut              = $this->create_controller( true );
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		delete_transient( 'wcpay_store_terminal_readers' );
		delete_transient( 'wcpay_store_terminal_locations' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		remove_action( 'rest_api_init', array( $this->sut, 'register_routes' ) );
		delete_transient( 'wcpay_store_terminal_readers' );
		delete_transient( 'wcpay_store_terminal_locations' );
		parent::tearDown();
	}

	/**
	 * @testdox The mobile and IPP routes are registered under wc/v3 when native owns runtime.
	 */
	public function test_registers_mobile_ipp_routes_when_native_owns_runtime(): void {
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
	 * @testdox Mobile and IPP routes are not registered when native does not own runtime.
	 */
	public function test_registers_no_routes_when_native_does_not_own_runtime(): void {
		$this->sut = $this->create_controller( false );
		$this->sut->register();

		$this->assertFalse( has_action( 'rest_api_init', array( $this->sut, 'register_routes' ) ) );
	}

	/**
	 * @testdox Mobile and IPP routes require manage_woocommerce.
	 */
	public function test_routes_require_manage_woocommerce(): void {
		$this->sut->register_routes();
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'POST', '/wc/v3/payments/connection_tokens' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( rest_authorization_required_code(), $response->get_status() );
	}

	/**
	 * @testdox Connection-token responses include the WooPayments test-mode flag for mobile clients.
	 */
	public function test_connection_token_response_appends_test_mode(): void {
		$this->api_client->connection_token_response = array( 'secret' => 'cnctok_test_secret' );

		$response = $this->sut->create_connection_token( new WP_REST_Request( 'POST', '/wc/v3/payments/connection_tokens' ) );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame(
			array(
				'secret'    => 'cnctok_test_secret',
				'test_mode' => true,
			),
			$response->get_data()
		);
	}

	/**
	 * @testdox Terminal intent creation sends the order amount, lower-case currency, metadata, and card-present defaults.
	 */
	public function test_create_terminal_intent_builds_reference_payload(): void {
		$order = $this->create_order( 12.34, 'USD' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_terminal_intent' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'customer_id', 'cus_terminal' );
		$request->set_param( 'metadata', array( 'channel' => 'mobile' ) );

		$response = $this->sut->create_terminal_intent( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array( 'id' => 'pi_terminal' ), $response->get_data() );
		$this->assertSame( 1234, $this->api_client->last_terminal_intent_payload['amount'] );
		$this->assertSame( 'usd', $this->api_client->last_terminal_intent_payload['currency'] );
		$this->assertSame( 'cus_terminal', $this->api_client->last_terminal_intent_payload['customer'] );
		$this->assertSame( 'mobile', $this->api_client->last_terminal_intent_payload['metadata']['channel'] );
		$this->assertSame( (string) $order->get_id(), $this->api_client->last_terminal_intent_payload['metadata']['order_id'] );
		$this->assertSame( $order->get_order_number(), $this->api_client->last_terminal_intent_payload['metadata']['order_number'] );
		$this->assertSame( array( 'card_present' ), $this->api_client->last_terminal_intent_payload['payment_method_types'] );
		$this->assertSame( 'manual', $this->api_client->last_terminal_intent_payload['capture_method'] );
	}

	/**
	 * @testdox Terminal intent creation rejects invalid payment method payloads like the reference mobile route.
	 */
	public function test_create_terminal_intent_rejects_invalid_payment_method_payload(): void {
		$order   = $this->create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_terminal_intent' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_methods', 'card_present' );

		$response = $this->sut->create_terminal_intent( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_server_error', $response->get_error_code() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
		$this->assertSame( array(), $this->api_client->last_terminal_intent_payload );
	}

	/**
	 * @testdox Terminal intent creation rejects unsupported payment methods instead of silently defaulting.
	 */
	public function test_create_terminal_intent_rejects_unsupported_payment_method(): void {
		$order   = $this->create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_terminal_intent' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_methods', array( 'card' ) );

		$response = $this->sut->create_terminal_intent( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_server_error', $response->get_error_code() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
		$this->assertSame( array(), $this->api_client->last_terminal_intent_payload );
	}

	/**
	 * @testdox Terminal intent creation rejects unsupported capture methods instead of silently defaulting.
	 */
	public function test_create_terminal_intent_rejects_invalid_capture_method(): void {
		$order   = $this->create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_terminal_intent' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'capture_method', 'later' );

		$response = $this->sut->create_terminal_intent( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_server_error', $response->get_error_code() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
		$this->assertSame( array(), $this->api_client->last_terminal_intent_payload );
	}

	/**
	 * @testdox Terminal preparation rejects invalid intent IDs before forwarding to WPCOM.
	 */
	public function test_prepare_terminal_payment_rejects_invalid_intent_id(): void {
		$order   = $this->create_order();
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/prepare_terminal_payment' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_intent_id', '../pi_bad' );

		$response = $this->sut->prepare_terminal_payment( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_invalid_payment_intent_id', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertSame( array(), $this->api_client->prepared_terminal_payments );
	}

	/**
	 * @testdox Reader listing uses the preserved transient and annotates the active reader.
	 */
	public function test_get_readers_uses_preserved_transient_and_active_status(): void {
		$this->api_client->terminal_readers_response      = array(
			'data' => array(
				array(
					'id'          => 'tmr_active',
					'livemode'    => false,
					'device_type' => 'bbpos_wisepos_e',
					'label'       => 'Counter',
					'location'    => 'tml_store',
					'metadata'    => array(),
					'status'      => 'online',
				),
			),
		);
		$this->api_client->reader_charge_summary_response = array(
			array(
				'reader_id' => 'tmr_active',
				'status'    => 'active',
			),
		);

		$response = $this->sut->get_readers( new WP_REST_Request( 'GET', '/wc/v3/payments/readers' ) );
		$cached   = get_transient( 'wcpay_store_terminal_readers' );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertSame( 'tmr_active', $data[0]['id'] );
		$this->assertTrue( $data[0]['is_active'] );
		$this->assertSame( $data, $cached );
	}

	/**
	 * @testdox Reader charge summary uses the source transaction creation date.
	 */
	public function test_get_reader_charge_summary_uses_transaction_created_date(): void {
		$this->api_client->transaction_response           = array(
			'id'      => 'txn_test',
			'created' => strtotime( '2026-06-01 12:00:00 UTC' ),
		);
		$this->api_client->reader_charge_summary_response = array(
			array(
				'reader_id' => 'tmr_active',
				'status'    => 'active',
			),
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/readers/charges/txn_test' );
		$request->set_param( 'transaction_id', 'txn_test' );

		$response = $this->sut->get_reader_charge_summary( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( $this->api_client->reader_charge_summary_response, $response->get_data() );
		$this->assertSame(
			array(
				array(
					'charge_date'    => '2026-06-01',
					'transaction_id' => 'txn_test',
				),
			),
			$this->api_client->reader_charge_summary_calls
		);
	}

	/**
	 * @testdox Reader charge summary returns an empty response when the source transaction is missing.
	 */
	public function test_get_reader_charge_summary_returns_empty_when_transaction_is_missing(): void {
		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/readers/charges/txn_missing' );
		$request->set_param( 'transaction_id', 'txn_missing' );

		$response = $this->sut->get_reader_charge_summary( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array(), $response->get_data() );
		$this->assertSame( array(), $this->api_client->reader_charge_summary_calls );
	}

	/**
	 * @testdox Receipt preview accepts the preserved camelCase settings payload.
	 */
	public function test_preview_print_receipt_uses_preserved_settings_payload(): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/readers/receipts/preview' );
		$request->set_body_params(
			array(
				'accountBusinessName'           => 'Receipt Lab',
				'accountBusinessSupportAddress' => array(
					'line1'       => '1 Support Way',
					'line2'       => 'Suite 2',
					'city'        => 'San Francisco',
					'state'       => 'CA',
					'postal_code' => '94107',
					'country'     => 'US',
				),
				'accountBusinessSupportPhone'   => '+1 555 0100',
				'accountBusinessSupportEmail'   => 'support@example.com',
			)
		);

		$response = $this->sut->preview_print_receipt( $request );
		$html     = $response->get_data()['html_content'];

		$this->assertStringContainsString( '<!DOCTYPE html>', $html );
		$this->assertStringContainsString( 'Receipt Lab', $html );
		$this->assertStringContainsString( '1 Support Way', $html );
		$this->assertStringContainsString( '+1 555 0100 support@example.com', $html );
		$this->assertStringContainsString( 'Sample', $html );
		$this->assertStringContainsString( 'Application name', $html );
		$this->assertStringContainsString( 'AID', $html );
		$this->assertStringContainsString( 'Powered by WooCommerce', $html );
	}

	/**
	 * @testdox Generated receipts include order lines and terminal receipt fields.
	 */
	public function test_generate_print_receipt_uses_order_and_charge_receipt_data(): void {
		$order = $this->create_order( 12.34, 'USD' );

		$this->gateway_settings                       = array(
			'account_business_name'            => 'Generated Receipt Lab',
			'account_business_support_address' => array(
				'line1'       => '123 Support St',
				'city'        => 'San Francisco',
				'state'       => 'CA',
				'postal_code' => '94107',
				'country'     => 'US',
			),
			'account_business_support_phone'   => '+1 555 0200',
			'account_business_support_email'   => 'generated@example.com',
		);
		$this->api_client->payment_intention_response = array(
			'id'       => 'pi_receipt',
			'status'   => 'succeeded',
			'currency' => 'usd',
			'metadata' => array(
				'order_id' => (string) $order->get_id(),
			),
			'charges'  => array(
				'data' => array(
					array( 'id' => 'ch_receipt' ),
				),
			),
		);
		$this->api_client->charge_response            = array(
			'id'                     => 'ch_receipt',
			'amount_captured'        => 1234,
			'currency'               => 'usd',
			'order'                  => array(
				'number' => $order->get_id(),
			),
			'payment_method_details' => array(
				'card_present' => array(
					'brand'   => 'visa',
					'last4'   => '4242',
					'receipt' => array(
						'application_preferred_name' => 'visa credit',
						'dedicated_file_name'        => 'a0000000031010',
						'account_type'               => 'credit',
					),
				),
			),
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/readers/receipts/pi_receipt' );
		$request->set_param( 'payment_intent_id', 'pi_receipt' );

		$response = $this->sut->generate_print_receipt( $request );
		$html     = $response->get_data()['html_content'];

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertStringContainsString( '<!DOCTYPE html>', $html );
		$this->assertStringContainsString( 'Generated Receipt Lab', $html );
		$this->assertStringContainsString( '123 Support St', $html );
		$this->assertStringContainsString( '+1 555 0200 generated@example.com', $html );
		$this->assertStringContainsString( 'Order ' . $order->get_id(), $html );
		$this->assertStringContainsString( 'AMOUNT PAID', $html );
		$this->assertStringContainsString( 'Visa - 4242', $html );
		$this->assertStringContainsString( 'Visa credit', $html );
		$this->assertStringContainsString( 'A0000000031010', $html );
		$this->assertStringContainsString( 'Credit', $html );
	}

	/**
	 * @testdox Generated receipts keep the preserved error envelope when the payment intent is invalid.
	 */
	public function test_generate_print_receipt_wraps_invalid_intent_in_preserved_error(): void {
		$this->api_client->payment_intention_response = array(
			'id'     => 'pi_receipt',
			'status' => 'requires_payment_method',
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/readers/receipts/pi_receipt' );
		$request->set_param( 'payment_intent_id', 'pi_receipt' );

		$response = $this->sut->generate_print_receipt( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'generate_print_receipt_error', $response->get_error_code() );
		$this->assertSame( 'Invalid payment intent', $response->get_error_message() );
		$this->assertSame( 500, $response->get_error_data()['status'] );
	}

	/**
	 * @testdox Store-location lookup creates a terminal location from the WooCommerce base address when none exists.
	 */
	public function test_get_store_location_creates_location_from_store_address(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_address', '123 Main St' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
		update_option( 'woocommerce_store_postcode', '94107' );
		update_option( 'woocommerce_store_address_2', '' );

		$response = $this->sut->get_store_location( new WP_REST_Request( 'GET', '/wc/v3/payments/terminal/locations/store' ) );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertSame( 'tml_created', $data['id'] );
		$this->assertSame( $this->get_site_location_name(), $this->api_client->last_created_location['display_name'] );
		$this->assertSame( 'US', $this->api_client->last_created_location['address']['country'] );
		$this->assertSame( 'CA', $this->api_client->last_created_location['address']['state'] );
		$this->assertSame( '123 Main St', $this->api_client->last_created_location['address']['line1'] );
	}

	/**
	 * @testdox Store-location lookup reuses hostname-named locations created by the reference client.
	 */
	public function test_get_store_location_reuses_matching_hostname_location(): void {
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_address', '123 Main St' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
		update_option( 'woocommerce_store_postcode', '94107' );
		update_option( 'woocommerce_store_address_2', '' );

		$this->api_client->terminal_locations_response = array(
			'data' => array(
				array(
					'id'           => 'tml_existing',
					'display_name' => $this->get_site_location_name(),
					'address'      => array(
						'country'     => 'US',
						'state'       => 'CA',
						'city'        => 'San Francisco',
						'postal_code' => '94107',
						'line1'       => '123 Main St',
					),
					'livemode'     => false,
				),
			),
		);

		$response = $this->sut->get_store_location( new WP_REST_Request( 'GET', '/wc/v3/payments/terminal/locations/store' ) );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'tml_existing', $response->get_data()['id'] );
		$this->assertSame( array(), $this->api_client->last_created_location );
	}

	/**
	 * @testdox Terminal location lookup falls back to the direct location endpoint on cache misses.
	 */
	public function test_get_terminal_location_falls_back_to_direct_lookup_on_cache_miss(): void {
		$this->api_client->terminal_locations_response = array( 'data' => array() );
		$this->api_client->terminal_location_response  = array(
			'id'           => 'tml_direct',
			'display_name' => 'Direct',
			'address'      => array(
				'country' => 'US',
				'line1'   => '456 Market',
			),
			'livemode'     => false,
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/payments/terminal/locations/tml_direct' );
		$request->set_param( 'location_id', 'tml_direct' );

		$response = $this->sut->get_terminal_location( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 'tml_direct', $response->get_data()['id'] );
		$this->assertSame( array( 'tml_direct' ), $this->api_client->terminal_location_calls );
	}

	/**
	 * @testdox Customer creation updates an existing Stripe customer stored on the order.
	 */
	public function test_create_customer_updates_existing_order_customer(): void {
		$order = $this->create_order();
		$order->set_billing_email( 'ada@example.com' );
		$order->update_meta_data( '_stripe_customer_id', 'cus_existing' );
		$order->save();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_customer' );
		$request->set_param( 'order_id', $order->get_id() );

		$response = $this->sut->create_customer( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array( 'id' => 'cus_existing' ), $response->get_data() );
		$this->assertSame( 'cus_existing', $this->api_client->updated_customers[0]['customer_id'] );
		$this->assertSame( 'ada@example.com', $this->api_client->updated_customers[0]['customer_data']['email'] );
	}

	/**
	 * @testdox Customer creation recreates a missing Stripe customer stored on the order.
	 */
	public function test_create_customer_recreates_missing_existing_order_customer(): void {
		$order = $this->create_order();
		$order->set_billing_email( 'missing@example.com' );
		$order->update_meta_data( '_stripe_customer_id', 'cus_missing' );
		$order->save();

		$this->api_client->created_customer_id       = 'cus_recreated';
		$this->api_client->update_customer_exception = new WooPaymentsApiException( 'No such customer', 'resource_missing', 404 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_customer' );
		$request->set_param( 'order_id', $order->get_id() );

		$response = $this->sut->create_customer( $request );
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array( 'id' => 'cus_recreated' ), $response->get_data() );
		$this->assertSame( 'cus_recreated', $order->get_meta( '_stripe_customer_id', true ) );
		$this->assertSame( 'cus_missing', $this->api_client->updated_customers[0]['customer_id'] );
		$this->assertSame( 'missing@example.com', $this->api_client->created_customers[0]['email'] );
	}

	/**
	 * @testdox Customer creation updates a cached user customer with the order billing data.
	 */
	public function test_create_customer_updates_cached_user_customer_with_order_data(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'customer' ) );
		update_user_option( $user_id, WooPaymentsCustomerService::TEST_CUSTOMER_ID_OPTION, 'cus_user' );

		$order = $this->create_order();
		$order->set_customer_id( $user_id );
		$order->set_billing_email( 'order@example.com' );
		$order->save();

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/create_customer' );
		$request->set_param( 'order_id', $order->get_id() );

		$response = $this->sut->create_customer( $request );
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( array( 'id' => 'cus_user' ), $response->get_data() );
		$this->assertSame( 'cus_user', $order->get_meta( '_stripe_customer_id', true ) );
		$this->assertSame( 'cus_user', $this->api_client->updated_customers[0]['customer_id'] );
		$this->assertSame( 'order@example.com', $this->api_client->updated_customers[0]['customer_data']['email'] );
		$this->assertSame( 'cus_user', get_user_option( WooPaymentsCustomerService::TEST_CUSTOMER_ID_OPTION, $user_id ) );
	}

	/**
	 * @testdox Capturing a terminal payment preserves WooPayments order meta and receipt URL.
	 */
	public function test_capture_terminal_payment_updates_order_state_and_receipt_url(): void {
		$order                                        = $this->create_order( 12.34, 'USD' );
		$this->api_client->payment_intention_response = array(
			'id'       => 'pi_terminal',
			'status'   => 'requires_capture',
			'currency' => 'usd',
			'metadata' => array(
				'order_id' => (string) $order->get_id(),
			),
			'charges'  => array(
				'data' => array(
					array(
						'id'                     => 'ch_terminal',
						'payment_method'         => 'pm_terminal',
						'payment_method_details' => array(
							'type'         => 'card_present',
							'card_present' => array(
								'brand' => 'visa',
								'last4' => '4242',
							),
						),
					),
				),
			),
		);
		$this->api_client->captured_intention_response = array(
			'id'       => 'pi_terminal',
			'status'   => 'succeeded',
			'currency' => 'usd',
			'charges'  => array(
				'data' => array(
					array(
						'id'             => 'ch_terminal',
						'payment_method' => 'pm_terminal',
					),
				),
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_terminal_payment' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_intent_id', 'pi_terminal' );

		$response = $this->sut->capture_terminal_payment( $request );
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame(
			array(
				'status' => 'succeeded',
				'id'     => 'pi_terminal',
			),
			$response->get_data()
		);
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $order->get_payment_method() );
		$this->assertSame( 'WooCommerce In-Person Payments', $order->get_payment_method_title() );
		$this->assertSame( 'pi_terminal', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'ch_terminal', $order->get_meta( '_charge_id', true ) );
		$this->assertSame( 'succeeded', $order->get_meta( '_intention_status', true ) );
		$this->assertSame( $order->get_meta( '_wcpay_payment_method_details', true ), $order->get_meta( '_wcpay_raw_payment_method_details', true ) );
		$this->assertStringContainsString( '/wc/v3/payments/readers/receipts/pi_terminal', (string) $order->get_meta( 'receipt_url', true ) );
	}

	/**
	 * @testdox Terminal capture rejects intents without matching order metadata.
	 */
	public function test_capture_terminal_payment_rejects_intent_without_order_metadata(): void {
		$order                                        = $this->create_order( 12.34, 'USD' );
		$this->api_client->payment_intention_response = array(
			'id'       => 'pi_terminal',
			'status'   => 'succeeded',
			'currency' => 'usd',
			'metadata' => array(),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_terminal_payment' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_intent_id', 'pi_terminal' );

		$response = $this->sut->capture_terminal_payment( $request );
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_intent_order_mismatch', $response->get_error_code() );
		$this->assertSame( 409, $response->get_error_data()['status'] );
		$this->assertNotSame( OrderPaymentStore::GATEWAY_ID, $order->get_payment_method() );
		$this->assertSame( '', $order->get_meta( '_intent_id', true ) );
	}

	/**
	 * @testdox Terminal capture returns an error when the capture result does not succeed.
	 */
	public function test_capture_terminal_payment_returns_error_when_capture_result_is_not_succeeded(): void {
		$order                                        = $this->create_order( 12.34, 'USD' );
		$this->api_client->payment_intention_response = array(
			'id'       => 'pi_terminal',
			'status'   => 'requires_capture',
			'currency' => 'usd',
			'metadata' => array(
				'order_id' => (string) $order->get_id(),
			),
		);
		$this->api_client->captured_intention_response = array(
			'id'        => 'pi_terminal',
			'status'    => 'requires_capture',
			'message'   => 'Capture failed.',
			'http_code' => 400,
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_terminal_payment' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_intent_id', 'pi_terminal' );

		$response = $this->sut->capture_terminal_payment( $request );
		$order    = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_capture_error', $response->get_error_code() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
		$this->assertStringContainsString( 'Capture failed.', $response->get_error_message() );
		$this->assertSame( '', $order->get_meta( '_intent_id', true ) );
	}

	/**
	 * @testdox Terminal capture preserves the amount-too-small machine-readable error code.
	 */
	public function test_capture_terminal_payment_returns_amount_too_small_error_details(): void {
		$order                                        = $this->create_order( 12.34, 'USD' );
		$error_details                                = array(
			'minimum_amount'          => 50,
			'minimum_amount_currency' => 'USD',
		);
		$this->api_client->payment_intention_response = array(
			'id'       => 'pi_terminal',
			'status'   => 'requires_capture',
			'currency' => 'usd',
			'metadata' => array(
				'order_id' => (string) $order->get_id(),
			),
		);
		$this->api_client->captured_intention_response = array(
			'id'            => 'pi_terminal',
			'status'        => 'requires_capture',
			'http_code'     => 400,
			'error_code'    => 'amount_too_small',
			'extra_details' => $error_details,
		);

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/orders/' . $order->get_id() . '/capture_terminal_payment' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'payment_intent_id', 'pi_terminal' );

		$response = $this->sut->capture_terminal_payment( $request );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'wcpay_capture_error_amount_too_small', $response->get_error_code() );
		$this->assertSame( esc_html( wp_json_encode( $error_details ) ), $response->get_error_message() );
		$this->assertSame( 400, $response->get_error_data()['status'] );
	}

	/**
	 * Create a native mobile REST controller.
	 *
	 * @param bool $native_register Whether native should own route registration.
	 * @return WooPaymentsMobileRestController
	 */
	private function create_controller( bool $native_register ): WooPaymentsMobileRestController {
		$arbiter = $this->getMockBuilder( NativePaymentsRuntimeArbiter::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'should_native_register' ) )
			->getMock();
		$arbiter->method( 'should_native_register' )->willReturn( $native_register );

		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'get_mode', 'get_gateway_setting' ) )
			->getMock();
		$account_service->method( 'is_test_mode_enabled' )->willReturn( true );
		$account_service->method( 'get_mode' )->willReturn( 'test' );
		$account_service->method( 'get_gateway_setting' )->willReturnCallback(
			function ( string $key, $fallback = null ) {
				return array_key_exists( $key, $this->gateway_settings ) ? $this->gateway_settings[ $key ] : $fallback;
			}
		);

		$customer_service = new WooPaymentsCustomerService();
		$customer_service->init( $this->api_client, $account_service );

		$controller = new WooPaymentsMobileRestController();
		$controller->init( $arbiter, $this->api_client, $account_service, $customer_service, new WooPaymentsOrderDataService() );

		return $controller;
	}

	/**
	 * Create a minimal order for terminal-route tests.
	 *
	 * @param float  $total    Order total.
	 * @param string $currency Order currency.
	 * @return \WC_Order
	 */
	private function create_order( float $total = 10.0, string $currency = 'USD' ): \WC_Order {
		$order = WC_Helper_Order::create_order();
		$order->set_total( $total );
		$order->set_currency( $currency );
		$order->set_status( 'pending' );
		$order->save();

		return $order;
	}

	/**
	 * Expected route map and HTTP methods.
	 *
	 * @return array<string,string[]>
	 */
	private function get_expected_routes(): array {
		return array(
			'/wc/v3/payments/connection_tokens'        => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/orders/(?P<order_id>\\w+)/capture_terminal_payment' => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/orders/(?P<order_id>\\w+)/prepare_terminal_payment' => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/orders/(?P<order_id>\\w+)/create_terminal_intent' => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/orders/(?P<order_id>\\d+)/create_customer' => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/readers'                  => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/readers/charges/(?P<transaction_id>\\w+)' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/readers/receipts/preview' => array( WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/readers/receipts/(?P<payment_intent_id>\\w+)' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/terminal/locations/store' => array( WP_REST_Server::READABLE ),
			'/wc/v3/payments/terminal/locations'       => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
			'/wc/v3/payments/terminal/locations/(?P<location_id>\\w+)' => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE, WP_REST_Server::DELETABLE ),
		);
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
	 * Get the current test site's hostname.
	 *
	 * @return string
	 */
	private function get_site_location_name(): string {
		return str_replace( array( 'https://', 'http://' ), '', get_site_url() );
	}
}
