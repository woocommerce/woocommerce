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

			$refund_ids[] = $refund->get_id();
			++$orders_and_refunds_count;
		}

		$request = new WP_REST_Request( 'GET', '/wc/v3/refunds' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );

		foreach ( $data as $refund ) {
			$this->assertContains( $refund['id'], $refund_ids );
		}
	}
}
