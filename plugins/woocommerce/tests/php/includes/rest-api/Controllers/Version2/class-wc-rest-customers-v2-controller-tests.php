<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- legacy conventions.
/**
 * Tests relating to WC_REST_Customers_V2_Controller.
 */
class WC_REST_Customers_V2_Controller_Tests extends WC_Unit_Test_Case {

	/**
	 * @var WC_REST_Customers_V2_Controller System under test.
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
	 * Test setup.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut         = new WC_REST_Customers_V2_Controller();
		$this->admin_id    = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->customer_id = self::factory()->user->create( array( 'role' => 'customer' ) );
	}

	/**
	 * @testDox Test that an admin user can create a customer.
	 */
	public function test_customer_create_permissions(): void {
		$api_request = new WP_REST_Request( 'POST', '/wc/v2/customers' );
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
		$this->assertEquals( 'customer', $data['role'] );
		$customer = new WC_Customer( $data['id'] );
		$this->assertEquals( 'customer', $customer->get_role() );
	}

	/**
	 * @testDox Test that an admin user can update a customer.
	 */
	public function test_customer_update_permissions(): void {
		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
		$api_request->set_param( 'id', $this->customer_id );
		wp_set_current_user( $this->admin_id );

		$this->assertTrue(
			$this->sut->update_item_permissions_check( $api_request ),
			'An admin user can update a customer.'
		);

		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
		$api_request->set_param( 'id', $this->admin_id );
		$api_request->set_param( 'first_name', 'Test name' );
		$this->assertTrue(
			$this->sut->update_item_permissions_check( $api_request ),
			'Non sensitive fields are allowed to be updated.'
		);

		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
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
		$api_request = new WP_REST_Request( 'DELETE', '/wc/v2/customers' );
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
		$api_request = new WP_REST_Request( 'GET', '/wc/v2/customers/' );
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

	/** @testDox Test metadata can be set as expected. */
	public function test_customer_update_metadata(): void {
		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
		$api_request->set_body_params(
			array(
				'id'        => $this->admin_id,
				'meta_data' => array(
					array(
						'key'   => 'test_key',
						'value' => 'test_value',
					),
					array(
						'key'   => '_internal_test_key',
						'value' => '_internal_test_value',
					),
				),
			)
		);
		wp_set_current_user( $this->admin_id );

		$response = $this->sut->update_item( $api_request );
		$this->assertEquals( 200, $response->get_status() );
		$customer = new WC_Customer( $this->admin_id );
		$this->assertEquals( 'test_value', $customer->get_meta( 'test_key' ) );
		$this->assertEmpty( $customer->get_meta( '_internal_test_key' ) );
	}

	/** @testDox Test metadata can be set as expected in a create request. */
	public function test_customer_create_metadata(): void {
		$api_request = new WP_REST_Request( 'POST', '/wc/v2/customers/' );
		$api_request->set_body_params(
			array(
				'email'     => 'test_customer_create_metadata@example.com',
				'meta_data' => array(
					array(
						'key'   => 'test_key',
						'value' => 'test_value',
					),
					array(
						'key'   => '_internal_test_key',
						'value' => '_internal_test_value',
					),
				),
			)
		);

		wp_set_current_user( $this->admin_id );
		$response = $this->sut->create_item( $api_request );
		$this->assertEquals( 201, $response->get_status() );
		$customer = new WC_Customer( $response->get_data()['id'] );
		$this->assertEquals( 'test_value', $customer->get_meta( 'test_key' ) );
		$this->assertEmpty( $customer->get_meta( '_internal_test_key' ) );
	}
}
