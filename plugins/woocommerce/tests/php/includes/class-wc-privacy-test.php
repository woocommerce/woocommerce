<?php

use Automattic\WooCommerce\Enums\OrderStatus;

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
	 * @testdox Erasing the personal data of an order blanks the stored coupon usage identities.
	 *
	 * @return void
	 */
	public function test_remove_order_personal_data_blanks_coupon_usage_identities(): void {
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'guest@example.com' );
		$order->update_meta_data( '_coupon_usage_identities', array( 123 => 'guest@example.com' ) );
		$order->save();

		WC_Privacy_Erasers::remove_order_personal_data( $order );

		$identities = wc_get_order( $order->get_id() )->get_meta( '_coupon_usage_identities' );
		$this->assertSame( array( 123 => '' ), $identities, 'The entries are kept with blank values.' );
		$this->assertStringNotContainsString( 'guest@example.com', wp_json_encode( $identities ), 'The email is gone.' );
	}

	/**
	 * @testdox Cancelling an erased order lowers the coupon count without removing another order's row.
	 *
	 * @return void
	 */
	public function test_cancel_after_erasure_keeps_other_orders_coupon_usage(): void {
		WC_Helper_Coupon::create_coupon( 'erased_release' );

		$order_a = WC_Helper_Order::create_order( 0 );
		$order_a->set_billing_email( 'guest@example.com' );
		$order_a->set_status( OrderStatus::PENDING );
		$order_a->save();
		$order_a->apply_coupon( 'erased_release' );

		$order_b = WC_Helper_Order::create_order( 0 );
		$order_b->set_billing_email( 'other@example.com' );
		$order_b->set_status( OrderStatus::PENDING );
		$order_b->save();
		$order_b->apply_coupon( 'erased_release' );

		$this->assertEquals( 2, ( new WC_Coupon( 'erased_release' ) )->get_usage_count(), 'Both orders are counted.' );

		$order_a = wc_get_order( $order_a->get_id() );
		WC_Privacy_Erasers::remove_order_personal_data( $order_a );

		$order_a = wc_get_order( $order_a->get_id() );
		$order_a->set_billing_email( 'other@example.com' );
		$order_a->save();
		$order_a->update_status( OrderStatus::CANCELLED );

		$coupon = new WC_Coupon( 'erased_release' );
		$this->assertEquals( 1, $coupon->get_usage_count(), 'The count drops by one.' );
		$this->assertContains( 'other@example.com', $coupon->get_used_by(), 'The other order keeps its row.' );
	}
}
