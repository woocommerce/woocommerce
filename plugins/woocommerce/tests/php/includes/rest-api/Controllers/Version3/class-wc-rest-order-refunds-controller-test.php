<?php

/**
 * Class WC_REST_Order_Refunds_Controller_Test.
 */

use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;

/**
 * Tests for the V3 Order Refunds REST API controller.
 */
class WC_REST_Order_Refunds_Controller_Test extends WC_REST_Unit_Test_Case {
	use MetaDataAssertionTrait;

	/**
	 * @testdox A nested V3 refund can be created, read, listed, and permanently deleted.
	 */
	public function test_nested_refund_lifecycle(): void {
		wp_set_current_user( 1 );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '10' );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();
		$order_item = current( $order->get_items( 'line_item' ) );

		$collection = '/wc/v3/orders/' . $order->get_id() . '/refunds';
		$request    = new WP_REST_Request( 'POST', $collection );
		$request->set_body_params(
			array(
				'amount'      => '5.00',
				'reason'      => 'Damaged item refund',
				'api_refund'  => false,
				'api_restock' => false,
				'line_items'  => array(
					array(
						'id'           => $order_item->get_id(),
						'quantity'     => 1,
						'refund_total' => 5,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$created  = $response->get_data();

		$this->assertSame( 201, $response->get_status() );
		$refund_id = $created['id'];
		$this->assertIsInt( $refund_id );
		$this->assertSame( '5.00', $created['amount'] );
		$this->assertSame( 'Damaged item refund', $created['reason'] );
		$this->assertCount( 1, $created['line_items'] );
		$this->assertSame( $product->get_id(), $created['line_items'][0]['product_id'] );
		$this->assertSame( '-5.00', $created['line_items'][0]['total'] );
		$this->assertSame( $refund_id, current( wc_get_order( $order->get_id() )->get_refunds() )->get_id() );

		$item_path = $collection . '/' . $refund_id;
		$response  = $this->server->dispatch( new WP_REST_Request( 'GET', $item_path ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $refund_id, $response->get_data()['id'] );
		$this->assertSame( '5.00', $response->get_data()['amount'] );
		$this->assertSame( 'Damaged item refund', $response->get_data()['reason'] );
		$this->assertSame( $product->get_id(), $response->get_data()['line_items'][0]['product_id'] );

		$links = $response->get_links();
		$this->assertSame( rest_url( $item_path ), $links['self'][0]['href'] );
		$this->assertSame( rest_url( $collection ), $links['collection'][0]['href'] );
		$this->assertSame( rest_url( '/wc/v3/orders/' . $order->get_id() ), $links['up'][0]['href'] );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $collection ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( $refund_id ), wp_list_pluck( $response->get_data(), 'id' ) );

		$request = new WP_REST_Request( 'DELETE', $item_path );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $refund_id, $response->get_data()['id'] );
		$this->assertSame( 404, $this->server->dispatch( new WP_REST_Request( 'GET', $item_path ) )->get_status() );
		$this->assertFalse( wc_get_order( $refund_id ) );
		$this->assertSame( array(), wc_get_order( $order->get_id() )->get_refunds() );
	}

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

		$request = new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() . '/refunds/' . $refund->get_id() );

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
	 * @testdox Creating a refund with incomplete meta_data entries does not cause errors.
	 */
	public function test_create_refund_meta_data_with_incomplete_entries(): void {
		wp_set_current_user( 1 );
		$order = WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/' . $order->get_id() . '/refunds' );
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
}
