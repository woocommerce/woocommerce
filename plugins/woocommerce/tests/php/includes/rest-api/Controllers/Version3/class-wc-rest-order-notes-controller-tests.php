<?php
declare( strict_types=1 );

/**
 * Tests for paginating order notes in the v2 and v3 REST APIs.
 */
class WC_REST_Order_Notes_Controller_Tests extends WC_REST_Unit_Test_Case {

	/**
	 * @testdox Should return paginated notes with totals and navigation while preserving unpaginated responses.
	 */
	public function test_get_items_pagination(): void {
		$order = WC_Helper_Order::create_order();
		$order->add_order_note( 'First internal note' );
		add_filter( 'woocommerce_email_log_add_order_note', '__return_false' );
		try {
			$order->add_order_note( 'Customer note', 1 );
		} finally {
			remove_filter( 'woocommerce_email_log_add_order_note', '__return_false' );
		}
		$order->add_order_note( 'Last internal note' );

		foreach ( array( 'wc/v2', 'wc/v3' ) as $namespace ) {
			$controller = 'wc/v2' === $namespace ? new WC_REST_Order_Notes_V2_Controller() : new WC_REST_Order_Notes_Controller();
			$path       = "/{$namespace}/orders/{$order->get_id()}/notes";
			$request    = new WP_REST_Request( 'GET', $path );
			$request->set_param( 'order_id', $order->get_id() );
			$request->set_param( 'page', 2 );
			$request->set_param( 'per_page', 1 );

			$response = $controller->get_items( $request );
			$this->assertSame( 'Customer note', $response->get_data()[0]['note'] );
			$this->assertSame( '3', $response->get_headers()['X-WP-Total'] );
			$this->assertSame( '3', $response->get_headers()['X-WP-TotalPages'] );
			$this->assertStringContainsString( 'page=1', $response->get_headers()['Link'] );
			$this->assertStringContainsString( 'page=3', $response->get_headers()['Link'] );

			$request->set_param( 'type', 'internal' );
			$response = $controller->get_items( $request );
			$this->assertSame( 'First internal note', $response->get_data()[0]['note'] );
			$this->assertSame( '2', $response->get_headers()['X-WP-Total'] );

			$request->set_param( 'type', 'customer' );
			$response = $controller->get_items( $request );
			$this->assertSame( array(), $response->get_data() );
			$this->assertSame( '1', $response->get_headers()['X-WP-Total'] );

			$request->set_param( 'type', 'internal' );
			$request->set_param( 'page', 5 );
			$response = $controller->get_items( $request );
			$this->assertSame( array(), $response->get_data() );
			$this->assertSame( '2', $response->get_headers()['X-WP-Total'] );

			$request = new WP_REST_Request( 'GET', $path );
			$request->set_param( 'order_id', $order->get_id() );
			$request->set_param( 'page', 1 );
			$response = $controller->get_items( $request );
			$this->assertCount( 3, $response->get_data() );
			$this->assertSame( '1', $response->get_headers()['X-WP-TotalPages'] );

			$request = new WP_REST_Request( 'GET', $path );
			$request->set_param( 'order_id', $order->get_id() );
			$response = $controller->get_items( $request );
			$this->assertCount( 3, $response->get_data() );
			$this->assertArrayNotHasKey( 'X-WP-Total', $response->get_headers() );
		}
	}
}
