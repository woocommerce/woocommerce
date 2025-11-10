<?php
/**
 * Unit tests for Request class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;
use Automattic\WooCommerce\Gateways\PayPal\Request as PayPalRequest;

/**
 * Class RequestTests.
 */
class RequestTests extends \WC_Unit_Test_Case {
	/**
	 * Set up the test environment.
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
	 */
	public function tearDown(): void {
		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		parent::tearDown();
	}

	/**
	 * Test create_paypal_order when API returns error.
	 */
	public function test_create_paypal_order_error() {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'create_paypal_order_error' ), 10, 2 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'create_paypal_order_error' ) );

		$this->assertNull( $result );
	}

	/**
	 * Test create_paypal_order when API returns success.
	 */
	public function test_create_paypal_order_success() {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'create_paypal_order_success' ), 10, 2 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'create_paypal_order_success' ) );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'redirect_url', $result );
	}

	/**
	 * Test that the create_paypal_order params are correct.
	 */
	public function test_create_paypal_order_params_are_correct() {
		$order = \WC_Helper_Order::create_order();
		$order->set_cart_tax( 10 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 60 );

		// Remove existing items to start fresh.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}

		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		$item_qty = 4;

		$item = new \WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => $item_qty,
				'subtotal' => $product->get_price() * $item_qty,
				'total'    => $product->get_price() * $item_qty,
			)
		);
		$item->save();

		$order->add_item( $item );
		$order->save();

		$request    = new PayPalRequest( new \WC_Gateway_Paypal() );
		$reflection = new \ReflectionClass( $request );
		$method     = $reflection->getMethod( 'get_paypal_create_order_request_params' );
		$method->setAccessible( true );

		$order_payload = $method->invoke(
			$request,
			$order,
			PayPalConstants::PAYMENT_SOURCE_PAYPAL,
			array(
				'is_js_sdk_flow'            => true,
				'app_switch_request_origin' => '',
			)
		);

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

		$items = $purchase_unit['items'];
		$this->assertEquals( 'Dummy Product', $items[0]['name'] );
		$this->assertEquals( '4', $items[0]['quantity'] );
		$this->assertEquals( '10.00', $items[0]['unit_amount']['value'] );
		$this->assertEquals( 'USD', $items[0]['unit_amount']['currency_code'] );

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
	}

	/**
	 * Helper function for creating PayPal order success response.
	 *
	 * @param bool  $value      Original pre-value, likely to be false.
	 * @param array $parsed_url Parsed URL object.
	 *
	 * @return array Return a 200 response.
	 */
	public function create_paypal_order_success( $value, $parsed_url ) {
		return array(
			'response' => array(
				'code' => 200,
			),
			'body'     => wp_json_encode(
				array(
					'id'    => '123',
					'links' => array(
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
	 * @param bool  $value      Original pre-value, likely to be false.
	 * @param array $parsed_url Parsed URL object.
	 *
	 * @return array Return a 500 error response.
	 */
	public function create_paypal_order_error( $value, $parsed_url ) {
		// Return a 500 error.
		return array( 'response' => array( 'code' => 500 ) );
	}

	/**
	 * Helper method to return valid site ID for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return int
	 */
	public function return_valid_site_id( $value ) {
		return array( 'id' => 12345 );
	}

	/**
	 * Helper method to return valid blog token for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_blog_token( $value ) {
		return array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' );
	}

	/**
	 * Tests for the `get_paypal_order_details` method.
	 *
	 * @param string      $paypal_order_id            The PayPal order ID.
	 * @param string|null $expected_exception         The expected exception class, or null if no exception is expected.
	 * @param string|null $expected_exception_message The expected exception message, or null if no exception is expected.
	 * @return void
	 *
	 * @dataProvider provide_test_get_paypal_order_details
	 */
	public function test_get_paypal_order_details( string $paypal_order_id, ?string $expected_exception, ?string $expected_exception_message ) {
		$response_mock_ref = function () use ( $paypal_order_id ) {
			if ( 'ERROR_ID' === $paypal_order_id ) {
				return new \WP_Error( 'error', 'Some error occurred.' );
			}

			if ( 'FAILED_ID' === $paypal_order_id ) {
				return array(
					'response' => array(
						'code' => 500,
					),
					'body'     => wp_json_encode(
						array(
							'name'    => 'SOME_ERROR',
							'details' => array(
								array( 'issue' => 'SOME_ISSUE' ),
							),
						)
					),
				);
			}

			return array(
				'response' => array(
					'code' => 200,
				),
				'body'     => wp_json_encode( array() ),
			);
		};

		add_filter( 'pre_http_request', $response_mock_ref, 10, 2 );

		if ( $expected_exception ) {
			$this->expectException( $expected_exception );
			$this->expectExceptionMessage( $expected_exception_message );
		}

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );

		$response_data = $request->get_paypal_order_details( $paypal_order_id );

		// Clean up the filter.
		remove_filter( 'pre_http_request', $response_mock_ref );

		if ( ! $expected_exception ) {
			$this->assertIsArray( $response_data );
		}
	}

	/**
	 * Data provider for the `test_get_paypal_order_details` method.
	 *
	 * @return array
	 */
	public function provide_test_get_paypal_order_details(): array {
		return array(
			'order details error response'   => array(
				'PayPal order ID'            => 'ERROR_ID',
				'expected exception'         => \Exception::class,
				'expected exception message' => 'PayPal order details request failed: Some error occurred.',
			),
			'order details failed response'  => array(
				'PayPal order ID'            => 'FAILED_ID',
				'expected exception'         => \Exception::class,
				'expected exception message' => 'PayPal order details request failed. HTTP 500',
			),
			'order details success response' => array(
				'PayPal order ID'            => 'SUCCESS_ID',
				'expected exception'         => null,
				'expected exception message' => null,
			),
		);
	}

	/**
	 * Tests for the `get_paypal_order_purchase_unit_amount` method.
	 *
	 * @param int   $cart_tax       The cart tax amount.
	 * @param int   $shipping_tax   The shipping tax amount.
	 * @param int   $discount_total The discount total amount.
	 * @param int   $total          The order total amount.
	 * @param array $expected       The expected purchase unit amount array.
	 * @return void
	 *
	 * @dataProvider provide_test_get_paypal_order_purchase_unit_amount
	 */
	public function test_get_paypal_order_purchase_unit_amount( int $cart_tax, int $shipping_tax, int $discount_total, int $total, array $expected ): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_cart_tax( $cart_tax );
		$order->set_shipping_tax( $shipping_tax );
		$order->set_discount_total( $discount_total );
		$order->set_total( $total );
		$order->save();

		// Remove existing items to start fresh.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}

		$product = \WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->save();

		$item_qty = 4;

		$item = new \WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => $item_qty,
				'subtotal' => $product->get_price() * $item_qty,
				'total'    => $product->get_price() * $item_qty,
			)
		);
		$item->save();

		$order->add_item( $item );
		$order->save();

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );

		$actual = $request->get_paypal_order_purchase_unit_amount( $order );

		// Clean up the order.
		$order->delete( true );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Data provider for `test_get_paypal_order_purchase_unit_amount` method.
	 *
	 * @return array
	 */
	public function provide_test_get_paypal_order_purchase_unit_amount(): array {
		return array(
			'test 1' => array(
				'cart tax'       => 10,
				'shipping tax'   => 0,
				'discount total' => 0,
				'total'          => 60,
				'expected'       => array(
					'currency_code' => 'USD',
					'value'         => '60.00',
					'breakdown'     => array(
						'item_total' => array(
							'currency_code' => 'USD',
							'value'         => '40.00',
						),
						'shipping'   => array(
							'currency_code' => 'USD',
							'value'         => '10.00',
						),
						'tax_total'  => array(
							'currency_code' => 'USD',
							'value'         => '10.00',
						),
						'discount'   => array(
							'currency_code' => 'USD',
							'value'         => '0.00',
						),
					),
				),
			),
			'test 2' => array(
				'cart tax'       => 0,
				'shipping tax'   => 5,
				'discount total' => 0,
				'total'          => 55,
				'expected'       => array(
					'currency_code' => 'USD',
					'value'         => '55.00',
					'breakdown'     => array(
						'item_total' => array(
							'currency_code' => 'USD',
							'value'         => '40.00',
						),
						'shipping'   => array(
							'currency_code' => 'USD',
							'value'         => '10.00',
						),
						'tax_total'  => array(
							'currency_code' => 'USD',
							'value'         => '5.00',
						),
						'discount'   => array(
							'currency_code' => 'USD',
							'value'         => '0.00',
						),
					),
				),
			),
			'test 3' => array(
				'cart tax'       => 0,
				'shipping tax'   => 0,
				'discount total' => 0,
				'total'          => 50,
				'expected'       => array(
					'currency_code' => 'USD',
					'value'         => '50.00',
					'breakdown'     => array(
						'item_total' => array(
							'currency_code' => 'USD',
							'value'         => '40.00',
						),
						'shipping'   => array(
							'currency_code' => 'USD',
							'value'         => '10.00',
						),
						'tax_total'  => array(
							'currency_code' => 'USD',
							'value'         => '0.00',
						),
						'discount'   => array(
							'currency_code' => 'USD',
							'value'         => '0.00',
						),
					),
				),
			),
			'test 4' => array(
				'cart tax'       => 10,
				'shipping tax'   => 0,
				'discount total' => 5,
				'total'          => 55,
				'expected'       => array(
					'currency_code' => 'USD',
					'value'         => '55.00',
					'breakdown'     => array(
						'item_total' => array(
							'currency_code' => 'USD',
							'value'         => '40.00',
						),
						'shipping'   => array(
							'currency_code' => 'USD',
							'value'         => '10.00',
						),
						'tax_total'  => array(
							'currency_code' => 'USD',
							'value'         => '10.00',
						),
						'discount'   => array(
							'currency_code' => 'USD',
							'value'         => '5.00',
						),
					),
				),
			),
		);
	}

	/**
	 * Tests for the `fetch_paypal_client_id` method.
	 *
	 * @param array       $response The mocked HTTP response.
	 * @param string|null $client_id The expected client ID, or null if none is.
	 * @return void
	 *
	 * @dataProvider provide_test_fetch_paypal_client_id
	 */
	public function test_fetch_paypal_client_id( $response, $client_id ): void {
		$response_mock_ref = function () use ( $response ) {
			return $response;
		};
		add_filter( 'pre_http_request', $response_mock_ref, 10, 2 );

		$request = new PayPalRequest( new \WC_Gateway_Paypal() );

		$actual = $request->fetch_paypal_client_id();

		// Clean up the filter.
		remove_filter( 'pre_http_request', $response_mock_ref );

		$this->assertEquals( $client_id, $actual );
	}

	/**
	 * Data provider for the `test_fetch_paypal_client_id` method.
	 *
	 * @return array
	 */
	public function provide_test_fetch_paypal_client_id(): array {
		$error_response   = new \WP_Error( 'error', 'Some error occurred.' );
		$invalid_response = array(
			'response' => array(
				'code' => 200,
			),
			'body'     => 'Invalid JSON',
		);
		$valid_response   = array(
			'response' => array(
				'code' => 200,
			),
			'body'     => wp_json_encode(
				array(
					'client_id' => 'SOME_CLIENT_ID',
				)
			),
		);

		return array(
			'request error'    => array(
				'response'  => $error_response,
				'client ID' => null,
			),
			'invalid response' => array(
				'response'  => $invalid_response,
				'client ID' => null,
			),
			'valid response'   => array(
				'response'  => $valid_response,
				'client ID' => 'SOME_CLIENT_ID',
			),
		);
	}
}
