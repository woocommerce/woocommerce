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
}
