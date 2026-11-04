<?php
declare(strict_types=1);

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
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
	 * Create a completed order with a deterministic total and paid date.
	 *
	 * @param int    $customer_id Customer ID.
	 * @param string $date_paid   Paid date in site time.
	 * @param float  $total       Order total.
	 * @return WC_Order
	 */
	private function create_paid_order( $customer_id, $date_paid, $total ) {
		$order = WC_Helper_Order::create_order( $customer_id, null, array( 'status' => OrderStatus::COMPLETED ) );
		$order->set_date_paid( $date_paid );
		$order->set_total( $total );
		$order->save();

		return $order;
	}

	/**
	 * Shared timeframe scenario: three paid orders across three months.
	 *
	 * @param string $cot_enabled Whether custom order tables are enabled ('yes' or 'no').
	 */
	private function assert_total_spent_timeframe( $cot_enabled ) {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $cot_enabled );
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
			$this->create_paid_order( $customer->get_id(), '2024-01-10 12:00:00', 10 );
			$this->create_paid_order( $customer->get_id(), '2024-02-10 12:00:00', 20 );
			$this->create_paid_order( $customer->get_id(), '2024-03-10 12:00:00', 30 );

			$sut = new WC_Customer_Data_Store();

			$this->assertEquals( '60.00', $sut->get_total_spent( $customer ) );

			// DateTime filters, range covering February only.
			$this->assertEquals(
				'20.00',
				$sut->get_total_spent_for_timeframe(
					$customer,
					array(
						'after'  => new DateTimeImmutable( '2024-01-31 23:59:59', wp_timezone() ),
						'before' => new DateTimeImmutable( '2024-03-01 00:00:00', wp_timezone() ),
					)
				)
			);

			// String 'after' filter only.
			$this->assertEquals(
				'30.00',
				$sut->get_total_spent_for_timeframe( $customer, array( 'after' => '2024-02-28 23:59:59' ) )
			);

			// Timestamp 'before' filter only.
			$this->assertEquals(
				'10.00',
				$sut->get_total_spent_for_timeframe(
					$customer,
					array( 'before' => ( new DateTimeImmutable( '2024-02-01 00:00:00', wp_timezone() ) )->getTimestamp() )
				)
			);

			// The timeframe value filter receives the calculated total and the args.
			$filtered_spent = null;
			$filtered_args  = null;
			$filter         = static function ( $spent, $filtered_customer, $args ) use ( &$filtered_spent, &$filtered_args ) {
				$filtered_spent = $spent;
				$filtered_args  = $args;
				return $spent;
			};
			add_filter( 'woocommerce_customer_get_total_spent_for_timeframe', $filter, 10, 3 );

			try {
				$this->assertEquals(
					'20.00',
					wc_get_customer_total_spent(
						$customer->get_id(),
						array(
							'after'  => '2024-01-31 23:59:59',
							'before' => '2024-03-01 00:00:00',
						)
					)
				);
			} finally {
				remove_filter( 'woocommerce_customer_get_total_spent_for_timeframe', $filter, 10 );
			}

			$this->assertEquals( 20.0, (float) $filtered_spent );
			$this->assertEquals(
				array(
					'after'  => '2024-01-31 23:59:59',
					'before' => '2024-03-01 00:00:00',
				),
				$filtered_args
			);

			// Timeframe queries must not disturb the all-time total.
			$this->assertEquals( '60.00', $sut->get_total_spent( $customer ) );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' filters by paid date when the posts table is used for storing orders.
	 */
	public function test_get_total_spent_with_timeframe_not_using_cot() {
		$this->assert_total_spent_timeframe( 'no' );
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' filters by paid date when the custom orders table is used for storing orders.
	 */
	public function test_get_total_spent_with_timeframe_using_cot() {
		$this->assert_total_spent_timeframe( 'yes' );
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' treats the before and after bounds as exclusive in both storages.
	 */
	public function test_get_total_spent_timeframe_bounds_are_exclusive() {
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			foreach ( array( 'no', 'yes' ) as $cot_enabled ) {
				update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $cot_enabled );

				$customer = WC_Helper_Customer::create_customer( 'bounds' . $cot_enabled, 'pass', 'bounds' . $cot_enabled . '@example.com' );
				$this->create_paid_order( $customer->get_id(), '2024-02-01 00:00:00', 10 ); // Paid exactly at the 'after' bound.
				$this->create_paid_order( $customer->get_id(), '2024-02-15 12:00:00', 20 ); // Paid inside the window.
				$this->create_paid_order( $customer->get_id(), '2024-03-01 00:00:00', 40 ); // Paid exactly at the 'before' bound.

				$sut = new WC_Customer_Data_Store();
				$this->assertEquals(
					'20.00',
					$sut->get_total_spent_for_timeframe(
						$customer,
						array(
							'after'  => '2024-02-01 00:00:00',
							'before' => '2024-03-01 00:00:00',
						)
					),
					"Orders paid exactly on a bound must be excluded (COT: $cot_enabled)."
				);

				$customer->delete( true );
			}
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' only counts orders in paid statuses, in both storages.
	 */
	public function test_get_total_spent_timeframe_excludes_unpaid_statuses() {
		foreach ( array( 'no', 'yes' ) as $cot_enabled ) {
			update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $cot_enabled );

			$customer = WC_Helper_Customer::create_customer( 'unpaid' . $cot_enabled, 'pass', 'unpaid' . $cot_enabled . '@example.com' );
			$this->create_paid_order( $customer->get_id(), '2024-02-10 12:00:00', 20 );

			$refunded = WC_Helper_Order::create_order( $customer->get_id(), null, array( 'status' => OrderStatus::REFUNDED ) );
			$refunded->set_date_paid( '2024-02-10 12:00:00' );
			$refunded->set_total( 50 );
			$refunded->save();

			$pending = WC_Helper_Order::create_order( $customer->get_id(), null, array( 'status' => OrderStatus::PENDING ) );
			$pending->set_date_paid( '2024-02-10 12:00:00' );
			$pending->set_total( 70 );
			$pending->save();

			$sut = new WC_Customer_Data_Store();
			$this->assertEquals(
				'20.00',
				$sut->get_total_spent_for_timeframe(
					$customer,
					array(
						'after'  => '2024-02-01 00:00:00',
						'before' => '2024-03-01 00:00:00',
					)
				),
				"Refunded and pending orders must not count even with a paid date in range (COT: $cot_enabled)."
			);

			$customer->delete( true );
		}
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' derives its statuses from wc_get_is_paid_statuses().
	 */
	public function test_get_total_spent_timeframe_uses_paid_statuses_filter() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$this->create_paid_order( $customer->get_id(), '2024-02-10 12:00:00', 20 );

		$pending = WC_Helper_Order::create_order( $customer->get_id(), null, array( 'status' => OrderStatus::PENDING ) );
		$pending->set_date_paid( '2024-02-10 12:00:00' );
		$pending->set_total( 70 );
		$pending->save();

		$timeframe = array(
			'after'  => '2024-02-01 00:00:00',
			'before' => '2024-03-01 00:00:00',
		);
		$sut       = new WC_Customer_Data_Store();
		$this->assertEquals( '20.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ) );

		$paid_statuses_filter = static function () {
			return array( OrderStatus::PENDING );
		};
		add_filter( 'woocommerce_order_is_paid_statuses', $paid_statuses_filter );

		try {
			$this->assertEquals( '70.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ) );
		} finally {
			remove_filter( 'woocommerce_order_is_paid_statuses', $paid_statuses_filter );
		}
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' does not fire the all-time total spent hooks.
	 */
	public function test_get_total_spent_timeframe_hooks_are_isolated() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$this->create_paid_order( $customer->get_id(), '2024-02-10 12:00:00', 20 );

		$all_time_value_calls   = 0;
		$all_time_query_calls   = 0;
		$timeframe_query_calls  = 0;
		$all_time_value_filter  = static function ( $spent ) use ( &$all_time_value_calls ) {
			++$all_time_value_calls;
			return $spent;
		};
		$all_time_query_filter  = static function ( $sql ) use ( &$all_time_query_calls ) {
			++$all_time_query_calls;
			return $sql;
		};
		$timeframe_query_filter = static function ( $sql ) use ( &$timeframe_query_calls ) {
			++$timeframe_query_calls;
			return $sql;
		};
		add_filter( 'woocommerce_customer_get_total_spent', $all_time_value_filter );
		add_filter( 'woocommerce_customer_get_total_spent_query', $all_time_query_filter );
		add_filter( 'woocommerce_customer_get_total_spent_for_timeframe_query', $timeframe_query_filter );

		try {
			$sut = new WC_Customer_Data_Store();
			$this->assertEquals(
				'20.00',
				$sut->get_total_spent_for_timeframe(
					$customer,
					array(
						'after'  => '2024-02-01 00:00:00',
						'before' => '2024-03-01 00:00:00',
					)
				)
			);
		} finally {
			remove_filter( 'woocommerce_customer_get_total_spent', $all_time_value_filter );
			remove_filter( 'woocommerce_customer_get_total_spent_query', $all_time_query_filter );
			remove_filter( 'woocommerce_customer_get_total_spent_for_timeframe_query', $timeframe_query_filter );
		}

		$this->assertSame( 0, $all_time_value_calls, 'The all-time value filter must not run for timeframe totals.' );
		$this->assertSame( 0, $all_time_query_calls, 'The all-time query filter must not run for timeframe totals.' );
		$this->assertSame( 1, $timeframe_query_calls, 'The timeframe query filter must run exactly once.' );
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' ignores and preserves the all-time cache, and empty args delegate to the all-time total.
	 */
	public function test_get_total_spent_for_timeframe_ignores_all_time_cache() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$this->create_paid_order( $customer->get_id(), '2024-02-10 12:00:00', 20 );

		Users::update_site_user_meta( $customer->get_id(), 'wc_money_spent', '999.99' );

		$sut = new WC_Customer_Data_Store();
		$this->assertEquals( '999.99', $sut->get_total_spent( $customer ), 'The all-time total reads the cache as before.' );
		$this->assertEquals(
			'20.00',
			$sut->get_total_spent_for_timeframe(
				$customer,
				array(
					'after'  => '2024-02-01 00:00:00',
					'before' => '2024-03-01 00:00:00',
				)
			),
			'Timeframe totals must be calculated from orders, not from the all-time cache.'
		);
		$this->assertEquals(
			'999.99',
			Users::get_site_user_meta( $customer->get_id(), 'wc_money_spent', true ),
			'Timeframe totals must not overwrite the all-time cache.'
		);
		$this->assertEquals(
			'999.99',
			$sut->get_total_spent_for_timeframe( $customer, array() ),
			'Empty timeframe args must delegate to the all-time total.'
		);
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' includes orders that only have the legacy paid-date meta with posts storage.
	 */
	public function test_get_total_spent_timeframe_includes_legacy_paid_date_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
			$order    = WC_Helper_Order::create_order( $customer->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
			$order->set_total( 20 );
			$order->save();
			delete_post_meta( $order->get_id(), '_date_paid' );
			update_post_meta( $order->get_id(), '_paid_date', '2024-02-10 12:00:00' );

			$sut       = new WC_Customer_Data_Store();
			$timeframe = array(
				'after'  => '2024-02-10 11:59:59',
				'before' => '2024-02-10 12:00:01',
			);
			$this->assertSame( '20.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ) );

			add_post_meta( $order->get_id(), '_paid_date', '2024-03-10 12:00:00' );
			$this->assertSame( '20.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ), 'The first legacy meta value should win without multiplying the order total.' );

			update_post_meta( $order->get_id(), '_date_paid', ( new DateTimeImmutable( '2024-02-10 12:00:00', wp_timezone() ) )->getTimestamp() );
			$this->assertSame( '20.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ), 'The canonical paid date should count the order only once.' );

			update_post_meta( $order->get_id(), '_date_paid', ( new DateTimeImmutable( '2024-03-10 12:00:00', wp_timezone() ) )->getTimestamp() );
			$this->assertSame( '0.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ), 'The canonical paid date should take precedence over the legacy value.' );

			delete_post_meta( $order->get_id(), '_date_paid' );
			delete_post_meta( $order->get_id(), '_paid_date' );
			add_post_meta( $order->get_id(), '_paid_date', '' );
			$this->assertSame( '0.00', $sut->get_total_spent_for_timeframe( $customer, array( 'before' => '2024-03-01 00:00:00' ) ), 'An empty legacy paid date should not satisfy a before-only filter.' );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' ignores the legacy paid-date meta with HPOS storage, which the HPOS order store never reads.
	 */
	public function test_get_total_spent_timeframe_ignores_migrated_legacy_paid_date_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		$original_timezone = get_option( 'timezone_string' );
		update_option( 'timezone_string', 'America/New_York' );

		try {
			$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
			$order    = WC_Helper_Order::create_order( $customer->get_id(), null, array( 'status' => OrderStatus::COMPLETED ) );
			$order->set_total( 20 );
			$order->save();
			delete_post_meta( $order->get_id(), '_date_paid' );
			update_post_meta( $order->get_id(), '_paid_date', '2024-02-10 12:00:00' );

			$migrator = wc_get_container()->get( PostsToOrdersMigrationController::class );
			$migrator->migrate_orders( array( $order->get_id() ) );
			update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

			global $wpdb;
			$meta_table         = OrdersTableDataStore::get_meta_table_name();
			$migrated_paid_date = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$meta_table} WHERE order_id = %d AND meta_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a fixed internal identifier from OrdersTableDataStore.
					$order->get_id(),
					'_paid_date'
				)
			);
			$this->assertSame( '2024-02-10 12:00:00', $migrated_paid_date, 'The fixture order must carry its paid date only in the migrated legacy meta.' );

			$sut       = new WC_Customer_Data_Store();
			$timeframe = array(
				'after'  => '2024-02-10 11:59:59',
				'before' => '2024-02-10 12:00:01',
			);
			$this->assertSame( '0.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ), 'The legacy paid-date meta must be ignored in HPOS, like the HPOS order store ignores it.' );

			$wpdb->update(
				OrdersTableDataStore::get_operational_data_table_name(),
				array( 'date_paid_gmt' => '2024-02-10 17:00:00' ),
				array( 'order_id' => $order->get_id() ),
				array( '%s' ),
				array( '%d' )
			);
			$this->assertSame( '20.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ), 'The canonical paid date should count the order once it is set.' );

			$wpdb->update(
				OrdersTableDataStore::get_operational_data_table_name(),
				array( 'date_paid_gmt' => '2024-03-10 17:00:00' ),
				array( 'order_id' => $order->get_id() ),
				array( '%s' ),
				array( '%d' )
			);
			$this->assertSame( '0.00', $sut->get_total_spent_for_timeframe( $customer, $timeframe ), 'A canonical paid date outside the timeframe should exclude the order.' );

			$wpdb->update(
				OrdersTableDataStore::get_operational_data_table_name(),
				array( 'date_paid_gmt' => null ),
				array( 'order_id' => $order->get_id() ),
				array( '%s' ),
				array( '%d' )
			);
			$this->assertSame( '0.00', $sut->get_total_spent_for_timeframe( $customer, array( 'before' => '2024-03-01 00:00:00' ) ), 'Without a canonical paid date, the legacy meta alone must not satisfy a before-only filter in HPOS.' );
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}

	/**
	 * @testdox 'get_total_spent_for_timeframe' rejects invalid paid-date filters.
	 */
	public function test_get_total_spent_rejects_invalid_timeframe_date() {
		$customer = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$sut      = new WC_Customer_Data_Store();

		$this->expectException( InvalidArgumentException::class );
		$sut->get_total_spent_for_timeframe( $customer, array( 'after' => 'not-a-date' ) );
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
