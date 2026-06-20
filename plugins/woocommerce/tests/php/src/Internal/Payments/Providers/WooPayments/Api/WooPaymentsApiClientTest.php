<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\Api;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsActivatePmPromotionRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsGetPmPromotionsRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsDocumentsListRequest;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsReportingBalanceSummaryRequest;
use WCPay\Core\Server\Request\Get_Reporting_Balance_Summary;
use WCPay\Core\Server\Request\List_Authorizations;
use WCPay\Core\Server\Request\List_Documents;
use WC_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * Tests for the WooPaymentsApiClient class.
 */
class WooPaymentsApiClientTest extends WC_Unit_Test_Case {

	/**
	 * Preserved WooPayments V1 client capability user agent.
	 */
	private const EXPECTED_USER_AGENT = 'WooCommerce Payments/10.8.0';

	/**
	 * @testdox Should build the site-scoped WPCOM endpoint and lift idempotency_key into the request headers.
	 */
	public function test_request_lifts_idempotency_key_and_preserves_filtered_params(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 're_test' ) ),
		);

		$sut    = new WooPaymentsApiClient();
		$filter = static function ( array $params ): array {
			$params['metadata']['filtered'] = 'yes';
			return $params;
		};

		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_api_request_params', $filter, 10, 3 );

		try {
			$result = $sut->refund_charge( 'ch_test', 250, 'requested_by_customer', 'native_transport', 'idem_test' );
		} finally {
			remove_filter( 'wcpay_api_request_params', $filter, 10 );
		}

		$this->assertSame( 're_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/refunds', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'application/json; charset=utf-8', $http_client->last_headers['Content-Type'] );
		$this->assertSame( self::EXPECTED_USER_AGENT, $http_client->last_headers['User-Agent'] );
		$this->assertSame( 'idem_test', $http_client->last_headers['Idempotency-Key'] );
		$this->assertArrayHasKey( 'X-Request-Initiated', $http_client->last_headers );
		$this->assertStringNotContainsString( 'idempotency_key', (string) $http_client->last_body );
		$this->assertStringContainsString( '"charge":"ch_test"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"filtered":"yes"', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should generate reference transport headers for non-GET requests without caller idempotency keys.
	 */
	public function test_post_request_generates_transport_headers_without_caller_idempotency_key(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 'cus_test' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_customer(
			array(
				'name'  => 'Ada Lovelace',
				'email' => 'ada@example.com',
			)
		);

		$this->assertSame( 'cus_test', $result );
		$this->assertSame( '/sites/123/wcpay/customers', $http_client->last_path );
		$this->assertStringStartsWith( '/sites/123/wcpay/', $http_client->last_path );
		$this->assertStringNotContainsString( '/transact/', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'application/json; charset=utf-8', $http_client->last_headers['Content-Type'] );
		$this->assertSame( self::EXPECTED_USER_AGENT, $http_client->last_headers['User-Agent'] );
		$this->assertNotEmpty( $http_client->last_headers['Idempotency-Key'] ?? '' );
		$this->assertNotEmpty( $http_client->last_headers['X-Request-Initiated'] ?? '' );
		$this->assertStringNotContainsString( 'idempotency_key', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should not generate idempotency headers for GET requests.
	 */
	public function test_get_request_does_not_generate_idempotency_header(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 'pm_test' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$filter = static function ( array $params ): array {
			$params['idempotency_key'] = 'ignored_for_get';
			return $params;
		};

		add_filter( 'wcpay_api_request_params', $filter, 10, 3 );

		try {
			$result = $sut->get_payment_method( 'pm_test' );
		} finally {
			remove_filter( 'wcpay_api_request_params', $filter, 10 );
		}

		$this->assertSame( 'pm_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/payment_methods/pm_test?test_mode=0', $http_client->last_path );
		$this->assertStringNotContainsString( '/transact/', $http_client->last_path );
		$this->assertStringNotContainsString( 'idempotency_key', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertArrayNotHasKey( 'Idempotency-Key', $http_client->last_headers );
		$this->assertArrayHasKey( 'X-Request-Initiated', $http_client->last_headers );
	}

	/**
	 * @testdox Should retry idempotent write requests when the native transport has no HTTP response.
	 */
	public function test_post_request_retries_transport_failure_with_idempotency_key(): void {
		$http_client            = new FakeWooPaymentsHttpClient();
		$http_client->blog_id   = 123;
		$http_client->responses = array(
			new WP_Error( 'http_request_failed', 'Could not connect to WPCOM.' ),
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'id' => 'cus_retry' ) ),
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_customer(
			array(
				'name'  => 'Ada Lovelace',
				'email' => 'ada@example.com',
			)
		);

		$this->assertSame( 'cus_retry', $result );
		$this->assertSame( 2, $http_client->request_count );
		$this->assertNotEmpty( $http_client->requests[0]['headers']['Idempotency-Key'] ?? '' );
		$this->assertSame( $http_client->requests[0]['headers']['Idempotency-Key'], $http_client->requests[1]['headers']['Idempotency-Key'] );
		$this->assertNotEmpty( $http_client->requests[0]['headers']['X-Request-Initiated'] ?? '' );
		$this->assertNotEmpty( $http_client->requests[1]['headers']['X-Request-Initiated'] ?? '' );
	}

	/**
	 * @testdox Should stop transient transport retries after the reference retry budget.
	 */
	public function test_post_request_stops_transient_transport_retries_after_retry_limit(): void {
		$http_client            = new FakeWooPaymentsHttpClient();
		$http_client->blog_id   = 123;
		$http_client->responses = array(
			new WP_Error( 'http_request_failed', 'Could not connect to WPCOM.' ),
			new WP_Error( 'http_request_failed', 'Could not connect to WPCOM.' ),
			new WP_Error( 'http_request_failed', 'Could not connect to WPCOM.' ),
			new WP_Error( 'http_request_failed', 'Could not connect to WPCOM.' ),
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'id' => 'cus_after_limit' ) ),
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$sut->create_customer(
				array(
					'name'  => 'Ada Lovelace',
					'email' => 'ada@example.com',
				)
			);
			$this->fail( 'Expected the transient transport error to surface after retry exhaustion.' );
		} catch ( WooPaymentsApiException $exception ) {
			$this->assertSame( 'http_request_failed', $exception->get_error_code() );
		}

		$this->assertSame( 4, $http_client->request_count, 'The retry budget is three retries after the initial attempt.' );
		$this->assertSame( $http_client->requests[0]['headers']['Idempotency-Key'], $http_client->requests[3]['headers']['Idempotency-Key'] );
	}

	/**
	 * @testdox Should not retry deterministic local transport readiness failures.
	 */
	public function test_post_request_does_not_retry_local_transport_readiness_failure(): void {
		$http_client            = new FakeWooPaymentsHttpClient();
		$http_client->blog_id   = 123;
		$http_client->responses = array(
			new WP_Error( 'wcpay_wpcom_not_connected', 'Site is not connected to WordPress.com.' ),
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'id' => 'cus_should_not_retry' ) ),
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$sut->create_customer(
				array(
					'name'  => 'Ada Lovelace',
					'email' => 'ada@example.com',
				)
			);
			$this->fail( 'Expected the local transport readiness failure to surface.' );
		} catch ( WooPaymentsApiException $exception ) {
			$this->assertSame( 'wcpay_wpcom_not_connected', $exception->get_error_code() );
		}

		$this->assertSame( 1, $http_client->request_count );
		$this->assertNotEmpty( $http_client->requests[0]['headers']['Idempotency-Key'] ?? '' );
	}

	/**
	 * @testdox Should not retry GET requests because they do not carry idempotency headers.
	 */
	public function test_get_request_does_not_retry_transport_failure_without_idempotency_key(): void {
		$http_client            = new FakeWooPaymentsHttpClient();
		$http_client->blog_id   = 123;
		$http_client->responses = array(
			new WP_Error( 'http_request_failed', 'Could not connect to WPCOM.' ),
			array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'id' => 'pm_test' ) ),
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$sut->get_payment_method( 'pm_test' );
			$this->fail( 'Expected the native transport request to surface a WooPaymentsApiException.' );
		} catch ( WooPaymentsApiException $exception ) {
			$this->assertSame( 'http_request_failed', $exception->get_error_code() );
		}

		$this->assertSame( 1, $http_client->request_count );
		$this->assertArrayNotHasKey( 'Idempotency-Key', $http_client->requests[0]['headers'] );
	}

	/**
	 * @testdox Should source test mode from the Core-owned account service.
	 */
	public function test_request_sources_test_mode_from_account_service(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 'cus_test' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$sut->create_customer(
			array(
				'name'  => 'Ada Lovelace',
				'email' => 'ada@example.com',
			)
		);

		$this->assertStringContainsString( '"test_mode":true', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should preserve server error codes and messages for failed native transport requests.
	 */
	public function test_request_preserves_server_error_codes_and_messages(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 402 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'error' => array(
						'code'    => 'card_declined',
						'message' => 'Card declined.',
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$sut->refund_charge( 'ch_test', 250, 'requested_by_customer', 'native_transport', 'idem_test' );
			$this->fail( 'Expected the native transport request to surface a WooPaymentsApiException.' );
		} catch ( WooPaymentsApiException $exception ) {
			$this->assertSame( 'card_declined', $exception->get_error_code() );
			$this->assertStringContainsString( 'Card declined', $exception->getMessage() );
		}
	}

	/**
	 * @testdox Should apply the preserved WooPayments response filter after transport requests.
	 */
	public function test_request_applies_preserved_response_filter_after_transport_requests(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 302 ),
			'headers'  => array( 'location' => 'https://local.test/wp-cron.php?doing_wp_cron=1' ),
			'body'     => '',
		);
		$filter_observations   = array();
		$filter                = static function ( $response, string $method, string $url, string $api ) use ( &$filter_observations ): array {
			$filter_observations = array(
				'method'        => $method,
				'url'           => $url,
				'api'           => $api,
				'response_code' => wp_remote_retrieve_response_code( $response ),
			);

			return array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode( array( 'id' => 're_filtered' ) ),
			);
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_api_request_response', $filter, 10, 4 );

		try {
			$result = $sut->refund_charge( 'ch_test', 250, 'requested_by_customer', 'native_transport', 'idem_test' );
		} finally {
			remove_filter( 'wcpay_api_request_response', $filter, 10 );
		}

		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 're_filtered', $result['id'] );
		$this->assertSame( 302, $filter_observations['response_code'] );
		$this->assertSame( 'POST', $filter_observations['method'] );
		$this->assertSame( 'refunds', $filter_observations['api'] );
		$this->assertStringStartsWith( 'https://public-api.wordpress.com/wpcom/v2/sites/%s/wcpay/refunds', $filter_observations['url'] );
	}

	/**
	 * @testdox Should create an embedded account session through the account-scoped user-token endpoint.
	 */
	public function test_create_embedded_account_session_posts_to_account_scoped_user_token_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'client_secret'   => 'cs_test',
					'expires_at'      => 1781740800,
					'account_id'      => 'acct_native',
					'is_live'         => false,
					'publishable_key' => 'pk_test_native',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_embedded_account_session();

		$this->assertSame( 'cs_test', $result['client_secret'] );
		$this->assertSame( '/sites/123/wcpay/accounts/embedded/session', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertTrue( $http_client->last_use_user_token, 'Embedded account sessions must use the connection-owner user token.' );
		$this->assertStringContainsString( '"test_mode":false', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should create a customer through the native transport customers endpoint.
	 */
	public function test_create_customer_posts_to_customers_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 'cus_test' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$customer_id = $sut->create_customer(
			array(
				'name'  => 'Ada Lovelace',
				'email' => 'ada@example.com',
			)
		);

		$this->assertSame( 'cus_test', $customer_id );
		$this->assertSame( '/sites/123/wcpay/customers', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"name":"Ada Lovelace"', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should update an existing customer through the native transport customer resource endpoint.
	 */
	public function test_update_customer_posts_to_customer_resource(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array() ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$sut->update_customer(
			'cus_test',
			array(
				'email' => 'ada@example.com',
			)
		);

		$this->assertSame( '/sites/123/wcpay/customers/cus_test', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"email":"ada@example.com"', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should create and confirm native WooPayments PaymentIntents with one payment credential and lifted idempotency.
	 */
	public function test_create_and_confirm_payment_intention_lifts_idempotency_and_preserves_request_shape(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'      => 'pi_test',
					'status'  => 'succeeded',
					'charges' => array(
						'total_count' => 0,
						'data'        => array(),
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_and_confirm_payment_intention(
			array(
				'amount'               => 1000,
				'currency'             => 'usd',
				'customer'             => 'cus_test',
				'metadata'             => array( 'order_id' => '123' ),
				'payment_method'       => 'pm_test',
				'payment_method_types' => array( 'card' ),
			),
			'idem_charge'
		);

		$this->assertSame( 'pi_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/intentions', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'idem_charge', $http_client->last_headers['Idempotency-Key'] );
		$this->assertStringContainsString( '"confirm":"true"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"payment_method":"pm_test"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"payment_method_types":["card"]', (string) $http_client->last_body );
		$this->assertStringNotContainsString( 'idempotency_key', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should create and confirm native WooPayments SetupIntents through the setup_intents endpoint.
	 */
	public function test_create_and_confirm_setup_intention_posts_to_setup_intents_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'            => 'seti_test',
					'status'        => 'succeeded',
					'client_secret' => 'seti_test_secret_abc',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_and_confirm_setup_intention(
			array(
				'customer'       => 'cus_test',
				'metadata'       => array( 'order_id' => '123' ),
				'payment_method' => 'pm_test',
			),
			'idem_setup'
		);

		$this->assertSame( 'seti_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/setup_intents', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'idem_setup', $http_client->last_headers['Idempotency-Key'] );
		$this->assertStringContainsString( '"confirm":"true"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"payment_method":"pm_test"', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should create unconfirmed native WooPayments SetupIntents with the server-compatible confirm flag.
	 */
	public function test_create_setup_intention_serializes_confirm_as_false_string(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'            => 'seti_unconfirmed',
					'status'        => 'requires_confirmation',
					'client_secret' => 'seti_unconfirmed_secret_abc',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_setup_intention(
			array(
				'customer'             => 'cus_test',
				'metadata'             => array( 'order_id' => '123' ),
				'payment_method_types' => array( 'card' ),
			),
			'idem_setup_unconfirmed'
		);

		$this->assertSame( 'seti_unconfirmed', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/setup_intents', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'idem_setup_unconfirmed', $http_client->last_headers['Idempotency-Key'] );
		$this->assertStringContainsString( '"confirm":"false"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"payment_method_types":["card"]', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should retrieve payment method details through the native transport payment methods endpoint.
	 */
	public function test_get_payment_method_reads_payment_methods_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'   => 'pm_test',
					'type' => 'card',
					'card' => array(
						'brand'     => 'visa',
						'last4'     => '4242',
						'exp_month' => 12,
						'exp_year'  => 2030,
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_payment_method( 'pm_test' );

		$this->assertSame( 'pm_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/payment_methods/pm_test?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertNull( $http_client->last_body );
	}

	/**
	 * @testdox Should track order payloads through the native transport tracking endpoint.
	 */
	public function test_track_order_posts_to_tracking_order_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'result' => 'success',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->track_order(
			array(
				'id'                 => 42,
				'_payment_method_id' => 'pm_test',
			),
			true
		);

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( '/sites/123/wcpay/tracking/order', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 42, $body['order_data']['id'] );
		$this->assertSame( 'pm_test', $body['order_data']['_payment_method_id'] );
		$this->assertTrue( $body['update'] );
		$this->assertTrue( $body['test_mode'] );
	}

	/**
	 * @testdox Should update payment method billing details through the native transport.
	 */
	public function test_update_payment_method_posts_billing_details(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 'pm_test' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->update_payment_method(
			'pm_test',
			array(
				'billing_details' => array(
					'email' => 'ada@example.com',
				),
			)
		);

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'pm_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/payment_methods/pm_test', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 'ada@example.com', $body['billing_details']['email'] );
		$this->assertTrue( $body['test_mode'] );
	}

	/**
	 * @testdox Should retrieve timeline events through the native transport.
	 */
	public function test_get_timeline_reads_timeline_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data' => array(
						array(
							'type' => 'captured',
						),
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_timeline( 'pi_test' );

		$this->assertSame( 'captured', $result['data'][0]['type'] );
		$this->assertSame( '/sites/123/wcpay/timeline/pi_test?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertNull( $http_client->last_body );
	}

	/**
	 * @testdox Should send store setup snapshots through the native transport.
	 */
	public function test_send_store_setup_posts_snapshot(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'result' => 'ok' ) ),
		);

		$account_service = $this->create_account_service( false, true );
		$sut             = new WooPaymentsApiClient();
		$sut->init( $http_client, $account_service );

		$result = $sut->send_store_setup(
			array(
				'gateway' => array(
					'enabled' => true,
				),
			)
		);

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( array(), $result );
		$this->assertSame( '/sites/123/wcpay/accounts/store_setup', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertFalse( $http_client->last_blocking );
		$this->assertIsArray( $body );
		$this->assertTrue( $body['snapshot']['gateway']['enabled'] );
		$this->assertTrue( $body['test_mode'] );
	}

	/**
	 * @testdox Should update compatibility data through the native transport.
	 */
	public function test_update_compatibility_data_posts_compatibility_payload(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'result' => 'ok' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->update_compatibility_data(
			array(
				'woocommerce_version' => '11.0.0',
			)
		);

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'ok', $result['result'] );
		$this->assertSame( '/sites/123/wcpay/compatibility', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( '11.0.0', $body['compatibility_data']['woocommerce_version'] );
		$this->assertFalse( $body['test_mode'] );
	}

	/**
	 * @testdox Should retrieve recommended payment methods through the public recommendations endpoint.
	 */
	public function test_get_recommended_payment_methods_reads_public_recommendations_endpoint(): void {
		$captured_url  = '';
		$captured_args = array();
		$filter        = static function ( $preempt, array $parsed_args, string $url ) use ( &$captured_url, &$captured_args ) {
			$captured_url  = $url;
			$captured_args = $parsed_args;

			return array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => wp_json_encode(
					array(
						array(
							'id'    => 'card',
							'title' => 'Cards',
						),
					)
				),
			);
		};

		add_filter( 'pre_http_request', $filter, 10, 3 );

		try {
			$sut    = new WooPaymentsApiClient();
			$result = $sut->get_recommended_payment_methods( 'GB', 'en_US' );
		} finally {
			remove_filter( 'pre_http_request', $filter, 10 );
		}

		$this->assertSame( 'card', $result[0]['id'] );
		$this->assertStringStartsWith( 'https://public-api.wordpress.com/wpcom/v2/wcpay/payment_methods/recommended?', $captured_url );
		$this->assertStringContainsString( 'country_code=GB', $captured_url );
		$this->assertStringContainsString( 'locale=en_US', $captured_url );
		$this->assertSame( self::EXPECTED_USER_AGENT, $captured_args['user-agent'] );
		$this->assertSame( 70, $captured_args['timeout'] );
		$this->assertTrue( $captured_args['sslverify'] );
	}

	/**
	 * @testdox Should build recommendation URLs from the Jetpack WPCOM JSON API base.
	 */
	public function test_get_recommended_payment_methods_uses_jetpack_wpcom_api_base(): void {
		$captured_url = '';
		$base_filter  = static function ( $constant_value, string $constant_name ) {
			return 'JETPACK__WPCOM_JSON_API_BASE' === $constant_name ? 'http://wpcom.localhost:30001' : $constant_value;
		};
		$http_filter  = static function ( $preempt, array $parsed_args, string $url ) use ( &$captured_url ) {
			unset( $parsed_args );
			$captured_url = $url;

			return array(
				'response' => array( 'code' => 200 ),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => '[]',
			);
		};

		add_filter( 'jetpack_constant_default_value', $base_filter, 10, 2 );
		add_filter( 'pre_http_request', $http_filter, 10, 3 );

		try {
			$sut = new WooPaymentsApiClient();
			$sut->get_recommended_payment_methods( 'GB', 'en_US' );
		} finally {
			remove_filter( 'pre_http_request', $http_filter, 10 );
			remove_filter( 'jetpack_constant_default_value', $base_filter, 10 );
		}

		$this->assertStringStartsWith( 'http://wpcom.localhost:30001/wpcom/v2/wcpay/payment_methods/recommended?', $captured_url );
	}

	/**
	 * @testdox Should retrieve onboarding field data through the native transport onboarding endpoint.
	 */
	public function test_get_onboarding_fields_data_reads_onboarding_fields_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'business_types' => array(
						array(
							'key'  => 'individual',
							'name' => 'Individual',
						),
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->get_onboarding_fields_data( 'en_US' );

		$this->assertSame( 'individual', $result['business_types'][0]['key'] );
		$this->assertSame( '/wcpay/onboarding/fields_data?test_mode=1&locale=en_US', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertNull( $http_client->last_body );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should initialize onboarding through the native onboarding endpoint.
	 */
	public function test_initialize_onboarding_posts_account_payload(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'url'   => 'https://connect.example.test',
					'state' => 'state_test',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->initialize_onboarding(
			false,
			'https://example.test/return',
			array( 'site_locale' => 'en_US' ),
			array( 'email' => 'merchant@example.com' ),
			array( 'business_type' => 'individual' ),
			array( 'wcpay-promo-test' ),
			'ref_test'
		);

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'state_test', $result['state'] );
		$this->assertSame( '/sites/123/wcpay/onboarding/init', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 'https://example.test/return', $body['return_url'] );
		$this->assertFalse( $body['create_live_account'] );
		$this->assertSame( 'en_US', $body['site_data']['site_locale'] );
		$this->assertSame( 'merchant@example.com', $body['user_data']['email'] );
		$this->assertSame( 'individual', $body['account_data']['business_type'] );
		$this->assertSame( array( 'wcpay-promo-test' ), $body['actioned_notes'] );
		$this->assertSame( 'ref_test', $body['referral_code'] );
		$this->assertTrue( $body['test_mode'] );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should preserve the existing onboarding payload filter for native onboarding.
	 */
	public function test_initialize_onboarding_applies_onboarding_data_args_filter(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'url' => false ) ),
		);
		$filter                = static function ( array $args ): array {
			$args['compatibility_data']                   = array( 'woocommerce' => '11.0.0' );
			$args['account_data']['woocommerce_store_id'] = 'store_123';
			return $args;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );
		add_filter( 'wc_payments_get_onboarding_data_args', $filter );

		try {
			$sut->initialize_onboarding(
				false,
				'https://example.test/return',
				array( 'site_locale' => 'en_US' ),
				array( 'email' => 'merchant@example.com' ),
				array( 'business_type' => 'individual' ),
				array(),
				'ref_test'
			);
		} finally {
			remove_filter( 'wc_payments_get_onboarding_data_args', $filter );
		}

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertIsArray( $body );
		$this->assertSame( 'https://example.test/return', $body['return_url'] );
		$this->assertSame( array( 'woocommerce' => '11.0.0' ), $body['compatibility_data'] );
		$this->assertSame( 'store_123', $body['account_data']['woocommerce_store_id'] );
		$this->assertSame( 'ref_test', $body['referral_code'] );
	}

	/**
	 * @testdox Should initialize embedded KYC through the native onboarding endpoint.
	 */
	public function test_initialize_onboarding_embedded_kyc_posts_account_payload(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'client_secret'   => 'secret_test',
					'publishable_key' => 'pk_test',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->initialize_onboarding_embedded_kyc(
			true,
			array( 'site_locale' => 'en_US' ),
			array( 'email' => 'merchant@example.com' ),
			array( 'business_type' => 'individual' ),
			array( 'wcpay-promo-test' )
		);

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'secret_test', $result['client_secret'] );
		$this->assertSame( '/sites/123/wcpay/onboarding/embedded', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertTrue( $body['create_live_account'] );
		$this->assertSame( 'en_US', $body['site_data']['site_locale'] );
		$this->assertSame( 'merchant@example.com', $body['user_data']['email'] );
		$this->assertSame( 'individual', $body['account_data']['business_type'] );
		$this->assertSame( array( 'wcpay-promo-test' ), $body['actioned_notes'] );
		$this->assertTrue( $body['test_mode'] );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should preserve the existing onboarding payload filter for embedded KYC.
	 */
	public function test_initialize_onboarding_embedded_kyc_applies_onboarding_data_args_filter(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'client_secret' => 'secret_test' ) ),
		);
		$filter                = static function ( array $args ): array {
			$args['compatibility_data']                   = array( 'woocommerce' => '11.0.0' );
			$args['account_data']['woocommerce_store_id'] = 'store_123';
			return $args;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );
		add_filter( 'wc_payments_get_onboarding_data_args', $filter );

		try {
			$sut->initialize_onboarding_embedded_kyc(
				true,
				array( 'site_locale' => 'en_US' ),
				array( 'email' => 'merchant@example.com' ),
				array( 'business_type' => 'individual' ),
				array()
			);
		} finally {
			remove_filter( 'wc_payments_get_onboarding_data_args', $filter );
		}

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertIsArray( $body );
		$this->assertSame( array( 'woocommerce' => '11.0.0' ), $body['compatibility_data'] );
		$this->assertSame( 'store_123', $body['account_data']['woocommerce_store_id'] );
	}

	/**
	 * @testdox Should finalize embedded KYC through the native onboarding endpoint.
	 */
	public function test_finalize_onboarding_embedded_kyc_posts_locale_source_and_notes(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'success' => true,
					'mode'    => 'live',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->finalize_onboarding_embedded_kyc( 'en_US', 'wcadmin-settings-page', array( 'wcpay-promo-test' ) );

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertTrue( $result['success'] );
		$this->assertSame( '/sites/123/wcpay/onboarding/embedded/finalize', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 'en_US', $body['locale'] );
		$this->assertSame( 'wcadmin-settings-page', $body['source'] );
		$this->assertSame( array( 'wcpay-promo-test' ), $body['actioned_notes'] );
		$this->assertFalse( $body['test_mode'] );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should delete the connected account through the native accounts endpoint.
	 */
	public function test_delete_account_posts_to_accounts_delete_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'result' => 'success',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->delete_account( true );

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( '/sites/123/wcpay/accounts/delete', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertTrue( $body['test_mode'] );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should update connected account settings through the native accounts endpoint.
	 */
	public function test_update_account_posts_to_accounts_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->update_account(
			array(
				'statement_descriptor'   => 'NATIVE STORE',
				'business_support_email' => 'support@example.test',
			)
		);
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertTrue( $result['success'] );
		$this->assertSame( '/sites/123/wcpay/accounts', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 'NATIVE STORE', $body['statement_descriptor'] );
		$this->assertSame( 'support@example.test', $body['business_support_email'] );
		$this->assertTrue( $body['test_mode'] );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should request account capabilities through the user-token native endpoint.
	 */
	public function test_request_capability_posts_to_capabilities_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'status' => 'active' ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->request_capability( 'link_payments', true );
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'active', $result['status'] );
		$this->assertSame( '/sites/123/wcpay/accounts/capabilities', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 'link_payments', $body['capability_id'] );
		$this->assertTrue( $body['requested'] );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should forward valid file uploads larger than the WooPay logo UI limit.
	 */
	public function test_upload_file_forwards_valid_files_larger_than_woopay_logo_ui_limit(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'id' => 'file_dispute_evidence' ) ),
		);
		$tmp_file              = tempnam( sys_get_temp_dir(), 'wcpay-large-file-' );
		$this->assertIsString( $tmp_file );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture temp file.
		file_put_contents( $tmp_file, str_repeat( 'x', 510001 ) );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/file' );
		$request->set_param( 'purpose', 'dispute_evidence' );
		$request->set_param( 'as_account', true );
		$request->set_file_params(
			array(
				'file' => array(
					'name'     => 'large-evidence.pdf',
					'type'     => 'application/pdf',
					'tmp_name' => $tmp_file,
					'error'    => 0,
					'size'     => 510001,
				),
			)
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$result = $sut->upload_file( $request );
			$body   = json_decode( (string) $http_client->last_body, true );

			$this->assertSame( 'file_dispute_evidence', $result['id'] );
			$this->assertSame( '/sites/123/wcpay/files', $http_client->last_path );
			$this->assertSame( 'POST', $http_client->last_method );
			$this->assertIsArray( $body );
			$this->assertSame( base64_encode( str_repeat( 'x', 510001 ) ), $body['file'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Expected provider payload encoding.
			$this->assertSame( 'large-evidence.pdf', $body['file_name'] );
			$this->assertSame( 'application/pdf', $body['file_type'] );
			$this->assertSame( 'dispute_evidence', $body['purpose'] );
			$this->assertTrue( $body['as_account'] );
		} finally {
			wp_delete_file( $tmp_file );
		}
	}

	/**
	 * @testdox Should wrap provider file upload failures with the preserved evidence upload code.
	 */
	public function test_upload_file_wraps_provider_failures_with_evidence_upload_error_code(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 413 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'error' => array(
						'code'    => 'file_too_large',
						'message' => 'The uploaded file is too large.',
					),
				)
			),
		);
		$tmp_file              = tempnam( sys_get_temp_dir(), 'wcpay-upload-error-' );
		$this->assertIsString( $tmp_file );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture temp file.
		file_put_contents( $tmp_file, 'evidence' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/payments/file' );
		$request->set_param( 'purpose', 'dispute_evidence' );
		$request->set_file_params(
			array(
				'file' => array(
					'name'     => 'evidence.pdf',
					'type'     => 'application/pdf',
					'tmp_name' => $tmp_file,
					'error'    => 0,
					'size'     => 8,
				),
			)
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$sut->upload_file( $request );
			$this->fail( 'Expected the provider file upload failure to be wrapped.' );
		} catch ( WooPaymentsApiException $exception ) {
			$this->assertSame( 'wcpay_evidence_file_upload_error', $exception->get_error_code() );
			$this->assertSame( 413, $exception->get_http_code() );
			$this->assertStringContainsString( 'The uploaded file is too large.', $exception->getMessage() );
		} finally {
			wp_delete_file( $tmp_file );
		}
	}

	/**
	 * @testdox Should fetch file details and contents through native file endpoints.
	 */
	public function test_get_file_details_and_contents_use_native_file_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'      => 'file_logo',
					'purpose' => 'business_logo',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$file  = $sut->get_file( 'file_logo', false );
		$query = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( 'file_logo', $file['id'] );
		$this->assertSame( '/sites/123/wcpay/files/file_logo', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( '0', $query['as_account'] );
		$this->assertSame( '0', $query['test_mode'] );
		$this->assertSame( 'GET', $http_client->last_method );

		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'content_type' => 'image/png',
					'file_content' => 'TE9HTw==',
				)
			),
		);

		$contents = $sut->get_file_contents( 'file_logo', false );
		$query    = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( 'image/png', $contents['content_type'] );
		$this->assertSame( 'TE9HTw==', $contents['file_content'] );
		$this->assertSame( '/sites/123/wcpay/files/file_logo/contents', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( '0', $query['as_account'] );
		$this->assertSame( '0', $query['test_mode'] );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should save fraud rulesets through the native fraud ruleset endpoint.
	 */
	public function test_save_fraud_ruleset_posts_to_fraud_ruleset_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->save_fraud_ruleset(
			array(
				array(
					'key'     => 'avs_verification',
					'outcome' => 'block',
				),
			)
		);
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertTrue( $result['success'] );
		$this->assertSame( '/sites/123/wcpay/fraud_ruleset', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 'avs_verification', $body['ruleset_config'][0]['key'] );
	}

	/**
	 * @testdox Should read the latest fraud ruleset through the native fraud ruleset endpoint.
	 */
	public function test_get_latest_fraud_ruleset_reads_from_fraud_ruleset_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'ruleset_config' => array(
						array(
							'key'     => 'avs_verification',
							'outcome' => 'block',
						),
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_latest_fraud_ruleset();

		$this->assertSame(
			array(
				array(
					'key'     => 'avs_verification',
					'outcome' => 'block',
				),
			),
			$result['ruleset_config']
		);
		$this->assertSame( '/sites/123/wcpay/fraud_ruleset?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should fetch account data through the native accounts endpoint with the WooCommerce store ID.
	 */
	public function test_get_account_fetches_accounts_endpoint_with_store_id(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'account_id'           => 'acct_native_123',
					'test_publishable_key' => 'pk_test_native',
					'is_live'              => false,
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false, true ) );

		$result = $sut->get_account( 'store_123' );

		$query = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( 'acct_native_123', $result['account_id'] );
		$this->assertSame( '/sites/123/wcpay/accounts', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertSame( 'store_123', $query['woocommerce_store_id'] );
		$this->assertSame( '1', $query['test_mode'] );
		$this->assertFalse( $http_client->last_use_user_token );
		$this->assertNull( $http_client->last_body );
	}

	/**
	 * @testdox Should fetch failed webhook events through the native WooPayments endpoint.
	 */
	public function test_get_failed_webhook_events_posts_to_failed_events_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data'     => array(
						array(
							'id'   => 'evt_failed_1',
							'type' => 'payment_intent.succeeded',
						),
					),
					'has_more' => true,
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->get_failed_webhook_events();
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( '/sites/123/wcpay/webhook/failed_events', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertTrue( $body['test_mode'] );
		$this->assertSame( 'evt_failed_1', $result['data'][0]['id'] );
		$this->assertTrue( $result['has_more'] );
	}

	/**
	 * @testdox Should create terminal connection tokens through the preserved WPCOM endpoint.
	 */
	public function test_create_terminal_connection_token_posts_to_terminal_connection_tokens_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'secret' => 'cnctok_test_secret',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->create_terminal_connection_token();
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( '/sites/123/wcpay/terminal/connection_tokens', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'cnctok_test_secret', $result['secret'] );
		$this->assertIsArray( $body );
		$this->assertTrue( $body['test_mode'] );
	}

	/**
	 * @testdox Should create terminal intents through the native intentions endpoint.
	 */
	public function test_create_terminal_payment_intention_posts_terminal_payment_payload(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'     => 'pi_terminal',
					'status' => 'requires_capture',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->create_terminal_payment_intention(
			array(
				'amount'               => 1234,
				'currency'             => 'usd',
				'capture_method'       => 'manual',
				'metadata'             => array( 'order_number' => '100' ),
				'payment_method_types' => array( 'card_present' ),
			)
		);
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'pi_terminal', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/intentions', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 1234, $body['amount'] );
		$this->assertSame( 'manual', $body['capture_method'] );
		$this->assertSame( array( 'card_present' ), $body['payment_method_types'] );
	}

	/**
	 * @testdox Should prepare terminal payments through the preserved intent subresource.
	 */
	public function test_prepare_terminal_payment_posts_to_intent_prepare_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'reader_id' => 'tmr_test',
					'status'    => 'collecting_payment_method',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->prepare_terminal_payment( 'pi_terminal', 42 );
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( '/sites/123/wcpay/intentions/pi_terminal/prepare_terminal_payment', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $body );
		$this->assertSame( 42, $body['order_id'] );
		$this->assertSame( 'collecting_payment_method', $result['status'] );
	}

	/**
	 * @testdox Should proxy terminal reader registration and location operations through preserved endpoints.
	 */
	public function test_terminal_reader_and_location_methods_use_preserved_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id' => 'tmr_test',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$sut->register_terminal_reader( 'tml_test', 'code_123', 'Counter', array( 'channel' => 'pos' ) );
		$reader_body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( '/sites/123/wcpay/terminal/readers', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $reader_body );
		$this->assertSame( 'tml_test', $reader_body['location'] );
		$this->assertSame( 'code_123', $reader_body['registration_code'] );
		$this->assertSame( 'Counter', $reader_body['label'] );
		$this->assertSame( array( 'channel' => 'pos' ), $reader_body['metadata'] );

		$sut->create_terminal_location(
			'Store',
			array(
				'country' => 'US',
				'line1'   => '123 Main',
			),
			array( 'source' => 'native' )
		);
		$location_body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( '/sites/123/wcpay/terminal/locations', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $location_body );
		$this->assertSame( 'Store', $location_body['display_name'] );
		$this->assertSame( 'US', $location_body['address']['country'] );
		$this->assertSame( array( 'source' => 'native' ), $location_body['metadata'] );
	}

	/**
	 * @testdox Should retrieve reader and location resources through preserved GET endpoints.
	 */
	public function test_terminal_reader_and_location_read_methods_use_preserved_get_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data' => array(),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$sut->get_terminal_readers();

		$this->assertSame( '/sites/123/wcpay/terminal/readers?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_terminal_locations();

		$this->assertSame( '/sites/123/wcpay/terminal/locations?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_terminal_location( 'tml_test' );

		$this->assertSame( '/sites/123/wcpay/terminal/locations/tml_test?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_readers_charge_summary( '2026-06-17', 'txn_test' );

		$this->assertStringStartsWith( '/sites/123/wcpay/reader-charges/summary?', $http_client->last_path );
		$this->assertStringContainsString( 'test_mode=1', $http_client->last_path );
		$this->assertStringContainsString( 'charge_date=2026-06-17', $http_client->last_path );
		$this->assertStringContainsString( 'transaction_id=txn_test', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_transaction( 'txn_test' );

		$this->assertSame( '/sites/123/wcpay/transactions/txn_test?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should update and delete terminal locations through preserved resource endpoints.
	 */
	public function test_terminal_location_mutations_use_preserved_resource_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id' => 'tml_test',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$sut->update_terminal_location( 'tml_test', 'Updated', array( 'line1' => '456 Market' ) );
		$update_body = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( '/sites/123/wcpay/terminal/locations/tml_test', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertIsArray( $update_body );
		$this->assertSame( 'Updated', $update_body['display_name'] );
		$this->assertSame( '456 Market', $update_body['address']['line1'] );

		$sut->delete_terminal_location( 'tml_test' );

		$this->assertSame( '/sites/123/wcpay/terminal/locations/tml_test', $http_client->last_path );
		$this->assertSame( 'DELETE', $http_client->last_method );
	}

	/**
	 * @testdox Should retrieve dispute summary through the preserved disputes endpoint.
	 */
	public function test_get_dispute_summary_uses_preserved_disputes_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'disputed_amount' => 500,
					'currency'        => 'usd',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$result = $sut->get_dispute_summary( 'du_test' );

		$this->assertSame( '/sites/123/wcpay/disputes/du_test/summary?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertSame( 500, $result['disputed_amount'] );
		$this->assertSame( 'usd', $result['currency'] );
	}

	/**
	 * @testdox Should reject invalid dispute summary route identifiers.
	 */
	public function test_get_dispute_summary_rejects_invalid_route_identifier(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array() ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_dispute_summary( '../du_test' );
	}

	/**
	 * @testdox Should retrieve payout overviews and lists through preserved deposits endpoints.
	 */
	public function test_deposits_read_methods_use_preserved_endpoints_and_query_names(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data'        => array(),
					'total_count' => 0,
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$sut->get_deposits_overview();

		$this->assertSame( '/sites/123/wcpay/deposits/overview-all?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_deposits(
			array(
				'page'              => 2,
				'pagesize'          => 25,
				'sort'              => 'date',
				'direction'         => 'desc',
				'store_currency_is' => 'usd',
				'status_is'         => 'paid',
			)
		);

		$this->assertStringStartsWith( '/sites/123/wcpay/deposits?', $http_client->last_path );
		$this->assertStringContainsString( 'test_mode=1', $http_client->last_path );
		$this->assertStringContainsString( 'page=2', $http_client->last_path );
		$this->assertStringContainsString( 'pagesize=25', $http_client->last_path );
		$this->assertStringContainsString( 'sort=date', $http_client->last_path );
		$this->assertStringContainsString( 'direction=desc', $http_client->last_path );
		$this->assertStringContainsString( 'store_currency_is=usd', $http_client->last_path );
		$this->assertStringContainsString( 'status_is=paid', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_deposits_summary(
			array(
				'store_currency_is' => 'usd',
				'status_is_not'     => 'failed',
			)
		);

		$this->assertStringStartsWith( '/sites/123/wcpay/deposits/summary?', $http_client->last_path );
		$this->assertStringContainsString( 'test_mode=1', $http_client->last_path );
		$this->assertStringContainsString( 'store_currency_is=usd', $http_client->last_path );
		$this->assertStringContainsString( 'status_is_not=failed', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should retrieve payout details and reject unsafe payout identifiers.
	 */
	public function test_get_deposit_uses_preserved_detail_endpoint_and_validates_identifier(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id' => 'po_test',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_deposit( 'po_test' );

		$this->assertSame( '/sites/123/wcpay/deposits/po_test?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertSame( 'po_test', $result['id'] );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_deposit( '../po_test' );
	}

	/**
	 * @testdox Should preserve deposits export and manual payout endpoints.
	 */
	public function test_deposits_export_and_manual_payout_methods_use_preserved_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'exported_deposits' => 42,
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$sut->get_deposits_export(
			array(
				'status_is'         => 'paid',
				'store_currency_is' => 'usd',
			),
			'merchant@example.com',
			'en_US'
		);

		$this->assertSame( '/sites/123/wcpay/deposits/download', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"test_mode":true', (string) $http_client->last_body );
		$this->assertStringContainsString( '"status_is":"paid"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"store_currency_is":"usd"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"user_email":"merchant@example.com"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"locale":"en_US"', (string) $http_client->last_body );

		$sut->get_payouts_export_url( 'poexp_test' );

		$this->assertSame( '/sites/123/wcpay/deposits/download/poexp_test?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->manual_deposit( 'instant', 'usd' );

		$this->assertSame( '/sites/123/wcpay/deposits', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"type":"instant"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"currency":"usd"', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should retrieve authorizations through the preserved list endpoint and request hook.
	 */
	public function test_get_authorizations_preserves_list_endpoint_and_request_hook(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data' => array(
						array( 'payment_intent_id' => 'pi_auth' ),
					),
				)
			),
		);
		$observed_request      = null;
		$filter                = static function ( List_Authorizations $request ) use ( &$observed_request ): List_Authorizations {
			$observed_request = $request;
			$request->set_param( 'pagesize', 50 );
			$request->set_param( 'customer_email_is', 'ada@example.com' );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->assertTrue( method_exists( $sut, 'get_authorizations' ), 'WooPaymentsApiClient should expose get_authorizations().' );

		add_filter( 'wcpay_list_authorizations_request', $filter );

		try {
			$result = $sut->get_authorizations(
				array(
					'page'      => 2,
					'pagesize'  => 25,
					'sort'      => 'created',
					'direction' => 'desc',
				)
			);
		} finally {
			remove_filter( 'wcpay_list_authorizations_request', $filter );
		}

		$this->assertSame( 'pi_auth', $result['data'][0]['payment_intent_id'] );
		$this->assertInstanceOf( List_Authorizations::class, $observed_request );
		$this->assertSame( 'authorizations', $observed_request->get_api() );
		$this->assertSame( 'GET', $observed_request->get_method() );
		$this->assertSame( '/sites/123/wcpay/authorizations?test_mode=0&page=2&pagesize=50&sort=created&direction=desc&limit=100&customer_email_is=ada%40example.com', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertNull( $http_client->last_body );
	}

	/**
	 * @testdox Should retrieve a single authorization through the preserved detail endpoint and request hook.
	 */
	public function test_get_authorization_preserves_detail_endpoint_and_request_hook(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'payment_intent_id' => 'pi_auth',
					'is_captured'       => false,
				)
			),
		);
		$observed_request      = null;
		$filter                = static function ( WooPaymentsApiRequest $request ) use ( &$observed_request ): WooPaymentsApiRequest {
			$observed_request = $request;

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->assertTrue( method_exists( $sut, 'get_authorization' ), 'WooPaymentsApiClient should expose get_authorization().' );

		add_filter( 'wcpay_get_authorization_request', $filter );

		try {
			$result = $sut->get_authorization( 'pi_auth' );
		} finally {
			remove_filter( 'wcpay_get_authorization_request', $filter );
		}

		$this->assertSame( 'pi_auth', $result['payment_intent_id'] );
		$this->assertInstanceOf( WooPaymentsApiRequest::class, $observed_request );
		$this->assertSame( 'authorizations/pi_auth', $observed_request->get_api() );
		$this->assertSame( 'GET', $observed_request->get_method() );
		$this->assertSame( '/sites/123/wcpay/authorizations/pi_auth?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_authorization( '../pi_auth' );
	}

	/**
	 * @testdox Should preserve authorization summary filters and the legacy summary hook.
	 */
	public function test_get_authorizations_summary_preserves_filters_and_legacy_hook(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'count' => 3,
				)
			),
		);
		$observed_request      = null;
		$filter                = static function ( WooPaymentsApiRequest $request ) use ( &$observed_request ): WooPaymentsApiRequest {
			$observed_request = $request;
			$request->set_param( 'pagesize', 50 );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );
		add_filter( 'wc_pay_get_authorizations_summary', $filter );

		try {
			$result = $sut->get_authorizations_summary(
				array(
					'page'     => 2,
					'pagesize' => 25,
				)
			);
		} finally {
			remove_filter( 'wc_pay_get_authorizations_summary', $filter );
		}

		$this->assertSame( 3, $result['count'] );
		$this->assertInstanceOf( WooPaymentsApiRequest::class, $observed_request );
		$this->assertSame( 'authorizations/summary', $observed_request->get_api() );
		$this->assertSame( '/sites/123/wcpay/authorizations/summary?test_mode=1&page=2&pagesize=50', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should retrieve Capital active loan summary and loans through preserved endpoints.
	 */
	public function test_capital_admin_methods_use_preserved_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data' => array(),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$sut->get_capital_active_loan_summary();

		$this->assertSame( '/sites/123/wcpay/capital/active_loan_summary?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertNull( $http_client->last_body );

		$sut->get_capital_loans();

		$this->assertSame( '/sites/123/wcpay/capital/loans?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertNull( $http_client->last_body );
	}

	/**
	 * @testdox Should create Capital financing offer links through the preserved accounts endpoint.
	 */
	public function test_create_capital_link_posts_financing_offer_payload_to_capital_links_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'url' => 'https://capital.example.test/view-offer',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$this->assertTrue( method_exists( $sut, 'create_capital_link' ), 'WooPaymentsApiClient should expose create_capital_link().' );

		$result = $sut->create_capital_link(
			'https://example.test/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview',
			'https://example.test/wp-admin/admin.php?wcpay-loan-offer'
		);
		$body   = json_decode( (string) $http_client->last_body, true );

		$this->assertSame( 'https://capital.example.test/view-offer', $result['url'] );
		$this->assertSame( '/sites/123/wcpay/accounts/capital_links?test_mode=1', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertTrue( $http_client->last_use_user_token );
		$this->assertSame(
			array(
				'type'        => 'capital_financing_offer',
				'return_url'  => 'https://example.test/wp-admin/admin.php?page=wc-settings&tab=checkout&path=/woopayments/overview',
				'refresh_url' => 'https://example.test/wp-admin/admin.php?wcpay-loan-offer',
			),
			$body
		);
	}

	/**
	 * @testdox Should preserve legacy Capital request filters before dispatch.
	 */
	public function test_capital_admin_methods_preserve_legacy_request_filters(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data' => array(),
				)
			),
		);

		$summary_filter = function ( WooPaymentsApiRequest $request ): WooPaymentsApiRequest {
			$this->assertSame( 'capital/active_loan_summary', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'include', 'details' );

			return $request;
		};
		$loans_filter   = function ( WooPaymentsApiRequest $request ): WooPaymentsApiRequest {
			$this->assertSame( 'capital/loans', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'limit', 25 );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );
		add_filter( 'wcpay_get_active_loan_summary_request', $summary_filter );
		add_filter( 'wcpay_get_loans_request', $loans_filter );

		try {
			$sut->get_capital_active_loan_summary();
			$summary_path = $http_client->last_path;

			$sut->get_capital_loans();
			$loans_path = $http_client->last_path;
		} finally {
			remove_filter( 'wcpay_get_active_loan_summary_request', $summary_filter );
			remove_filter( 'wcpay_get_loans_request', $loans_filter );
		}

		$this->assertStringContainsString( 'include=details', $summary_path );
		$this->assertStringContainsString( 'test_mode=1', $summary_path );
		$this->assertStringContainsString( 'limit=25', $loans_path );
		$this->assertStringContainsString( 'test_mode=1', $loans_path );
	}

	/**
	 * @testdox Should preserve the legacy Capital link request filter object.
	 */
	public function test_create_capital_link_preserves_legacy_request_filter_object(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'url' => 'https://capital.example.test/filtered-offer',
				)
			),
		);

		$observed_request = null;
		$filter           = function ( \WCPay\Core\Server\Request\Get_Account_Capital_Link $request ) use ( &$observed_request ): \WCPay\Core\Server\Request\Get_Account_Capital_Link {
			$observed_request = $request;

			$this->assertSame( 'accounts/capital_links', $request->get_api() );
			$this->assertSame( 'POST', $request->get_method() );
			$this->assertTrue( $request->should_use_user_token() );
			$this->assertSame( 'capital_financing_offer', $request->get_param( 'type' ) );
			$this->assertSame( 'https://example.test/return', $request->get_param( 'return_url' ) );
			$this->assertSame( 'https://example.test/refresh', $request->get_param( 'refresh_url' ) );
			$request->set_type( 'filtered_capital_financing_offer' );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$this->assertTrue( method_exists( $sut, 'create_capital_link' ), 'WooPaymentsApiClient should expose create_capital_link().' );

		add_filter( 'wcpay_get_account_capital_link', $filter );

		try {
			$sut->create_capital_link( 'https://example.test/return', 'https://example.test/refresh' );
		} finally {
			remove_filter( 'wcpay_get_account_capital_link', $filter );
		}

		$body = json_decode( (string) $http_client->last_body, true );

		$this->assertInstanceOf( WooPaymentsApiRequest::class, $observed_request );
		$this->assertSame( 'filtered_capital_financing_offer', $body['type'] );
		$this->assertSame( '/sites/123/wcpay/accounts/capital_links?test_mode=1', $http_client->last_path );
		$this->assertTrue( $http_client->last_use_user_token );
	}

	/**
	 * @testdox Should expose Capital request filters as legacy request objects.
	 */
	public function test_capital_admin_methods_expose_legacy_request_object_aliases(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data' => array(),
				)
			),
		);

		$observed_summary_request = null;
		$observed_loans_request   = null;

		$summary_filter = function ( \WCPay\Core\Server\Request $request ) use ( &$observed_summary_request ): \WCPay\Core\Server\Request {
			$observed_summary_request = $request;
			$this->assertSame( 'capital/active_loan_summary', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'include', 'details' );

			return $request;
		};
		$loans_filter   = function ( \WCPay\Core\Server\Request\Get_Request $request ) use ( &$observed_loans_request ): \WCPay\Core\Server\Request\Get_Request {
			$observed_loans_request = $request;
			$this->assertSame( 'capital/loans', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'limit', 25 );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );
		add_filter( 'wcpay_get_active_loan_summary_request', $summary_filter );
		add_filter( 'wcpay_get_loans_request', $loans_filter );

		try {
			$sut->get_capital_active_loan_summary();
			$summary_path = $http_client->last_path;
			$sut->get_capital_loans();
			$loans_path = $http_client->last_path;
		} finally {
			remove_filter( 'wcpay_get_active_loan_summary_request', $summary_filter );
			remove_filter( 'wcpay_get_loans_request', $loans_filter );
		}

		$this->assertInstanceOf( WooPaymentsApiRequest::class, $observed_summary_request );
		$this->assertInstanceOf( WooPaymentsApiRequest::class, $observed_loans_request );
		$this->assertStringContainsString( 'include=details', $summary_path );
		$this->assertStringContainsString( 'test_mode=1', $summary_path );
		$this->assertStringContainsString( 'limit=25', $loans_path );
		$this->assertStringContainsString( 'test_mode=1', $loans_path );
	}

	/**
	 * @testdox Should preserve transactions list, summary, search, detail, and export endpoints.
	 */
	public function test_transactions_admin_methods_use_preserved_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'data'        => array(),
					'total_count' => 0,
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( true ) );

		$sut->get_transactions(
			array(
				'page'       => 2,
				'pagesize'   => 25,
				'deposit_id' => 'po_test',
			)
		);

		$this->assertStringStartsWith( '/sites/123/wcpay/transactions?', $http_client->last_path );
		$this->assertStringContainsString( 'test_mode=1', $http_client->last_path );
		$this->assertStringContainsString( 'page=2', $http_client->last_path );
		$this->assertStringContainsString( 'pagesize=25', $http_client->last_path );
		$this->assertStringContainsString( 'deposit_id=po_test', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_transactions_summary( array( 'store_currency_is' => 'usd' ), 'po_test' );

		$this->assertStringStartsWith( '/sites/123/wcpay/transactions/summary?', $http_client->last_path );
		$this->assertStringContainsString( 'store_currency_is=usd', $http_client->last_path );
		$this->assertStringContainsString( 'deposit_id=po_test', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$http_client->response['body'] = wp_json_encode(
			array(
				array(
					'customer_name'  => 'Ada Lovelace',
					'customer_email' => 'ada@example.com',
				),
			)
		);

		$sut->get_transactions_search_autocomplete( 'Ada' );

		$this->assertStringStartsWith( '/sites/123/wcpay/transactions/search?', $http_client->last_path );
		$this->assertStringContainsString( 'search_term=Ada', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_fraud_outcomes(
			array(
				'status'      => 'review',
				'search_term' => 'Ada',
			)
		);

		$this->assertStringStartsWith( '/sites/123/wcpay/fraud_outcomes/status/review?', $http_client->last_path );
		$this->assertStringContainsString( 'search_term=Ada', $http_client->last_path );
		$this->assertStringNotContainsString( 'status=review', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_transaction( 'txn_test' );

		$this->assertSame( '/sites/123/wcpay/transactions/txn_test?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_transactions_export( array( 'type_is' => 'charge' ), 'merchant@example.com', 'po_test', 'en_US' );

		$this->assertSame( '/sites/123/wcpay/transactions/download', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"type_is":"charge"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"user_email":"merchant@example.com"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"deposit_id":"po_test"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"locale":"en_US"', (string) $http_client->last_body );

		$sut->get_transactions_export_url( 'txexp-test.01==' );

		$this->assertSame( '/sites/123/wcpay/transactions/download/txexp-test.01==?test_mode=1', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should preserve disputes list, summary, detail, update, close, and export endpoints.
	 */
	public function test_disputes_admin_methods_use_preserved_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					'id'     => 'dp_test',
					'reason' => 'fraudulent',
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$sut->get_disputes( array( 'status_is' => 'needs_response' ) );

		$this->assertStringStartsWith( '/sites/123/wcpay/disputes?', $http_client->last_path );
		$this->assertStringContainsString( 'test_mode=0', $http_client->last_path );
		$this->assertStringContainsString( 'status_is=needs_response', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_disputes_summary( array( 'currency_is' => 'usd' ) );

		$this->assertStringStartsWith( '/sites/123/wcpay/disputes/summary?', $http_client->last_path );
		$this->assertStringContainsString( '0%5Bcurrency_is%5D=usd', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_dispute( 'dp_test' );

		$this->assertSame( '/sites/123/wcpay/disputes/dp_test?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->update_dispute( 'dp_test', array( 'customer_name' => 'Ada' ), true, array( 'order_id' => 123 ) );

		$this->assertSame( '/sites/123/wcpay/disputes/dp_test', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"customer_name":"Ada"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"submit":true', (string) $http_client->last_body );
		$this->assertStringContainsString( '"order_id":123', (string) $http_client->last_body );

		$sut->close_dispute( 'dp_test' );

		$this->assertSame( '/sites/123/wcpay/disputes/dp_test/close', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );

		$sut->get_disputes_export( array( 'status_is' => 'needs_response' ), 'merchant@example.com', 'en_US' );

		$this->assertSame( '/sites/123/wcpay/disputes/download', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"status_is":"needs_response"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"user_email":"merchant@example.com"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"locale":"en_US"', (string) $http_client->last_body );

		$sut->get_disputes_export_url( 'dpexp-test.01==' );

		$this->assertSame( '/sites/123/wcpay/disputes/download/dpexp-test.01==?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should reject unsafe transaction export identifiers.
	 */
	public function test_get_transactions_export_url_rejects_invalid_route_identifier(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array() ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_transactions_export_url( '../txexp_test' );
	}

	/**
	 * @testdox Should reject invalid fraud outcome statuses.
	 */
	public function test_get_fraud_outcomes_rejects_invalid_status(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array() ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_fraud_outcomes( array( 'status' => 'invalid' ) );
	}

	/**
	 * @testdox Should reject unsafe dispute export identifiers.
	 */
	public function test_get_disputes_export_url_rejects_invalid_route_identifier(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array() ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_disputes_export_url( 'dpexp%2Ftest' );
	}

	/**
	 * @testdox Should call preserved admin badge count endpoints.
	 */
	public function test_gets_preserved_admin_badge_count_endpoints(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'count' => 3 ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$sut->get_dispute_status_counts();

		$this->assertSame( '/sites/123/wcpay/disputes/status_counts?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );

		$sut->get_authorizations_summary();

		$this->assertSame( '/sites/123/wcpay/authorizations/summary?test_mode=0', $http_client->last_path );
		$this->assertSame( 'GET', $http_client->last_method );
	}

	/**
	 * @testdox Should preserve admin badge count legacy request filters.
	 */
	public function test_admin_badge_count_methods_preserve_legacy_request_filters(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'count' => 3 ) ),
		);

		$dispute_hook_called       = false;
		$authorization_hook_called = false;

		$dispute_filter = function ( WooPaymentsApiRequest $request ) use ( &$dispute_hook_called ): WooPaymentsApiRequest {
			$dispute_hook_called = true;
			$this->assertSame( 'disputes/status_counts', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'status_is', 'needs_response' );

			return $request;
		};

		$authorization_filter = function ( WooPaymentsApiRequest $request ) use ( &$authorization_hook_called ): WooPaymentsApiRequest {
			$authorization_hook_called = true;
			$this->assertSame( 'authorizations/summary', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'manual_capture', true );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_get_dispute_status_counts', $dispute_filter );
		add_filter( 'wc_pay_get_authorizations_summary', $authorization_filter );

		try {
			$sut->get_dispute_status_counts();
			$dispute_path = $http_client->last_path;

			$sut->get_authorizations_summary();
			$authorization_path = $http_client->last_path;
		} finally {
			remove_filter( 'wcpay_get_dispute_status_counts', $dispute_filter );
			remove_filter( 'wc_pay_get_authorizations_summary', $authorization_filter );
		}

		$this->assertTrue( $dispute_hook_called, 'Dispute badge count requests should run the preserved request filter.' );
		$this->assertStringContainsString( 'status_is=needs_response', $dispute_path );
		$this->assertStringContainsString( 'test_mode=0', $dispute_path );
		$this->assertTrue( $authorization_hook_called, 'Authorization summary requests should run the preserved request filter.' );
		$this->assertStringContainsString( 'manual_capture=true', $authorization_path );
		$this->assertStringContainsString( 'test_mode=0', $authorization_path );
	}

	/**
	 * @testdox Should fetch payment method promotions through the preserved platform endpoint.
	 */
	public function test_get_pm_promotions_uses_preserved_platform_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode(
				array(
					array(
						'id'             => 'klarna-promo__spotlight',
						'promo_id'       => 'klarna-promo',
						'payment_method' => 'klarna',
						'type'           => 'spotlight',
						'title'          => 'Activate Klarna',
						'description'    => 'Offer flexible payments.',
						'cta_label'      => 'Activate now',
						'tc_url'         => 'https://example.com/terms',
						'tc_label'       => 'See terms',
					),
				)
			),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_pm_promotions(
			array(
				'dismissals' => array( 'klarna-promo__spotlight' => 1781740800 ),
				'locale'     => 'en_US',
			)
		);
		$query  = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( 'klarna-promo__spotlight', $result[0]['id'] );
		$this->assertSame( '/sites/123/wcpay/payment_method_promotions', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( 'GET', $http_client->last_method );
		$this->assertSame( '0', $query['test_mode'] );
		$this->assertSame( 'en_US', $query['locale'] );
		$this->assertSame(
			array( 'klarna-promo__spotlight' => 1781740800 ),
			json_decode( (string) $query['dismissals'], true )
		);
	}

	/**
	 * @testdox Should activate payment method promotions through the preserved platform endpoint.
	 */
	public function test_activate_pm_promotion_uses_preserved_platform_endpoint(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->activate_pm_promotion( 'klarna-promo__spotlight' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( '/sites/123/wcpay/payment_method_promotions/klarna-promo__spotlight/activate', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
	}

	/**
	 * @testdox Should preserve payment method promotion legacy request filters.
	 */
	public function test_pm_promotion_methods_preserve_legacy_request_filters(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$get_hook_called      = false;
		$activate_hook_called = false;
		$get_filter           = function ( WooPaymentsApiRequest $request ) use ( &$get_hook_called ): WooPaymentsApiRequest {
			$get_hook_called = true;
			$this->assertSame( 'payment_method_promotions', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_param( 'locale', 'fr_FR' );

			return $request;
		};
		$activate_filter      = function ( WooPaymentsApiRequest $request ) use ( &$activate_hook_called ): WooPaymentsApiRequest {
			$activate_hook_called = true;
			$this->assertSame( 'payment_method_promotions/klarna-promo__spotlight/activate', $request->get_api() );
			$this->assertSame( 'POST', $request->get_method() );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_get_pm_promotions_request', $get_filter );
		add_filter( 'wcpay_activate_pm_promotion_request', $activate_filter );

		try {
			$sut->get_pm_promotions( array( 'locale' => 'en_US' ) );
			$get_path = $http_client->last_path;

			$sut->activate_pm_promotion( 'klarna-promo__spotlight' );
		} finally {
			remove_filter( 'wcpay_get_pm_promotions_request', $get_filter );
			remove_filter( 'wcpay_activate_pm_promotion_request', $activate_filter );
		}

		$this->assertTrue( $get_hook_called, 'PM promotions list requests should run the preserved request filter.' );
		$this->assertStringContainsString( 'locale=fr_FR', $get_path );
		$this->assertTrue( $activate_hook_called, 'PM promotion activation requests should run the preserved request filter.' );
	}

	/**
	 * @testdox Should expose payment method promotion filters as concrete legacy request objects.
	 */
	public function test_pm_promotion_methods_expose_concrete_legacy_request_object_aliases(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$observed_get_request      = null;
		$observed_activate_request = null;
		$get_filter                = function ( \WCPay\Core\Server\Request\Get_PM_Promotions $request ) use ( &$observed_get_request ): \WCPay\Core\Server\Request\Get_PM_Promotions {
			$observed_get_request = $request;
			$this->assertSame( 'payment_method_promotions', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$this->assertTrue( $request->should_return_raw_response() );
			$request->set_store_context_params( array( 'locale' => 'fr_FR' ) );

			return $request;
		};
		$activate_filter           = function ( \WCPay\Core\Server\Request\Activate_PM_Promotion $request ) use ( &$observed_activate_request ): \WCPay\Core\Server\Request\Activate_PM_Promotion {
			$observed_activate_request = $request;
			$this->assertSame( 'klarna-promo__spotlight', $request->get_id() );
			$this->assertSame( 'payment_method_promotions/klarna-promo__spotlight/activate', $request->get_api() );
			$this->assertSame( 'POST', $request->get_method() );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_get_pm_promotions_request', $get_filter );
		add_filter( 'wcpay_activate_pm_promotion_request', $activate_filter );

		try {
			$sut->get_pm_promotions( array( 'locale' => 'en_US' ) );
			$get_path = $http_client->last_path;

			$sut->activate_pm_promotion( 'klarna-promo__spotlight' );
		} finally {
			remove_filter( 'wcpay_get_pm_promotions_request', $get_filter );
			remove_filter( 'wcpay_activate_pm_promotion_request', $activate_filter );
		}

		$this->assertInstanceOf( WooPaymentsGetPmPromotionsRequest::class, $observed_get_request );
		$this->assertInstanceOf( WooPaymentsActivatePmPromotionRequest::class, $observed_activate_request );
		$this->assertStringContainsString( 'locale=fr_FR', $get_path );
	}

	/**
	 * @testdox Should list documents through the preserved documents request object and filter.
	 */
	public function test_get_documents_preserves_legacy_request_filter_contract(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'data' => array() ) ),
		);
		$observed_request      = null;

		$filter = function ( List_Documents $request ) use ( &$observed_request ): List_Documents {
			$observed_request = $request;
			$this->assertSame( 'documents', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_type_is( 'vat_invoice' );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_list_documents_request', $filter );

		try {
			$result = $sut->get_documents(
				array(
					'page'     => 2,
					'pagesize' => 50,
					'match'    => 'all',
					'type_is'  => 'statement',
					'ignored'  => 'drop-me',
				)
			);
		} finally {
			remove_filter( 'wcpay_list_documents_request', $filter );
		}

		$this->assertSame( array( 'data' => array() ), $result );
		$this->assertInstanceOf( WooPaymentsDocumentsListRequest::class, $observed_request );
		$query = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( '/sites/123/wcpay/documents', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( '0', $query['test_mode'] );
		$this->assertSame( '2', $query['page'] );
		$this->assertSame( '50', $query['pagesize'] );
		$this->assertSame( 'date', $query['sort'] );
		$this->assertSame( 'desc', $query['direction'] );
		$this->assertSame( '100', $query['limit'] );
		$this->assertSame( 'all', $query['match'] );
		$this->assertSame( 'vat_invoice', $query['type_is'] );
	}

	/**
	 * @testdox Should request documents summary with filter-only params.
	 */
	public function test_get_documents_summary_forwards_filter_only_params(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'count' => 3 ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_documents_summary(
			array(
				'match'       => 'all',
				'date_before' => '2026-06-18',
				'type_is'     => 'vat_invoice',
				'page'        => 2,
				'pagesize'    => 50,
			)
		);

		$this->assertSame( array( 'count' => 3 ), $result );
		$query = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( '/sites/123/wcpay/documents/summary', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( '0', $query['test_mode'] );
		$this->assertSame( 'all', $query['match'] );
		$this->assertSame( '2026-06-18', $query['date_before'] );
		$this->assertSame( 'vat_invoice', $query['type_is'] );
		$this->assertArrayNotHasKey( 'page', $query );
		$this->assertArrayNotHasKey( 'pagesize', $query );
	}

	/**
	 * @testdox Should download document responses without JSON decoding.
	 */
	public function test_get_document_returns_raw_document_response(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'headers'  => array(
				'content-type'        => 'application/pdf',
				'content-disposition' => 'attachment; filename="invoice.pdf"',
			),
			'body'     => '%PDF document',
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->get_document( 'vat_invoice-123' );

		$this->assertSame( $http_client->response, $result );
		$this->assertSame( '/sites/123/wcpay/documents/vat_invoice-123?test_mode=0', $http_client->last_path );
	}

	/**
	 * @testdox Should reject unsafe document identifiers.
	 */
	public function test_get_document_rejects_invalid_route_identifier(): void {
		$sut = new WooPaymentsApiClient();
		$sut->init( new FakeWooPaymentsHttpClient(), $this->create_account_service( false ) );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_document( '../vat_invoice-123' );
	}

	/**
	 * @testdox Should validate VAT through the preserved VAT request filter.
	 */
	public function test_validate_vat_preserves_legacy_request_filter_contract(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'is_valid' => true ) ),
		);
		$hook_called           = false;

		$filter = function ( WooPaymentsApiRequest $request ) use ( &$hook_called ): WooPaymentsApiRequest {
			$hook_called = true;
			$this->assertSame( 'vat/RO123456', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_validate_vat_request', $filter );

		try {
			$result = $sut->validate_vat( 'RO123456' );
		} finally {
			remove_filter( 'wcpay_validate_vat_request', $filter );
		}

		$this->assertSame( array( 'is_valid' => true ), $result );
		$this->assertTrue( $hook_called, 'VAT validation should run the preserved request filter.' );
		$this->assertSame( '/sites/123/wcpay/vat/RO123456?test_mode=0', $http_client->last_path );
	}

	/**
	 * @testdox Should not double-encode already encoded VAT route parameters.
	 */
	public function test_validate_vat_does_not_double_encode_route_parameter(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'is_valid' => true ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->validate_vat( 'CHE%20123' );

		$this->assertSame( array( 'is_valid' => true ), $result );
		$this->assertSame( '/sites/123/wcpay/vat/CHE%20123?test_mode=0', $http_client->last_path );
		$this->assertStringNotContainsString( '%2520', $http_client->last_path );
	}

	/**
	 * @testdox Should save VAT details with optional VAT number, name, and address.
	 */
	public function test_save_vat_details_posts_optional_vat_payload(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'success' => true ) ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$result = $sut->save_vat_details( 'RO123456', 'ACME SRL', '1 Market Street' );

		$this->assertSame( array( 'success' => true ), $result );
		$this->assertSame( '/sites/123/wcpay/vat', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertStringContainsString( '"vat_number":"RO123456"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"name":"ACME SRL"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"address":"1 Market Street"', (string) $http_client->last_body );
	}

	/**
	 * @testdox Should reject unsafe payout export identifiers.
	 */
	public function test_get_payouts_export_url_rejects_invalid_route_identifier(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array() ),
		);

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		$this->expectException( WooPaymentsApiException::class );

		$sut->get_payouts_export_url( '../poexp_test' );
	}

	/**
	 * @testdox Should retrieve reporting balance summary through the preserved request object and filter.
	 */
	public function test_get_reporting_balance_summary_preserves_legacy_request_filter_contract(): void {
		$http_client           = new FakeWooPaymentsHttpClient();
		$http_client->blog_id  = 123;
		$http_client->response = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => wp_json_encode( array( 'data' => array( 'available' => array() ) ) ),
		);
		$observed_request      = null;

		$filter = function ( Get_Reporting_Balance_Summary $request ) use ( &$observed_request ): Get_Reporting_Balance_Summary {
			$observed_request = $request;
			$this->assertSame( 'reporting/balance_summary', $request->get_api() );
			$this->assertSame( 'GET', $request->get_method() );
			$request->set_currency( 'EUR' );

			return $request;
		};

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );
		add_filter( 'wcpay_get_reporting_balance_summary_request', $filter );

		try {
			$result = $sut->get_reporting_balance_summary(
				array(
					'date_start' => '2026-06-01T00:00:00Z',
					'date_end'   => '2026-06-19T23:59:59Z',
					'currency'   => 'usd',
					'ignored'    => 'drop-me',
				)
			);
		} finally {
			remove_filter( 'wcpay_get_reporting_balance_summary_request', $filter );
		}

		$this->assertSame( array( 'data' => array( 'available' => array() ) ), $result );
		$this->assertInstanceOf( WooPaymentsReportingBalanceSummaryRequest::class, $observed_request );
		$query = array();
		parse_str( (string) wp_parse_url( $http_client->last_path, PHP_URL_QUERY ), $query );

		$this->assertSame( '/sites/123/wcpay/reporting/balance_summary', strtok( $http_client->last_path, '?' ) );
		$this->assertSame( '0', $query['test_mode'] );
		$this->assertSame( '2026-06-01T00:00:00Z', $query['date_start'] );
		$this->assertSame( '2026-06-19T23:59:59Z', $query['date_end'] );
		$this->assertSame( 'eur', $query['currency'] );
		$this->assertArrayNotHasKey( 'ignored', $query );
	}

	/**
	 * @testdox Should reject invalid reporting balance summary currencies before transport.
	 */
	public function test_get_reporting_balance_summary_rejects_invalid_currency_before_transport(): void {
		$http_client          = new FakeWooPaymentsHttpClient();
		$http_client->blog_id = 123;

		$sut = new WooPaymentsApiClient();
		$sut->init( $http_client, $this->create_account_service( false ) );

		try {
			$sut->get_reporting_balance_summary(
				array(
					'date_start' => '2026-06-01T00:00:00Z',
					'date_end'   => '2026-06-19T23:59:59Z',
					'currency'   => 'usd1',
				)
			);
			$this->fail( 'Invalid reporting currency should throw before transport.' );
		} catch ( WooPaymentsApiException $exception ) {
			$this->assertSame( 400, $exception->get_http_code() );
		}

		$this->assertSame( '', $http_client->last_path );
	}

	/**
	 * Create a WooPayments account service mock.
	 *
	 * @param bool      $test_mode            Whether WooPayments should run in test mode.
	 * @param bool|null $test_mode_onboarding Whether WooPayments should use test-mode onboarding.
	 * @return WooPaymentsAccountService
	 */
	private function create_account_service( bool $test_mode, ?bool $test_mode_onboarding = null ): WooPaymentsAccountService {
		$account_service = $this->getMockBuilder( WooPaymentsAccountService::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'is_test_mode_enabled', 'is_test_mode_onboarding_enabled' ) )
			->getMock();

		$account_service->method( 'is_test_mode_enabled' )->willReturn( $test_mode );
		$account_service->method( 'is_test_mode_onboarding_enabled' )->willReturn( $test_mode_onboarding ?? $test_mode );

		return $account_service;
	}
}
