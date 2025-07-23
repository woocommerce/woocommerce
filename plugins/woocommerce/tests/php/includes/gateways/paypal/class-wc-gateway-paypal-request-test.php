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
		$order->add_product( WC_Helper_Product::create_simple_product(), 2 );
		$order->set_shipping_total( 10 );
		$order->set_total( 40 );
		$order->set_currency( 'USD' );
		$order->save();

		$item = current( $order->get_items() );
		$item->set_total_tax( 10 );
		$item->save();

		add_filter( 'pre_http_request', array( $this, 'check_create_paypal_order_params' ), 10, 2 );

		$request = new WC_Gateway_Paypal_Request( new WC_Gateway_Paypal() );
		$result  = $request->create_paypal_order( $order );

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
		$this->assertEquals( 'CAPTURE', $body['intent'] );

		$purchase_unit = $body['purchase_units'][0];
		$this->assertEquals( '40.00', $purchase_unit['amount']['value'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['currency_code'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['breakdown']['item_total']['currency_code'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['breakdown']['shipping']['currency_code'] );
		$this->assertEquals( 'USD', $purchase_unit['amount']['breakdown']['tax_total']['currency_code'] );
		$this->assertEquals( '20.00', $purchase_unit['amount']['breakdown']['item_total']['value'] );
		$this->assertEquals( '10.00', $purchase_unit['amount']['breakdown']['shipping']['value'] );
		$this->assertEquals( '10.00', $purchase_unit['amount']['breakdown']['tax_total']['value'] );

		$items = $purchase_unit['items'];
		$this->assertEquals( 'Dummy Product', $items[0]['name'] );
		$this->assertEquals( '2', $items[0]['quantity'] );
		$this->assertEquals( '20.00', $items[0]['unit_amount']['value'] );
		$this->assertEquals( 'USD', $items[0]['unit_amount']['currency_code'] );

		$this->assertArrayHasKey( 'return_url', $body['application_context'] );
		$this->assertArrayHasKey( 'cancel_url', $body['application_context'] );

		$custom_id = json_decode( $body['purchase_units'][0]['custom_id'], true );
		$this->assertEquals( $order->get_id(), $custom_id['order_id'] );
		$this->assertEquals( $order->get_order_key(), $custom_id['order_key'] );
		$this->assertHasKey( 'endpoint', $custom_id );

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
}
