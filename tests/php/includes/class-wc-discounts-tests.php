<?php
/**
 * Unit tests for WC_Discounts class.
 *
 * @package WooCommerce\Tests.
 */

 /**
  * Class WC_Discounts_Tests.
  */
class WC_Discounts_Tests extends WC_Unit_Test_Case {

	/**
	 * Helper method to create limited coupon.
	 */
	private function create_limited_coupon() {
		update_option( 'woocommerce_hold_stock_minutes', 60 );
		return WC_Helper_Coupon::create_coupon(
			'coupon4one' . microtime( true ) . wp_generate_password( 6, false, false ),
			array(
				'usage_limit'          => 1,
				'usage_limit_per_user' => 1,
			)
		);
	}

	/**
	 * Helper method to create customer.
	 */
	public function create_customer() {
		$username = sanitize_title( 'testusername-' . microtime( true ) . wp_generate_password( 6, false, false ) );
		$customer = new WC_Customer();
		$customer->set_username( $username );
		$customer->set_password( 'test123' );
		$customer->set_email( "$username@woo.local" );
		$customer->save();
		return $customer;
	}

	/**
	 * Test if coupon is valid when usage limit is reached for guest
	 */
	public function test_is_coupon_valid_when_limit_reached_for_guest() {
		$coupon     = $this->create_limited_coupon();
		$data_store = WC_Data_Store::load( 'coupon' );

		$result = $data_store->check_and_hold_coupon( $coupon );
		$this->assertNotNull( $result );

		wp_set_current_user( 0 );
		$valid = ( new WC_Discounts() )->is_coupon_valid( $coupon );
		$this->assertWPError( $valid );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK_GUEST ), $valid->get_error_message() );
	}

	/**
	 * Test if coupon is valid when usage limit is reached for logged in user.
	 */
	public function test_is_coupon_valid_when_limit_reached_for_user() {
		$coupon     = $this->create_limited_coupon();
		$customer   = $this->create_customer();
		$data_store = WC_Data_Store::load( 'coupon' );
		$order      = wc_create_order(
			array(
				'status'      => 'pending',
				'customer_id' => $customer->get_id(),
			)
		);
		$order->save();

		$result = $data_store->check_and_hold_coupon( $coupon );
		$this->assertNotNull( $result );

		wp_set_current_user( $customer->get_id() );
		$valid = ( new WC_Discounts() )->is_coupon_valid( $coupon );
		$this->assertWPError( $valid );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK ), $valid->get_error_message() );
	}

	/**
	 * Test if coupon is valid when usage limit per user is reached for logged in user.
	 */
	public function test_is_coupon_valid_per_user_when_limit_reached_for_user() {
		$coupon     = $this->create_limited_coupon();
		$data_store = WC_Data_Store::load( 'coupon' );
		$customer   = $this->create_customer();

		$result = $data_store->check_and_hold_coupon_for_user( $coupon, array( $customer->get_id() ), $customer->get_id() );
		$this->assertNotNull( $result );

		wp_set_current_user( $customer->get_id() );
		$valid = ( new WC_Discounts() )->is_coupon_valid( $coupon );
		$this->assertWPError( $valid );
		$this->assertEquals( $coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK ), $valid->get_error_message() );
	}
}
