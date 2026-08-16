<?php
/**
 * Tests for the Customers REST API.
 *
 * @package WooCommerce\Tests\API
 * @since   3.5.0
 */

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

/**
 * Tests for the Customers REST API.
 *
 * @package WooCommerce\Tests\API
 * @extends WC_REST_Unit_Test_Case
 */
class Customers extends WC_REST_Unit_Test_Case {
	use ArraySubsetAsserts;

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Customers_Controller();
	}

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wc/v3/customers', $routes );
		$this->assertArrayHasKey( '/wc/v3/customers/(?P<id>[\d]+)', $routes );
		$this->assertArrayHasKey( '/wc/v3/customers/batch', $routes );
	}

	/**
	 * Test getting customers.
	 *
	 * @since 3.5.0
	 */
	public function test_get_customers() {
		wp_set_current_user( 1 );

		$customer_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer();
		\Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test2', 'test2', 'test2@woo.local' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/customers' );
		$request->set_query_params(
			array(
				'orderby' => 'id',
			)
		);
		$response     = $this->server->dispatch( $request );
		$customers    = $response->get_data();
		$date_created = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $customer_1->get_date_created() ) ) );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $customers ) );

		$matching_customer_data = current(
			array_filter(
				$customers,
				function( $customer ) use ( $customer_1 ) {
					return $customer['id'] === $customer_1->get_id();
				}
			)
		);
		$this->assertIsArray( $matching_customer_data );

		$this->assertArraySubset(
			array(
				'id'                 => $customer_1->get_id(),
				'date_created'       => wc_rest_prepare_date_response( $date_created, false ),
				'date_created_gmt'   => wc_rest_prepare_date_response( $date_created ),
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
				'avatar_url'         => $customer_1->get_avatar_url(),
				'meta_data'          => array(),
				'_links'             => array(
					'self'       => array(
						array(
							'href' => rest_url( '/wc/v3/customers/' . $customer_1->get_id() . '' ),
						),
					),
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v3/customers' ),
						),
					),
				),
			),
			$matching_customer_data
		);

		update_option( 'timezone_tring', 'America/New York' );
		$customer_3 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'timezonetest', 'timezonetest', 'timezonetest@woo.local' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/customers' );
		$request->set_query_params(
			array(
				'orderby' => 'id',
			)
		);
		$response     = $this->server->dispatch( $request );
		$customers    = $response->get_data();
		$date_created = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $customer_3->get_date_created() ) ) );

		$this->assertEquals( 200, $response->get_status() );

		$matching_customer_data = current(
			array_filter(
				$customers,
				function( $customer ) use ( $customer_3 ) {
					return $customer['id'] === $customer_3->get_id();
				}
			)
		);
		$this->assertIsArray( $matching_customer_data );

		$this->assertArraySubset(
			array(
				'id'                 => $customer_3->get_id(),
				'date_created'       => wc_rest_prepare_date_response( $date_created, false ),
				'date_created_gmt'   => wc_rest_prepare_date_response( $date_created ),
				'date_modified'      => wc_rest_prepare_date_response( $customer_3->get_date_modified(), false ),
				'date_modified_gmt'  => wc_rest_prepare_date_response( $customer_3->get_date_modified() ),
				'email'              => 'timezonetest@woo.local',
				'first_name'         => 'Justin',
				'last_name'          => '',
				'role'               => 'customer',
				'username'           => 'timezonetest',
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
				'avatar_url'         => $customer_3->get_avatar_url(),
				'meta_data'          => array(),
				'_links'             => array(
					'self'       => array(
						array(
							'href' => rest_url( '/wc/v3/customers/' . $customer_3->get_id() . '' ),
						),
					),
					'collection' => array(
						array(
							'href' => rest_url( '/wc/v3/customers' ),
						),
					),
				),
			),
			$matching_customer_data
		);
	}

	/**
	 * Test getting customers without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_customers_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test creating a new customer.
	 *
	 * @since 3.5.0
	 */
	public function test_create_customer() {
		wp_set_current_user( 1 );

		// Test just the basics first..
		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
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
				'avatar_url'         => $data['avatar_url'],
			),
			$data
		);

		// Test extra data.
		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
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
				'avatar_url'         => $data['avatar_url'],
			),
			$data
		);

		// Test without required field.
		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
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
	 * @since 3.5.0
	 */
	public function test_create_customer_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
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
	 * @since 3.5.0
	 */
	public function test_get_customer() {
		wp_set_current_user( 1 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'get_customer_test', 'test123', 'get_customer_test@woo.local' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer->get_id() ) );
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
				'avatar_url'         => $data['avatar_url'],
			),
			$data
		);
	}

	/**
	 * Test getting a single customer without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_customer_without_permission() {
		wp_set_current_user( 0 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'get_customer_test_without_permission', 'test123', 'get_customer_test_without_permission@woo.local' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting a single customer with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_get_customer_invalid_id() {
		wp_set_current_user( 1 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test updating a customer.
	 *
	 * @since 3.5.0
	 */
	public function test_update_customer() {
		wp_set_current_user( 1 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'update_customer_test', 'test123', 'update_customer_test@woo.local' );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer->get_id() ) );
		$data     = $response->get_data();
		$this->assertEquals( 'update_customer_test', $data['username'] );
		$this->assertEquals( 'update_customer_test@woo.local', $data['email'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/customers/' . $customer->get_id() );
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
	 * @since 3.5.0
	 */
	public function test_update_customer_without_permission() {
		wp_set_current_user( 0 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'update_customer_test_without_permission', 'test123', 'update_customer_test_without_permission@woo.local' );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test updating a customer with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_update_customer_invalid_id() {
		wp_set_current_user( 1 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}


	/**
	 * Test deleting a customer.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_customer() {
		wp_set_current_user( 1 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'delete_customer_test', 'test123', 'delete_customer_test@woo.local' );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/customers/' . $customer->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test deleting a customer with an invalid ID.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_customer_invalid_id() {
		wp_set_current_user( 1 );
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/customers/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test deleting a customer without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_customer_without_permission() {
		wp_set_current_user( 0 );
		$customer = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'delete_customer_test_without_permission', 'test123', 'delete_customer_test_without_permission@woo.local' );
		$request  = new WP_REST_Request( 'DELETE', '/wc/v3/customers/' . $customer->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test customer batch endpoint.
	 *
	 * @since 3.5.0
	 */
	public function test_batch_customer() {
		wp_set_current_user( 1 );

		$customer_1 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer', 'test123', 'test_batch_customer@woo.local' );
		$customer_2 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer2', 'test123', 'test_batch_customer2@woo.local' );
		$customer_3 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer3', 'test123', 'test_batch_customer3@woo.local' );
		$customer_4 = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper::create_customer( 'test_batch_customer4', 'test123', 'test_batch_customer4@woo.local' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/customers/batch' );
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

		$request  = new WP_REST_Request( 'GET', '/wc/v3/customers' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}

	/**
	 * @testdox Registered V3 routes preserve role filtering, invalid-ID errors, and non-sensitive updates for non-customer users.
	 */
	public function test_customer_roles_and_collection_contract(): void {
		wp_set_current_user( 1 );

		$user_ids = array();

		try {
			$administrator_id = $this->factory->user->create(
				array(
					'user_login' => 'customer_roles_contract_admin',
					'user_email' => 'customer_roles_contract_admin@woo.local',
					'role'       => 'administrator',
				)
			);
			$user_ids[]       = $administrator_id;
			$subscriber_id    = $this->factory->user->create(
				array(
					'user_login' => 'customer_roles_contract_subscriber',
					'user_email' => 'customer_roles_contract_subscriber@woo.local',
					'role'       => 'subscriber',
				)
			);
			$user_ids[]       = $subscriber_id;
			$customer_id      = $this->factory->user->create(
				array(
					'user_login' => 'customer_roles_contract_customer',
					'user_email' => 'customer_roles_contract_customer@woo.local',
					'role'       => 'customer',
				)
			);
			$user_ids[]       = $customer_id;

			foreach (
				array(
					$administrator_id => 'administrator',
					$subscriber_id    => 'subscriber',
					$customer_id      => 'customer',
				) as $user_id => $expected_role
			) {
				$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $user_id ) );
				$data     = $response->get_data();

				$this->assertSame( 200, $response->get_status() );
				$this->assertSame( $user_id, $data['id'] );
				$this->assertSame( $expected_role, $data['role'] );
			}

			$invalid_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/0' ) );
			$invalid_data     = $invalid_response->get_data();

			$this->assertSame( 404, $invalid_response->get_status() );
			$this->assertSame( 'wc_user_invalid_id', $invalid_data['code'] );
			$this->assertSame( 'Invalid user ID.', $invalid_data['message'] );

			$default_request = new WP_REST_Request( 'GET', '/wc/v3/customers' );
			$default_request->set_query_params(
				array(
					'include'  => $user_ids,
					'orderby'  => 'id',
					'per_page' => count( $user_ids ),
				)
			);
			$default_response = $this->server->dispatch( $default_request );

			$this->assertSame( 200, $default_response->get_status() );
			$this->assertSame( array( $customer_id ), wp_list_pluck( $default_response->get_data(), 'id' ) );

			$all_roles_request = new WP_REST_Request( 'GET', '/wc/v3/customers' );
			$all_roles_request->set_query_params(
				array(
					'include'  => $user_ids,
					'orderby'  => 'id',
					'per_page' => count( $user_ids ),
					'role'     => 'all',
				)
			);
			$all_roles_response = $this->server->dispatch( $all_roles_request );
			$all_role_ids       = wp_list_pluck( $all_roles_response->get_data(), 'id' );
			sort( $all_role_ids );
			$expected_all_role_ids = $user_ids;
			sort( $expected_all_role_ids );

			$this->assertSame( 200, $all_roles_response->get_status() );
			$this->assertSame( $expected_all_role_ids, $all_role_ids );

			foreach (
				array(
					$administrator_id => 'Registered Admin',
					$subscriber_id    => 'Registered Subscriber',
				) as $user_id => $updated_name
			) {
				$update_request = new WP_REST_Request( 'PUT', '/wc/v3/customers/' . $user_id );
				$update_request->set_body_params(
					array(
						'first_name' => $updated_name,
						'billing'    => array( 'first_name' => $updated_name ),
						'shipping'   => array( 'first_name' => $updated_name ),
					)
				);
				$update_response = $this->server->dispatch( $update_request );
				$update_data     = $update_response->get_data();

				$this->assertSame( 200, $update_response->get_status() );
				$this->assertSame( $updated_name, $update_data['first_name'] );
				$this->assertSame( $updated_name, $update_data['billing']['first_name'] );
				$this->assertSame( $updated_name, $update_data['shipping']['first_name'] );

				clean_user_cache( $user_id );
				$fresh_customer = new WC_Customer( $user_id );
				$this->assertSame( $updated_name, $fresh_customer->get_first_name() );
				$this->assertSame( $updated_name, $fresh_customer->get_billing_first_name() );
				$this->assertSame( $updated_name, $fresh_customer->get_shipping_first_name() );

				$fresh_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $user_id ) );
				$fresh_data     = $fresh_response->get_data();

				$this->assertSame( 200, $fresh_response->get_status() );
				$this->assertSame( $updated_name, $fresh_data['first_name'] );
				$this->assertSame( $updated_name, $fresh_data['billing']['first_name'] );
				$this->assertSame( $updated_name, $fresh_data['shipping']['first_name'] );
			}
		} finally {
			$this->delete_customer_contract_users( $user_ids );
		}
	}

	/**
	 * @testdox Registered V3 routes round-trip a customer's full address and lifecycle through persistence.
	 */
	public function test_customer_crud_lifecycle_contract(): void {
		wp_set_current_user( 1 );

		$customer_id = 0;
		$billing     = array(
			'first_name' => 'Ada',
			'last_name'  => 'Lovelace',
			'company'    => 'Analytical Engines',
			'address_1'  => '12 St James Square',
			'address_2'  => 'Suite 3',
			'city'       => 'London',
			'state'      => 'London',
			'postcode'   => 'SW1Y 4JH',
			'country'    => 'GB',
			'email'      => 'customer_crud_contract@woo.local',
			'phone'      => '02079460000',
		);
		$shipping    = array(
			'first_name' => 'Ada',
			'last_name'  => 'Lovelace',
			'company'    => 'Analytical Engines',
			'address_1'  => '15 Hanover Square',
			'address_2'  => 'Floor 2',
			'city'       => 'London',
			'state'      => 'London',
			'postcode'   => 'W1S 1HS',
			'country'    => 'GB',
			'phone'      => '02079460001',
		);

		try {
			$create_request = new WP_REST_Request( 'POST', '/wc/v3/customers' );
			$create_request->set_body_params(
				array(
					'username'   => 'customer_crud_contract',
					'password'   => 'test123',
					'email'      => 'customer_crud_contract@woo.local',
					'first_name' => 'Ada',
					'last_name'  => 'Lovelace',
					'billing'    => $billing,
					'shipping'   => $shipping,
				)
			);
			$create_response = $this->server->dispatch( $create_request );
			$create_data     = $create_response->get_data();
			$customer_id     = $create_data['id'];

			$this->assertSame( 201, $create_response->get_status() );
			$this->assertIsInt( $customer_id );
			$this->assertGreaterThan( 0, $customer_id );
			$this->assertSame( 'customer', $create_data['role'] );
			$this->assert_customer_address_matches( $billing, $create_data['billing'] );
			$this->assert_customer_address_matches( $shipping, $create_data['shipping'] );

			$fresh_customer = new WC_Customer( $customer_id );
			$this->assertSame( $billing['address_1'], $fresh_customer->get_billing_address_1() );
			$this->assertSame( $shipping['address_1'], $fresh_customer->get_shipping_address_1() );

			$item_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer_id ) );
			$item_data     = $item_response->get_data();
			$this->assertSame( 200, $item_response->get_status() );
			$this->assertSame( $customer_id, $item_data['id'] );
			$this->assert_customer_address_matches( $billing, $item_data['billing'] );
			$this->assert_customer_address_matches( $shipping, $item_data['shipping'] );

			$collection_request = new WP_REST_Request( 'GET', '/wc/v3/customers' );
			$collection_request->set_query_params( array( 'include' => array( $customer_id ) ) );
			$collection_response = $this->server->dispatch( $collection_request );
			$this->assertSame( 200, $collection_response->get_status() );
			$this->assertSame( array( $customer_id ), wp_list_pluck( $collection_response->get_data(), 'id' ) );

			$billing['first_name']  = 'Augusta';
			$billing['address_1']   = '18 St James Square';
			$shipping['first_name'] = 'Augusta';
			$shipping['address_1']  = '20 Hanover Square';
			$update_request         = new WP_REST_Request( 'PUT', '/wc/v3/customers/' . $customer_id );
			$update_request->set_body_params(
				array(
					'first_name' => 'Augusta',
					'billing'    => $billing,
					'shipping'   => $shipping,
				)
			);
			$update_response = $this->server->dispatch( $update_request );
			$update_data     = $update_response->get_data();

			$this->assertSame( 200, $update_response->get_status() );
			$this->assertSame( 'Augusta', $update_data['first_name'] );
			$this->assert_customer_address_matches( $billing, $update_data['billing'] );
			$this->assert_customer_address_matches( $shipping, $update_data['shipping'] );

			clean_user_cache( $customer_id );
			$fresh_customer = new WC_Customer( $customer_id );
			$this->assertSame( 'Augusta', $fresh_customer->get_first_name() );
			$this->assertSame( $billing['address_1'], $fresh_customer->get_billing_address_1() );
			$this->assertSame( $shipping['address_1'], $fresh_customer->get_shipping_address_1() );

			$fresh_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer_id ) );
			$fresh_data     = $fresh_response->get_data();
			$this->assertSame( 200, $fresh_response->get_status() );
			$this->assertSame( 'Augusta', $fresh_data['first_name'] );
			$this->assert_customer_address_matches( $billing, $fresh_data['billing'] );
			$this->assert_customer_address_matches( $shipping, $fresh_data['shipping'] );

			if ( ! is_multisite() ) {
				$delete_request = new WP_REST_Request( 'DELETE', '/wc/v3/customers/' . $customer_id );
				$delete_request->set_param( 'force', true );
				$delete_response = $this->server->dispatch( $delete_request );
				$this->assertSame( 200, $delete_response->get_status() );

				$deleted_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer_id ) );
				$this->assertSame( 404, $deleted_response->get_status() );
				$customer_id = 0;
			}
		} finally {
			$this->delete_customer_contract_users( array( $customer_id ) );
		}
	}

	/**
	 * @testdox Registered V3 batch routes preserve ordered customer create, update, and delete persistence.
	 */
	public function test_customer_batch_lifecycle_contract(): void {
		wp_set_current_user( 1 );

		$customer_ids = array();
		$customers    = array(
			array(
				'username'   => 'customer_batch_contract_one',
				'password'   => 'test123',
				'email'      => 'customer_batch_contract_one@woo.local',
				'first_name' => 'Grace',
				'last_name'  => 'Hopper',
				'billing'    => array(
					'first_name' => 'Grace',
					'last_name'  => 'Hopper',
					'company'    => 'Navy',
					'address_1'  => '1 Compiler Way',
					'address_2'  => 'Room 101',
					'city'       => 'Arlington',
					'state'      => 'VA',
					'postcode'   => '22201',
					'country'    => 'US',
					'email'      => 'customer_batch_contract_one@woo.local',
					'phone'      => '5550101',
				),
				'shipping'   => array(
					'first_name' => 'Grace',
					'last_name'  => 'Hopper',
					'company'    => 'Navy',
					'address_1'  => '2 Compiler Way',
					'address_2'  => 'Room 102',
					'city'       => 'Arlington',
					'state'      => 'VA',
					'postcode'   => '22201',
					'country'    => 'US',
					'phone'      => '5550102',
				),
			),
			array(
				'username'   => 'customer_batch_contract_two',
				'password'   => 'test123',
				'email'      => 'customer_batch_contract_two@woo.local',
				'first_name' => 'Katherine',
				'last_name'  => 'Johnson',
				'billing'    => array(
					'first_name' => 'Katherine',
					'last_name'  => 'Johnson',
					'company'    => 'NASA',
					'address_1'  => '3 Orbital Lane',
					'address_2'  => 'Desk 201',
					'city'       => 'Hampton',
					'state'      => 'VA',
					'postcode'   => '23666',
					'country'    => 'US',
					'email'      => 'customer_batch_contract_two@woo.local',
					'phone'      => '5550201',
				),
				'shipping'   => array(
					'first_name' => 'Katherine',
					'last_name'  => 'Johnson',
					'company'    => 'NASA',
					'address_1'  => '4 Orbital Lane',
					'address_2'  => 'Desk 202',
					'city'       => 'Hampton',
					'state'      => 'VA',
					'postcode'   => '23666',
					'country'    => 'US',
					'phone'      => '5550202',
				),
			),
		);

		try {
			$create_request = new WP_REST_Request( 'POST', '/wc/v3/customers/batch' );
			$create_request->set_body_params( array( 'create' => $customers ) );
			$create_response = $this->server->dispatch( $create_request );
			$create_data     = $create_response->get_data();
			$customer_ids    = wp_list_pluck( $create_data['create'], 'id' );

			$this->assertSame( 200, $create_response->get_status() );
			$this->assertCount( 2, $create_data['create'] );
			$this->assertSame( wp_list_pluck( $customers, 'username' ), wp_list_pluck( $create_data['create'], 'username' ) );
			$this->assertSame( wp_list_pluck( $customers, 'email' ), wp_list_pluck( $create_data['create'], 'email' ) );
			$this->assert_customer_address_matches( $customers[0]['billing'], $create_data['create'][0]['billing'] );
			$this->assert_customer_address_matches( $customers[1]['shipping'], $create_data['create'][1]['shipping'] );

			foreach ( $customer_ids as $index => $customer_id ) {
				clean_user_cache( $customer_id );
				$fresh_customer = new WC_Customer( $customer_id );
				$this->assertSame( $customers[ $index ]['billing']['address_1'], $fresh_customer->get_billing_address_1() );
				$this->assertSame( $customers[ $index ]['shipping']['address_1'], $fresh_customer->get_shipping_address_1() );

				$fresh_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer_id ) );
				$fresh_data     = $fresh_response->get_data();
				$this->assertSame( 200, $fresh_response->get_status() );
				$this->assertSame( $customer_id, $fresh_data['id'] );
				$this->assert_customer_address_matches( $customers[ $index ]['billing'], $fresh_data['billing'] );
				$this->assert_customer_address_matches( $customers[ $index ]['shipping'], $fresh_data['shipping'] );
			}

			$updated_addresses = array( '11 Updated Way', '12 Updated Way' );
			$update_request    = new WP_REST_Request( 'POST', '/wc/v3/customers/batch' );
			$update_request->set_body_params(
				array(
					'update' => array(
						array(
							'id'         => $customer_ids[0],
							'first_name' => 'Amazing Grace',
							'billing'    => array( 'address_1' => $updated_addresses[0] ),
						),
						array(
							'id'         => $customer_ids[1],
							'first_name' => 'Calculated Katherine',
							'shipping'   => array( 'address_1' => $updated_addresses[1] ),
						),
					),
				)
			);
			$update_response = $this->server->dispatch( $update_request );
			$update_data     = $update_response->get_data();

			$this->assertSame( 200, $update_response->get_status() );
			$this->assertSame( $customer_ids, wp_list_pluck( $update_data['update'], 'id' ) );
			$this->assertSame( 'Amazing Grace', $update_data['update'][0]['first_name'] );
			$this->assertSame( $updated_addresses[0], $update_data['update'][0]['billing']['address_1'] );
			$this->assertSame( 'Calculated Katherine', $update_data['update'][1]['first_name'] );
			$this->assertSame( $updated_addresses[1], $update_data['update'][1]['shipping']['address_1'] );

			foreach ( $customer_ids as $index => $customer_id ) {
				clean_user_cache( $customer_id );
				$fresh_customer = new WC_Customer( $customer_id );
				$fresh_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer_id ) );
				$fresh_data     = $fresh_response->get_data();

				$this->assertSame( 200, $fresh_response->get_status() );
				if ( 0 === $index ) {
					$this->assertSame( 'Amazing Grace', $fresh_customer->get_first_name() );
					$this->assertSame( $updated_addresses[0], $fresh_customer->get_billing_address_1() );
					$this->assertSame( 'Amazing Grace', $fresh_data['first_name'] );
					$this->assertSame( $updated_addresses[0], $fresh_data['billing']['address_1'] );
				} else {
					$this->assertSame( 'Calculated Katherine', $fresh_customer->get_first_name() );
					$this->assertSame( $updated_addresses[1], $fresh_customer->get_shipping_address_1() );
					$this->assertSame( 'Calculated Katherine', $fresh_data['first_name'] );
					$this->assertSame( $updated_addresses[1], $fresh_data['shipping']['address_1'] );
				}
			}

			if ( ! is_multisite() ) {
				$delete_request = new WP_REST_Request( 'POST', '/wc/v3/customers/batch' );
				$delete_request->set_body_params( array( 'delete' => $customer_ids ) );
				$delete_response = $this->server->dispatch( $delete_request );
				$delete_data     = $delete_response->get_data();

				$this->assertSame( 200, $delete_response->get_status() );
				$this->assertSame( $customer_ids, wp_list_pluck( $delete_data['delete'], 'id' ) );

				foreach ( $customer_ids as $customer_id ) {
					$deleted_response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/customers/' . $customer_id ) );
					$this->assertSame( 404, $deleted_response->get_status() );
				}

				$customer_ids = array();
			}
		} finally {
			$this->delete_customer_contract_users( $customer_ids );
		}
	}

	/**
	 * Assert an exact customer address without treating associative key order as behavior.
	 *
	 * @param array $expected Expected address fields.
	 * @param array $actual   Actual address fields.
	 */
	private function assert_customer_address_matches( array $expected, array $actual ): void {
		ksort( $expected );
		ksort( $actual );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Delete users created by the registered-route contract tests.
	 *
	 * @param int[] $user_ids User IDs to delete.
	 */
	private function delete_customer_contract_users( array $user_ids ): void {
		foreach ( array_filter( $user_ids ) as $user_id ) {
			if ( ! get_userdata( $user_id ) ) {
				continue;
			}

			if ( is_multisite() ) {
				wpmu_delete_user( $user_id );
			} else {
				wp_delete_user( $user_id );
			}
		}
	}

	/**
	 * Test customer schema.
	 *
	 * @since 3.5.0
	 */
	public function test_customer_schema() {
		wp_set_current_user( 1 );
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/customers' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 16, count( $properties ) );
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
		$this->assertArrayHasKey( 'phone', $properties['shipping']['properties'] );
	}
}
