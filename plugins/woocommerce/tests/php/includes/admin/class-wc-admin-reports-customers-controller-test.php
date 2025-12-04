<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Reports Customers REST API Test Class
 *
 * @package WooCommerce\Admin\Tests\API
 */
class WC_Admin_Reports_Customers_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/reports/customers';

	/**
	 * User ID for authentication.
	 *
	 * @var int
	 */
	protected $user = 0;

	/**
	 * Test product used for orders.
	 *
	 * @var WC_Product_Simple
	 */
	protected $product;

	/**
	 * Registered customers.
	 *
	 * @var array
	 */
	protected $registered_customers = array();

	/**
	 * Guest orders (no user_id).
	 *
	 * @var array
	 */
	protected $guest_orders = array();

	/**
	 * Setup test reports customers data.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		wp_set_current_user( $this->user );
		WC_Helper_Reports::reset_stats_dbs();

		// Create a test product.
		$this->product = new WC_Product_Simple();
		$this->product->set_name( 'Test Product' );
		$this->product->set_regular_price( 25 );
		$this->product->save();

		// Create registered customers with different names for search testing.
		$customer1 = WC_Helper_Customer::create_customer( 'customer1', 'password', 'customer1@example.com' );
		$customer1->set_first_name( 'John' );
		$customer1->set_last_name( 'Doe' );
		$customer1->set_billing_state( 'CA' );
		$customer1->set_billing_country( 'US' );
		$customer1->save();
		$this->registered_customers[] = $customer1;

		$customer2 = WC_Helper_Customer::create_customer( 'customer2', 'password', 'customer2@example.com' );
		$customer2->set_first_name( 'Jane' );
		$customer2->set_last_name( 'Smith' );
		$customer2->set_billing_state( 'NY' );
		$customer2->set_billing_country( 'US' );
		$customer2->save();
		$this->registered_customers[] = $customer2;

		$customer3 = WC_Helper_Customer::create_customer( 'customer3', 'password', 'customer3@example.com' );
		$customer3->set_first_name( 'Bob' );
		$customer3->set_last_name( 'Johnson' );
		$customer3->set_billing_state( 'CA' );
		$customer3->set_billing_country( 'US' );
		$customer3->save();
		$this->registered_customers[] = $customer3;

		// Create orders for registered customers with location data.
		foreach ( $this->registered_customers as $index => $customer ) {
			$order = WC_Helper_Order::create_order( $customer->get_id(), $this->product );
			$order->set_status( OrderStatus::COMPLETED );
			$order->set_total( 100 + ( $index * 50 ) );
			$order->set_billing_state( $customer->get_billing_state() );
			$order->set_billing_country( $customer->get_billing_country() );
			$order->save();
		}

		// Create guest orders (no user_id) with different locations.
		$guest_order1 = WC_Helper_Order::create_order( 0, $this->product );
		$guest_order1->set_billing_email( 'guest1@example.com' );
		$guest_order1->set_billing_first_name( 'Guest' );
		$guest_order1->set_billing_last_name( 'Customer' );
		$guest_order1->set_billing_state( 'TX' );
		$guest_order1->set_billing_country( 'US' );
		$guest_order1->set_status( OrderStatus::COMPLETED );
		$guest_order1->set_total( 50 );
		$guest_order1->save();
		$this->guest_orders[] = $guest_order1;

		$guest_order2 = WC_Helper_Order::create_order( 0, $this->product );
		$guest_order2->set_billing_email( 'guest2@example.com' );
		$guest_order2->set_billing_first_name( 'Guest' );
		$guest_order2->set_billing_last_name( 'User' );
		$guest_order2->set_billing_state( 'ON' );
		$guest_order2->set_billing_country( 'CA' );
		$guest_order2->set_status( OrderStatus::COMPLETED );
		$guest_order2->set_total( 75 );
		$guest_order2->save();
		$this->guest_orders[] = $guest_order2;

		// Sync all data to lookup tables.
		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		// This namespace may be lazy loaded, so we make a discovery request to trigger loading for this test.
		$this->server->dispatch( new WP_REST_Request( 'GET', '/' ) );
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test user_type parameter with 'all' value (default).
	 */
	public function test_user_type_all() {
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'user_type' => 'all',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		// Should return both registered and guest customers.
		// We have 3 registered customers and 2 guest customers = 5 total.
		$this->assertGreaterThanOrEqual( 5, count( $reports ) );
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertGreaterThanOrEqual( 5, $headers['X-WP-Total'] );
	}

	/**
	 * Test user_type parameter with 'customer' value.
	 */
	public function test_user_type_customer() {
		// Test with user_type='customer'.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'user_type' => 'customer',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		// Should only return registered customers (with user_id).
		// We have 3 registered customers.
		$this->assertGreaterThanOrEqual( 3, count( $reports ) );
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertGreaterThanOrEqual( 3, $headers['X-WP-Total'] );

		// Verify all returned customers have a user_id.
		foreach ( $reports as $report ) {
			$this->assertNotNull( $report['user_id'], 'All customers should have a user_id when user_type=customer' );
		}
	}

	/**
	 * Test user_type parameter with 'guest' value.
	 */
	public function test_user_type_guest() {
		// Test with user_type='guest'.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'user_type' => 'guest',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		// Should only return guest customers (without user_id).
		// We have 2 guest customers.
		$this->assertGreaterThanOrEqual( 2, count( $reports ) );
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertGreaterThanOrEqual( 2, $headers['X-WP-Total'] );

		// Verify all returned customers have user_id of 0 (intval converts NULL to 0).
		foreach ( $reports as $report ) {
			$this->assertEquals( 0, $report['user_id'], 'All customers should have user_id of 0 when user_type=guest' );
		}
	}

	/**
	 * Test user_type parameter default behavior (when not specified).
	 */
	public function test_user_type_default() {
		// Test without specifying user_type (should default to 'all').
		$request = new WP_REST_Request( 'GET', $this->endpoint );

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();
		$headers  = $response->get_headers();

		$this->assertEquals( 200, $response->get_status() );
		// Should return both registered and guest customers (default behavior).
		// We have 3 registered customers and 2 guest customers = 5 total.
		$this->assertGreaterThanOrEqual( 5, count( $reports ) );
		$this->assertArrayHasKey( 'X-WP-Total', $headers );
		$this->assertGreaterThanOrEqual( 5, $headers['X-WP-Total'] );
	}

	/**
	 * Test user_type parameter combined with other filters.
	 */
	public function test_user_type_with_other_filters() {
		// Test user_type='customer' combined with search.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'user_type' => 'customer',
				'search'    => 'John',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		// Should only return registered customers matching the search.
		// We have one customer named "John Doe".
		$this->assertGreaterThanOrEqual( 1, count( $reports ) );
		foreach ( $reports as $report ) {
			$this->assertNotNull( $report['user_id'], 'All customers should have a user_id when user_type=customer' );
		}

		// Verify the search actually filtered correctly.
		$found_john = false;
		foreach ( $reports as $report ) {
			if ( false !== strpos( $report['name'], 'John' ) ) {
				$found_john = true;
				break;
			}
		}
		$this->assertTrue( $found_john, 'Search should return customer named John' );
	}

	/**
	 * Test location ordering parameter (sorts by state, then country).
	 */
	public function test_orderby_location() {
		// Test with orderby='location' ascending.
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'orderby' => 'location',
				'order'   => 'asc',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		// Should have at least 5 customers (3 registered + 2 guest).
		$this->assertGreaterThanOrEqual( 5, count( $reports ) );

		// Verify ordering: should sort by state first, then country.
		// Expected order (ascending by state, then country):
		// - CA, US (customer1)
		// - CA, US (customer3)
		// - NY, US (customer2)
		// - ON, CA (guest2)
		// - TX, US (guest1)
		$previous_state   = '';
		$previous_country = '';
		foreach ( $reports as $report ) {
			$current_state   = $report['state'] ?? '';
			$current_country = $report['country'] ?? '';

			// If we have a previous entry, verify ordering.
			if ( '' !== $previous_state ) {
				// State should be >= previous state (ascending).
				// If states are equal, country should be >= previous country.
				$state_comparison = strcmp( $current_state, $previous_state );
				if ( 0 === $state_comparison ) {
					// States are equal, so country should be >= (ascending).
					$this->assertGreaterThanOrEqual(
						0,
						strcmp( $current_country, $previous_country ),
						"When states are equal ({$current_state}), countries should be in ascending order. Previous: {$previous_country}, Current: {$current_country}"
					);
				} else {
					// States are different, current should be >= previous (ascending).
					$this->assertGreaterThanOrEqual(
						0,
						$state_comparison,
						"States should be in ascending order. Previous: {$previous_state}, Current: {$current_state}"
					);
				}
			}

			$previous_state   = $current_state;
			$previous_country = $current_country;
		}

		// Test with orderby='location' descending.
		$request->set_query_params(
			array(
				'orderby' => 'location',
				'order'   => 'desc',
			)
		);

		$response = $this->server->dispatch( $request );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertGreaterThanOrEqual( 5, count( $reports ) );

		// Verify descending ordering.
		$previous_state   = '';
		$previous_country = '';
		foreach ( $reports as $report ) {
			$current_state   = $report['state'] ?? '';
			$current_country = $report['country'] ?? '';

			if ( '' !== $previous_state ) {
				$state_comparison = strcmp( $current_state, $previous_state );
				if ( 0 === $state_comparison ) {
					// States are equal, so country should be <= (descending).
					$this->assertLessThanOrEqual(
						0,
						strcmp( $current_country, $previous_country ),
						"When states are equal ({$current_state}), countries should be in descending order. Previous: {$previous_country}, Current: {$current_country}"
					);
				} else {
					// States are different, current should be <= previous (descending).
					$this->assertLessThanOrEqual(
						0,
						$state_comparison,
						"States should be in descending order. Previous: {$previous_state}, Current: {$current_state}"
					);
				}
			}

			$previous_state   = $current_state;
			$previous_country = $current_country;
		}
	}
}
