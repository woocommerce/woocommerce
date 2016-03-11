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
		$customer->save();
		$wp_user = new \WP_User( $customer->get_id() );

		$this->assertEquals( $username, $customer->get_username() );
		$this->assertNotEquals( 0, $customer->get_id() );
		$this->assertEquals( strtotime( $wp_user->user_registered ), $customer->get_date_created() );
	}

}
