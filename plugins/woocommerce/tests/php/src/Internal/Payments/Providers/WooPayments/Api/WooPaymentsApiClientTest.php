<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\Api;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsAccountService;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsApiClient class.
 */
class WooPaymentsApiClientTest extends WC_Unit_Test_Case {

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
		$this->assertSame( 'idem_test', $http_client->last_headers['Idempotency-Key'] );
		$this->assertStringNotContainsString( 'idempotency_key', (string) $http_client->last_body );
		$this->assertStringContainsString( '"charge":"ch_test"', (string) $http_client->last_body );
		$this->assertStringContainsString( '"filtered":"yes"', (string) $http_client->last_body );
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
		$this->assertStringStartsWith( 'WooCommerce Payments/', $captured_args['user-agent'] );
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
