<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\Api;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiClient;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api\WooPaymentsApiException;
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

		$sut->init( $http_client );
		add_filter( 'wcpay_api_request_params', $filter, 10, 3 );

		try {
			$result = $sut->refund_charge( 'ch_test', 250, 'requested_by_customer', 'native_transport', 'idem_test' );
		} finally {
			remove_filter( 'wcpay_api_request_params', $filter, 10 );
		}

		$this->assertSame( 're_test', $result['id'] );
		$this->assertSame( '/sites/123/wcpay/refunds/ch_test', $http_client->last_path );
		$this->assertSame( 'POST', $http_client->last_method );
		$this->assertSame( 'idem_test', $http_client->last_headers['Idempotency-Key'] );
		$this->assertStringNotContainsString( 'idempotency_key', (string) $http_client->last_body );
		$this->assertStringContainsString( '"filtered":"yes"', (string) $http_client->last_body );
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
		$sut->init( $http_client );

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
		$sut->init( $http_client );

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
		$sut->init( $http_client );

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
		$sut->init( $http_client );

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
		$sut->init( $http_client );

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
		$this->assertStringContainsString( '"confirm":true', (string) $http_client->last_body );
		$this->assertStringContainsString( '"payment_method":"pm_test"', (string) $http_client->last_body );
	}
}
