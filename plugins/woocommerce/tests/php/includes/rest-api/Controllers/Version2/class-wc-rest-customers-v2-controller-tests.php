<?php
declare( strict_types = 1 );

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- legacy conventions.

use Automattic\WooCommerce\Tests\Helpers\MetaDataAssertionTrait;

/**
 * Tests relating to WC_REST_Customers_V2_Controller.
 */
class WC_REST_Customers_V2_Controller_Tests extends WC_Unit_Test_Case {
	use MetaDataAssertionTrait;

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

	/**
	 * @testdox Updating a customer with incomplete meta_data entries does not cause errors.
	 */
	public function test_update_meta_data_with_incomplete_entries(): void {
		wp_set_current_user( $this->admin_id );

		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
		$api_request->set_body_params(
			array(
				'id'        => $this->admin_id,
				'meta_data' => $this->get_incomplete_meta_data_input(),
			)
		);

		$response = $this->sut->update_item( $api_request );
		$this->assertEquals( 200, $response->get_status() );

		$this->assert_incomplete_meta_data_handled_correctly( new \WC_Customer( $this->admin_id ) );
	}

	/**
	 * @testdox Creating a customer ignores meta_data keys that store roles or user levels for any site.
	 * @testWith ["capabilities"]
	 *           ["2_capabilities"]
	 *           ["2_user_level"]
	 *           ["user_level"]
	 *
	 * @param string $suffix Meta key suffix appended to the database base prefix.
	 */
	public function test_customer_create_ignores_capabilities_meta( string $suffix ): void {
		global $wpdb;

		$keys = array( $wpdb->base_prefix . $suffix, strtoupper( $wpdb->base_prefix . $suffix ), $wpdb->base_prefix . $suffix . ' ' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$api_request = new WP_REST_Request( 'POST', '/wc/v2/customers' );
		$api_request->set_body_params(
			array(
				'email'     => 'capabilities_meta@example.com',
				'meta_data' => array_merge(
					array_map(
						fn( $key ) => array(
							'key'   => $key,
							'value' => array( 'administrator' => true ),
						),
						$keys
					),
					array(
						array(
							'key'   => 'my_capabilities',
							'value' => 'test_value',
						),
					)
				),
			)
		);

		$response = $this->sut->create_item( $api_request );
		$this->assertEquals( 201, $response->get_status() );

		$customer_id = $response->get_data()['id'];
		$this->assertSame( 0, $this->count_injected_capabilities_rows( $customer_id ), 'Capabilities meta keys should not be written.' );
		$this->assertSame( array( 'customer' ), get_userdata( $customer_id )->roles );
		$this->assertSame( 'test_value', get_user_meta( $customer_id, 'my_capabilities', true ), 'Other meta keys should still be written.' );
	}

	/**
	 * @testdox Updating a customer cannot rename an existing meta entry to a capabilities key.
	 */
	public function test_customer_update_cannot_rename_meta_to_capabilities_key(): void {
		global $wpdb;

		$customer = new WC_Customer( $this->customer_id );
		$customer->update_meta_data( 'test_key', 'test_value' );
		$customer->save();
		$meta_id = wp_list_pluck( $customer->get_meta_data(), 'id', 'key' )['test_key'];

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
		$api_request->set_body_params(
			array(
				'id'        => $this->customer_id,
				'meta_data' => array(
					array(
						'id'    => $meta_id,
						'key'   => $wpdb->base_prefix . '2_capabilities',
						'value' => array( 'administrator' => true ),
					),
				),
			)
		);

		$response = $this->sut->update_item( $api_request );
		$this->assertEquals( 200, $response->get_status() );

		$this->assertSame( 0, $this->count_injected_capabilities_rows( $this->customer_id ), 'The meta entry should not be renamed to a capabilities key.' );
		$this->assertSame( 'test_value', get_user_meta( $this->customer_id, 'test_key', true ), 'The existing meta entry should be unchanged.' );
	}

	/**
	 * @testdox Updating a customer cannot rename or delete a protected meta entry by its ID.
	 * @testWith [null]
	 *           ["new_value"]
	 *
	 * @param string|null $value Value sent for the entry; null deletes it.
	 */
	public function test_customer_update_cannot_target_protected_meta_by_id( ?string $value ): void {
		$meta_id = add_user_meta( $this->customer_id, '_protected_test_key', 'secret' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/customers/' );
		$api_request->set_body_params(
			array(
				'id'        => $this->customer_id,
				'meta_data' => array(
					array(
						'id'    => $meta_id,
						'key'   => 'harmless_key',
						'value' => $value,
					),
				),
			)
		);

		$response = $this->sut->update_item( $api_request );
		$this->assertEquals( 200, $response->get_status() );

		$this->assertSame( 'secret', get_user_meta( $this->customer_id, '_protected_test_key', true ), 'The protected meta entry should be unchanged.' );
		$this->assertFalse( metadata_exists( 'user', $this->customer_id, 'harmless_key' ), 'The protected meta entry should not be renamed.' );
	}

	/**
	 * Count a user's meta rows holding the administrator role injected by the capabilities tests.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private function count_injected_capabilities_rows( int $user_id ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_value = %s",
				$user_id,
				maybe_serialize( array( 'administrator' => true ) )
			)
		);
	}
}
