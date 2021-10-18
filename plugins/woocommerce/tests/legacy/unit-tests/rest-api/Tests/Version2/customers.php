<?php
/**
 * Tests for the Customers REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.0.0
 */

/**
 * Tests for the Customers REST API.
 *
 * @package WooCommerce\Tests\API
 * @extends WC_REST_Unit_Test_Case
 */
class Customers_V2 extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Customers_Controller();
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.0.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v2/customers', $routes );
		$this->assertArrayHasKey( '/wc/v2/customers/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v2/customers/batch', $routes );
	}

	/**
	 * Test getting customers.
	 *
	 * @since 3.0.0
	 */
	public function test_get_customers() {
		wp_set_current_user( 1 );

		$customer_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer();
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test2', 'test2', 'test2@woo.local' );

		$request = new WP_REST_Request( 'GET', '/wc/v2/customers' );
		$request->set_query_params(
			array(
				'orderby' => 'id',
			)
		);
		$response  = $this->server->dispatch( $request );
		$customers = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $customers ) );

		$this->assertContains(
			array(
				'id'                 => $customer_1->get_id(),
				'date_created'       => wc_rest_prepare_date_response( $customer_1->get_date_created(), false ),
				'date_created_gmt'   => wc_rest_prepare_date_response( $customer_1->get_date_created() ),
				'date_modified'      => wc_rest_prepare_date_response( $customer_1->get_date_modified(), false ),
				'date_modified_gmt'  => wc_rest_prepare_date_response( $customer_1->get_date_modified() ),
				'email'              => 'test@woo.local',
				'first_name'         => 'Justin',
				'last_name'          => '',
				'role'               => 'customer',
				'username'           => 'testcustomer',
				'billing'            => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '123 South Street',
					'address_2'  => 'Apt 1',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94110',
					'country'    => 'US',
					'email'      => '',
					'phone'      => '',
				),
				'shipping'           => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '123 South Street',
					'address_2'  => 'Apt 1',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94110',
					'country'    => 'US',
					'phone'      => '',
				),
				'is_paying_customer' => false,
				'orders_count'       => 0,
				'total_spent'        => '0.00',
				'avatar_url'         => $customer_1->get_avatar_url(),
				'meta_data'          => array(),
				'_links'             => array(
					'self'       => array(
						array(
							'href' => rest_url( '/wc/v2/customers/' . $customer_1->get_id() . '' ),
						),
					),
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v2/customers' ),
						),
					),
				),
			),
			$customers
		);
	}

	/**
	 * Test getting customers without valid permissions.
	 *
	 * @since 3.0.0
	 */
	public function test_get_customers_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test creating a new customer.
	 *
	 * @since 3.0.0
	 */
	public function test_create_customer() {
		wp_set_current_user( 1 );

		// Test just the basics first..
		$request = new WP_REST_Request( 'POST', '/wc/v2/customers' );
		$request->set_body_params(
			array(
				'username' => 'create_customer_test',
				'password' => 'test123',
				'email'    => 'create_customer_test@woo.local',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                 => $data['id'],
				'date_created'       => $data['date_created'],
				'date_created_gmt'   => $data['date_created_gmt'],
				'date_modified'      => $data['date_modified'],
				'date_modified_gmt'  => $data['date_modified_gmt'],
				'email'              => 'create_customer_test@woo.local',
				'first_name'         => '',
				'last_name'          => '',
				'role'               => 'customer',
				'username'           => 'create_customer_test',
				'billing'            => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => '',
					'postcode'   => '',
					'country'    => '',
					'email'      => '',
					'phone'      => '',
				),
				'shipping'           => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => '',
					'postcode'   => '',
					'country'    => '',
					'phone'      => '',
				),
				'is_paying_customer' => false,
				'meta_data'          => array(),
				'orders_count'       => 0,
				'total_spent'        => '0.00',
				'avatar_url'         => $data['avatar_url'],
			),
			$data
		);

		// Test extra data.
		$request = new WP_REST_Request( 'POST', '/wc/v2/customers' );
		$request->set_body_params(
			array(
				'username'   => 'create_customer_test2',
				'password'   => 'test123',
				'email'      => 'create_customer_test2@woo.local',
				'first_name' => 'Test',
				'last_name'  => 'McTestFace',
				'billing'    => array(
					'country' => 'US',
					'state'   => 'WA',
				),
				'shipping'   => array(
					'state'   => 'CA',
					'country' => 'US',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals(
			array(
				'id'                 => $data['id'],
				'date_created'       => $data['date_created'],
				'date_created_gmt'   => $data['date_created_gmt'],
				'date_modified'      => $data['date_modified'],
				'date_modified_gmt'  => $data['date_modified_gmt'],
				'email'              => 'create_customer_test2@woo.local',
				'first_name'         => 'Test',
				'last_name'          => 'McTestFace',
				'role'               => 'customer',
				'username'           => 'create_customer_test2',
				'billing'            => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => 'WA',
					'postcode'   => '',
					'country'    => 'US',
					'email'      => '',
					'phone'      => '',
				),
				'shipping'           => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '',
					'address_2'  => '',
					'city'       => '',
					'state'      => 'CA',
					'postcode'   => '',
					'country'    => 'US',
					'phone'      => '',
				),
				'is_paying_customer' => false,
				'meta_data'          => array(),
				'orders_count'       => 0,
				'total_spent'        => '0.00',
				'avatar_url'         => $data['avatar_url'],
			),
			$data
		);

		// Test without required field.
		$request = new WP_REST_Request( 'POST', '/wc/v2/customers' );
		$request->set_body_params(
			array(
				'username'   => 'create_customer_test3',
				'first_name' => 'Test',
				'last_name'  => 'McTestFace',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test creating customers without valid permissions.
	 *
	 * @since 3.0.0
	 */
	public function test_create_customer_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/wc/v2/customers' );
		$request->set_body_params(
			array(
				'username' => 'create_customer_test_without_permission',
				'password' => 'test123',
				'email'    => 'create_customer_test_without_permission@woo.local',
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single customer.
	 *
	 * @since 3.0.0
	 */
	public function test_get_customer() {
		wp_set_current_user( 1 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'get_customer_test', 'test123', 'get_customer_test@woo.local' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/' . $customer->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals(
			array(
				'id'                 => $data['id'],
				'date_created'       => $data['date_created'],
				'date_created_gmt'   => $data['date_created_gmt'],
				'date_modified'      => $data['date_modified'],
				'date_modified_gmt'  => $data['date_modified_gmt'],
				'email'              => 'get_customer_test@woo.local',
				'first_name'         => 'Justin',
				'billing'            => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '123 South Street',
					'address_2'  => 'Apt 1',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94110',
					'country'    => 'US',
					'email'      => '',
					'phone'      => '',
				),
				'shipping'           => array(
					'first_name' => '',
					'last_name'  => '',
					'company'    => '',
					'address_1'  => '123 South Street',
					'address_2'  => 'Apt 1',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94110',
					'country'    => 'US',
					'phone'      => '',
				),
				'is_paying_customer' => false,
				'meta_data'          => array(),
				'last_name'          => '',
				'role'               => 'customer',
				'username'           => 'get_customer_test',
				'orders_count'       => 0,
				'total_spent'        => '0.00',
				'avatar_url'         => $data['avatar_url'],
			),
			$data
		);
	}

	/**
	 * Test getting a single customer without valid permissions.
	 *
	 * @since 3.0.0
	 */
	public function test_get_customer_without_permission() {
		wp_set_current_user( 0 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'get_customer_test_without_permission', 'test123', 'get_customer_test_without_permission@woo.local' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/' . $customer->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single customer with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_get_customer_invalid_id() {
		wp_set_current_user( 1 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test updating a customer.
	 *
	 * @since 3.0.0
	 */
	public function test_update_customer() {
		wp_set_current_user( 1 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'update_customer_test', 'test123', 'update_customer_test@woo.local' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/' . $customer->get_id() ) );
		$data     = $response->get_data();
		$this->assertEquals( 'update_customer_test', $data['username'] );
		$this->assertEquals( 'update_customer_test@woo.local', $data['email'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' . $customer->get_id() );
		$request->set_body_params(
			array(
				'email'      => 'updated_email@woo.local',
				'first_name' => 'UpdatedTest',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'updated_email@woo.local', $data['email'] );
		$this->assertEquals( 'UpdatedTest', $data['first_name'] );
	}

	/**
	 * Test updating a customer without valid permissions.
	 *
	 * @since 3.0.0
	 */
	public function test_update_customer_without_permission() {
		wp_set_current_user( 0 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'update_customer_test_without_permission', 'test123', 'update_customer_test_without_permission@woo.local' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/' . $customer->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a customer with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_update_customer_invalid_id() {
		wp_set_current_user( 1 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/customers/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}


	/**
	 * Test deleting a customer.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_customer() {
		wp_set_current_user( 1 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'delete_customer_test', 'test123', 'delete_customer_test@woo.local' );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v2/customers/' . $customer->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test deleting a customer with an invalid ID.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_customer_invalid_id() {
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'DELETE', '/wc/v2/customers/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test deleting a customer without valid permissions.
	 *
	 * @since 3.0.0
	 */
	public function test_delete_customer_without_permission() {
		wp_set_current_user( 0 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'delete_customer_test_without_permission', 'test123', 'delete_customer_test_without_permission@woo.local' );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v2/customers/' . $customer->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test customer batch endpoint.
	 *
	 * @since 3.0.0
	 */
	public function test_batch_customer() {
		wp_set_current_user( 1 );

		$customer_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer', 'test123', 'test_batch_customer@woo.local' );
		$customer_2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer2', 'test123', 'test_batch_customer2@woo.local' );
		$customer_3 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer3', 'test123', 'test_batch_customer3@woo.local' );
		$customer_4 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer4', 'test123', 'test_batch_customer4@woo.local' );

		$request = new WP_REST_Request( 'POST', '/wc/v2/customers/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'        => $customer_1->get_id(),
						'last_name' => 'McTest',
					),
				),
				'delete' => array(
					$customer_2->get_id(),
					$customer_3->get_id(),
				),
				'create' => array(
					array(
						'username' => 'newuser',
						'password' => 'test123',
						'email'    => 'newuser@woo.local',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'McTest', $data['update'][0]['last_name'] );
		$this->assertEquals( 'newuser', $data['create'][0]['username'] );
		$this->assertEmpty( $data['create'][0]['last_name'] );
		$this->assertEquals( $customer_2->get_id(), $data['delete'][0]['id'] );
		$this->assertEquals( $customer_3->get_id(), $data['delete'][1]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v2/customers' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}

	/**
	 * Test customer schema.
	 *
	 * @since 3.0.0
	 */
	public function test_customer_schema() {
		wp_set_current_user( 1 );
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v2/customers' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 18, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'date_modified', $properties );
		$this->assertArrayHasKey( 'date_modified_gmt', $properties );
		$this->assertArrayHasKey( 'email', $properties );
		$this->assertArrayHasKey( 'first_name', $properties );
		$this->assertArrayHasKey( 'last_name', $properties );
		$this->assertArrayHasKey( 'role', $properties );
		$this->assertArrayHasKey( 'username', $properties );
		$this->assertArrayHasKey( 'password', $properties );
		$this->assertArrayHasKey( 'orders_count', $properties );
		$this->assertArrayHasKey( 'total_spent', $properties );
		$this->assertArrayHasKey( 'avatar_url', $properties );
		$this->assertArrayHasKey( 'billing', $properties );
		$this->assertArrayHasKey( 'first_name', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'last_name', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'company', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'address_1', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'address_2', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'city', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'state', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'postcode', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'country', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'email', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'phone', $properties['billing']['properties'] );
		$this->assertArrayHasKey( 'shipping', $properties );
		$this->assertArrayHasKey( 'first_name', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'last_name', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'company', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'address_1', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'address_2', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'city', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'state', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'postcode', $properties['shipping']['properties'] );
		$this->assertArrayHasKey( 'country', $properties['shipping']['properties'] );
	}
}
