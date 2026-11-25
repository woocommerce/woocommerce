<?php
/**
 * Reports customers tests.
 *
 * @package WooCommerce\Admin\Tests\Customers
 */

use Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\DataStore;
use Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore as CustomersDataStore;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;

/**
 * Class WC_Admin_Tests_Reports_Customers
 */
class WC_Admin_Tests_Reports_Customer extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preserve registered customer data when importing guest orders with the same email.
	 */
	public function test_guest_import_preserves_registered_customer_data(): void {
		WC_Helper_Reports::reset_stats_dbs();
		$customer = WC_Helper_Customer::create_customer();
		CustomersDataStore::update_registered_customer( $customer->get_id() );
		$customer_id       = CustomersDataStore::get_customer_id_by_user_id( $customer->get_id() );
		$original_row      = $this->get_customer_record( $customer_id );
		$updated_customers = array();
		add_action(
			'woocommerce_analytics_update_customer',
			static function ( $updated_customer_id ) use ( &$updated_customers ) {
				$updated_customers[] = $updated_customer_id;
			}
		);

		$order = new WC_Order();
		$order->set_billing_email( $customer->get_email() );
		$order->set_billing_first_name( 'Guest' );
		$order->set_billing_last_name( 'Customer' );
		$order->set_billing_city( 'Boston' );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		OrdersScheduler::import( $order->get_id() );

		$this->assertEquals( $original_row, $this->get_customer_record( $customer_id ), 'A guest import must preserve the entire registered lookup row.' );
		$this->assertSame( $customer_id, (int) CustomersDataStore::get_existing_customer_id_from_order( $order ), 'Historical email attribution should remain unchanged.' );
		$this->assertSame( array(), $updated_customers, 'A blocked update must not emit a customer-update notification.' );
	}

	/**
	 * @testdox Should protect registered customer data on repeated guest imports after the billing email changes.
	 */
	public function test_guest_reimport_preserves_registered_customer_data(): void {
		WC_Helper_Reports::reset_stats_dbs();
		$customer = WC_Helper_Customer::create_customer();
		CustomersDataStore::update_registered_customer( $customer->get_id() );
		$customer_id  = CustomersDataStore::get_customer_id_by_user_id( $customer->get_id() );
		$original_row = $this->get_customer_record( $customer_id );

		$order = new WC_Order();
		$order->set_billing_email( $customer->get_email() );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		OrdersStatsDataStore::sync_order( $order->get_id() );

		$order->set_billing_email( 'updated-guest@example.com' );
		$order->set_billing_first_name( 'Updated Guest' );
		$order->set_billing_city( 'Boston' );
		$order->save();
		OrdersScheduler::import( $order->get_id() );
		OrdersScheduler::import( $order->get_id() );

		$this->assertEquals( $original_row, $this->get_customer_record( $customer_id ), 'A stale order statistics association must not authorize a registered customer update.' );
		$this->assertSame( $customer_id, (int) CustomersDataStore::get_existing_customer_id_from_order( $order ), 'The existing order statistics association should remain unchanged.' );
		$this->assertFalse( CustomersDataStore::sync_order_customer( $order->get_id() ), 'A guest order must not report updating a registered lookup row.' );
	}

	/**
	 * @testdox Should update customer data when the order and lookup row have matching user identities.
	 * @testWith [true]
	 *           [false]
	 * @param bool $registered Whether to use a registered customer or a guest.
	 */
	public function test_sync_order_customer_with_matching_identity( bool $registered ): void {
		WC_Helper_Reports::reset_stats_dbs();
		$customer = $registered ? WC_Helper_Customer::create_customer() : null;
		if ( $customer ) {
			CustomersDataStore::update_registered_customer( $customer->get_id() );
		}

		$order = new WC_Order();
		$order->set_customer_id( $customer ? $customer->get_id() : 0 );
		$order->set_billing_email( $customer ? $customer->get_email() : 'guest@example.com' );
		$order->set_billing_city( 'Boston' );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
		OrdersStatsDataStore::sync_order( $order->get_id() );
		$customer_id = (int) CustomersDataStore::get_existing_customer_id_from_order( $order );

		$order->set_billing_city( 'Chicago' );
		$order->save();
		$updated_customers = array();
		add_action(
			'woocommerce_analytics_update_customer',
			static function ( $updated_customer_id ) use ( &$updated_customers ) {
				$updated_customers[] = (int) $updated_customer_id;
			}
		);

		$this->assertTrue( CustomersDataStore::sync_order_customer( $order->get_id() ), 'Matching identities should permit an update.' );
		$row = $this->get_customer_record( $customer_id )[0];
		$this->assertSame( 'Chicago', $row->city, 'The lookup row should receive the latest order location.' );
		$this->assertSame( $customer ? (string) $customer->get_id() : null, $row->user_id, 'Synchronizing must preserve the registered or guest identity.' );
		$this->assertSame( array( $customer_id ), $updated_customers, 'An actual update should emit one customer-update notification.' );
		$this->assertFalse( CustomersDataStore::sync_order_customer( $order->get_id() ), 'An unchanged lookup row should report no update.' );
		$this->assertSame( array( $customer_id ), $updated_customers, 'A no-op synchronization should not emit another notification.' );
	}

	/**
	 * @testdox Removing a guest order preserves a registered lookup row attributed by email.
	 * @testWith ["delete"]
	 *           ["erase"]
	 * @param string $operation Order operation.
	 */
	public function test_guest_removal_preserves_registered_identity( string $operation ): void {
		WC_Helper_Reports::reset_stats_dbs();
		$customer = WC_Helper_Customer::create_customer();
		CustomersDataStore::update_registered_customer( $customer->get_id() );
		$customer_id = CustomersDataStore::get_customer_id_by_user_id( $customer->get_id() );
		$order       = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( $customer->get_email() );
		$order->save();
		OrdersScheduler::import( $order->get_id() );
		$before = $this->get_customer_record( $customer_id );

		if ( 'delete' === $operation ) {
			$order->delete( true );
		} else {
			WC_Privacy_Erasers::remove_order_personal_data( $order );
		}

		$this->assertEquals( $before, $this->get_customer_record( $customer_id ), 'Guest order cleanup must preserve the registered customer profile.' );
	}

	/**
	 * @testdox Erasing an order anonymizes its own guest or registered Analytics identity.
	 * @testWith [false]
	 *           [true]
	 * @param bool $registered Whether the order belongs to a registered customer.
	 */
	public function test_order_erasure_preserves_authorized_anonymization( bool $registered ): void {
		WC_Helper_Reports::reset_stats_dbs();
		$customer = $registered ? WC_Helper_Customer::create_customer() : null;
		$order    = WC_Helper_Order::create_order( $customer ? $customer->get_id() : 0 );
		$order->set_billing_email( $customer ? $customer->get_email() : 'erase-guest@example.com' );
		$order->save();
		OrdersScheduler::import( $order->get_id() );
		$customer_id = CustomersDataStore::get_existing_customer_id_from_order( $order );

		WC_Privacy_Erasers::remove_order_personal_data( $order );

		$row = $this->get_customer_record( $customer_id )[0];
		$this->assertNull( $row->user_id, 'Authorized erasure must remove the Analytics account association.' );
		$this->assertSame( 'deleted@site.invalid', $row->email, 'Authorized erasure must remove the Analytics email.' );
	}

	/**
	 * @testdox Deleting the last guest order removes its guest Analytics row.
	 */
	public function test_last_guest_order_deletion_cleans_lookup(): void {
		WC_Helper_Reports::reset_stats_dbs();
		$order = WC_Helper_Order::create_order( 0 );
		$order->set_billing_email( 'delete-guest@example.com' );
		$order->save();
		OrdersScheduler::import( $order->get_id() );
		$customer_id = CustomersDataStore::get_existing_customer_id_from_order( $order );

		$order->delete( true );

		$this->assertCount( 0, $this->get_customer_record( $customer_id ), 'Unused guest rows should still be removed.' );
	}

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

		$customer_id = DataStore::get_customer_id_by_user_id( $customer->get_id() );
		// This is the customer ID from lookup table.

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
	 * Test registered customer lookup rows survive order deletion.
	 *
	 * Registered profiles remain until account or privacy operations remove them.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::sync_on_order_delete
	 */
	public function test_order_deletion_preserves_registered_customer() {
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

		$customer_id = DataStore::get_customer_id_by_user_id( $customer->get_id() );
		// This is the customer ID from lookup table.

		// Customer should remain in lookup table after first order deleted.
		$order1->delete( true );
		$this->assertCount( 1, $this->get_customer_record( $customer_id ), 'customer remains' );

		// Registered profiles remain available after their last order is deleted.
		$order2->delete( true );
		$this->assertCount( 1, $this->get_customer_record( $customer_id ), 'registered customer remains' );
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
	 * Test that get_or_create_customer_from_order() handles a plain WC_Order
	 * (not the Overrides\Order subclass) without a fatal error.
	 *
	 * This is a regression test for the scenario described in issue #64338,
	 * where get_customer_first_name/get_customer_last_name do not exist on plain WC_Order.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_or_create_customer_from_order
	 */
	public function test_get_or_create_customer_from_plain_wc_order() {
		WC_Helper_Reports::reset_stats_dbs();

		// Build a plain WC_Order directly, bypassing the woocommerce_order_class filter
		// that normally swaps in the Overrides\Order subclass. This is the exact path
		// that produced the production fatal.
		$order = new WC_Order();
		$order->set_billing_first_name( 'Jane' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_email( 'jane.doe.plain@example.com' );
		$order->set_status( 'completed' );
		$order->save();

		$customer_id = \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_or_create_customer_from_order( $order );

		$this->assertNotFalse( $customer_id, 'Should return a customer ID for a plain WC_Order without fataling.' );
		$this->assertIsInt( $customer_id, 'Returned customer ID should be an integer.' );
	}

	/**
	 * Test that get_customer_order_data_and_format() cascades through date_modified
	 * and date_paid when date_created is null, and ultimately falls back to null
	 * rather than stamping an incorrect current timestamp.
	 *
	 * @covers \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_customer_order_data_and_format
	 */
	public function test_date_last_active_falls_back_when_date_created_is_null() {
		$order = $this->createMock( \Automattic\WooCommerce\Admin\Overrides\Order::class );
		$order->method( 'get_id' )->willReturn( 0 );
		$order->method( 'get_date_created' )->willReturn( null );
		$order->method( 'get_date_modified' )->willReturn( null );
		$order->method( 'get_date_paid' )->willReturn( null );
		$order->method( 'get_user_id' )->willReturn( 0 );
		$order->method( 'get_billing_email' )->willReturn( 'test@example.com' );
		$order->method( 'get_billing_first_name' )->willReturn( 'Test' );
		$order->method( 'get_billing_last_name' )->willReturn( 'User' );
		$order->method( 'get_billing_city' )->willReturn( '' );
		$order->method( 'get_billing_state' )->willReturn( '' );
		$order->method( 'get_billing_postcode' )->willReturn( '' );
		$order->method( 'get_billing_country' )->willReturn( '' );
		$order->method( 'get_customer_first_name' )->willReturn( 'Test' );
		$order->method( 'get_customer_last_name' )->willReturn( 'User' );

		list( $data, ) = \Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore::get_customer_order_data_and_format( $order );

		$this->assertNull(
			$data['date_last_active'],
			'date_last_active must be null (not the current timestamp) when all date fields are null.'
		);
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
