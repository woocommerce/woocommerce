<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Meta_Box_Order_Downloads::save().
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Meta_Box_Order_Downloads_Test extends WC_Unit_Test_Case {

	/**
	 * @testdox Should skip a download permission row deleted after the order-edit page loaded, and still save the rest.
	 */
	public function test_skips_a_permission_row_deleted_before_save(): void {
		$valid_permission = new WC_Customer_Download();
		$valid_permission->set_order_id( 1 );
		$valid_permission->set_user_email( 'customer@example.com' );
		$valid_permission->save();

		$stale_permission = new WC_Customer_Download();
		$stale_permission->set_order_id( 1 );
		$stale_permission->set_user_email( 'customer@example.com' );
		$stale_permission->save();
		$stale_permission_id = $stale_permission->get_id();

		// Simulate the row being revoked/cleaned up after the order-edit page rendered but before the form posted.
		WC_Data_Store::load( 'customer-download' )->delete_by_id( $stale_permission_id );

		$_POST['permission_id']       = array(
			$valid_permission->get_id(),
			$stale_permission_id,
		);
		$_POST['downloads_remaining'] = array( '5', '5' );
		$_POST['access_expires']      = array( '', '' );

		WC_Meta_Box_Order_Downloads::save( 1, null );

		$reloaded = new WC_Customer_Download( $valid_permission->get_id() );
		$this->assertSame( 5, $reloaded->get_downloads_remaining(), 'The valid permission row should still be updated even though a sibling row was stale.' );
	}
}
