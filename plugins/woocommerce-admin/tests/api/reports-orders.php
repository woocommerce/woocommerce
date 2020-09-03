<?php
/**
 * Reports Orders REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */

use \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;

/**
 * Reports Orders REST API Test Class
 *
 * @package WooCommerce\Admin\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Reports_Orders extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/orders';

	/**
	 * Setup test reports orders data.
	 *
	 * @since 3.5.0
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
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Populate all of the data.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		WC_Helper_Queue::run_all_pending();

		$expected_customer_id = CustomersDataStore::get_customer_id_by_user_id( 1 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$order_report = reset( $reports );

		$this->assertEquals( $order->get_id(), $order_report['order_id'] );
		$this->assertEquals( $order->get_order_number(), $order_report['order_number'] );
		$this->assertEquals( gmdate( 'Y-m-d H:i:s', $order->get_date_created()->getTimestamp() ), $order_report['date_created'] );
		$this->assertEquals( $expected_customer_id, $order_report['customer_id'] );
		$this->assertEquals( 4, $order_report['num_items_sold'] );
		$this->assertEquals( 90.0, $order_report['net_total'] ); // 25 x 4 - 10 (shipping)
		$this->assertEquals( 'new', $order_report['customer_type'] );
		$this->assertArrayHasKey( '_links', $order_report );
		$this->assertArrayHasKey( 'order', $order_report['_links'] );
	}

	/**
	 * Test getting reports without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test reports schema.
	 *
	 * @since 3.5.0
	 */
	public function test_reports_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 10, count( $properties ) );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'order_id', $properties );
		$this->assertArrayHasKey( 'order_number', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'customer_id', $properties );
		$this->assertArrayHasKey( 'net_total', $properties );
		$this->assertArrayHasKey( 'num_items_sold', $properties );
		$this->assertArrayHasKey( 'customer_type', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}

	/**
	 * Test filtering by product attribute(s).
	 */
	public function test_product_attributes_filter() {
		global $wpdb;
		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Create a variable product.
		$variable_product   = WC_Helper_Product::create_variation_product( new WC_Product_Variable() );
		$product_variations = $variable_product->get_children();
		$order_variation_1  = wc_get_product( $product_variations[0] ); // Variation: size = small.
		$order_variation_2  = wc_get_product( $product_variations[2] ); // Variation: size = huge, colour = red, number = 0.

		// Create orders for variations.
		$variation_order_1 = WC_Helper_Order::create_order( $this->user, $order_variation_1 );
		$variation_order_1->set_status( 'completed' );
		$variation_order_1->save();

		$variation_order_2 = WC_Helper_Order::create_order( $this->user, $order_variation_2 );
		$variation_order_2->set_status( 'completed' );
		$variation_order_2->save();

		// Create more orders for simple products.
		for ( $i = 0; $i < 10; $i++ ) {
			$order = WC_Helper_Order::create_order( $this->user );
			$order->set_status( 'completed' );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending();

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'per_page' => 15 ) );
		$response        = $this->server->dispatch( $request );
		$response_orders = $response->get_data();

		// Sanity check before filtering by attribute.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 12, count( $response_orders ) );

		// To filter by later.
		$size_attr_id = wc_attribute_taxonomy_id_by_name( 'pa_size' );
		$small_term   = get_term_by( 'slug', 'small', 'pa_size' );

		// Test bad values to filter parameter.
		$bad_args = array(
			'not an array!',                   // Not an array.
			array( 1, 2, 3 ),                  // Not a tuple.
			array( 'a', 1 ),                   // Non-numeric attribute ID.
			array( 1, 'a' ),                   // Non-numeric term ID.
			array( -1, $small_term->term_id ), // Invaid attribute ID.
			array( $size_attr_id, -1 ),        // Invaid term ID.
		);

		foreach ( $bad_args as $bad_arg ) {
			$request = new WP_REST_Request( 'GET', $this->endpoint );
			$request->set_query_params(
				array(
					'per_page'     => 15,
					'attribute_is' => $bad_arg,
				)
			);
			$response        = $this->server->dispatch( $request );
			$response_orders = $response->get_data();

			$this->assertEquals( 200, $response->get_status() );
			// We expect all results since the attribute param is malformed.
			$this->assertEquals( 12, count( $response_orders ) );
		}

		// Filter by the "size" attribute, with value "small".
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'attribute_is' => array(
					array( $size_attr_id, $small_term->term_id ),
				),
			)
		);
		$response        = $this->server->dispatch( $request );
		$response_orders = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_orders ) );
		$this->assertEquals( $response_orders[0]['order_id'], $variation_order_1->get_id() );

		// Verify the opposite result set.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'attribute_is_not' => array(
					array( $size_attr_id, $small_term->term_id ),
				),
				'per_page'         => 15,
			)
		);
		$response        = $this->server->dispatch( $request );
		$response_orders = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_orders ) );
		$this->assertEquals( $response_orders[0]['order_id'], $variation_order_2->get_id() );
	}
}
