<?php

/**
 * Class WC_REST_Refunds_Controller_Test.
 */
class WC_REST_Refunds_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * @testdox Check that the refunds endpoint returns all refunds, from multiple orders.
	 */
	public function test_get_items_multiple_orders(): void {
		wp_set_current_user( 1 );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10' );
		$product->save();

		$expected_by_id = array();
		$refund_reasons = array(
			'1.25' => 'First global refund',
			'2.50' => 'Second global refund',
		);
		foreach ( $refund_reasons as $amount => $reason ) {
			$order = wc_create_order();
			$order->add_product( $product, 1 );
			$order->calculate_totals();
			$order->save();

			$refund = wc_create_refund(
				array(
					'order_id' => $order->get_id(),
					'amount'   => $amount,
					'reason'   => $reason,
				)
			);
			$this->assertNotWPError( $refund );

			$item_path  = '/wc/v3/orders/' . $order->get_id() . '/refunds/' . $refund->get_id();
			$collection = '/wc/v3/orders/' . $order->get_id() . '/refunds';

			$expected_by_id[ $refund->get_id() ] = array(
				'id'         => $refund->get_id(),
				'parent_id'  => $order->get_id(),
				'amount'     => $amount,
				'reason'     => $reason,
				'self'       => rest_url( $item_path ),
				'collection' => rest_url( $collection ),
				'up'         => rest_url( '/wc/v3/orders/' . $order->get_id() ),
			);
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/refunds' ) );
		$this->assertSame( 200, $response->get_status() );

		$actual_by_id = array();
		foreach ( $response->get_data() as $refund ) {
			$actual_by_id[ $refund['id'] ] = array(
				'id'         => $refund['id'],
				'parent_id'  => $refund['parent_id'],
				'amount'     => $refund['amount'],
				'reason'     => $refund['reason'],
				'self'       => $refund['_links']['self'][0]['href'],
				'collection' => $refund['_links']['collection'][0]['href'],
				'up'         => $refund['_links']['up'][0]['href'],
			);
		}

		ksort( $expected_by_id );
		ksort( $actual_by_id );
		$this->assertSame( $expected_by_id, $actual_by_id );
	}
}
