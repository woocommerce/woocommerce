<?php

/**
 * Class WC_REST_Refunds_Controller_Test.
 */
class WC_REST_Refunds_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * @testdox Check that the refunds endpoint returns all refunds, from multiple orders.
	 */
	public function test_get_items_multiple_orders(): void {
		$product    = null;
		$orders     = array();
		$refund_ids = array();

		try {
			wp_set_current_user( 1 );

			$product = WC_Helper_Product::create_simple_product();
			$product->set_regular_price( '10' );
			$product->save();

			$refund_specs   = array(
				array(
					'amount' => 1.25,
					'reason' => 'First global refund',
				),
				array(
					'amount' => 2.50,
					'reason' => 'Second global refund',
				),
			);
			$expected_by_id = array();

			foreach ( $refund_specs as $refund_spec ) {
				$order = wc_create_order();
				$this->assertNotWPError( $order );
				$orders[] = $order;
				$order->add_product( $product, 1 );
				$order->calculate_totals();
				$order->save();

				$refund = wc_create_refund(
					array(
						'order_id' => $order->get_id(),
						'amount'   => $refund_spec['amount'],
						'reason'   => $refund_spec['reason'],
					)
				);
				$this->assertNotWPError( $refund );

				$refund_id    = $refund->get_id();
				$refund_ids[] = $refund_id;
				$this->assertGreaterThan( 0, $refund_id );

				$item_path  = '/wc/v3/orders/' . $order->get_id() . '/refunds/' . $refund_id;
				$collection = '/wc/v3/orders/' . $order->get_id() . '/refunds';

				$expected_by_id[ $refund_id ] = array(
					'id'         => $refund_id,
					'parent_id'  => $order->get_id(),
					'amount'     => wc_format_decimal( $refund_spec['amount'], 2 ),
					'reason'     => $refund_spec['reason'],
					'self'       => rest_url( $item_path ),
					'collection' => rest_url( $collection ),
					'up'         => rest_url( '/wc/v3/orders/' . $order->get_id() ),
				);
			}

			$routes = $this->server->get_routes();
			$this->assertArrayHasKey( '/wc/v3/refunds', $routes );

			$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/refunds' ) );
			$this->assertSame( 200, $response->get_status() );

			$data = $response->get_data();
			$this->assertIsArray( $data );
			$this->assertCount( count( $expected_by_id ), $data );

			$actual_by_id = array();
			foreach ( $data as $refund ) {
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
		} finally {
			foreach ( $refund_ids as $refund_id ) {
				$refund = wc_get_order( $refund_id );
				if ( $refund instanceof WC_Order_Refund ) {
					$refund->delete( true );
				}
			}
			foreach ( $orders as $order ) {
				if ( $order instanceof WC_Order ) {
					$order->delete( true );
				}
			}
			if ( $product instanceof WC_Product ) {
				$product->delete( true );
			}
		}
	}
}
