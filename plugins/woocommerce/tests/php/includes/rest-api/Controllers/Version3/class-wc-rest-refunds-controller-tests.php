<?php

/**
 * Class WC_REST_Refunds_Controller_Test.
 */
class WC_REST_Refunds_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * @testdox Check that the refunds endpoint returns all refunds, from multiple orders.
	 */
	public function test_get_items_multiple_orders() {
		wp_set_current_user( 1 );

		$orders_and_refunds_count = 0;
		$refund_ids               = array();

		while ( $orders_and_refunds_count < 3 ) {
			$order = wc_create_order();

			$refund = wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'reason'   => 'testing',
				)
			);

			$refund_ids[] = $refund->get_id();
			++$orders_and_refunds_count;
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/refunds' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 3, $data );

		foreach ( $data as $refund ) {
			$this->assertContains( $refund['id'], $refund_ids );
		}
	}
}
