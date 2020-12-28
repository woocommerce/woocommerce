<?php

/**
 * class WC_REST_Order_V2_Controller_Test.
 * Orders controller test.
 */
class WC_REST_Order_V2_Controller_Test extends WC_REST_Unit_Test_case {

	/**
	 * Test that `prepare_object_for_response` method works.
	 */
	public function test_prepare_object_for_response() {
		$order = WC_Helper_Order::create_order();
		$order->save();
		$response = ( new WC_REST_Orders_V2_Controller() )->prepare_object_for_response( $order, new WP_REST_Request() );
		$this->assertArrayHasKey( 'id', $response->data );
		$this->assertEquals( $order->get_id(), $response->data['id'] );
	}
}
