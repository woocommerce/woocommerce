<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- legacy conventions.
/**
 * Tests relating to WC_REST_Customers_V1_Controller.
 */
class WC_REST_Customers_V1_Controller_Tests extends WC_Unit_Test_Case {

	/**
	 * @var WC_REST_Customers_V1_Controller System under test.
	 */
	private $sut;

	/**
	 * @var int Admin user id.
	 */
	private $admin_id;

	/**
	 * @var int Customer user ID.
	 */
	private $customer_id;

	/**
	 * @var int Customer user ID 2.
	 */
	private $customer_id_2;

	/**
	 * @var string API version.
	 */
	private $version = 'v1';

	/**
	 * Test setup.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut           = new WC_REST_Customers_V1_Controller();
		$this->admin_id      = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->customer_id   = self::factory()->user->create( array( 'role' => 'customer' ) );
		$this->customer_id_2 = self::factory()->user->create( array( 'role' => 'customer' ) );

		global $wp_rest_server;
		if ( is_null( $wp_rest_server ) ) {
			$wp_rest_server = new WP_REST_Server();
		}
	}

	/**
	 * @testDox Test that an admin user can create a customer.
	 */
	public function test_customer_create_permissions(): void {
		$api_request = new WP_REST_Request( 'POST', '/wc/v1/customers' );
		$api_request->set_body_params(
			array(
				'email' => 'test_customer',
				'role'  => 'customer',
			)
		);
		wp_set_current_user( $this->admin_id );

		$this->assertTrue(
			$this->sut->create_item_permissions_check( $api_request ),
			'An admin user can create a customer.'
		);

		$api_request->set_body_params(
			array(
				'role'  => 'administrator',
				'email' => 'test_admin@example.com',
			)
		);
		$response = $this->sut->create_item( $api_request );
		$data     = $response->get_data();
		$customer = new WC_Customer( $data['id'] );
		$this->assertEquals( 'customer', $customer->get_role() );
	}

	/**
	 * @testDox Test that an admin user can update a customer.
	 */
	public function test_customer_update_permissions(): void {
		$api_request = new WP_REST_Request( 'PUT', '/wc/v1/customers/' );
		$api_request->set_param( 'id', $this->customer_id );
		wp_set_current_user( $this->admin_id );

		$this->assertTrue(
			$this->sut->update_item_permissions_check( $api_request ),
			'An admin user can update a customer.'
		);

		$api_request = new WP_REST_Request( 'PUT', '/wc/v1/customers/' );
		$api_request->set_param( 'id', $this->admin_id );
		$api_request->set_param( 'first_name', 'Test name' );
		$this->assertTrue(
			$this->sut->update_item_permissions_check( $api_request ),
			'Non sensitive fields are allowed to be updated.'
		);

		$api_request = new WP_REST_Request( 'PUT', '/wc/v1/customers/' );
		$api_request->set_param( 'id', $this->admin_id );
		$api_request->set_param( 'role', 'customer' );
		$api_request->set_param( 'password', 'test password' );
		$api_request->set_param( 'username', 'admin2' );
		$api_request->set_param( 'email', 'admin2example.com' );
		$this->assertEquals(
			'woocommerce_rest_cannot_edit',
			$this->sut->update_item_permissions_check( $api_request )->get_error_code(),
			'Sensitive fields cannot be updated via the customers api.'
		);
	}

	/**
	 * @testDox Test that an admin user can delete a customer.
	 */
	public function test_customer_delete_permission(): void {
		$api_request = new WP_REST_Request( 'DELETE', '/wc/v1/customers' );
		$api_request->set_param( 'id', $this->customer_id );
		wp_set_current_user( $this->admin_id );

		$this->assertTrue(
			$this->sut->delete_item_permissions_check( $api_request ),
			'An admin user can delete a customer.'
		);

		$api_request->set_param( 'id', $this->admin_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_delete',
			$this->sut->delete_item_permissions_check( $api_request )->get_error_code(),
			'An admin user cannot delete any admin user from customer API.'
		);
	}

	/**
	 * @testDox Test that an admin user can view a customer.
	 */
	public function test_customer_view_permission(): void {
		$api_request = new WP_REST_Request( 'GET', '/wc/v1/customers/' );
		$api_request->set_param( 'id', $this->customer_id );
		wp_set_current_user( $this->admin_id );

		$this->assertTrue(
			$this->sut->get_item_permissions_check( $api_request ),
			'An admin user can view a customer.'
		);

		$api_request->set_param( 'id', $this->admin_id );
		$this->assertTrue(
			$this->sut->get_item_permissions_check( $api_request ),
			'An admin user can view any user, including admins.'
		);
	}

	/**
	 * @testDox test permission of customers request.
	 */
	public function test_batch_permissions_customer() {
		$api_request = new WP_REST_Request( 'POST', '/wc/v1/customers/batch' );
		wp_set_current_user( $this->admin_id );
		$data = array(
			'create' => array(
				array(
					'email' => 'test_customer@example.com',
					'role'  => 'customer',
				),
			),
			'update' => array(
				array(
					'id'    => $this->customer_id,
					'email' => 'dummy_customer_email2@example.com',
				),
			),
			'delete' => array( $this->customer_id_2 ),
		);

		$api_request->set_param( 'create', $data['create'] );
		$api_request->set_param( 'update', $data['update'] );
		$api_request->set_param( 'delete', $data['delete'] );

		$response = $this->sut->batch_items( $api_request );

		$this->assertTrue( $response['create'][0]['id'] > 0, 'Admin user can create a customer.' );

		$this->assertEquals( $this->customer_id, $response['update'][0]['id'] === $this->customer_id, 'Admin user can update a customer.' );
		$this->assertEquals( 'dummy_customer_email2@example.com', get_userdata( $this->customer_id )->user_email, 'Admin user can update a customer.' );

		$this->assertEquals( $this->customer_id_2, $response['delete'][0]['id'], 'Admin user can delete a customer.' );
		$this->assertNotContains( 'error', $response['delete'][0], 'Admin user can delete a customer.' );
	}

	/**
	 * @testDox test permission of admin request.
	 */
	public function test_batch_permissions_admin() {
		$api_request = new WP_REST_Request( 'POST', '/wc/v1/customers/batch' );
		wp_set_current_user( $this->admin_id );
		$data = array(
			'create' => array(
				array( // Invalid role.
					'email' => 'test_customer@example.com',
					'role'  => 'admniistrator',
				),
			),
			'update' => array(
				array( // Invalid user (admin user's email can't be updated).
					'id'    => $this->admin_id,
					'email' => 'dummy_email@example.com',
				),
			),
			'delete' => array( $this->admin_id ),
		);

		$api_request->set_param( 'create', $data['create'] );
		$api_request->set_param( 'update', $data['update'] );
		$api_request->set_param( 'delete', $data['delete'] );

		$response = $this->sut->batch_items( $api_request );

		$create_results = $response['create'];
		$id             = $create_results[0]['id'];
		$customer       = new WC_Customer( $id );
		$this->assertEquals( 'customer', $customer->get_role(), 'Role will always default to customer' );

		$this->assertEquals( 'woocommerce_rest_cannot_edit', $response['update'][0]['error']['code'], 'Admin cannot update an admin' );

		$this->assertEquals( 'woocommerce_rest_cannot_delete', $response['delete'][0]['error']['code'], 'Admin user can delete a customer.' );
	}
}
