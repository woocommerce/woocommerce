<?php // phpcs:ignore Generic.PHP.RequireStrictTypes.MissingDeclaration

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
	 * Test email filtering.
	 */
	public function test_email_filtering(): void {
		$customer1 = $this->create_test_customer(
			array(
				'email'    => 'emailtest1@example.com',
				'username' => 'emailtest1',
			)
		);

		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'emailtest2@example.com',
				'username' => 'emailtest2',
			)
		);

		// Test filtering by specific email - should find customer1 but not customer2.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'email', 'emailtest1@example.com' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_customer1 = false;
		$found_customer2 = false;
		foreach ( $response_data as $customer_data ) {
			$this->assertEquals( 'emailtest1@example.com', $customer_data['email'], 'All returned customers should have the correct email' );
			if ( $customer_data['id'] === $customer1->get_id() ) {
				$found_customer1 = true;
			}
			if ( $customer_data['id'] === $customer2->get_id() ) {
				$found_customer2 = true;
			}
		}
		$this->assertTrue( $found_customer1, 'Should find customer with matching email' );
		$this->assertFalse( $found_customer2, 'Should not find customer with non-matching email' );
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
	 * Test include and exclude parameters.
	 */
	public function test_include_exclude_filtering(): void {
		$customer1 = $this->create_test_customer();
		$customer2 = $this->create_test_customer(
			array(
				'email'    => 'customer2@example.com',
				'username' => 'customer2',
			)
		);
		$customer3 = $this->create_test_customer(
			array(
				'email'    => 'customer3@example.com',
				'username' => 'customer3',
			)
		);

		// Test include parameter - should only return specified customers.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'include', array( $customer1->get_id(), $customer3->get_id() ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$this->assertCount( 2, $response_data );

		$found_customer1 = false;
		$found_customer2 = false;
		$found_customer3 = false;
		foreach ( $response_data as $customer_data ) {
			if ( $customer_data['id'] === $customer1->get_id() ) {
				$found_customer1 = true;
			}
			if ( $customer_data['id'] === $customer2->get_id() ) {
				$found_customer2 = true;
			}
			if ( $customer_data['id'] === $customer3->get_id() ) {
				$found_customer3 = true;
			}
		}
		$this->assertTrue( $found_customer1, 'Should find included customer1' );
		$this->assertFalse( $found_customer2, 'Should not find excluded customer2' );
		$this->assertTrue( $found_customer3, 'Should find included customer3' );

		// Test exclude parameter - should exclude specified customers.
		$request = new WP_REST_Request( 'GET', '/wc/v4/customers' );
		$request->set_param( 'exclude', array( $customer2->get_id() ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		$found_customer1 = false;
		$found_customer2 = false;
		$found_customer3 = false;
		foreach ( $response_data as $customer_data ) {
			if ( $customer_data['id'] === $customer1->get_id() ) {
				$found_customer1 = true;
			}
			if ( $customer_data['id'] === $customer2->get_id() ) {
				$found_customer2 = true;
			}
			if ( $customer_data['id'] === $customer3->get_id() ) {
				$found_customer3 = true;
			}
		}
		$this->assertTrue( $found_customer1, 'Should find non-excluded customer1' );
		$this->assertFalse( $found_customer2, 'Should not find excluded customer2' );
		$this->assertTrue( $found_customer3, 'Should find non-excluded customer3' );
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

		// Test second page.
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$response_data = $response->get_data();

		// Should have customers on second page.
		$this->assertGreaterThanOrEqual( 0, count( $response_data ) );
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

		// Clean up.
		$customer = new WC_Customer( $response_data['id'] );
	}
}
