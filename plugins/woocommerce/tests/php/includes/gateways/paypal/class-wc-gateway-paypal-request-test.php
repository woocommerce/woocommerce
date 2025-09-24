<?php
/**
 * Unit tests for WC_Gateway_Paypal_Request class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

require_once WC_ABSPATH . 'includes/gateways/paypal/includes/class-wc-gateway-paypal-request.php';

/**
 * Class WC_Gateway_Paypal_Test.
 */
class WC_Gateway_Paypal_Request_Test extends \WC_Unit_Test_Case {

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
		$order = WC_Helper_Order::create_order();
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'create_paypal_order_error' ), 10, 2 );

		$request = new WC_Gateway_Paypal_Request( new WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'create_paypal_order_error' ) );

		$this->assertNull( $result );
	}

	/**
	 * Test create_paypal_order when API returns success.
	 */
	public function test_create_paypal_order_success() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'create_paypal_order_success' ), 10, 2 );

		$request = new WC_Gateway_Paypal_Request( new WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

		remove_filter( 'pre_http_request', array( $this, 'create_paypal_order_success' ) );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'redirect_url', $result );
	}

	/**
	 * Test that the create_paypal_order params are correct.
	 */
	public function test_create_paypal_order_params_are_correct() {
		$order = WC_Helper_Order::create_order();
		$order->set_cart_tax( 10 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 60 );
		$order->save();

		add_filter( 'pre_http_request', array( $this, 'check_create_paypal_order_params' ), 10, 2 );

		$request = new WC_Gateway_Paypal_Request( new WC_Gateway_Paypal() );
		$this->assertNotNull( $request->create_paypal_order( $order ) );

		remove_filter( 'pre_http_request', array( $this, 'check_create_paypal_order_params' ) );
	}

	/**
	 * Check that the create_paypal_order params are correct.
	 *
	 * @param bool  $value      Original value.
	 * @param array $parsed_args Parsed arguments.
	 *
	 * @return array Return a 200 response.
	 */
	public function check_create_paypal_order_params( $value, $parsed_args ) {
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

		return $this->create_paypal_order_success( $value, $parsed_args );
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
}
