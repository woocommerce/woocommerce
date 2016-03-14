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
		$customer->set_address_2( 'Apt 5' );
		$customer->update();

		$customer = new \WC_Customer( $customer_id ); // so we can read fresh copies from the DB
		$this->assertEquals( 'test@wc.local', $customer->get_email() );
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
	 * @since 2.7.0
	 */
	public function test_customer_backwards_compat() {

	}

	/**
	 * Test getting a customer's ID.
	 * @since 2.7.0
	 */
	public function test_customer_get_id() {

	}

	/**
	 * Test getting a customer's username.
	 * @since 2.7.0
	 */
	public function test_customer_get_username() {

	}

	/**
	 * Test getting a customer's email.
	 * @since 2.7.0
	 */
	public function test_customer_get_email() {

	}

	/**
	 * Test getting a customer's first name.
	 * @since 2.7.0
	 */
	public function test_customer_get_first_name() {

	}

	/**
	 * Test getting a customer's last name.
	 * @since 2.7.0
	 */
	public function test_customer_get_last_name() {

	}

	/**
	 * Test getting a customer's role.
	 * @since 2.7.0
	 */
	public function test_customer_get_role() {

	}

	/**
	 * Test getting a customer's last order ID.
	 * @since 2.7.0
	 */
	public function test_customer_get_last_order_id() {

	}

	/**
	 * Test getting a customer's last order date.
	 * @since 2.7.0
	 */
	public function test_customer_get_last_order_date() {

	}

	/**
	 * Test getting a customer's order count.
	 * @since 2.7.0
	 */
	public function test_customer_get_orders_count() {

	}

	/**
	 * Test getting a customer's total amount spent.
	 * @since 2.7.0
	 */
	public function test_customer_get_total_spent() {

	}

	/**
	 * Test getting a customer's avatar URL.
	 * @since 2.7.0
	 */
	public function test_customer_get_avatar_url() {

	}

	/**
	 * Test getting a customer's creation date.
	 * @since 2.7.0
	 */
	public function test_customer_get_date_created() {

	}

	/**
	 * Test getting a customer's modification date.
	 * @since 2.7.0
	 */
	public function test_customer_get_date_modified() {

	}

	/**
	 * Test getting a customer's billing postcode.
	 * @since 2.7.0
	 */
	public function test_customer_get_postcode() {

	}

	/**
	 * Test getting a customer's billing city.
	 * @since 2.7.0
	 */
	public function test_customer_get_city() {

	}

	/**
	 * Test getting a customer's billing address.
	 * @since 2.7.0
	 */
	public function test_customer_get_address() {

	}

	/**
	 * Test getting a customer's billing address (2).
	 * @since 2.7.0
	 */
	public function test_customer_get_address_2() {

	}

	/**
	 * Test getting a customer's billing state.
	 * @since 2.7.0
	 */
	public function test_customer_get_state() {

	}

	/**
	 * Test getting a customer's billing country.
	 * @since 2.7.0
	 */
	public function test_customer_get_country() {

	}

	/**
	 * Test getting a customer's shipping state.
	 * @since 2.7.0
	 */
	public function test_customer_get_shipping_state() {

	}

	/**
	 * Test getting a customer's shipping country.
	 * @since 2.7.0
	 */
	public function test_customer_get_shipping_country() {

	}

	/**
	 * Test getting a customer's shipping postcode/
	 * @since 2.7.0
	 */
	public function test_customer_get_shipping_postcode() {

	}

	/**
	 * Test getting a customer's shipping city.
	 * @since 2.7.0
	 */
	public function test_customer_get_shipping_city() {

	}

	/**
	 * Test getting a customer's shipping address.
	 * @since 2.7.0
	 */
	public function test_customer_get_shipping_address() {

	}

	/**
	 * Test getting a customer's shipping address (2).
	 * @since 2.7.0
	 */
	public function test_customer_get_shipping_address_2() {

	}

	/**
	 * Test getting a customer's vat exempt status.
	 * @since 2.7.0
	 */
	public function test_customer_get_is_vat_exempt() {

	}

	/**
	 * Test getting a customer's "calculated shipping" flag.
	 * @since 2.7.0
	 */
	public function test_customer_get_calculated_shipping() {

	}

	/**
	 * Test getting a customer's taxable address.
	 * @since 2.7.0
	 */
	public function test_customer_get_taxable_address() {

	}

	/**
	 * Test getting a customer's downloadable products.
	 * @since 2.7.0
	 */
	public function test_customer_get_downloadable_products() {

	}

	/**
	 * Test getting a customer's "is paying customer" flag.
	 * @since 2.7.0
	 */
	public function test_customer_get_is_paying_customer() {

	}

	/**
	 * Test getting a customer's data array.
	 * @since 2.7.0
	 */
	public function test_customer_get_data() {

	}

	/**
	 * Test setting a customer's username.
	 * @since 2.7.0
	 */
	public function test_customer_set_username() {

	}

	/**
	 * Test setting a customer's email.
	 * @since 2.7.0
	 */
	public function test_customer_set_email() {

	}

	/**
	 * Test setting a customer's first name.
	 * @since 2.7.0
	 */
	public function test_customer_set_first_name() {

	}

	/**
	 * Test setting a customer's last name.
	 * @since 2.7.0
	 */
	public function test_customer_set_last_name() {

	}

	/**
	 * Test setting a customer's role.
	 * @since 2.7.0
	 */
	public function test_customer_set_role() {

	}

	/**
	 * Test setting a customer's password.
	 * @since 2.7.0
	 */
	public function test_customer_set_password() {

	}

	/**
	 * Test setting a password on update - making sure it actually changes the users password.
	 * @since 2.7.0
	 */
	public function test_customer_password_updates_on_save() {

	}

	/**
	 * Test setting a customer's address to the base address.
	 * @since 2.7.0
	 */
	public function test_customer_set_address_to_base() {

	}

	/**
	 * Test setting a customer's shipping address to the base address.
	 * @since 2.7.0
	 */
	public function test_customer_set_address_shipping_to_base() {

	}

	/**
	 * Test setting a customer's location (multiple address fields at once)
	 * @since 2.7.0
	 */
	public function test_customer_set_location() {

	}

	/**
	 * Test setting a customer's shipping location (multiple address fields at once)
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_location() {

	}

	/**
	 * Test setting a customer's billing postcode.
	 * @since 2.7.0
	 */
	public function test_customer_set_postcode() {

	}

	/**
	 * Test setting a customer's billing city.
	 * @since 2.7.0
	 */
	public function test_customer_set_city() {

	}

	/**
	 * Test setting a customer's billing address.
	 * @since 2.7.0
	 */
	public function test_customer_set_address() {

	}

	/**
	 * Test setting a customer's billing address (2).
	 * @since 2.7.0
	 */
	public function test_customer_set_address_2() {

	}

	/**
	 * Test setting a customer's billing state.
	 * @since 2.7.0
	 */
	public function test_customer_set_state() {

	}

	/**
	 * Test setting a customer's billing country.
	 * @since 2.7.0
	 */
	public function test_customer_set_country() {

	}

	/**
	 * Test setting a customer's shipping state.
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_state() {

	}

	/**
	 * Test setting a customer's shipping country.
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_country() {

	}

	/**
	 * Test setting a customer's shipping postcode.
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_postcode() {

	}

	/**
	 * Test setting a customer's shipping city.
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_city() {

	}

	/**
	 * Test setting a customer's shipping address.
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_address() {

	}

	/**
	 * Test setting a customer's shipping address (2).
	 * @since 2.7.0
	 */
	public function test_customer_set_shipping_address_2() {

	}

	/**
	 * Test setting a customer's "vat exempt" status.
	 * @since 2.7.0
	 */
	public function test_customer_set_is_vat_exempt() {

	}

	/**
	 * Test setting a customer's "calculted shipping" flag.
	 * @since 2.7.0
	 */
	public function test_customer_set_calculated_shipping() {

	}

	/**
	 * Test setting a customer's "is a paying customer" flag.
	 * @since 2.7.0
	 */
	public function test_customer_set_is_paying_customer() {

	}

	/**
	 * Test is_customer_outside_base.
	 * @since 2.7.0
	 */
	public function test_customer_is_customer_outside_base() {

	}

	/**
	 * Test WC_Customer's session handling code.
	 * @since 2.7.0
	 */
	public function test_customer_sessions() {

	}

}
