<?php
/**
 * Orders REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Controller as ReportsController;

/**
 * WC Tests API Orders
 */
class WC_Admin_Tests_API_Orders extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/orders';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test the order status (it should allow all actionable statuses).
	 */
	public function test_order_status() {
		// Add a status to the actionable list.
		$actionable_statuses = get_option( 'woocommerce_actionable_order_statuses', array() );
		update_option( 'woocommerce_actionable_order_statuses', array( 'test-status' ) );

		// Ideally this would be a test using an endpoint, but the option value is used
		// at `rest_api_init` time to create the collection param schema. It's too late
		// here to test with the actual endpoint code. Instantiating a new WP_REST_Server
		// didn't seem to work either.
		$this->assertContains( 'test-status', ReportsController::get_order_statuses() );

		// Restore the actionable statuses.
		update_option( 'woocommerce_actionable_order_statuses', $actionable_statuses );
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

	/**
	 * Verify that deleted refund parent orders don't cause
	 */
	public function test_refund_parent_order_deleted() {
		global $wpdb;

		wp_set_current_user( $this->user );

		// Create an order.
		$order = WC_Helper_Order::create_order( $this->user );

		// Create a refund order.
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
			)
		);

		// Forcibly delete the original order.
		$wpdb->delete( $wpdb->prefix . 'posts', array( 'ID' => $order->get_id() ), array( '%d' ) );
		clean_post_cache( $order->get_id() );

		// Trigger an order sync on the refund which should handle the missing parent order.
		$this->assertTrue( OrdersStatsDataStore::sync_order( $refund->get_id() ) );
	}
}
