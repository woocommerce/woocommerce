<?php

/**
 * Tests relating to the WC_Privacy class.
 */
class WC_Privacy_Test extends WC_Unit_Test_Case {
	/**
	 * @testdox When user accounts are deleted, their content should be preserved.
	 *
	 * @return void
	 */
	public function test_delete_inactive_accounts(): void {
		$customer = WC_Helper_Customer::create_customer();
		$post_id  = $this->factory->post->create( array( 'post_author' => $customer->get_id() ) );
		update_user_meta( $customer->get_id(), 'wc_last_active', time() - YEAR_IN_SECONDS );

		update_option(
			'woocommerce_delete_inactive_accounts',
			array(
				'number' => 1,
				'unit'   => 'months',
			)
		);

		$this->assertEquals( $customer->get_id(), get_post_field( 'post_author', $post_id ), 'The test post was successfully assigned to the customer.' );
		$this->assertEquals( 1, WC_Privacy::delete_inactive_accounts(), 'Dormant customer accounts are removed in accordance with configured privacy settings.' );
		$this->assertFalse( get_user_by( 'id', $customer->get_id() ), 'The deleted user account no longer exists.' );
		$this->assertEquals( '0', get_post_field( 'post_author', $post_id ), 'Posts belonging to the removed user still exist, but do not have an author.' );
	}

	/**
	 * @testdox Erasing a customer removes their email records from coupons and keeps other people's.
	 *
	 * @return void
	 */
	public function test_customer_data_eraser_removes_coupon_email_records(): void {
		$customer_id = WC_Helper_Customer::create_customer( 'eraseme', 'password', 'eraseme@example.com' )->get_id();
		$coupon_a    = WC_Helper_Coupon::create_coupon( 'erase_records_a' )->get_id();
		$coupon_b    = WC_Helper_Coupon::create_coupon( 'erase_records_b' )->get_id();

		add_post_meta( $coupon_a, '_used_by_email', $customer_id . ':eraseme@example.com' );
		add_post_meta( $coupon_a, '_used_by_email', $customer_id . ':billing@example.com' );
		add_post_meta( $coupon_a, '_used_by_email', '999:keep@example.com' );
		add_post_meta( $coupon_a, '_used_by_email', $customer_id . '0:other@example.com' );
		add_post_meta( $coupon_b, '_used_by_email', '888:eraseme@example.com' );
		add_post_meta( $coupon_b, '_used_by_email', '888:noteraseme@example.com' );

		$response = WC_Privacy_Erasers::customer_data_eraser( 'eraseme@example.com', 1 );

		$this->assertTrue( $response['items_removed'], 'The eraser reports removed items.' );
		$this->assertContains( 'Removed customer email from coupon usage records', $response['messages'] );
		$this->assertSame( array( '999:keep@example.com', $customer_id . '0:other@example.com' ), get_post_meta( $coupon_a, '_used_by_email', false ), 'All records of the user and of the email are gone, and a user ID that starts with the same digits is kept.' );
		$this->assertSame( array( '888:noteraseme@example.com' ), get_post_meta( $coupon_b, '_used_by_email', false ), 'A similar email is kept.' );
	}

	/**
	 * @testdox Erasing an email with no account removes the records that hold it.
	 *
	 * @return void
	 */
	public function test_customer_data_eraser_removes_coupon_email_records_without_account(): void {
		$coupon_id = WC_Helper_Coupon::create_coupon( 'erase_records_guest' )->get_id();
		add_post_meta( $coupon_id, '_used_by_email', '777:guest.billing@example.com' );
		add_post_meta( $coupon_id, '_used_by_email', '777:owner@example.com' );

		$response = WC_Privacy_Erasers::customer_data_eraser( 'Guest.Billing@example.com', 1 );

		$this->assertTrue( $response['items_removed'], 'The eraser reports removed items.' );
		$this->assertContains( 'Removed customer email from coupon usage records', $response['messages'] );
		$this->assertSame( array( '777:owner@example.com' ), get_post_meta( $coupon_id, '_used_by_email', false ) );
	}
}
