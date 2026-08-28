<?php
declare(strict_types=1);

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Utilities\Users;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class WC_Customer_Data_Store_CPT_Test.
 */
class WC_Customer_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * Runs before all tests in the class.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		OrderHelper::delete_order_custom_tables();
		OrderHelper::create_order_custom_table_if_not_exist();
	}

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		parent::tearDown();
	}

	/**
	 * Test that metadata cannot overwrite customer's column data.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/28100
	 */
	public function test_meta_data_cannot_overwrite_column_data() {
		$customer    = WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$username    = $customer->get_username();
		$customer->add_meta_data( 'id', '99999' );
		$customer->add_meta_data( 'username', 'abcde' );
		$customer->save();

		$customer_datastore = new WC_Customer_Data_Store();
		$customer_datastore->read( $customer );
		$this->assertEquals( $customer_id, $customer->get_id() );
		$this->assertEquals( $username, $customer->get_username() );
	}

	/**
	 * @testdox WordPress personal preferences are excluded from customer meta data.
	 */
	public function test_wordpress_personal_preferences_are_excluded_from_customer_meta_data(): void {
		$customer = WC_Helper_Customer::create_customer();

		update_user_meta( $customer->get_id(), 'infinite_scrolling', 'true' );
		update_user_meta( $customer->get_id(), 'custom_preference', 'custom-value' );

		$read_customer = new WC_Customer( $customer->get_id() );
		$meta_keys     = wp_list_pluck( $read_customer->get_meta_data(), 'key' );

		$this->assertNotContains( 'infinite_scrolling', $meta_keys, 'WordPress personal preferences should not be exposed as customer meta data.' );
		$this->assertContains( 'custom_preference', $meta_keys, 'Custom user meta should remain available as customer meta data.' );
	}

	/**
	 * @testdox A backslash in a customer address field survives a save/read round-trip.
	 *
	 * Addresses entered through the Store API (block checkout) are not magic-quoted, so the value
	 * reaches the customer object unslashed (e.g. "apt 4\"). When the data store persists it via
	 * WP's update_user_meta(), update_metadata() runs wp_unslash() on the value before writing,
	 * stripping the backslash. The customer fix in PR #65643 only stops the Store API schema from
	 * unslashing; the meta-persistence layer still corrupts the value for logged-in users.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/58214
	 * @link https://github.com/woocommerce/woocommerce/pull/65643#pullrequestreview-4485832478
	 */
	public function test_backslash_in_address_survives_save_and_read(): void {
		$customer = WC_Helper_Customer::create_customer();

		$customer->set_billing_address_2( 'apt 4\\' );
		$customer->save();

		$read_customer = new WC_Customer( $customer->get_id() );

		$this->assertSame(
			'apt 4\\',
			$read_customer->get_billing_address_2(),
			'The trailing backslash should be preserved when the customer address is persisted and read back.'
		);
	}

	/**
	 * Handler for the wc_order_statuses filter, returns just 'pending" as the valid order statuses list.
	 *
	 * @return string[]
	 */
	public function get_pending_only_as_order_statuses() {
		return array( OrderInternalStatus::PENDING => OrderStatus::PENDING );
	}

	/**
	 * @testdox 'get_last_order' works when the posts table is used for storing orders.
	 */
	public function test_get_last_customer_order_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		$last_valid_order_of_1 = WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
		WC_Helper_Order::create_order( $customer_2->get_id() );
		WC_Helper_Order::create_order( $customer_2->get_id() );

		$sut = new WC_Customer_Data_Store();
		add_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10, 0 );
		$actual_order = $sut->get_last_order( $customer_1 );
		remove_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10 );

		$this->assertEquals( $last_valid_order_of_1->get_id(), $actual_order->get_id() );
	}

	/**
	 * @testdox 'get_last_order' works when the custom orders table is used for storing orders.
	 */
	public function test_get_last_customer_order_using_cot() {
		global $wpdb;

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$orders_table = OrdersTableDataStore::get_orders_table_name();

		// Derive the base ID from the orders table itself so the raw rows never collide with a row that
		// already exists there (under HPOS, order creation writes into this table). Using MAX(id) keeps
		// the test collision-safe whether or not HPOS is enabled, and inserting every order directly
		// into the orders table keeps it independent of the storage mode.
		$base_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE( MAX( id ), 0 ) FROM %i', $orders_table ) );

		$customer_1_id = $customer_1->get_id();
		$customer_2_id = $customer_2->get_id();

		// Customer 1 has two completed orders plus a higher-ID invalid order; get_last_order() must
		// skip the invalid one and return the most recent completed order ($last_valid_order_id).
		// Customer 2's orders have even higher IDs, so they must be excluded by customer scoping
		// rather than by ID ordering.
		$last_valid_order_id = $base_id + 2;

		$sql =
			'INSERT INTO %i' . "
				( id, customer_id, status, type )
			VALUES
				( %d, %d, '" . OrderInternalStatus::COMPLETED . "', 'shop_order' ),
				( %d, %d, '" . OrderInternalStatus::COMPLETED . "', 'shop_order' ),
				( %d, %d, 'wc-invalid-status', 'shop_order' ),
				( %d, %d, '" . OrderInternalStatus::COMPLETED . "', 'shop_order' ),
				( %d, %d, '" . OrderInternalStatus::COMPLETED . "', 'shop_order' )";

		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare(
			$sql,
			$orders_table,
			$base_id + 1,
			$customer_1_id,
			$last_valid_order_id,
			$customer_1_id,
			$base_id + 3,
			$customer_1_id,
			$base_id + 4,
			$customer_2_id,
			$base_id + 5,
			$customer_2_id
		);
		$this->assertSame( 5, $wpdb->query( $query ), 'All custom order table fixtures should be inserted.' );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$sut          = new WC_Customer_Data_Store();
		$actual_order = $sut->get_last_order( $customer_1 );

		$this->assertEquals( $last_valid_order_id, $actual_order->get_id() );
	}

	/**
	 * Even if the stored meta data is incorrect/corrupted in some fashion, it should not generally be possible to fetch
	 * an order belonging to another user via the `get_last_customer_order` method.
	 *
	 * @return void
	 */
	public function test_get_last_customer_order_safety(): void {
		$customer_a = CustomerHelper::create_customer( 'bill', 'basket', 'bill@buy.good' );
		$customer_b = CustomerHelper::create_customer( 'ben', 'bumper', 'ben@boutique.shopper' );
		$order_a    = OrderHelper::create_order( $customer_a->get_id() );
		$order_b    = OrderHelper::create_order( $customer_b->get_id() );
		$sut        = new WC_Customer_Data_Store();

		$this->assertEquals(
			$order_a->get_id(),
			$sut->get_last_order( $customer_a )->get_id(),
			'Last customer order fetched as expected.'
		);

		// Simulate a situation where a rogue plugin changes the cached last order information for Customer A, so it
		// instead references an order placed by Customer B.
		Users::update_site_user_meta(
			$customer_a->get_id(),
			'wc_last_order',
			$order_b->get_id()
		);

		$this->assertEquals(
			$order_a->get_id(),
			$sut->get_last_order( $customer_a )->get_id(),
			'Safeguards prevent fetching an order placed by another customer (it "self-corrects" and returns the actual last order).'
		);
	}

	/**
	 * @testdox 'get_order_count' works when the posts table is used for storing orders.
	 */
	public function test_order_count_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
		WC_Helper_Order::create_order( $customer_2->get_id() );
		WC_Helper_Order::create_order( $customer_2->get_id() );

		$sut = new WC_Customer_Data_Store();
		add_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10, 0 );
		$actual_count = $sut->get_order_count( $customer_1 );
		remove_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10 );

		$this->assertEquals( 3, $actual_count );
	}

	/**
	 * @testdox 'get_order_count' works when the custom orders table is used for storing orders.
	 */
	public function test_get_order_count_using_cot() {
		global $wpdb;

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );

		$sql =
			'INSERT INTO ' . OrdersTableDataStore::get_orders_table_name() . "
				( id, customer_id, status )
			VALUES
				( 1, %d, '" . OrderInternalStatus::COMPLETED . "' ),
				( 2, %d, '" . OrderInternalStatus::COMPLETED . "' ),
				( 3, %d, '" . OrderInternalStatus::COMPLETED . "' ),
				( 4, %d, 'wc-invalid-status' ),
				( 5, %d, '" . OrderInternalStatus::COMPLETED . "' ),
				( 6, %d, '" . OrderInternalStatus::COMPLETED . "' )";

		$customer_1_id = $customer_1->get_id();
		$customer_2_id = $customer_2->get_id();
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, $customer_1_id, $customer_1_id, $customer_1_id, $customer_1_id, $customer_2_id, $customer_2_id );
		$wpdb->query( $query );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$sut          = new WC_Customer_Data_Store();
		$actual_count = $sut->get_order_count( $customer_1 );

		$this->assertEquals( 3, $actual_count );
	}

	/**
	 * @testdox 'get_total_spent' works when the posts table is used for storing orders.
	 */
	public function test_get_total_spent_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => OrderStatus::PENDING ) );
		WC_Helper_Order::create_order( $customer_2->get_id() );
		WC_Helper_Order::create_order( $customer_2->get_id() );

		$sut = new WC_Customer_Data_Store();
		add_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10, 0 );
		$actual_amount = $sut->get_total_spent( $customer_1 );
		remove_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10 );

		// Each order created by WC_Helper_Order::create_order has a total amount of 50.
		$this->assertEquals( '150.00', $actual_amount );
	}

	/**
	 * @testdox 'get_total_spent' works when the custom orders table is used for storing orders.
	 */
	public function test_get_total_spent_using_cot() {
		global $wpdb;

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );

		$sql =
			'INSERT INTO ' . OrdersTableDataStore::get_orders_table_name() . "
				( id, customer_id, status, total_amount )
			VALUES
				( 1, %d, '" . OrderInternalStatus::COMPLETED . "', 10 ),
				( 2, %d, '" . OrderInternalStatus::COMPLETED . "', 20 ),
				( 3, %d, '" . OrderInternalStatus::COMPLETED . "', 30 ),
				( 4, %d, 'wc-invalid-status', 40 ),
				( 5, %d, '" . OrderInternalStatus::COMPLETED . "', 200 ),
				( 6, %d, '" . OrderInternalStatus::COMPLETED . "', 300 )";

		$customer_1_id = $customer_1->get_id();
		$customer_2_id = $customer_2->get_id();
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, $customer_1_id, $customer_1_id, $customer_1_id, $customer_1_id, $customer_2_id, $customer_2_id );
		$wpdb->query( $query );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$sut          = new WC_Customer_Data_Store();
		$actual_spent = $sut->get_total_spent( $customer_1 );

		$this->assertEquals( '60.00', $actual_spent );
	}
}
