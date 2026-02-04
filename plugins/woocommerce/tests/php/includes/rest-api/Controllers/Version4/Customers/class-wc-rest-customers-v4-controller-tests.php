<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers\Controller as CustomersController;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers\CustomerSchema;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers\CollectionQuery;
use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Customers\UpdateUtils;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Customers Controller tests for V4 REST API.
 *
 * @group customer-query-tests
 */
class WC_REST_Customers_V4_Controller_Tests extends WC_REST_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * Created customers for cleanup.
	 *
	 * @var array
	 */
	private $created_customers = array();

	/**
	 * Endpoint instance.
	 *
	 * @var CustomersController
	 */
	private $endpoint;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Customer schema instance.
	 *
	 * @var CustomerSchema
	 */
	private $customer_schema;

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->cleanup_test_customers();
		$this->disable_rest_api_v4_feature();
	}

	/**
	 * Enable the REST API v4 feature.
	 */
	public static function enable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			},
		);
	}

	/**
	 * Disable the REST API v4 feature.
	 */
	public static function disable_rest_api_v4_feature() {
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features = array_diff( $features, array( 'rest-api-v4' ) );
				return $features;
			}
		);
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		$this->enable_rest_api_v4_feature();
		parent::setUp();

		// Create schema instances with dependency injection.
		$this->customer_schema = new CustomerSchema();

		// Create utils instances.
		$collection_query = new CollectionQuery();
		$update_utils     = new UpdateUtils();

		$this->endpoint = new CustomersController();
		$this->endpoint->init( $this->customer_schema, $collection_query, $update_utils );

		$this->user_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Clean up customers created during tests.
	 */
	private function cleanup_test_customers(): void {
		foreach ( $this->created_customers as $customer_id ) {
			if ( $customer_id && get_userdata( $customer_id ) ) {
				wp_delete_user( $customer_id );
			}
		}
		$this->created_customers = array();
	}

	/**
	 * Helper method to create a test customer.
	 *
	 * @param array $customer_data Optional customer data.
	 * @return WC_Customer
	 */
	private function create_test_customer( array $customer_data = array() ): WC_Customer {
		$default_data = array(
			'email'      => 'test@example.com',
			'first_name' => 'John',
			'last_name'  => 'Doe',
			'username'   => 'johndoe',
			'billing'    => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'email'      => 'test@example.com',
				'phone'      => '555-1234',
				'address_1'  => '123 Main St',
				'city'       => 'Anytown',
				'state'      => 'CA',
				'postcode'   => '12345',
				'country'    => 'US',
			),
			'shipping'   => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'address_1'  => '123 Main St',
				'city'       => 'Anytown',
				'state'      => 'CA',
				'postcode'   => '12345',
				'country'    => 'US',
			),
		);

		$customer_data = wp_parse_args( $customer_data, $default_data );

		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();

		$customer = new WC_Customer( $data['id'] );

		$this->created_customers[] = $customer->get_id();

		return $customer;
	}

	/**
	 * Helper method to validate response against schema.
	 *
	 * @param array $response_data Response data to validate.
	 * @param array $schema_properties Schema properties to check against.
	 */
	private function validate_response_against_schema( array $response_data, array $schema_properties ): void {
		foreach ( $schema_properties as $key => $schema ) {
			$this->assertArrayHasKey( $key, $response_data, "Response missing schema key: {$key}" );
		}
	}

	/**
	 * Test CustomerSchema properties match response.
	 */
	public function test_customer_schema_properties_match_response(): void {
		$customer = $this->create_test_customer();
		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers/' . $customer->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data     = $response->get_data();
		$schema_properties = $this->customer_schema->get_item_schema_properties();

		$this->validate_response_against_schema( $response_data, $schema_properties );
	}

	/**
	 * Test LIST endpoint returns customers with correct schema.
	 */
	public function test_customers_list_endpoint(): void {
		// Create test customers.
		$customer1 = $this->create_test_customer();
		$customer2 = $this->create_test_customer(
			array(
				'email'      => 'test2@example.com',
				'username'   => 'johndoe2',
				'first_name' => 'Jane',
				'last_name'  => 'Smith',
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Validate first customer against schema.
		$first_customer    = $response_data[0];
		$schema_properties = $this->customer_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $first_customer, $schema_properties );
	}

	/**
	 * Test GET endpoint returns customer with correct schema.
	 */
	public function test_customers_get_endpoint(): void {
		$customer = $this->create_test_customer();

		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers/' . $customer->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$schema_properties = $this->customer_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );
	}

	/**
	 * Test CREATE endpoint creates customer with correct schema and values.
	 */
	public function test_customers_create_endpoint(): void {
		$customer_data = array(
			'email'      => 'jane.smith@example.com',
			'first_name' => 'Jane',
			'last_name'  => 'Smith',
			'username'   => 'janesmith',
			'password'   => 'password123',
			'billing'    => array(
				'first_name' => 'Jane',
				'last_name'  => 'Smith',
				'email'      => 'jane.smith@example.com',
				'phone'      => '555-5678',
				'address_1'  => '456 Oak Ave',
				'city'       => 'Springfield',
				'state'      => 'IL',
				'postcode'   => '62701',
				'country'    => 'US',
			),
			'shipping'   => array(
				'first_name' => 'Jane',
				'last_name'  => 'Smith',
				'address_1'  => '456 Oak Ave',
				'city'       => 'Springfield',
				'state'      => 'IL',
				'postcode'   => '62701',
				'country'    => 'US',
				'phone'      => '555-5678',
			),
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		$schema_properties = $this->customer_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );

		// Verify customer was created with correct values.
		$this->assertEquals( 'jane.smith@example.com', $response_data['email'] );
		$this->assertEquals( 'Jane', $response_data['first_name'] );
		$this->assertEquals( 'Smith', $response_data['last_name'] );
		$this->assertEquals( 'janesmith', $response_data['username'] );
		$this->assertEquals( 'customer', $response_data['role'] );

		// Verify billing address.
		$this->assertEquals( 'Jane', $response_data['billing']['first_name'] );
		$this->assertEquals( 'Smith', $response_data['billing']['last_name'] );
		$this->assertEquals( 'jane.smith@example.com', $response_data['billing']['email'] );
		$this->assertEquals( '555-5678', $response_data['billing']['phone'] );
		$this->assertEquals( '456 Oak Ave', $response_data['billing']['address_1'] );
		$this->assertEquals( 'Springfield', $response_data['billing']['city'] );
		$this->assertEquals( 'IL', $response_data['billing']['state'] );
		$this->assertEquals( '62701', $response_data['billing']['postcode'] );
		$this->assertEquals( 'US', $response_data['billing']['country'] );

		// Verify shipping address.
		$this->assertEquals( 'Jane', $response_data['shipping']['first_name'] );
		$this->assertEquals( 'Smith', $response_data['shipping']['last_name'] );
		$this->assertEquals( '456 Oak Ave', $response_data['shipping']['address_1'] );
		$this->assertEquals( 'Springfield', $response_data['shipping']['city'] );
		$this->assertEquals( 'IL', $response_data['shipping']['state'] );
		$this->assertEquals( '62701', $response_data['shipping']['postcode'] );
		$this->assertEquals( 'US', $response_data['shipping']['country'] );
		$this->assertEquals( '555-5678', $response_data['shipping']['phone'] );

		$customer = new WC_Customer( $response_data['id'] );
	}

	/**
	 * Test UPDATE endpoint updates customer with correct schema.
	 */
	public function test_customers_update_endpoint(): void {
		$customer = $this->create_test_customer();

		$update_data = array(
			'first_name' => 'Updated',
			'last_name'  => 'Name',
			'billing'    => array(
				'first_name' => 'Updated',
				'last_name'  => 'Name',
				'email'      => 'updated@example.com',
				'phone'      => '555-9999',
				'address_1'  => '789 New St',
				'city'       => 'New City',
				'state'      => 'NY',
				'postcode'   => '10001',
				'country'    => 'US',
			),
			'shipping'   => array(
				'first_name' => 'Updated',
				'last_name'  => 'Name',
				'address_1'  => '789 New St',
				'city'       => 'New City',
				'state'      => 'NY',
				'postcode'   => '10001',
				'country'    => 'US',
				'phone'      => '555-5678',
			),
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/customers/' . $customer->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$schema_properties = $this->customer_schema->get_item_schema_properties();
		$this->validate_response_against_schema( $response_data, $schema_properties );

		// Verify customer was updated with correct values.
		$this->assertEquals( 'Updated', $response_data['first_name'] );
		$this->assertEquals( 'Name', $response_data['last_name'] );

		// Verify billing address was updated.
		$this->assertEquals( 'Updated', $response_data['billing']['first_name'] );
		$this->assertEquals( 'Name', $response_data['billing']['last_name'] );
		$this->assertEquals( 'updated@example.com', $response_data['billing']['email'] );
		$this->assertEquals( '555-9999', $response_data['billing']['phone'] );
		$this->assertEquals( '789 New St', $response_data['billing']['address_1'] );
		$this->assertEquals( 'New City', $response_data['billing']['city'] );
		$this->assertEquals( 'NY', $response_data['billing']['state'] );
		$this->assertEquals( '10001', $response_data['billing']['postcode'] );
		$this->assertEquals( 'US', $response_data['billing']['country'] );

		// Verify shipping address was updated.
		$this->assertEquals( 'Updated', $response_data['shipping']['first_name'] );
		$this->assertEquals( 'Name', $response_data['shipping']['last_name'] );
		$this->assertEquals( '789 New St', $response_data['shipping']['address_1'] );
		$this->assertEquals( 'New City', $response_data['shipping']['city'] );
		$this->assertEquals( 'NY', $response_data['shipping']['state'] );
		$this->assertEquals( '10001', $response_data['shipping']['postcode'] );
		$this->assertEquals( 'US', $response_data['shipping']['country'] );
		$this->assertEquals( '555-5678', $response_data['shipping']['phone'] );
	}

	/**
	 * Test DELETE endpoint removes customer.
	 */
	public function test_customers_delete_endpoint(): void {
		$customer    = $this->create_test_customer();
		$customer_id = $customer->get_id();

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/customers/' . $customer_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		// Check that the response includes customer data from the customer (before deletion).
		$response_data = $response->get_data();
		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertEquals( $response_data['id'], $customer_id );

		// Check the customer was actually deleted.
		$customer_check = new WC_Customer( $customer_id );
		$this->assertEmpty( $customer_check->get_id() );
	}

	/**
	 * Test _fields parameter filters response correctly.
	 */
	public function test_fields_parameter_filtering(): void {
		$customer = $this->create_test_customer();

		// Test single field.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers/' . $customer->get_id() );
		$request->set_param( '_fields', 'id,email' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertArrayHasKey( 'email', $response_data );
		$this->assertArrayNotHasKey( 'first_name', $response_data );
		$this->assertArrayNotHasKey( 'billing', $response_data );

		// Test multiple fields.
		$request->set_param( '_fields', 'id,email,first_name' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertArrayHasKey( 'id', $response_data );
		$this->assertArrayHasKey( 'email', $response_data );
		$this->assertArrayHasKey( 'first_name', $response_data );
		$this->assertArrayNotHasKey( 'billing', $response_data );
	}

	/**
	 * Test search functionality.
	 */
	public function test_search(): void {
		// Create customers with different searchable content.
		$customer1 = $this->create_test_customer(
			array(
				'email'      => 'searchtest@example.com',
				'username'   => 'searchtest',
				'first_name' => 'SearchTest',
				'last_name'  => 'User',
			)
		);

		$customer2 = $this->create_test_customer(
			array(
				'email'      => 'different@example.com',
				'username'   => 'differentuser',
				'first_name' => 'DifferentName',
				'last_name'  => 'DifferentUser',
			)
		);

		// Test searching by first name - should find customer1 but not customer2.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'search', 'SearchTest' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should find customer1.
		$found_customer1 = false;
		$found_customer2 = false;
		foreach ( $response_data as $customer_data ) {
			if ( $customer_data['id'] === $customer1->get_id() ) {
				$found_customer1 = true;
			}
			if ( $customer_data['id'] === $customer2->get_id() ) {
				$found_customer2 = true;
			}
		}
		$this->assertTrue( $found_customer1, 'Should find customer with matching first name' );
		$this->assertFalse( $found_customer2, 'Should not find customer with non-matching first name' );

		// Test searching by email - should find customer1 but not customer2.
		$request->set_param( 'search', 'searchtest@example.com' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_customer1 = false;
		$found_customer2 = false;
		foreach ( $response_data as $customer_data ) {
			if ( $customer_data['id'] === $customer1->get_id() ) {
				$found_customer1 = true;
			}
			if ( $customer_data['id'] === $customer2->get_id() ) {
				$found_customer2 = true;
			}
		}
		$this->assertTrue( $found_customer1, 'Should find customer with matching email' );
		$this->assertFalse( $found_customer2, 'Should not find customer with non-matching email' );

		// Test searching for non-existent term - should find no customers.
		$request->set_param( 'search', 'NonExistentTerm123' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_any_customer = false;
		foreach ( $response_data as $customer_data ) {
			if ( $customer_data['id'] === $customer1->get_id() || $customer_data['id'] === $customer2->get_id() ) {
				$found_any_customer = true;
				break;
			}
		}
		$this->assertFalse( $found_any_customer, 'Should not find any customers for non-existent search term' );
	}

	/**
	 * Test order by parameters.
	 */
	public function test_order_by(): void {
		$customer1 = $this->create_test_customer();
		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'customer2@example.com',
				'username' => 'customer2',
			)
		);

		// Test ordering by ID (default).
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should have at least 2 customers.
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Test ordering by registered date.
		$request->set_param( 'orderby', 'registered_date' );
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );
	}

	/**
	 * Test pagination.
	 */
	public function test_pagination(): void {
		// Create multiple customers for pagination testing.
		$customers = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$customers[] = $this->create_test_customer(
				array(
					'email'    => "pagination{$i}@example.com",
					'username' => "pagination{$i}",
				)
			);
		}

		// Test first page with 2 per page.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'page', 1 );
		$request->set_param( 'per_page', 2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should have at least 2 customers on first page.
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );

		// Verify pagination headers.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-WP-Total', $headers, 'Response should include X-WP-Total header' );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers, 'Response should include X-WP-TotalPages header' );
		$this->assertEquals( 5, $headers['X-WP-Total'], 'Total should be 5 customers' );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'], 'Should have 3 pages with 2 per page' );

		// Test second page.
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should have customers on second page.
		$this->assertGreaterThanOrEqual( 0, count( $response_data ) );

		// Verify pagination headers on second page.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'X-WP-Total', $headers, 'Response should include X-WP-Total header on page 2' );
		$this->assertArrayHasKey( 'X-WP-TotalPages', $headers, 'Response should include X-WP-TotalPages header on page 2' );
		$this->assertEquals( 5, $headers['X-WP-Total'], 'Total should be 5 customers' );
		$this->assertEquals( 3, $headers['X-WP-TotalPages'], 'Should have 3 pages with 2 per page' );
	}

	/**
	 * Test edge case: invalid per_page values should fall back to default.
	 */
	public function test_invalid_per_page_values(): void {
		// Create test customers.
		for ( $i = 1; $i <= 12; $i++ ) {
			$this->create_test_customer(
				array(
					'email'    => "perpage{$i}@example.com",
					'username' => "perpage{$i}",
				)
			);
		}

		// Test with number = 0 should fall back to default (10).
		// Use filter to inject invalid value into query args.
		add_filter(
			'woocommerce_customer_query_args',
			function ( $query_args ) {
				$query_args['number'] = 0;
				return $query_args;
			}
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should return default per_page (10), not 0.
		$this->assertEquals( 10, count( $response_data ), 'number=0 should fall back to default (10)' );

		// Remove filter for next test.
		remove_all_filters( 'woocommerce_customer_query_args' );

		// Test with number = -5 should fall back to default (10).
		add_filter(
			'woocommerce_customer_query_args',
			function ( $query_args ) {
				$query_args['number'] = -5;
				return $query_args;
			}
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should return default per_page (10), not 0.
		$this->assertEquals( 10, count( $response_data ), 'number=-5 should fall back to default (10)' );

		// Clean up filter.
		remove_all_filters( 'woocommerce_customer_query_args' );
	}

	/**
	 * Test that role=all returns users of all roles, not just customers.
	 */
	public function test_role_all_returns_all_roles(): void {
		// Create a customer with a unique name.
		$customer = $this->create_test_customer(
			array(
				'email'      => 'jonny.customer@example.com',
				'username'   => 'jonny_customer',
				'first_name' => 'Jonny',
				'last_name'  => 'Customer',
			)
		);

		// Create an admin user with a similar name.
		$admin_id = wp_insert_user(
			array(
				'user_login' => 'jonny_admin',
				'user_email' => 'jonny.admin@example.com',
				'user_pass'  => 'password',
				'first_name' => 'Jonny',
				'last_name'  => 'Admin',
				'role'       => 'administrator',
			)
		);

		// Test with role=all and search for "jonny" - should return both.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'role', 'all' );
		$request->set_param( 'search', 'jonny' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should return both the customer and the admin.
		$this->assertCount( 2, $response_data, 'role=all should return both customer and admin users' );

		$returned_ids = array_map(
			function ( $user ) {
				return $user['id'];
			},
			$response_data
		);

		$this->assertContains( $customer->get_id(), $returned_ids, 'Should include customer user' );
		$this->assertContains( $admin_id, $returned_ids, 'Should include admin user' );

		// Test with role=customer (default) - should only return customer.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'search', 'jonny' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should only return the customer.
		$this->assertCount( 1, $response_data, 'role=customer should return only customer users' );
		$this->assertEquals( $customer->get_id(), $response_data[0]['id'], 'Should be the customer user' );

		// Clean up.
		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox Customers without wc_last_active meta should return null for last_active fields.
	 */
	public function test_last_active_null_when_not_set(): void {
		// Create a customer without setting wc_last_active meta.
		$customer = $this->create_test_customer(
			array(
				'email'    => 'inactive@example.com',
				'username' => 'inactive_customer',
			)
		);

		// Verify the meta doesn't exist or is empty.
		$this->assertEmpty( $customer->get_meta( 'wc_last_active' ) );

		// Fetch via REST API.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers/' . $customer->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		// last_active and last_active_gmt should be null, not a current timestamp.
		$this->assertNull( $data['last_active'], 'last_active should be null when wc_last_active meta is not set' );
		$this->assertNull( $data['last_active_gmt'], 'last_active_gmt should be null when wc_last_active meta is not set' );
	}

	/**
	 * Test edge case: invalid customer ID.
	 */
	public function test_invalid_customer_id(): void {
		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers/99999' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_api_v4_customers_invalid_id', $response->get_data()['code'] );
	}

	/**
	 * Test edge case: creating customer with existing email.
	 */
	public function test_create_customer_with_existing_email(): void {
		$customer = $this->create_test_customer();

		$customer_data = array(
			'email'      => $customer->get_email(),
			'first_name' => 'Duplicate',
			'last_name'  => 'Email',
			'username'   => 'duplicate',
			'password'   => 'password123',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * Test edge case: creating customer with existing username.
	 */
	public function test_create_customer_with_existing_username(): void {
		$customer = $this->create_test_customer();

		$customer_data = array(
			'email'      => 'different@example.com',
			'first_name' => 'Duplicate',
			'last_name'  => 'Username',
			'username'   => $customer->get_username(),
			'password'   => 'password123',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * Test edge case: updating customer email to existing email.
	 */
	public function test_update_customer_email_to_existing(): void {
		$customer1 = $this->create_test_customer();
		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'customer2@example.com',
				'username' => 'customer2',
			)
		);

		$update_data = array(
			'email' => $customer2->get_email(),
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/customers/' . $customer1->get_id() );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test edge case: deleting customer without force parameter.
	 */
	public function test_delete_customer_without_force(): void {
		$customer = $this->create_test_customer();

		$request  = new WP_REST_Request( 'DELETE', '/wc/v4/customers/' . $customer->get_id() );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 501, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_api_v4_customers_trash_not_supported', $response->get_data()['code'] );
	}

	/**
	 * Test edge case: updating administrator customer email.
	 */
	public function test_update_administrator_email(): void {
		// Create an administrator user.
		$admin_user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$update_data = array(
			'email' => 'newadmin@example.com',
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v4/customers/' . $admin_user );
		$request->set_body_params( $update_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cannot_edit', $response->get_data()['code'] );

		wp_delete_user( $admin_user );
	}

	/**
	 * Test edge case: deleting administrator customer.
	 */
	public function test_delete_administrator_customer(): void {
		// Create an administrator user.
		$admin_user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$request = new WP_REST_Request( 'DELETE', '/wc/v4/customers/' . $admin_user );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_cannot_delete', $response->get_data()['code'] );

		wp_delete_user( $admin_user );
	}

	/**
	 * Test edge case: creating customer with invalid email format.
	 */
	public function test_create_customer_with_invalid_email(): void {
		$customer_data = array(
			'email'      => 'invalid-email',
			'first_name' => 'Test',
			'last_name'  => 'User',
			'username'   => 'testuser',
			'password'   => 'password123',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 500, $response->get_status() );
	}

	/**
	 * Test edge case: creating customer without required email.
	 */
	public function test_create_customer_without_email(): void {
		$customer_data = array(
			'first_name' => 'Test',
			'last_name'  => 'User',
			'username'   => 'testuser',
			'password'   => 'password123',
		);

		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test comprehensive value validation for all customer fields.
	 */
	public function test_comprehensive_value_validation(): void {
		$customer_data = array(
			'email'      => 'comprehensive@example.com',
			'first_name' => 'Comprehensive',
			'last_name'  => 'Test',
			'username'   => 'comprehensive',
			'password'   => 'password123',
			'billing'    => array(
				'first_name' => 'Comprehensive',
				'last_name'  => 'Test',
				'company'    => 'Test Company',
				'email'      => 'comprehensive@example.com',
				'phone'      => '555-1234',
				'address_1'  => '123 Test St',
				'address_2'  => 'Suite 100',
				'city'       => 'Test City',
				'state'      => 'TS',
				'postcode'   => '12345',
				'country'    => 'US',
			),
			'shipping'   => array(
				'first_name' => 'Comprehensive',
				'last_name'  => 'Test',
				'company'    => 'Test Company',
				'address_1'  => '123 Test St',
				'address_2'  => 'Suite 100',
				'city'       => 'Test City',
				'state'      => 'TS',
				'postcode'   => '12345',
				'country'    => 'US',
			),
		);

		// Create customer.
		$request = new WP_REST_Request( 'POST', '/wc/v4/customers' );
		$request->set_body_params( $customer_data );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$response_data = $response->get_data();

		// Verify all values match what was sent.
		$this->assertEquals( 'comprehensive@example.com', $response_data['email'] );
		$this->assertEquals( 'Comprehensive', $response_data['first_name'] );
		$this->assertEquals( 'Test', $response_data['last_name'] );
		$this->assertEquals( 'comprehensive', $response_data['username'] );
		$this->assertEquals( 'customer', $response_data['role'] );

		// Verify billing address.
		$this->assertEquals( 'Comprehensive', $response_data['billing']['first_name'] );
		$this->assertEquals( 'Test', $response_data['billing']['last_name'] );
		$this->assertEquals( 'Test Company', $response_data['billing']['company'] );
		$this->assertEquals( 'comprehensive@example.com', $response_data['billing']['email'] );
		$this->assertEquals( '555-1234', $response_data['billing']['phone'] );
		$this->assertEquals( '123 Test St', $response_data['billing']['address_1'] );
		$this->assertEquals( 'Suite 100', $response_data['billing']['address_2'] );
		$this->assertEquals( 'Test City', $response_data['billing']['city'] );
		$this->assertEquals( 'TS', $response_data['billing']['state'] );
		$this->assertEquals( '12345', $response_data['billing']['postcode'] );
		$this->assertEquals( 'US', $response_data['billing']['country'] );

		// Verify shipping address.
		$this->assertEquals( 'Comprehensive', $response_data['shipping']['first_name'] );
		$this->assertEquals( 'Test', $response_data['shipping']['last_name'] );
		$this->assertEquals( 'Test Company', $response_data['shipping']['company'] );
		$this->assertEquals( '123 Test St', $response_data['shipping']['address_1'] );
		$this->assertEquals( 'Suite 100', $response_data['shipping']['address_2'] );
		$this->assertEquals( 'Test City', $response_data['shipping']['city'] );
		$this->assertEquals( 'TS', $response_data['shipping']['state'] );
		$this->assertEquals( '12345', $response_data['shipping']['postcode'] );
		$this->assertEquals( 'US', $response_data['shipping']['country'] );
	}

	/**
	 * Test orderBy functionality with different sorting fields.
	 */
	public function test_orderby_functionality(): void {
		// Create test customers with different registration dates.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'orderby1@example.com',
				'username' => 'orderby1',
			)
		);

		// Wait a moment to ensure different registration times.
		sleep( 1 );

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'orderby2@example.com',
				'username' => 'orderby2',
			)
		);

		// Test ordering by ID ascending.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		$this->assertLessThanOrEqual( $response_data[1]['id'], $response_data[0]['id'] );

		// Test ordering by ID descending.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		$this->assertGreaterThanOrEqual( $response_data[1]['id'], $response_data[0]['id'] );

		// Test ordering by registered_date ascending.
		$request->set_param( 'orderby', 'registered_date' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		$this->assertLessThanOrEqual( strtotime( $response_data[1]['date_created'] ), strtotime( $response_data[0]['date_created'] ) );

		// Test ordering by registered_date descending.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		$this->assertGreaterThanOrEqual( strtotime( $response_data[1]['date_created'] ), strtotime( $response_data[0]['date_created'] ) );
	}

	/**
	 * Test orderBy with name sorting.
	 */
	public function test_orderby_name(): void {
		// Create customers with different names.
		$customer1 = $this->create_test_customer(
			array(
				'email'      => 'alpha@example.com',
				'username'   => 'alpha',
				'first_name' => 'Alpha',
				'last_name'  => 'Customer',
			)
		);

		$customer2 = $this->create_test_customer(
			array(
				'email'      => 'beta@example.com',
				'username'   => 'beta',
				'first_name' => 'Beta',
				'last_name'  => 'Customer',
			)
		);

		// Test ordering by name ascending.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'name' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		// Should be ordered by display name (first_name + last_name).
		$this->assertLessThanOrEqual( $response_data[1]['first_name'], $response_data[0]['first_name'] );

		// Test ordering by name descending.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		$this->assertGreaterThanOrEqual( $response_data[1]['first_name'], $response_data[0]['first_name'] );
	}

	/**
	 * Test orderBy with orders_count sorting.
	 */
	public function test_orderby_orders_count(): void {
		global $wpdb;
		$site_specific_key = rtrim( $wpdb->get_blog_prefix( get_current_blog_id() ), '_' );

		// Create customers with different order counts.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'orders1@example.com',
				'username' => 'orders1',
			)
		);
		update_user_meta( $customer1->get_id(), 'wc_order_count_' . $site_specific_key, 5 );

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'orders2@example.com',
				'username' => 'orders2',
			)
		);
		update_user_meta( $customer2->get_id(), 'wc_order_count_' . $site_specific_key, 2 );

		$customer3 = $this->create_test_customer(
			array(
				'email'    => 'orders3@example.com',
				'username' => 'orders3',
			)
		);
		// customer3 has 0 orders (default value).

		// Test ordering by orders_count ascending.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'order_count' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 3, $response_data );
		// Customer3 should come first (0 orders), then customer2 (2 orders), then customer1 (5 orders).
		$this->assertEquals( 0, $response_data[0]['orders_count'] );
		$this->assertEquals( 2, $response_data[1]['orders_count'] );
		$this->assertEquals( 5, $response_data[2]['orders_count'] );

		// Test ordering by orders_count descending.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 3, $response_data );
		// Customer1 should come first (5 orders), then customer2 (2 orders), then customer3 (0 orders).
		$this->assertEquals( 5, $response_data[0]['orders_count'] );
		$this->assertEquals( 2, $response_data[1]['orders_count'] );
		$this->assertEquals( 0, $response_data[2]['orders_count'] );
	}

	/**
	 * Test orderBy with total_spent sorting.
	 */
	public function test_orderby_total_spent(): void {
		global $wpdb;
		$site_specific_key = rtrim( $wpdb->get_blog_prefix( get_current_blog_id() ), '_' );

		// Create customers.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'spent1@example.com',
				'username' => 'spent1',
			)
		);
		update_user_meta( $customer1->get_id(), 'wc_money_spent_' . $site_specific_key, 300 );

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'spent2@example.com',
				'username' => 'spent2',
			)
		);
		update_user_meta( $customer2->get_id(), 'wc_money_spent_' . $site_specific_key, 200 );

		// Test ordering by total_spent ascending.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'total_spent' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		// Customer2 should come first (lower total), then customer1 (higher total).
		$this->assertEquals( $response_data[0]['id'], $customer2->get_id() );
		$this->assertEquals( $response_data[1]['id'], $customer1->get_id() );

		// Test ordering by total_spent descending.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		// Customer1 should come first (higher total), then customer2 (lower total).
		$this->assertEquals( $response_data[0]['id'], $customer1->get_id() );
		$this->assertEquals( $response_data[1]['id'], $customer2->get_id() );
	}

	/**
	 * Test orderBy with last_active sorting.
	 */
	public function test_orderby_last_active(): void {
		// Create customers.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'active1@example.com',
				'username' => 'active1',
			)
		);
		update_user_meta( $customer1->get_id(), 'wc_last_active', time() - 3600 ); // 1 hour ago

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'active2@example.com',
				'username' => 'active2',
			)
		);
		update_user_meta( $customer2->get_id(), 'wc_last_active', time() - 1800 ); // 30 minutes ago

		// Test ordering by last_active ascending.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'last_active' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertEquals( 2, count( $response_data ) );
		// Customer1 should come first (1 hour ago), then customer2 (30 minutes ago).
		$this->assertEquals( $response_data[0]['id'], $customer1->get_id() );
		$this->assertEquals( $response_data[1]['id'], $customer2->get_id() );

		// Test ordering by last_active descending.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );
		// Customer2 should come first (30 minutes ago), then customer1 (1 hour ago).
		$this->assertEquals( $response_data[0]['id'], $customer2->get_id() );
		$this->assertEquals( $response_data[1]['id'], $customer1->get_id() );
	}

	/**
	 * Test default ordering when no parameters are provided.
	 */
	public function test_default_ordering(): void {
		// Create a few customers.
		$this->create_test_customer(
			array(
				'email'    => 'default1@example.com',
				'username' => 'default1',
			)
		);

		$this->create_test_customer(
			array(
				'email'    => 'default2@example.com',
				'username' => 'default2',
			)
		);

		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertIsArray( $response_data );
		$this->assertGreaterThanOrEqual( 2, count( $response_data ) );
	}

	/**
	 * @testdox When orderby parameter is not provided, customers should be ordered by user_registered (registered_date) in ascending order by default
	 */
	public function test_orderby_defaults_to_user_registered(): void {
		// Create customers with staggered registration times.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'orderbydefault1@example.com',
				'username' => 'orderbydefault1',
			)
		);

		// Wait to ensure different registration times.
		sleep( 1 );

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'orderbydefault2@example.com',
				'username' => 'orderbydefault2',
			)
		);

		sleep( 1 );

		$customer3 = $this->create_test_customer(
			array(
				'email'    => 'orderbydefault3@example.com',
				'username' => 'orderbydefault3',
			)
		);

		// Make request without orderby parameter.
		$request  = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Find our test customers in the results.
		$test_customers = array();
		foreach ( $response_data as $customer_data ) {
			if ( in_array( $customer_data['id'], array( $customer1->get_id(), $customer2->get_id(), $customer3->get_id() ), true ) ) {
				$test_customers[] = $customer_data;
			}
		}

		$this->assertCount( 3, $test_customers, 'Should have found all 3 test customers' );

		// Verify they are ordered by registration date (ascending by default).
		// customer1 should come before customer2, customer2 before customer3.
		$customer1_index = array_search( $customer1->get_id(), array_column( $test_customers, 'id' ), true );
		$customer2_index = array_search( $customer2->get_id(), array_column( $test_customers, 'id' ), true );
		$customer3_index = array_search( $customer3->get_id(), array_column( $test_customers, 'id' ), true );

		$this->assertLessThan( $customer2_index, $customer1_index, 'Customer1 (registered first) should come before customer2' );
		$this->assertLessThan( $customer3_index, $customer2_index, 'Customer2 (registered second) should come before customer3' );

		// Verify the dates are in ascending order.
		$date1 = strtotime( $test_customers[ $customer1_index ]['date_created'] );
		$date2 = strtotime( $test_customers[ $customer2_index ]['date_created'] );
		$date3 = strtotime( $test_customers[ $customer3_index ]['date_created'] );

		$this->assertLessThanOrEqual( $date2, $date1, 'Customer1 registration date should be before or equal to customer2' );
		$this->assertLessThanOrEqual( $date3, $date2, 'Customer2 registration date should be before or equal to customer3' );
	}

	/**
	 * @testdox When orderby parameter is explicitly provided, customers should be ordered by that field
	 */
	public function test_orderby_is_applied_when_present(): void {
		global $wpdb;
		$site_specific_key = rtrim( $wpdb->get_blog_prefix( get_current_blog_id() ), '_' );

		// Create customers with different order counts.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'orderbypresent1@example.com',
				'username' => 'orderbypresent1',
			)
		);
		update_user_meta( $customer1->get_id(), 'wc_order_count_' . $site_specific_key, 10 );

		sleep( 1 );

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'orderbypresent2@example.com',
				'username' => 'orderbypresent2',
			)
		);
		update_user_meta( $customer2->get_id(), 'wc_order_count_' . $site_specific_key, 25 );

		sleep( 1 );

		$customer3 = $this->create_test_customer(
			array(
				'email'    => 'orderbypresent3@example.com',
				'username' => 'orderbypresent3',
			)
		);
		update_user_meta( $customer3->get_id(), 'wc_order_count_' . $site_specific_key, 5 );

		// Test orderby=order_count with ascending order.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'order_count' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Find our test customers in the results.
		$test_customers = array();
		foreach ( $response_data as $customer_data ) {
			if ( in_array( $customer_data['id'], array( $customer1->get_id(), $customer2->get_id(), $customer3->get_id() ), true ) ) {
				$test_customers[] = $customer_data;
			}
		}

		$this->assertCount( 3, $test_customers, 'Should have found all 3 test customers' );

		// When ordered by order_count ascending: customer3 (5), customer1 (10), customer2 (25).
		$this->assertEquals( 5, $test_customers[0]['orders_count'], 'First customer should have 5 orders' );
		$this->assertEquals( 10, $test_customers[1]['orders_count'], 'Second customer should have 10 orders' );
		$this->assertEquals( 25, $test_customers[2]['orders_count'], 'Third customer should have 25 orders' );

		// Verify they are our expected customers.
		$this->assertEquals( $customer3->get_id(), $test_customers[0]['id'], 'First should be customer3' );
		$this->assertEquals( $customer1->get_id(), $test_customers[1]['id'], 'Second should be customer1' );
		$this->assertEquals( $customer2->get_id(), $test_customers[2]['id'], 'Third should be customer2' );

		// Test orderby=order_count with descending order.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Find our test customers in the results.
		$test_customers = array();
		foreach ( $response_data as $customer_data ) {
			if ( in_array( $customer_data['id'], array( $customer1->get_id(), $customer2->get_id(), $customer3->get_id() ), true ) ) {
				$test_customers[] = $customer_data;
			}
		}

		$this->assertCount( 3, $test_customers, 'Should have found all 3 test customers' );

		// When ordered by order_count descending: customer2 (25), customer1 (10), customer3 (5).
		$this->assertEquals( 25, $test_customers[0]['orders_count'], 'First customer should have 25 orders' );
		$this->assertEquals( 10, $test_customers[1]['orders_count'], 'Second customer should have 10 orders' );
		$this->assertEquals( 5, $test_customers[2]['orders_count'], 'Third customer should have 5 orders' );

		// Verify they are our expected customers.
		$this->assertEquals( $customer2->get_id(), $test_customers[0]['id'], 'First should be customer2' );
		$this->assertEquals( $customer1->get_id(), $test_customers[1]['id'], 'Second should be customer1' );
		$this->assertEquals( $customer3->get_id(), $test_customers[2]['id'], 'Third should be customer3' );

		// Test orderby=id to verify another orderby field works correctly.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Find our test customers in the results.
		$test_customers = array();
		foreach ( $response_data as $customer_data ) {
			if ( in_array( $customer_data['id'], array( $customer1->get_id(), $customer2->get_id(), $customer3->get_id() ), true ) ) {
				$test_customers[] = $customer_data;
			}
		}

		$this->assertCount( 3, $test_customers, 'Should have found all 3 test customers' );

		// When ordered by ID ascending, verify they are in ID order.
		$ids        = array_column( $test_customers, 'id' );
		$sorted_ids = $ids;
		sort( $sorted_ids );
		$this->assertEquals( $sorted_ids, $ids, 'Customers should be ordered by ID ascending' );
	}

	/**
	 * @testdox When orderby parameter is set to registered_date, customers should be ordered by user_registered field
	 */
	public function test_orderby_registered_date_uses_user_registered(): void {
		// Create customers with staggered registration times.
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'registered1@example.com',
				'username' => 'registered1',
			)
		);

		sleep( 1 );

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'registered2@example.com',
				'username' => 'registered2',
			)
		);

		sleep( 1 );

		$customer3 = $this->create_test_customer(
			array(
				'email'    => 'registered3@example.com',
				'username' => 'registered3',
			)
		);

		// Test with orderby=registered_date.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'orderby', 'registered_date' );
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Find our test customers in the results.
		$test_customers = array();
		foreach ( $response_data as $customer_data ) {
			if ( in_array( $customer_data['id'], array( $customer1->get_id(), $customer2->get_id(), $customer3->get_id() ), true ) ) {
				$test_customers[] = $customer_data;
			}
		}

		$this->assertCount( 3, $test_customers, 'Should have found all 3 test customers' );

		// Verify they are ordered by registration date (ascending).
		$customer1_index = array_search( $customer1->get_id(), array_column( $test_customers, 'id' ), true );
		$customer2_index = array_search( $customer2->get_id(), array_column( $test_customers, 'id' ), true );
		$customer3_index = array_search( $customer3->get_id(), array_column( $test_customers, 'id' ), true );

		$this->assertLessThan( $customer2_index, $customer1_index, 'Customer1 should come before customer2' );
		$this->assertLessThan( $customer3_index, $customer2_index, 'Customer2 should come before customer3' );

		// Test with descending order.
		$request->set_param( 'order', 'desc' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Find our test customers in the results.
		$test_customers = array();
		foreach ( $response_data as $customer_data ) {
			if ( in_array( $customer_data['id'], array( $customer1->get_id(), $customer2->get_id(), $customer3->get_id() ), true ) ) {
				$test_customers[] = $customer_data;
			}
		}

		$this->assertCount( 3, $test_customers, 'Should have found all 3 test customers' );

		// Verify they are ordered by registration date (descending).
		$customer1_index = array_search( $customer1->get_id(), array_column( $test_customers, 'id' ), true );
		$customer2_index = array_search( $customer2->get_id(), array_column( $test_customers, 'id' ), true );
		$customer3_index = array_search( $customer3->get_id(), array_column( $test_customers, 'id' ), true );

		$this->assertGreaterThan( $customer2_index, $customer1_index, 'Customer1 should come after customer2' );
		$this->assertGreaterThan( $customer3_index, $customer2_index, 'Customer2 should come after customer3' );
	}
}
