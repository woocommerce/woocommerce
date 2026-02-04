<?php
/**
 * Unit tests for PayPal Request class.
 *
 * @package WooCommerce\Tests\Gateways\PayPal
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\Request as PayPalRequest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class RequestTest.
 */
class RequestTest extends \WC_Unit_Test_Case {

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
	}

	/**
	 * Tear down the test environment.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		remove_all_filters( 'pre_http_request' );

		parent::tearDown();
	}

	/**
	 * Test create_paypal_order when API returns error.
	 *
	 * @return void
	 */
	public function test_create_paypal_order_error(): void {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'create_paypal_order_error' ), 10, 3 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'create_paypal_order_error' ) );

		$this->assertNull( $result );
	}

	/**
	 * Test create_paypal_order when API returns success.
	 *
	 * @return void
	 */
	public function test_create_paypal_order_success(): void {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'create_paypal_order_success' ), 10, 3 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'create_paypal_order_success' ) );

		$this->assertNotNull( $result, 'create_paypal_order should return an array, not null' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'redirect_url', $result );
	}

	/**
	 * Test that the create_paypal_order params are correct.
	 *
	 * @return void
	 */
	public function test_create_paypal_order_params_are_correct(): void {
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$order = \WC_Helper_Order::create_order();
		$order->set_cart_tax( 10 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 60 );
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'check_create_paypal_order_params' ), 10, 3 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'check_create_paypal_order_params' ), 10 );

		$this->assertNotNull( $result );
	}

	/**
	 * Check that the create_paypal_order params are correct.
	 *
	 * @param bool   $value      Original value.
	 * @param array  $parsed_args Parsed arguments.
	 * @param string $url The URL of the request.
	 *
	 * @return array|bool Return a 200 response or false if the URL is not a create-order request.
	 */
	public function check_create_paypal_order_params( $value, $parsed_args, $url ) {
		// Match Jetpack proxy requests for PayPal orders.
		// Check if this is a POST request to the proxy order endpoint.
		if ( ! isset( $parsed_args['method'] ) || 'POST' !== $parsed_args['method'] ) {
			return $value;
		}

		// Check if URL contains the create order endpoint.
		if ( strpos( $url, 'paypal_standard/proxy/order' ) === false ) {
			return $value;
		}

		// Perform assertions to validate the request parameters.
		$this->assertEquals( 'application/json', $parsed_args['headers']['Content-Type'] );
		$this->assertEquals( 'POST', $parsed_args['method'] );
		$body = json_decode( $parsed_args['body'], true );
		$this->assertArrayHasKey( 'order', $body );
		$order_payload = $body['order'];
		$this->assertEquals( 'CAPTURE', $order_payload['intent'] );

		$purchase_unit = $order_payload['purchase_units'][0];
		$this->assertEquals( '60.00', $purchase_unit['amount']['value'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['currency_code'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['breakdown']['item_total']['currency_code'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['breakdown']['shipping']['currency_code'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['breakdown']['tax_total']['currency_code'] );
		$this->assertEquals( '40.00', $purchase_unit['amount']['breakdown']['item_total']['value'] );
		$this->assertEquals( '10.00', $purchase_unit['amount']['breakdown']['shipping']['value'] );
		$this->assertEquals( '10.00', $purchase_unit['amount']['breakdown']['tax_total']['value'] );

		if ( ! empty( $purchase_unit['items'] ) ) {
			$items = $purchase_unit['items'];
			$this->assertEquals( 'Dummy Product', $items[0]['name'] );
			$this->assertEquals( '4', $items[0]['quantity'] );
			$this->assertEquals( '10.00', $items[0]['unit_amount']['value'] );
			$this->assertEquals( 'USD', $items[0]['unit_amount']['currency_code'] );
		} else {
			$this->assertArrayNotHasKey( 'items', $purchase_unit );
		}

		$this->assertArrayHasKey( 'payment_source', $order_payload );
		$this->assertArrayHasKey( 'paypal', $order_payload['payment_source'] );
		$this->assertArrayHasKey( 'experience_context', $order_payload['payment_source']['paypal'] );
		$this->assertArrayHasKey( 'return_url', $order_payload['payment_source']['paypal']['experience_context'] );
		$this->assertArrayHasKey( 'cancel_url', $order_payload['payment_source']['paypal']['experience_context'] );

		$custom_id = json_decode( $order_payload['purchase_units'][0]['custom_id'], true );
		$this->assertArrayHasKey( 'order_id', $custom_id );
		$this->assertArrayHasKey( 'order_key', $custom_id );
		$this->assertArrayHasKey( 'site_url', $custom_id );
		$this->assertArrayHasKey( 'site_id', $custom_id );
		$this->assertArrayHasKey( 'v', $custom_id );

		return array(
			'response' => array(
				'code' => 200,
			),
			'body'     => wp_json_encode(
				array(
					'id'     => '123',
					'status' => 'CREATED',
					'links'  => array(
						array(
							'rel'    => 'approve',
							'href'   => 'https://www.paypal.com/checkoutnow?token=123',
							'method' => 'GET',
						),
					),
				)
			),
		);
	}

	/**
	 * Helper function for creating PayPal order success response.
	 *
	 * @param bool   $value      Original pre-value, likely to be false.
	 * @param array  $parsed_args Parsed arguments.
	 * @param string $url The URL of the request.
	 *
	 * @return array|bool Return a 200 response or false if the URL is not a create-order request.
	 */
	public function create_paypal_order_success( $value, $parsed_args, $url ) {
		// Match Jetpack proxy requests for PayPal orders.
		// Check if this is a POST request to the proxy order endpoint.
		if ( ! isset( $parsed_args['method'] ) || 'POST' !== $parsed_args['method'] ) {
			return $value;
		}

		// Check if URL contains the create order endpoint.
		if ( strpos( $url, 'paypal_standard/proxy/order' ) === false ) {
			return $value;
		}

		return array(
			'response' => array(
				'code' => 200,
			),
			'body'     => wp_json_encode(
				array(
					'id'     => '123',
					'status' => 'CREATED',
					'links'  => array(
						array(
							'rel'    => 'approve',
							'href'   => 'https://www.paypal.com/checkoutnow?token=123',
							'method' => 'GET',
						),
					),
				)
			),
		);
	}

	/**
	 * Helper function for creating PayPal order error response.
	 *
	 * @param bool   $value      Original pre-value, likely to be false.
	 * @param array  $parsed_args Parsed arguments.
	 * @param string $url The URL of the request.
	 *
	 * @return array|bool Return a 500 error response or false if the URL is not a create-order request.
	 */
	public function create_paypal_order_error( $value, $parsed_args, $url ) {
		// Match Jetpack proxy requests for PayPal orders.
		// Check if this is a POST request to the proxy order endpoint.
		if ( ! isset( $parsed_args['method'] ) || 'POST' !== $parsed_args['method'] ) {
			return $value;
		}

		// Check if URL contains the create order endpoint.
		if ( strpos( $url, 'paypal_standard/proxy/order' ) === false ) {
			return $value;
		}

		// Return a 500 error.
		return array( 'response' => array( 'code' => 500 ) );
	}

	/**
	 * Helper method to return valid site ID for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_valid_site_id( $value ): array {
		return array( 'id' => 12345 );
	}

	/**
	 * Helper method to return valid blog token for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_blog_token( $value ): array {
		return array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' );
	}

	/**
	 * Data provider for normalize_url_for_paypal test scenarios.
	 *
	 * @return array
	 */
	public function provider_normalize_url_scenarios(): array {
		return array(
			'absolute_url_https'                   => array(
				'input'    => 'https://example.com/checkout',
				'expected' => 'https://example.com/checkout',
			),
			'absolute_url_http'                    => array(
				'input'    => 'http://example.com/checkout',
				'expected' => 'http://example.com/checkout',
			),
			'relative_url_with_leading_slash'      => array(
				'input'    => '/checkout',
				'expected' => home_url() . '/checkout',
			),
			'relative_url_without_leading_slash'   => array(
				'input'    => 'checkout',
				'expected' => home_url() . '/checkout',
			),
			'url_with_encoded_ampersand'           => array(
				'input'    => 'https://example.com/checkout?foo=bar&#038;baz=qux',
				'expected' => 'https://example.com/checkout?foo=bar&baz=qux',
			),
			'url_with_multiple_encoded_ampersands' => array(
				'input'    => 'https://example.com/checkout?a=1&#038;b=2&#038;c=3',
				'expected' => 'https://example.com/checkout?a=1&b=2&c=3',
			),
			'relative_url_with_encoded_ampersand'  => array(
				'input'    => '/checkout?foo=bar&#038;baz=qux',
				'expected' => home_url() . '/checkout?foo=bar&baz=qux',
			),
			'url_starting_with_home_url'           => array(
				'input'    => home_url() . '/checkout',
				'expected' => home_url() . '/checkout',
			),
			'url_starting_with_home_url_and_encoded_ampersand' => array(
				'input'    => home_url() . '/checkout?foo=bar&#038;baz=qux',
				'expected' => home_url() . '/checkout?foo=bar&baz=qux',
			),
			'empty_string'                         => array(
				'input'    => '',
				'expected' => home_url() . '/',
			),
			'url_with_query_params'                => array(
				'input'    => '/checkout?order_id=123&key=abc',
				'expected' => home_url() . '/checkout?order_id=123&key=abc',
			),
			'url_with_fragment'                    => array(
				'input'    => '/checkout#payment',
				'expected' => home_url() . '/checkout#payment',
			),
			'url_with_different_domain'            => array(
				'input'    => 'https://external.com/callback',
				'expected' => 'https://external.com/callback',
			),
			'url_with_html_entities'               => array(
				'input'    => '/checkout?product=Test<Product>',
				'expected' => home_url() . '/checkout?product=TestProduct',
			),
		);
	}

	/**
	 * Test normalize_url_for_paypal with various URL scenarios.
	 *
	 * @dataProvider provider_normalize_url_scenarios
	 *
	 * @param string $input    The input URL to normalize.
	 * @param string $expected The expected normalized URL.
	 *
	 * @return void
	 */
	public function test_normalize_url_for_paypal( string $input, string $expected ): void {
		$gateway = new \WC_Gateway_Paypal();
		$request = new PayPalRequest( $gateway );

		// Use reflection to access the private method.
		$reflection = new \ReflectionClass( $request );
		$method     = $reflection->getMethod( 'normalize_url_for_paypal' );
		$method->setAccessible( true );

		$result = $method->invokeArgs( $request, array( $input ) );

		$this->assertEquals( $expected, $result );
	}

	// ========================================================================
	// Tests for capture_authorized_payment method
	// ========================================================================

	/**
	 * Test capture is not attempted when order is null.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_not_attempted_when_order_is_null(): void {
		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}
				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( null );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was not called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when order is null' );
	}

	/**
	 * Test capture is not attempted when PayPal Order ID is missing.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_not_attempted_when_paypal_order_id_missing(): void {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was not called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when PayPal Order ID is missing' );
	}

	/**
	 * Test capture is skipped when payment is already captured (via capture_id).
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_skipped_when_already_captured_via_capture_id(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, 'CAPTURE_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was not called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when payment already captured' );
		// Verify status was not changed.
		$this->assertEquals( 'CAPTURE_123', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true ) );
	}

	/**
	 * Data provider for already captured status scenarios.
	 *
	 * @return array
	 */
	public function provider_already_captured_statuses(): array {
		return array(
			'status_captured'  => array( PayPalConstants::STATUS_CAPTURED ),
			'status_completed' => array( PayPalConstants::STATUS_COMPLETED ),
		);
	}

	/**
	 * Test capture is skipped when payment status is already captured or completed.
	 *
	 * @dataProvider provider_already_captured_statuses
	 *
	 * @param string $status The payment status.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_skipped_when_status_already_captured( string $status ): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, $status );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was not called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when status is ' . $status );
		// Verify status remained the same.
		$this->assertEquals( $status, $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );
	}

	/**
	 * Test capture succeeds with HTTP 200 response.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_succeeds_with_http_200(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was called once.
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called once' );
		// Verify status was updated.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals(
			PayPalConstants::STATUS_CAPTURED,
			$order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true )
		);
	}

	/**
	 * Test capture fails with various HTTP error codes.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_fails(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_AUTHORIZED );
		$order->save();

		$capture_api_call_count = 0;
		$debug_id               = 'DEBUG_ID_12345';
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count, $debug_id ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_error( 400, array( 'debug_id' => $debug_id ) );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was called (but returned error).
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called once' );
		// Verify order note was added.
		$order = wc_get_order( $order->get_id() );
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'limit'    => 1,
			)
		);

		$this->assertNotEmpty( $notes );
		$this->assertStringContainsString( 'PayPal capture authorized payment failed', $notes[0]->content );
		$this->assertStringContainsString( $debug_id, $notes[0]->content );
		// Verify capture ID was not set.
		$this->assertEmpty( $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true ) );
		// Verify status was not updated.
		$this->assertEquals( PayPalConstants::STATUS_AUTHORIZED, $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );
	}

	/**
	 * Test capture handles 404 error and sets authorization_checked flag.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_404_error_and_sets_authorization_checked_flag(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, PayPalConstants::STATUS_AUTHORIZED );
		$order->save();

		$capture_api_call_count = 0;
		$authorization_id       = 'AUTH_123';

		$filter_callback = function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
			// Track if capture_auth endpoint is called.
			if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
				++$capture_api_call_count;
				return $this->return_capture_error( 404, array() );
			}

			return $value;
		};
		add_filter( 'pre_http_request', $filter_callback, 10, 3 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_filter( 'pre_http_request', $filter_callback, 10 );

		// Verify capture_auth API was called (but returned 404).
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called once' );
		// Verify order note was added with authorization ID message.
		$order = wc_get_order( $order->get_id() );
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'limit'    => 1,
			)
		);

		$this->assertNotEmpty( $notes );
		$this->assertStringContainsString( 'PayPal capture authorized payment failed', $notes[0]->content );
		$this->assertStringContainsString( 'Authorization ID: ' . $authorization_id . ' not found', $notes[0]->content );
		// Verify authorization_checked flag was set.
		$this->assertEquals( 'yes', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, true ), 'Expected _paypal_authorization_checked flag to be set to yes' );
		// Verify capture ID was not set.
		$this->assertEmpty( $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true ) );
		// Verify status was not updated.
		$this->assertEquals( PayPalConstants::STATUS_AUTHORIZED, $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );
	}

	/**
	 * Test capture handles WP_Error response.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_wp_error_response(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return new \WP_Error( 'http_request_failed', 'Connection timeout' );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was called (but returned WP_Error).
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called once' );
		// Verify order note was added.
		$order = wc_get_order( $order->get_id() );
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'limit'    => 1,
			)
		);

		$this->assertNotEmpty( $notes );
		$this->assertStringContainsString( 'PayPal capture authorized payment failed', $notes[0]->content );
	}

	/**
	 * Test capture request includes correct parameters.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_request_includes_correct_parameters(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->save();

		$captured_request       = null;
		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$captured_request, &$capture_api_call_count ) {
				// Capture the capture_auth request.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					$captured_request = $parsed_args;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was called exactly once.
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called once' );
		$this->assertNotNull( $captured_request, 'Expected to capture the request' );
		$this->assertEquals( 'POST', $captured_request['method'] );
		$this->assertEquals( 'application/json', $captured_request['headers']['Content-Type'] );

		$body = json_decode( $captured_request['body'], true );
		$this->assertIsArray( $body );
		$this->assertEquals( 'AUTH_123', $body['authorization_id'] );
		$this->assertEquals( 'PAYPAL_ORDER_123', $body['paypal_order_id'] );
		$this->assertArrayHasKey( 'test_mode', $body );
	}

	/**
	 * Test authorization ID is retrieved from API when not in meta.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_retrieves_authorization_id_from_api(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		// Don't set _paypal_authorization_id.
		$order->save();

		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) {
				// Mock get PayPal order details API call.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'             => 'PAYPAL_ORDER_123',
								'status'         => 'COMPLETED',
								'purchase_units' => array(
									array(
										'payments' => array(
											'authorizations' => array(
												array(
													'id' => 'AUTH_1',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-01T00:00:00Z',
												),
												array(
													'id' => 'AUTH_3',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-02-02T00:00:00Z',
												),
												array(
													'id' => 'AUTH_2',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-03T00:00:00Z',
												),
											),
										),
									),
								),
							)
						),
					);
				}

				// Mock capture API call.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify authorization ID was stored.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'AUTH_3', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, true ) );
	}

	/**
	 * Test capture is skipped when API returns capture data in order details.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_skipped_when_api_returns_capture_data(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Mock get PayPal order details API call.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'             => 'PAYPAL_ORDER_123',
								'status'         => 'COMPLETED',
								'purchase_units' => array(
									array(
										'payments' => array(
											'authorizations' => array(
												array(
													'id' => 'AUTH_1',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-01T00:00:00Z',
												),
											),
											'captures' => array(
												array(
													'id' => 'CAPTURE_1',
													'status' => PayPalConstants::STATUS_CAPTURED,
													'update_time' => '2024-01-01T00:00:00Z',
												),
												array(
													'id' => 'CAPTURE_2',
													'status' => PayPalConstants::STATUS_COMPLETED,
													'update_time' => '2024-02-02T00:00:00Z',
												),
											),
										),
									),
								),
							)
						),
					);
				}

				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was NOT called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when capture already exists' );
		// Verify capture ID was stored and no capture request was made.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'CAPTURE_2', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true ) );
		$this->assertEquals( PayPalConstants::STATUS_COMPLETED, $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );
	}

	/**
	 * Test capture is skipped when authorization status is already CAPTURED.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_skipped_when_authorization_status_is_captured(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Mock get PayPal order details API call.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'             => 'PAYPAL_ORDER_123',
								'status'         => 'COMPLETED',
								'purchase_units' => array(
									array(
										'payments' => array(
											'authorizations' => array(
												array(
													'id' => 'AUTH_123',
													'status' => PayPalConstants::STATUS_CAPTURED,
													'update_time' => '2024-01-01T00:00:00Z',
												),
											),
										),
									),
								),
							)
						),
					);
				}

				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was NOT called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when authorization already captured' );
		// Verify status was updated but no capture request was made.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( PayPalConstants::STATUS_CAPTURED, $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true ) );
	}

	/**
	 * Test authorization checked flag prevents repeated API calls.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_authorization_checked_flag_prevents_repeated_calls(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, 'yes' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was not called.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when authorization_checked flag is set' );
		// Verify capture ID was not set.
		$this->assertEmpty( $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true ) );
	}

	/**
	 * Test capture handles API exception during authorization ID retrieval.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_api_exception_during_retrieval(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Mock get PayPal order details API call with error.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 500 ),
						'body'     => wp_json_encode( array( 'error' => 'Internal Server Error' ) ),
					);
				}

				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was NOT called after error.
		$this->assertEquals( 0, $capture_api_call_count, 'Expected no capture_auth API call when order details retrieval fails' );
		// Verify capture ID was not set.
		$order = wc_get_order( $order->get_id() );
		$this->assertEmpty( $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_CAPTURE_ID, true ) );
	}

	/**
	 * Test get_latest_transaction_data selects most recent authorization.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_selects_most_recent_authorization(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) {
				// Mock get PayPal order details API call with multiple authorizations.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'             => 'PAYPAL_ORDER_123',
								'status'         => 'COMPLETED',
								'purchase_units' => array(
									array(
										'payments' => array(
											'authorizations' => array(
												array(
													'id' => 'AUTH_OLD',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-01T00:00:00Z',
												),
												array(
													'id' => 'AUTH_NEWEST',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-03T00:00:00Z',
												),
												array(
													'id' => 'AUTH_MIDDLE',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-02T00:00:00Z',
												),
											),
										),
									),
								),
							)
						),
					);
				}

				// Mock capture API call.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify the most recent authorization ID was stored.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'AUTH_NEWEST', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, true ) );
	}

	/**
	 * Test capture in test mode vs production mode.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_respects_test_mode_setting(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->save();

		// Test with test mode enabled.
		$gateway           = new \WC_Gateway_Paypal();
		$gateway->testmode = true;

		$captured_request       = null;
		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$captured_request, &$capture_api_call_count ) {
				// Capture the capture_auth request.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					$captured_request = $parsed_args;
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( $gateway );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was called.
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called' );
		$body = json_decode( $captured_request['body'], true );
		$this->assertTrue( $body['test_mode'], 'Expected test_mode to be true' );
	}

	/**
	 * Test capture handles empty authorization array from API.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_empty_authorization_array(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) {
				// Mock get PayPal order details API call with empty authorizations.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'             => 'PAYPAL_ORDER_123',
								'status'         => 'COMPLETED',
								'purchase_units' => array(
									array(
										'payments' => array(
											'authorizations' => array(),
										),
									),
								),
							)
						),
					);
				}

				// Mock capture API call.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify authorization_checked flag was set.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'yes', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, true ) );
	}

	/**
	 * Test capture handles authorization with invalid update_time.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_invalid_update_time(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) {
				// Mock get PayPal order details API call with invalid update_time.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'             => 'PAYPAL_ORDER_123',
								'status'         => 'COMPLETED',
								'purchase_units' => array(
									array(
										'payments' => array(
											'authorizations' => array(
												array(
													'id' => 'AUTH_NO_TIME',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													// Missing update_time.
												),
												array(
													'id' => 'AUTH_VALID',
													'status' => PayPalConstants::STATUS_AUTHORIZED,
													'update_time' => '2024-01-01T00:00:00Z',
												),
											),
										),
									),
								),
							)
						),
					);
				}

				// Mock capture API call.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify the valid authorization was used.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'AUTH_VALID', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, true ) );
	}

	/**
	 * Test capture handles missing purchase_units in API response.
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_missing_purchase_units(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->save();

		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) {
				// Mock get PayPal order details API call without purchase_units.
				if ( \strpos( $url, 'order/PAYPAL_ORDER_123' ) !== false ) {
					return array(
						'response' => array( 'code' => 200 ),
						'body'     => wp_json_encode(
							array(
								'id'     => 'PAYPAL_ORDER_123',
								'status' => 'COMPLETED',
							)
						),
					);
				}

				// Mock capture API call.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					return $this->return_capture_success_200( $value, $parsed_args );
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify authorization_checked flag was set.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'yes', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_CHECKED, true ) );
	}

	/**
	 * Test capture handles already captured authorization errors (from the PayPal side).
	 *
	 * @return void
	 */
	public function test_capture_authorized_payment_handles_auth_already_captured_errors(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'PAYPAL_ORDER_123' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_AUTHORIZATION_ID, 'AUTH_123' );
		$order->save();

		$capture_api_call_count = 0;
		add_filter(
			'pre_http_request',
			function ( $value, $parsed_args, $url ) use ( &$capture_api_call_count ) {
				// Track if capture_auth endpoint is called.
				if ( \strpos( $url, 'payment/capture_auth' ) !== false ) {
					++$capture_api_call_count;
					return array(
						'response' => array( 'code' => 422 ),
						'body'     => wp_json_encode(
							array(
								'name'     => 'UNPROCESSABLE_ENTITY',
								'message'  => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
								'debug_id' => '1234567890',
								'details'  => array(
									array(
										'issue'       => PayPalConstants::PAYPAL_ISSUE_AUTHORIZATION_ALREADY_CAPTURED,
										'description' => 'The authorization has already been captured.',
									),
								),
							)
						),
					);
				}

				return $value;
			},
			10,
			3
		);

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$request->capture_authorized_payment( $order );

		remove_all_filters( 'pre_http_request' );

		// Verify capture_auth API was called once.
		$this->assertEquals( 1, $capture_api_call_count, 'Expected capture_auth API to be called once' );

		// Verify status was updated.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals(
			PayPalConstants::STATUS_CAPTURED,
			$order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS, true )
		);
	}

	// ========================================================================
	// Helper methods for capture_authorized_payment tests
	// ========================================================================

	/**
	 * Helper method to return HTTP 200 success response for capture.
	 *
	 * @param mixed $value      Original value.
	 * @param array $parsed_args Parsed arguments.
	 *
	 * @return array
	 */
	public function return_capture_success_200( $value, $parsed_args ): array {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'id'     => 'CAPTURE_123',
					'status' => PayPalConstants::STATUS_COMPLETED,
				)
			),
		);
	}

	/**
	 * Helper method to return error response for capture.
	 *
	 * @param int   $http_code HTTP error code.
	 * @param array $body_data Additional body data.
	 *
	 * @return array
	 */
	public function return_capture_error( int $http_code, array $body_data = array() ): array {
		$default_body = array(
			'name'    => 'ERROR',
			'message' => 'An error occurred',
		);

		return array(
			'response' => array( 'code' => $http_code ),
			'body'     => wp_json_encode( array_merge( $default_body, $body_data ) ),
		);
	}
}
