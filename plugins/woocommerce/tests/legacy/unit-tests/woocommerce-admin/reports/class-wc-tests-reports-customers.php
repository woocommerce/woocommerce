<?php
/**
 * Reports customers tests.
 *
 * @package WooCommerce\Admin\Tests\Customers
 */

use Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\DataStore;

/**
 * Class WC_Admin_Tests_Reports_Customers
 */
class WC_Admin_Tests_Reports_Customer extends WC_Unit_Test_Case {

	/**
	 * Test order count calculation for customer.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_order_count
	 */
	public function test_customer_order_count() {
		WC_Helper_Reports::reset_stats_dbs();

		// Create a customer.
		$customer = WC_Helper_Customer::create_customer();

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_id = DataStore::get_customer_id_by_user_id( $customer->get_id() ); // This is the customer ID from lookup table.

		// Create 3 orders.
		foreach ( range( 1, 3 ) as $i ) {
			$order = WC_Helper_Order::create_order( $customer->get_id(), $product );
			$order->save();
		}

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Customer should have 3 orders.
		$this->assertSame( 3, DataStore::get_order_count( $customer_id ) );

		// Failure from bad customer IDs.
		$this->assertSame( null, DataStore::get_order_count( 0 ) );
		$this->assertSame( null, DataStore::get_order_count( 'ABC' ) );
		$this->assertSame( null, DataStore::get_order_count( false ) );
		$this->assertSame( null, DataStore::get_order_count( null ) );
	}

	/**
	 * Test customer lookup tables are cleaned after deleting an order.
	 *
	 * A customer record should only be deleted if the customer has no other orders.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::sync_on_order_delete
	 */
	public function test_order_deletion_removes_customer() {
		WC_Helper_Reports::reset_stats_dbs();

		// Create a customer.
		$customer = WC_Helper_Customer::create_customer();

		// Create products.
		$product1 = new WC_Product_Simple();
		$product1->set_name( 'Test Product 1' );
		$product1->set_regular_price( 1 );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_name( 'Test Product 2' );
		$product2->set_regular_price( 2 );
		$product2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Create the first order.
		$order1 = WC_Helper_Order::create_order( $customer->get_id(), $product1 );
		$order1->save();

		// Create the second order.
		$order2 = WC_Helper_Order::create_order( $customer->get_id(), $product2 );
		$order2->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$customer_id = DataStore::get_customer_id_by_user_id( $customer->get_id() ); // This is the customer ID from lookup table.

		// Customer should remain in lookup table after first order deleted.
		$order1->delete( true );
		$this->assertCount( 1, $this->get_customer_record( $customer_id ), 'customer remains' );

		// Customer should be removed in lookup table after both orders are deleted.
		$order2->delete( true );
		$this->assertCount( 0, $this->get_customer_record( $customer_id ), 'customer removed' );
	}

	/**
	 * Test that delayed account creation (order confirmation page) merges the
	 * guest customer_lookup row instead of creating a duplicate.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::merge_guest_customer_on_delayed_account_creation
	 */
	public function test_delayed_account_creation_merges_guest_row() {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		$email = 'guest-merge-test@example.com';

		// Create a guest order.
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// Verify guest row exists.
		$guest_customer_id = \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_guest_id_by_email( $email );
		$this->assertNotFalse( $guest_customer_id, 'Guest customer row should exist after guest order.' );

		// Register via delayed account creation (same source as the order confirmation page).
		$user_id = wc_create_new_customer(
			$email,
			'',
			'test_password',
			array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'source'     => 'delayed-account-creation',
			)
		);
		$this->assertNotWPError( $user_id );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// The guest row should have been updated in place, not duplicated.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT customer_id, user_id FROM {$wpdb->prefix}wc_customer_lookup WHERE email = %s",
				$email
			)
		);

		$this->assertCount( 1, $rows, 'There should be exactly one customer_lookup row for this email.' );
		$this->assertEquals( $guest_customer_id, (int) $rows[0]->customer_id, 'The original customer_id should be preserved.' );
		$this->assertEquals( $user_id, (int) $rows[0]->user_id, 'The user_id should be updated to the new registered user.' );
	}

	/**
	 * Test that normal (non-delayed) registration does NOT merge a guest row.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::merge_guest_customer_on_delayed_account_creation
	 */
	public function test_normal_registration_does_not_merge_guest_row() {
		global $wpdb;

		WC_Helper_Reports::reset_stats_dbs();

		$email = 'normal-register-test@example.com';

		// Create a guest order.
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $email );
		$order->save();

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		$guest_customer_id = \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_guest_id_by_email( $email );
		$this->assertNotFalse( $guest_customer_id, 'Guest customer row should exist.' );

		// Register via normal flow (no source = no merge).
		$user_id = wc_create_new_customer( $email, '', 'test_password' );
		$this->assertNotWPError( $user_id );

		WC_Helper_Queue::run_all_pending( 'wc-admin-data' );

		// The guest row must remain untouched: same customer_id, user_id still NULL.
		// update_registered_customer skips users with no orders, so no second row is
		// inserted either. What we're guarding against here is the merge function
		// silently claiming the guest row for an unverified registration.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT customer_id, user_id FROM {$wpdb->prefix}wc_customer_lookup WHERE customer_id = %d",
				$guest_customer_id
			)
		);

		$this->assertCount( 1, $rows, 'Guest row should still exist.' );
		$this->assertNull( $rows[0]->user_id, 'Guest row user_id should remain NULL for normal registration.' );
	}

	/**
	 * Get a customer's record from the database.
	 *
	 * @param int $customer_id Analytics Customer ID (not WP User ID).
	 */
	private function get_customer_record( $customer_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wc_customer_lookup WHERE customer_id = %d",
				$customer_id
			)
		);

		return $results;
	}
}
