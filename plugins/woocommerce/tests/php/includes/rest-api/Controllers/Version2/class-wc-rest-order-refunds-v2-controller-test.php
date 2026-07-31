<?php

/**
 * Class WC_REST_Order_Refunds_Controller_Test.
 */

use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;

/**
 * Tests for the V2 Order Refunds REST API controller.
 */
class WC_REST_Order_Refunds_V2_Controller_Test extends WC_REST_Unit_Test_Case {
	use MetaDataAssertionTrait;

	/**
	 * Test if line, fees and shipping items are all included in refund response.
	 */
	public function test_items_response_fields() {
		wp_set_current_user( 1 );
		$order = WC_Helper_Order::create_order_with_fees_and_shipping();

		$product_item  = current( $order->get_items( 'line_item' ) );
		$fee_item      = current( $order->get_items( 'fee' ) );
		$shipping_item = current( $order->get_items( 'shipping' ) );

		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'reason'     => 'testing',
				'line_items' => array(
					$product_item->get_id()  =>
						array(
							'qty'          => 1,
							'refund_total' => 1,
						),
					$fee_item->get_id()      =>
						array(
							'refund_total' => 10,
						),
					$shipping_item->get_id() =>
						array(
							'refund_total' => 20,
						),
				),
			)
		);

		$this->assertNotWPError( $refund );

		$request = new WP_REST_Request( 'GET', '/wc/v2/orders/' . $order->get_id() . '/refunds/' . $refund->get_id() );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertContains( 'line_items', array_keys( $data ) );
		$this->assertEquals( -1, $data['line_items'][0]['total'] );

		$this->assertContains( 'fee_lines', array_keys( $data ) );
		$this->assertEquals( -10, $data['fee_lines'][0]['total'] );

		$this->assertContains( 'shipping_lines', array_keys( $data ) );
		$this->assertEquals( -20, $data['shipping_lines'][0]['total'] );
	}

	/**
	 * @testdox Creating a V2 refund with incomplete meta_data entries does not cause errors.
	 */
	public function test_create_refund_meta_data_with_incomplete_entries(): void {
		wp_set_current_user( 1 );
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/' . $order->get_id() . '/refunds' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'amount'     => '1.00',
					'api_refund' => false,
					'meta_data'  => $this->get_incomplete_meta_data_input(),
				)
			)
		);

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$this->assert_incomplete_meta_data_handled_correctly( wc_get_order( $response->get_data()['id'] ) );
	}

	/**
	 * @testdox Creating a refund returns an error response instead of a fatal error when an unexpected exception is thrown.
	 */
	public function test_create_refund_returns_error_response_on_unexpected_exception(): void {
		wp_set_current_user( 1 );
		$order = WC_Helper_Order::create_order();

		$throw_exception = function () {
			throw new Exception( 'Simulated post-create failure.' );
		};
		add_filter( 'woocommerce_rest_pre_insert_shop_order_refund_object', $throw_exception );

		$request = new WP_REST_Request( 'POST', '/wc/v2/orders/' . $order->get_id() . '/refunds' );
		$request->set_body_params(
			array(
				'amount'     => '1.00',
				'api_refund' => false,
			)
		);

		$response = $this->server->dispatch( $request );

		remove_filter( 'woocommerce_rest_pre_insert_shop_order_refund_object', $throw_exception );

		$this->assertEquals( 400, $response->get_status(), 'The unexpected exception should surface as a 400 error response' );
		$this->assertEquals( 'woocommerce_rest_shop_order_refund_not_created', $response->get_data()['code'] );
	}
}
