<?php
namespace WooCommerce\Tests\Customer;

/**
 * Class CustomerCRUD.
 * @package WooCommerce\Tests\Customer
 */
class CustomerCRUD extends \WC_Unit_Test_Case {

	/**
	 * Test creating a new customer.
	 * @since 2.7.0
	 */
	public function test_create_customer() {
		$username = 'testusername-' . time();
		$customer = new \WC_Customer();
		$customer->set_username( 'testusername-' . time() );
		$customer->set_password( 'test123' );
		$customer->set_email( 'test@woo.local' );
		$customer->create();
		$wp_user = new \WP_User( $customer->get_id() );

		$this->assertEquals( $username, $customer->get_username() );
		$this->assertNotEquals( 0, $customer->get_id() );
		$this->assertEquals( strtotime( $wp_user->user_registered ), $customer->get_date_created() );
	}

	/**
	 * Test updating a customer.
	 * @since 2.7.0
	 */
	public function test_update_customer() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$this->assertEquals( 'test@woo.local', $customer->get_email() );
		$this->assertEquals( 'Apt 1', $customer->get_address_2() );
		$customer->set_email( 'test@wc.local' );
		$customer->set_first_name( 'Justin' );
		$customer->set_address_2( 'Apt 5' );
		$customer->update();

		$customer = new \WC_Customer( $customer_id ); // so we can read fresh copies from the DB
		$this->assertEquals( 'test@wc.local', $customer->get_email() );
		$this->assertEquals( 'Justin', $customer->get_first_name() );
		$this->assertEquals( 'Apt 5', $customer->get_address_2() );
	}


	/**
	 * Test saving a customer.
	 * @since 2.7.0
	 */
	public function test_save_customer() {
		// test save() -> Create
		$customer = new \WC_Customer();
		$customer->set_username( 'testusername-' . time() );
		$customer->set_password( 'test123' );
		$customer->set_email( 'test@woo.local' );
		$this->assertEquals( 0, $customer->get_id() );
		$customer->save();
		$customer_id = $customer->get_id();
		$wp_user = new \WP_User( $customer->get_id() );

		$this->assertNotEquals( 0, $customer->get_id() );

		// test save() -> Update
		$this->assertEquals( 'test@woo.local', $customer->get_email() );
		$customer->set_email( 'test@wc.local' );
		$customer->save();

		$customer = new \WC_Customer( $customer_id );
		$this->assertEquals( 'test@wc.local', $customer->get_email() );
	}

	/**
	 * Test deleting a customer.
	 * @since 2.7.0
	 */
	public function test_delete_customer() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$this->assertNotEquals( 0, $customer->get_id() );
		$customer->delete();
		$customer->read( $customer_id );
		$this->assertEquals( 0, $customer->get_id() );
	}

	/**
	 * Test reading a customer.
	 * @since 2.7.0
	 */
	public function test_read_customer() {
		$username = 'user-' . time();
		$customer = new \WC_Customer();
		$customer->set_username( $username );
		$customer->set_email( 'test@woo.local' );
		$customer->set_password( 'hunter2' );
		$customer->set_first_name( 'Bob' );
		$customer->set_last_name( 'Bob' );
		$customer->create();
		$customer_id = $customer->get_id();
		$customer_read = new \WC_Customer();
		$customer_read->read( $customer_id );

		$this->assertEquals( $customer_id, $customer_read->get_id() );
		$this->assertEquals( 'test@woo.local', $customer_read->get_email() );
		$this->assertEquals( 'Bob', $customer_read->get_first_name() );
		$this->assertEquals( 'Bob', $customer_read->get_last_name() );
		$this->assertEquals( $username, $customer_read->get_username() );
	}

	/**
	 * Tests backwards compat / legacy handling.
	 * @expectedDeprecated WC_Customer::is_vat_exempt
	 * @expectedDeprecated WC_Customer::has_calculated_shipping
	 * @expectedDeprecated WC_Customer::get_default_country
	 * @expectedDeprecated WC_Customer::get_default_state
	 * @expectedDeprecated WC_Customer::is_paying_customer
	 * @expectedDeprecated WC_Customer::calculated_shipping
	 * @since 2.7.0
	 */
	public function test_customer_backwards_compat() {
		// Properties.
		// Accessing properties directly will throw some wanted deprected notices
		// So we need to let PHPUnit know we are expecting them and it's fine to continue
		$legacy_keys = array(
			'id', 'country', 'state', 'postcode', 'city', 'address', 'address_1', 'address_2',
			'shipping_country', 'shipping_state', 'shipping_postcode', 'shipping_city',
			'shipping_address', 'shipping_address_1', 'shipping_address_2',
			'is_vat_exempt', 'calculated_shipping',
		);

		$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, $legacy_keys );

		$customer = \WC_Helper_Customer::create_customer();

		$this->assertEquals( $customer->get_id(), $customer->id );
		$this->assertEquals( $customer->get_country(), $customer->country );
		$this->assertEquals( $customer->get_state(), $customer->state );
		$this->assertEquals( $customer->get_postcode(), $customer->postcode );
		$this->assertEquals( $customer->get_city(), $customer->city );
		$this->assertEquals( $customer->get_address(), $customer->address );
		$this->assertEquals( $customer->get_address(), $customer->address_1 );
		$this->assertEquals( $customer->get_address_2(), $customer->address_2 );
		$this->assertEquals( $customer->get_shipping_country(), $customer->shipping_country );
		$this->assertEquals( $customer->get_shipping_state(), $customer->shipping_state );
		$this->assertEquals( $customer->get_shipping_postcode(), $customer->shipping_postcode );
		$this->assertEquals( $customer->get_shipping_city(), $customer->shipping_city );
		$this->assertEquals( $customer->get_shipping_address(), $customer->shipping_address );
		$this->assertEquals( $customer->get_shipping_address(), $customer->shipping_address_1 );
		$this->assertEquals( $customer->get_shipping_address_2(), $customer->shipping_address_2 );
		$this->assertEquals( $customer->get_is_vat_exempt(), $customer->is_vat_exempt );
		$this->assertEquals( $customer->get_calculated_shipping(), $customer->calculated_shipping );

		// Functions
		$this->assertEquals( $customer->get_is_vat_exempt(), $customer->is_vat_exempt() );
		$this->assertEquals( $customer->get_calculated_shipping(), $customer->has_calculated_shipping() );
		$default = wc_get_customer_default_location();
		$this->assertEquals( $default['country'], $customer->get_default_country() );
		$this->assertEquals( $default['state'], $customer->get_default_state() );
		$this->assertFalse( $customer->get_calculated_shipping() );
		$customer->calculated_shipping( true );
		$this->assertTrue( $customer->get_calculated_shipping() );
		$this->assertEquals( $customer->get_is_paying_customer(), $customer->is_paying_customer() );
	}

	/**
	 * Test generic getters & setters
	 * @since 2.7.0
	 */
	public function test_customer_setters_and_getters() {
		$time = time();
		$standard_getters_and_setters = array(
			'username' => 'test', 'email' => 'test@woo.local', 'first_name' => 'Bob', 'last_name' => 'tester',
			'role' => 'customer', 'last_order_id' => 5, 'last_order_date' => $time, 'orders_count' => 2,
			'total_spent' => 10.57, 'date_created' => $time, 'date_modified' => $time, 'postcode' => 11010,
			'city' => 'New York', 'address' => '123 Main St.', 'address_1' => '123 Main St.', 'address_2' => 'Apt 2', 'state' => 'NY',
			'country' => 'US', 'shipping_state' => 'NY', 'shipping_postcode' => 11011, 'shipping_city' =>
			'New York', 'shipping_address' => '123 Main St.', 'shipping_address_1' => '123 Main St.',
			'shipping_address_2' => 'Apt 2', 'is_vat_exempt' => true, 'calculated_shipping' => true,
			'is_paying_customer' => true
		);

		$customer = new \WC_Customer;
		foreach ( $standard_getters_and_setters as $function => $value ) {
			$customer->{"set_{$function}"}( $value );
			$this->assertEquals( $value, $customer->{"get_{$function}"}(), $function );
		}
	}

	/**
	 * Test getting a customer's last order ID and date
	 * @since 2.7.0
	 */
	public function test_customer_get_last_order_info() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$order = \WC_Helper_Order::create_order( $customer_id );
		$customer->read( $customer_id );
		$this->assertEquals( $order->id, $customer->get_last_order_id() );
		$this->assertEquals( strtotime( $order->order_date ), $customer->get_last_order_date() );
	}

	/**
	 * Test getting a customer's order count from DB.
	 * @since 2.7.0
	 */
	public function test_customer_get_orders_count_read() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		\WC_Helper_Order::create_order( $customer_id );
		\WC_Helper_Order::create_order( $customer_id );
		\WC_Helper_Order::create_order( $customer_id );
		$customer->read( $customer_id );
		$this->assertEquals( 3, $customer->get_orders_count() );
	}

	/**
	 * Test getting a customer's total amount spent from DB.
	 * @since 2.7.0
	 */
	public function test_customer_get_total_spent_read() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$order = \WC_Helper_Order::create_order( $customer_id );
		$customer->read( $customer_id );
		$this->assertEquals( 0, $customer->get_total_spent() );
		$order->update_status( 'wc-completed' );
		$customer->read( $customer_id );
		$this->assertEquals( 40, $customer->get_total_spent() );
	}

	/**
	 * Test getting a customer's avatar URL.
	 * @since 2.7.0
	 */
	public function test_customer_get_avatar_url() {
		$customer = \WC_Helper_Customer::create_customer();
		$this->assertContains( 'gravatar.com/avatar', $customer->get_avatar_url() );
		$this->assertContains( md5( 'test@woo.local' ), $customer->get_avatar_url() );
	}

	/**
	 * Test getting a customer's creation date from DB.
	 * @since 2.7.0
	 */
	public function test_customer_get_date_created_read() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$user = new \WP_User( $customer_id );
		$this->assertEquals( strtotime( $user->data->user_registered ), $customer->get_date_created() );
	}

	/**
	 * Test getting a customer's modification date from DB.
	 * @since 2.7.0
	 */
	public function test_customer_get_date_modified_read() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$last = get_user_meta( $customer_id, 'last_update', true );
		sleep(1);
		$this->assertEquals( $last, $customer->get_date_modified() );
		$customer->set_address( '1234 Some St.' );
		$customer->save();
		$update = get_user_meta( $customer_id, 'last_update', true );
		$this->assertEquals( $update, $customer->get_date_modified() );
		$this->assertNotEquals( $update, $last );
	}

	/**
	 * Test getting a customer's taxable address.
	 * @since 2.7.0
	 */
	public function test_customer_get_taxable_address() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$customer->set_shipping_postcode( '11111' );
		$customer->set_shipping_city( 'Test' );
		$customer->save();
		$customer->read( $customer_id );

		update_option( 'woocommerce_tax_based_on', 'shipping' );
		$taxable = $customer->get_taxable_address();
		$this->assertEquals( 'US', $taxable[0] );
		$this->assertEquals( 'PA', $taxable[1] );
		$this->assertEquals( '11111', $taxable[2] );
		$this->assertEquals( 'Test', $taxable[3] );

		update_option( 'woocommerce_tax_based_on', 'billing' );
		$taxable = $customer->get_taxable_address();
		$this->assertEquals( 'US', $taxable[0] );
		$this->assertEquals( 'PA', $taxable[1] );
		$this->assertEquals( '19123', $taxable[2] );
		$this->assertEquals( 'Philadelphia', $taxable[3] );

		update_option( 'woocommerce_tax_based_on', 'base' );
		$taxable = $customer->get_taxable_address();
		$this->assertEquals( WC()->countries->get_base_country(), $taxable[0] );
		$this->assertEquals( WC()->countries->get_base_state(), $taxable[1] );
		$this->assertEquals( WC()->countries->get_base_postcode(), $taxable[2] );
		$this->assertEquals( WC()->countries->get_base_city(), $taxable[3] );
	}

	/**
	 * Test getting a customer's downloadable products.
	 * @since 2.7.0
	 */
	public function test_customer_get_downloadable_products() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$this->assertEquals( wc_get_customer_available_downloads( $customer_id ), $customer->get_downloadable_products() );
	}

	/**
	 * Test setting a password on update - making sure it actually changes the users password.
	 * @since 2.7.0
	 */
	public function test_customer_password() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();

		$user = get_user_by( 'id', $customer_id );
		$this->assertTrue( wp_check_password( 'hunter2', $user->data->user_pass, $user->ID ) );

		$customer->set_password( 'hunter3' );
		$customer->save();

		$user = get_user_by( 'id', $customer_id );
		$this->assertTrue( wp_check_password( 'hunter3', $user->data->user_pass, $user->ID ) );
	}

	/**
	 * Test setting a customer's address to the base address.
	 * @since 2.7.0
	 */
	public function test_customer_set_address_to_base() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer->set_address_to_base();
		$base = wc_get_customer_default_location();
		$this->assertEquals( $base['country'], $customer->get_country() );
		$this->assertEquals( $base['state'], $customer->get_state() );
		$this->assertEmpty( $customer->get_postcode() );
		$this->assertEmpty( $customer->get_city() );
	}

	/**
	 * Test setting a customer's shipping address to the base address.
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_address_to_base() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer->set_shipping_address_to_base();
		$base = wc_get_customer_default_location();
		$this->assertEquals( $base['country'], $customer->get_shipping_country() );
		$this->assertEquals( $base['state'], $customer->get_shipping_state() );
		$this->assertEmpty( $customer->get_shipping_postcode() );
		$this->assertEmpty( $customer->get_shipping_city() );
	}

	/**
	 * Test setting a customer's location (multiple address fields at once)
	 * @since 2.7.0
	 */
	public function test_customer_set_location() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer->set_location( 'US', 'OH', '12345', 'Cleveland' );
		$this->assertEquals( 'US', $customer->get_country() );
		$this->assertEquals( 'OH', $customer->get_state() );
		$this->assertEquals( '12345', $customer->get_postcode() );
		$this->assertEquals( 'Cleveland', $customer->get_city() );
	}

	/**
	 * Test setting a customer's shipping location (multiple address fields at once)
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_location() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer->set_shipping_location( 'US', 'OH', '12345', 'Cleveland' );
		$this->assertEquals( 'US', $customer->get_shipping_country() );
		$this->assertEquals( 'OH', $customer->get_shipping_state() );
		$this->assertEquals( '12345', $customer->get_shipping_postcode() );
		$this->assertEquals( 'Cleveland', $customer->get_shipping_city() );
	}

	/**
	 * Test is_customer_outside_base.
	 * @since 2.7.0
	 */
	public function test_customer_is_customer_outside_base() {
		$customer = \WC_Helper_Customer::create_customer();
		$this->assertTrue( $customer->is_customer_outside_base() );
		update_option( 'woocommerce_tax_based_on', 'base' );
		$customer->set_address_to_base();
		$this->assertFalse( $customer->is_customer_outside_base() );
	}

	/**
	 * Test WC_Customer's session handling code.
	 * @since 2.7.0
	 */
	public function test_customer_sessions() {
		$customer = \WC_Helper_Customer::create_customer();
		$session  = \WC_Helper_Customer::create_mock_customer(); // set into session....

		$this->assertNotEmpty( $session->get_id() );
		$this->assertFalse( is_numeric( $session->get_id() ) );
		$this->assertEquals( '19123', $session->get_postcode() );
		$this->assertEquals( '123 South Street', $session->get_address() );
		$this->assertEquals( 'Philadelphia', $session->get_city() );

		$session->set_address( '124 South Street' );
		$session->save_to_session();

		$session = new \WC_Customer();
		$session->load_session();
		$this->assertEquals( '124 South Street', $session->get_address() );
	}

	/**
	 * Test getting meta.
	 * @since 2.7.0
	 */
	public function test_get_meta() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id  = $customer->get_id();
		$meta_value = time() . '-custom-value';
		add_user_meta( $customer_id, 'test_field', $meta_value, true );
		$customer->read( $customer_id );
		$fields = $customer->get_meta_data();
		$this->assertCount( 1, $fields );
		$this->assertEquals( $meta_value, $customer->get_meta( 'test_field') );
	}

	/**
	 * Test setting meta.
	 * @since 2.7.0
	 */
	public function test_set_meta() {
		$customer = \WC_Helper_Customer::create_customer();
		$customer_id  = $customer->get_id();
		$meta_value = time() . '-custom-value';
		$customer->add_meta_data( 'my-field', $meta_value, true );
		$customer->save();
		$customer->read( $customer_id );
		$this->assertEquals( $meta_value, $customer->get_meta( 'my-field' ) );
	}

}
