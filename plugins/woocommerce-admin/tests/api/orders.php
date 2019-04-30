<?php
/**
 * Orders REST API Test
 *
 * @package WooCommerce Admin\Tests\API
 */

/**
 * WC Tests API Orders
 */
class WC_Tests_API_Orders extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/orders';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test order number param.
	 */
	public function test_get_items_number_param() {
		wp_set_current_user( $this->user );

		$orders = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$orders[] = WC_Helper_Order::create_order( $this->user );
		}

		$order = $orders[9];

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'number' => (string) $order->get_id(),
			)
		);

		$response = $this->server->dispatch( $request );
		$orders   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $orders[0]['id'] );
	}
}
