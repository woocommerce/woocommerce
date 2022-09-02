<?php

/**
 * Class WC_REST_Order_Refunds_Controller_Test.
 */
class WC_REST_Order_Refunds_V2_Controller_Test extends WC_REST_Unit_Test_Case {

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
}
