<?php

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrdersTableDataStoreTests.
 *
 * Test for OrdersTableDataStore class.
 */
class OrdersTableDataStoreTests extends HposTestCase {
	use HPOSToggleTrait;

	/**
	 * Original timezone before this test started.
	 * @var string
	 */
	private $original_time_zone;

	/**
	 * @var PostsToOrdersMigrationController
	 */
	private $migrator;

	/**
	 * @var OrdersTableDataStore
	 */
	private $sut;

	/**
	 * @var WC_Order_Data_Store_CPT
	 */
	private $cpt_data_store;

	/**
	 * Whether COT was enabled before the test.
	 * @var bool
	 */
	private $cot_state;

	/**
	 * Temporarily store webhook delivery counters.
	 * @var array
	 */
	private $delivery_counter = array();

	/**
	 * Initializes system under test.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		$this->reset_legacy_proxy_mocks();
		$this->original_time_zone = wp_timezone_string();
		//phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- We need to change the timezone to test the date sync fields.
		update_option( 'timezone_string', 'Asia/Kolkata' );
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		$this->setup_cot();
		$this->cot_state = OrderUtil::custom_orders_table_usage_is_enabled();
		$this->toggle_cot_feature_and_usage( false );
		$container = wc_get_container();
		$container->reset_all_resolved();
		$this->sut            = wc_get_container()->get( OrdersTableDataStore::class );
		$this->migrator       = wc_get_container()->get( PostsToOrdersMigrationController::class );
		$this->cpt_data_store = new WC_Order_Data_Store_CPT();
	}

	/**
	 * Destroys system under test.
	 */
	public function tearDown(): void {
		global $wpdb;
		//phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- We need to change the timezone to test the date sync fields.
		update_option( 'timezone_string', $this->original_time_zone );
		$this->toggle_cot_feature_and_usage( $this->cot_state );
		$this->clean_up_cot_setup();

		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );

		parent::tearDown();
	}

	/**
	 * @testDox Test reading from migrated post order.
	 */
	public function test_read_from_migrated_order() {
		$post_order_id = OrderHelper::create_complex_wp_post_order();
		$this->migrator->migrate_orders( array( $post_order_id ) );

		wp_cache_flush();
		$cot_order = new WC_Order();
		$cot_order->set_id( $post_order_id );
		$this->switch_data_store( $cot_order, $this->sut );
		$this->sut->read( $cot_order );

		wp_cache_flush();
		$post_order = new WC_Order();
		$post_order->set_id( $post_order_id );
		$this->switch_data_store( $post_order, $this->cpt_data_store );
		$this->cpt_data_store->read( $post_order );

		$this->assertEquals( $post_order->get_base_data(), $cot_order->get_base_data() );
		$post_order_meta_keys = wp_list_pluck( $post_order->get_meta_data(), 'key' );
		foreach ( $post_order_meta_keys as $meta_key ) {
			$this->assertEquals( $post_order->get_meta( $meta_key ), $cot_order->get_meta( $meta_key ) );
		}
	}

	/**
	 * @testDox Test whether backfill_post_record works as expected.
	 */
	public function test_backfill_post_record() {
		$post_order_id = OrderHelper::create_complex_wp_post_order();
		$this->disable_cot_sync();
		$this->migrator->migrate_orders( array( $post_order_id ) );

		$post_data             = get_post( $post_order_id, ARRAY_A );
		$post_meta_data        = get_post_meta( $post_order_id );
		$exempted_keys         = array( 'post_modified', 'post_modified_gmt' );
		$convert_to_float_keys = array(
			'_cart_discount_tax',
			'_order_shipping',
			'_order_shipping_tax',
			'_order_tax',
			'_cart_discount',
			'cart_tax',
		);

		$convert_to_bool_keys = array(
			'_order_stock_reduced',
			'_download_permissions_granted',
			'_new_order_email_sent',
			'_recorded_sales',
			'_recorded_coupon_usage_counts',
		);

		$exempted_keys        = array_flip( array_merge( $exempted_keys, $convert_to_float_keys, $convert_to_bool_keys ) );
		$post_data_float      = array_intersect_key( $post_data, array_flip( $convert_to_float_keys ) );
		$post_meta_data_float = array_intersect_key( $post_meta_data, array_flip( $convert_to_float_keys ) );
		$post_meta_data_bool  = array_intersect_key( $post_meta_data, array_flip( $convert_to_bool_keys ) );
		$post_data            = array_diff_key( $post_data, $exempted_keys );
		$post_meta_data       = array_diff_key( $post_meta_data, $exempted_keys );

		// Let's update post data.
		wp_update_post(
			array(
				'ID'            => $post_order_id,
				'post_status'   => 'migration_pending',
				'post_type'     => DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE,
				'ping_status'   => 'closed',
				'post_parent'   => 0,
				'menu_order'    => 0,
				'post_date'     => '',
				'post_date_gmt' => '',
			)
		);
		$this->delete_all_meta_for_post( $post_order_id );

		$this->assertEquals( 'migration_pending', get_post_status( $post_order_id ) ); // assert post was updated.
		$this->assertEquals( array(), get_post_meta( $post_order_id ) ); // assert postmeta was deleted.

		$cot_order = new WC_Order();
		$cot_order->set_id( $post_order_id );
		$this->switch_data_store( $cot_order, $this->sut );
		$this->sut->read( $cot_order );
		$this->sut->backfill_post_record( $cot_order );

		$this->assertEquals( $post_data, array_diff_key( get_post( $post_order_id, ARRAY_A ), $exempted_keys ) );
		$this->assertEquals( $post_meta_data, array_diff_key( get_post_meta( $post_order_id ), $exempted_keys ) );

		foreach ( $post_data_float as $float_key => $value ) {
			$this->assertEquals( (float) get_post( $post_order_id, ARRAY_A )[ $float_key ], (float) $value, "Value for $float_key does not match." );
		}

		foreach ( $post_meta_data_float as $float_key => $value ) {
			$this->assertEquals( (float) get_post_meta( $post_order_id )[ $float_key ], (float) $value, "Value for $float_key does not match." );
		}

		foreach ( $post_meta_data_bool as $bool_key => $value ) {
			$this->assertEquals( wc_string_to_bool( get_post_meta( $post_order_id, $bool_key, true ) ), wc_string_to_bool( current( $value ) ), "Value for $bool_key does not match." );
		}
	}

	/**
	 * @testDox Test that modified date is backfilled correctly when syncing order.
	 */
	public function test_backfill_updated_date() {
		$order                   = $this->create_complex_cot_order();
		$hardcoded_modified_date = time() - 100;
		$order->set_date_modified( $hardcoded_modified_date );
		$order->save();

		$this->sut->backfill_post_record( $order );
		$this->assertEquals( $hardcoded_modified_date, get_post_modified_time( 'U', true, $order->get_id() ) );
	}

	/**
	 * @testDox Tests that array metadata is handled properly when backfilling posts.
	 */
	public function test_backfill_array_meta() {
		$tricky_meta = array(
			'an_array'                        => array( 'because', 'why', 'not' ),
			'something_that_looks_serialized' => 'a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}',
		);

		$this->disable_cot_sync();

		$order = new \WC_Order();
		$this->switch_data_store( $order, $this->sut );
		foreach ( $tricky_meta as $meta_key => $meta_value ) {
			$order->add_meta_data( $meta_key, $meta_value );
		}
		$order->save();

		$this->sut->backfill_post_record( $order );

		$post_meta = get_post_meta( $order->get_id() );

		foreach ( $tricky_meta as $meta_key => $meta_value ) {
			$this->assertArrayHasKey( $meta_key, $post_meta );
			$this->assertEquals( $meta_value, maybe_unserialize( $post_meta[ $meta_key ][0] ) );
		}
	}

	/**
	 * @testDox Tests update() on the COT datastore.
	 */
	public function test_cot_datastore_update() {
		static $props_to_update = array(
			'billing_first_name' => 'John',
			'billing_last_name'  => 'Doe',
			'shipping_phone'     => '555-55-55',
			'status'             => 'on-hold',
			'cart_hash'          => 'YET-ANOTHER-CART-HASH',
		);

		static $datastore_updates = array(
			'email_sent'          => true,
			'order_stock_reduced' => true,
		);

		static $meta_to_update = array(
			'my_meta_key' => array( 'my', 'custom', 'meta' ),
		);

		// Set up order.
		$post_order = OrderHelper::create_order();
		$this->migrator->migrate_orders( array( $post_order->get_id() ) );

		// Read order using the COT datastore.
		wp_cache_flush();
		$order = new WC_Order();
		$order->set_id( $post_order->get_id() );
		$this->toggle_cot_feature_and_usage( true );
		$this->switch_data_store( $order, $this->sut );
		$this->sut->read( $order );

		// Make some changes to the order and save.
		$order->set_props( $props_to_update );

		foreach ( $meta_to_update as $meta_key => $meta_value ) {
			$order->add_meta_data( $meta_key, $meta_value, true );
		}

		foreach ( $datastore_updates as $prop => $value ) {
			$this->sut->{"set_$prop"}( $order, $value );
		}

		$order->save();

		// Re-read order and make sure changes were persisted.
		wp_cache_flush();
		$order = new WC_Order();
		$order->set_id( $post_order->get_id() );
		$this->switch_data_store( $order, $this->sut );
		$this->sut->read( $order );

		foreach ( $props_to_update as $prop => $value ) {
			$this->assertEquals( $order->{"get_$prop"}( 'edit' ), $value );
		}

		foreach ( $meta_to_update as $meta_key => $meta_value ) {
			$this->assertEquals( $order->get_meta( $meta_key, true, 'edit' ), $meta_value );
		}

		foreach ( $datastore_updates as $prop => $value ) {
			$this->assertEquals( $value, $this->sut->{"get_$prop"}( $order ), "Unable to match prop $prop" );
		}
		$this->toggle_cot_feature_and_usage( false );
	}

	/**
	 * @testDox Test update when row in one of the associated tables is missing.
	 */
	public function test_cot_datastore_update_when_incomplete_record() {
		global $wpdb;
		static $props_to_update = array(
			'billing_first_name' => 'John',
			'billing_last_name'  => 'Doe',
			'shipping_phone'     => '555-55-55',
			'status'             => 'on-hold',
			'cart_hash'          => 'YET-ANOTHER-CART-HASH',
		);

		// Set up order.
		$post_order = OrderHelper::create_order();
		$this->migrator->migrate_orders( array( $post_order->get_id() ) );

		// Read order using the COT datastore.
		wp_cache_flush();
		$order = new WC_Order();
		$order->set_id( $post_order->get_id() );
		$this->switch_data_store( $order, $this->sut );
		$this->sut->read( $order );

		// Make some changes to the order and save.
		$order->set_props( $props_to_update );

		// Let's delete a row from one of the table.
		$wpdb->delete( $this->sut::get_addresses_table_name(), array( 'order_id' => $order->get_id() ), array( '%d' ) );

		// Try to update as if nothing happened.
		// Make some changes to the order and save.
		$order->set_props( $props_to_update );

		$order->save();
		// Re-read order and make sure changes were persisted.
		wp_cache_flush();
		$order = new WC_Order();
		$order->set_id( $post_order->get_id() );
		$this->switch_data_store( $order, $this->sut );
		$this->sut->read( $order );

		foreach ( $props_to_update as $prop => $value ) {
			$this->assertEquals( $order->{"get_$prop"}( 'edit' ), $value );
		}

	}

	/**
	 * @testDox Tests create() on the COT datastore.
	 */
	public function test_cot_datastore_create() {
		$order    = $this->create_complex_cot_order();
		$order_id = $order->get_id();

		$this->assertIsInteger( $order_id );
		$this->assertLessThan( $order_id, 0 );

		wp_cache_flush();

		// Read the order again (fresh).
		$r_order = new WC_Order();
		$r_order->set_id( $order_id );
		$this->switch_data_store( $r_order, $this->sut );
		$this->sut->read( $r_order );

		// Compare some of the prop/meta values to those that should've been persisted.
		$props_to_compare = array(
			'status',
			'created_via',
			'currency',
			'customer_ip_address',
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_city',
			'billing_state',
			'billing_postcode',
			'billing_country',
			'billing_email',
			'billing_phone',
			'shipping_total',
			'total',
			'order_stock_reduced',
			'download_permissions_granted',
			'recorded_sales',
			'recorded_coupon_usage_counts',
		);

		foreach ( $props_to_compare as $prop ) {
			$this->assertEquals( $order->{"get_$prop"}( 'edit' ), $r_order->{"get_$prop"}( 'edit' ) );
		}

		$this->assertEquals( $order->get_meta( 'my_meta', true, 'edit' ), $r_order->get_meta( 'my_meta', true, 'edit' ) );
		$this->assertEquals( $this->sut->get_stock_reduced( $order ), $this->sut->get_stock_reduced( $r_order ) );
	}

	/**
	 * Confirm we store the order creation date in GMT.
	 */
	public function test_order_dates_are_gmt(): void {
		global $wpdb;

		// Switch to the COT datastore, set WordPress to use a non-UTC timezone, and create a new order.
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		update_option( 'timezone_string', 'America/Los_Angeles' );

		$order = new WC_Order();
		$this->sut->create( $order );
		$date_created_gmt = $wpdb->get_var(
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'SELECT date_created_gmt FROM ' . OrdersTableDataStore::get_orders_table_name() . ' WHERE id = ' . $order->get_id()
		);

		$this->assertNotEquals(
			$date_created_gmt,
			$order->get_date_created()->format( 'Y-m-d H:i:s' ),
			'The creation date in the database should be in GMT, but the retrieved datetime should be in the local WP timezone.'
		);

		$this->assertEquals(
			$date_created_gmt,
			$order->get_date_created()->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
			'The order creation datetime, when cast to UTC/GMT, should match the same value stored in the database.'
		);
	}

	/**
	 * @testDox Even corrupted order can be inserted as expected.
	 */
	public function test_cot_data_store_update_corrupted_order() {
		global $wpdb;
		$order    = $this->create_complex_cot_order();
		$order_id = $order->get_id();

		// Corrupt the order.
		$wpdb->delete( $this->sut::get_addresses_table_name(), array( 'order_id' => $order->get_id() ), array( '%d' ) );

		// Try to update the order.
		$order->set_status( 'completed' );
		$order->set_billing_address_1( 'New address' );
		$order->save();

		// Re-read order and make sure changes were persisted.
		wp_cache_flush();
		$order = new WC_Order();
		$order->set_id( $order_id );
		$this->switch_data_store( $order, $this->sut );
		$this->sut->read( $order );
		$this->assertEquals( 'New address', $order->get_billing_address_1() );
	}

	/**
	 * @testDox We should be able to save multiple orders without them overwriting each other.
	 */
	public function test_cot_data_store_multiple_saved_orders() {
		$order1 = $this->create_complex_cot_order();
		$order2 = $this->create_complex_cot_order();

		$order1_id          = $order1->get_id();
		$order1_billing     = $order1->get_billing_address_1();
		$order1_created_via = $order1->get_created_via();
		$order1_key         = $order1->get_order_key();

		$order2_id          = $order2->get_id();
		$order2_billing     = $order2->get_billing_address_1();
		$order2_created_via = $order2->get_created_via();
		$order2_key         = $order2->get_order_key();

		wp_cache_flush();

		// Read the order again (fresh).
		$r_order1 = new WC_Order();
		$r_order1->set_id( $order1_id );
		$this->switch_data_store( $r_order1, $this->sut );
		$this->sut->read( $r_order1 );

		$r_order2 = new WC_Order();
		$r_order2->set_id( $order2_id );
		$this->switch_data_store( $r_order2, $this->sut );
		$this->sut->read( $r_order2 );

		$this->assertEquals( $order1_billing, $r_order1->get_billing_address_1() );
		$this->assertEquals( $order1_created_via, $r_order1->get_created_via() );
		$this->assertEquals( $order1_key, $r_order1->get_order_key() );

		$this->assertEquals( $order2_billing, $r_order2->get_billing_address_1() );
		$this->assertEquals( $order2_created_via, $r_order2->get_created_via() );
		$this->assertEquals( $order2_key, $r_order2->get_order_key() );
	}

	/**
	 * @testDox Tests creation of full vs placeholder records in the posts table when creating orders in the COT datastore.
	 *
	 * @return void
	 */
	public function test_cot_datastore_create_sync() {
		global $wpdb;

		// Sync enabled implies a full post should be created.
		$this->enable_cot_sync();
		$order = $this->create_complex_cot_order();
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s", $order->get_id(), 'shop_order' ) ) );

		// Sync disabled implies a placeholder post should be created.
		$this->disable_cot_sync();
		$order = $this->create_complex_cot_order();
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s", $order->get_id(), DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE ) ) );
	}

	/**
	 * @testDox Tests the `delete()` method on the COT datastore -- trashing.
	 *
	 * @return void
	 */
	public function test_cot_datastore_delete_trash() {
		global $wpdb;

		// Tests trashing of orders.
		$order    = $this->create_complex_cot_order();
		$order_id = $order->get_id();
		$order->delete();

		$orders_table = $this->sut::get_orders_table_name();
		$this->assertEquals( 'trash', $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$orders_table} WHERE id = %d", $order_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Make sure order data persists in the database.
		$this->assertNotEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $order_id ) ) );

		foreach ( $this->sut->get_all_table_names() as $table ) {
			if ( $table === $orders_table ) {
				continue;
			}

			$this->assertNotEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d", $order_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * @testdox Test the trash-untrash cycle with sync enabled.
	 */
	public function test_cot_datastore_untrash() {
		global $wpdb;

		$this->enable_cot_sync();
		$this->toggle_cot_feature_and_usage( true );

		// Tests trashing of orders.
		$order = $this->create_complex_cot_order();
		$order->set_status( 'on-hold' );
		$order->save();
		$order_id = $order->get_id();

		$this->sut->trash_order( $order );

		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
		$orders_table = $this->sut::get_orders_table_name();
		$this->assertEquals( 'trash', $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$orders_table} WHERE id = %d", $order_id ) ) );
		$this->assertEquals( 'trash', $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM {$wpdb->posts} WHERE id = %d", $order_id ) ) );

		$this->sut->read( $order );
		$this->sut->untrash_order( $order );

		$this->assertEquals( 'on-hold', $order->get_status() );
		$this->assertEquals( 'wc-on-hold', get_post_status( $order_id ) );

		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->sut->get_meta_table_name()} WHERE order_id = %d AND meta_key LIKE '_wp_trash_meta_%'", $order_id ) ) );
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE '_wp_trash_meta_%'", $order_id ) ) );
		//phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders
	}

	/**
	 * @testDox Tests the `delete()` method on the COT datastore -- full deletes.
	 *
	 * @return void
	 */
	public function test_cot_datastore_delete() {
		global $wpdb;

		// Tests trashing of orders.
		$order    = $this->create_complex_cot_order();
		$order_id = $order->get_id();
		$order->delete( true );

		// Make sure no data order persists in the database.
		$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $order_id ) ) );

		foreach ( $this->sut->get_all_table_names() as $table ) {
			$field_name = ( $table === $this->sut::get_orders_table_name() ) ? 'id' : 'order_id';
			$this->assertEmpty( $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$field_name} = %d", $order_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * @testDox Tests the `OrdersTableQuery` class on the COT datastore.
	 */
	public function test_cot_query_basic() {
		// We bypass the `query()` method as it's mainly just a thin wrapper around
		// `OrdersTableQuery`.
		$user_id = wp_insert_user(
			array(
				'user_login' => 'testname',
				'user_pass'  => 'testpass',
				'user_email' => 'email@example.com',
			)
		);

		$order1 = new WC_Order();
		$this->switch_data_store( $order1, $this->sut );
		$order1->set_prices_include_tax( true );
		$order1->set_total( '100.0' );
		$order1->set_order_key( 'my-order-key' );
		$order1->set_customer_id( $user_id );
		$order1->save();

		$order2 = new WC_Order();
		$this->switch_data_store( $order2, $this->sut );
		$order2->set_prices_include_tax( false );
		$order2->set_total( '50.0' );
		$order2->save();

		$query_vars = array(
			'status' => 'all',
		);

		// Get all orders.
		$query = new OrdersTableQuery( $query_vars );
		$this->assertEquals( 2, count( $query->orders ) );

		// Get orders with a specific property.
		$query_vars['prices_include_tax'] = 'no';
		$query                            = new OrdersTableQuery( $query_vars );
		$this->assertEquals( 1, count( $query->orders ) );
		$this->assertEquals( $query->orders[0], $order2->get_id() );

		$query_vars['prices_include_tax'] = 'yes';
		$query                            = new OrdersTableQuery( $query_vars );
		$this->assertEquals( 1, count( $query->orders ) );
		$this->assertEquals( $query->orders[0], $order1->get_id() );

		// Get orders with two specific properties.
		$query_vars['total'] = '100.0';
		$query               = new OrdersTableQuery( $query_vars );
		$this->assertEquals( 1, count( $query->orders ) );
		$this->assertEquals( $query->orders[0], $order1->get_id() );

		// Limit results.
		unset( $query_vars['total'], $query_vars['prices_include_tax'] );
		$query_vars['limit'] = 1;
		$query               = new OrdersTableQuery( $query_vars );
		$this->assertEquals( 1, count( $query->orders ) );

		// By customer ID.
		$query = new OrdersTableQuery(
			array(
				'status'      => 'all',
				'customer_id' => $user_id,
			)
		);
		$this->assertEquals( 1, count( $query->orders ) );
	}

	/**
	 * @testDox Tests meta queries in the `OrdersTableQuery` class.
	 *
	 * @return void
	 */
	public function test_cot_query_meta() {
		$order1 = new WC_Order();
		$this->switch_data_store( $order1, $this->sut );
		$order1->add_meta_data( 'color', 'green', true );
		$order1->add_meta_data( 'animal', 'lion', true );
		$order1->add_meta_data( 'place', 'London', true );
		$order1->add_meta_data( 'movie', 'Magnolia', true );
		$order1->set_status( 'completed' );
		$order1->save();

		$order2 = new WC_Order();
		$this->switch_data_store( $order2, $this->sut );
		$order2->add_meta_data( 'color', 'blue', true );
		$order2->add_meta_data( 'animal', 'cow', true );
		$order2->add_meta_data( 'place', 'near London', true );
		$order2->set_status( 'completed' );
		$order2->save();

		$order3 = new WC_Order();
		$this->switch_data_store( $order3, $this->sut );
		$order3->add_meta_data( 'color', 'green', true );
		$order3->add_meta_data( 'animal', 'lion', true );
		$order3->add_meta_data( 'place', 'Paris', true );
		$order3->add_meta_data( 'movie', 'Citizen Kane', true );
		$order3->set_status( 'completed' );
		$order3->save();

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query,WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		// Orders with color=green.
		$query = new OrdersTableQuery(
			array(
				'meta_query' => array(
					array(
						'key'   => 'color',
						'value' => 'green',
					),
				),
			)
		);
		$this->assertEquals( 2, count( $query->orders ) );

		// Orders with a 'movie' meta (regardless of value) and animal=lion.
		$query = new OrdersTableQuery(
			array(
				'meta_query' => array(
					array(
						'key'   => 'animal',
						'value' => 'lion',
					),
					array(
						'key' => 'movie',
					),
				),
			)
		);
		$this->assertEquals( 2, count( $query->orders ) );
		$this->assertContains( $order1->get_id(), $query->orders );
		$this->assertContains( $order3->get_id(), $query->orders );

		// Orders with place ~London ("London" and "near London").
		$query = new OrdersTableQuery(
			array(
				'meta_query' => array(
					array(
						'key'     => 'place',
						'value'   => 'London',
						'compare' => 'LIKE',
					),
				),
			)
		);
		$this->assertEquals( 2, count( $query->orders ) );
		$this->assertContains( $order1->get_id(), $query->orders );
		$this->assertContains( $order2->get_id(), $query->orders );

		// Orders with (color=blue OR place=Paris) AND 'animal' set.
		$query = new OrdersTableQuery(
			array(
				'meta_query' => array(
					array(
						'key' => 'animal',
					),
					array(
						'relation' => 'OR',
						array(
							'key'   => 'color',
							'value' => 'blue',
						),
						array(
							'key'   => 'place',
							'value' => 'Paris',
						),
					),
				),
			)
		);
		$this->assertEquals( 2, count( $query->orders ) );
		$this->assertContains( $order2->get_id(), $query->orders );
		$this->assertContains( $order3->get_id(), $query->orders );

		// Orders with no 'movie' set (and using meta_key and meta_compare directly instead of meta_query as shortcut).
		$query = new OrdersTableQuery(
			array(
				'meta_key'     => 'movie',
				'meta_compare' => 'NOT EXISTS',
			)
		);
		$this->assertEquals( 1, count( $query->orders ) );
		$this->assertContains( $order2->get_id(), $query->orders );

		// phpcs:enable
	}

	/**
	 * @testDox Tests queries involving 'orderby' and meta queries.
	 */
	public function test_cot_query_meta_orderby() {
		$this->toggle_cot_feature_and_usage( true );

		$order1 = new \WC_Order();
		$order1->add_meta_data( 'color', 'red' );
		$order1->add_meta_data( 'animal', 'lion' );
		$order1->add_meta_data( 'numeric_meta', '1000' );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->add_meta_data( 'color', 'green' );
		$order2->add_meta_data( 'animal', 'lion' );
		$order2->add_meta_data( 'numeric_meta', '500' );
		$order2->save();

		$query_args = array(
			'orderby'  => 'id',
			'order'    => 'ASC',
			'meta_key' => 'color', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		);

		// Check that orders are in order (when no meta ordering is involved).
		$q = new OrdersTableQuery( $query_args );
		$this->assertEquals( $q->orders, array( $order1->get_id(), $order2->get_id() ) );

		// When ordering by color $order2 should come first.
		// Also tests that the key name is a valid synonym for the primary meta query.
		$query_args['orderby'] = 'color';
		$q                     = new OrdersTableQuery( $query_args );
		$this->assertEquals( $q->orders, array( $order2->get_id(), $order1->get_id() ) );

		// When ordering by 'numeric_meta' 1000 < 500 (due to alphabetical sorting by default).
		// Also tests that 'meta_value' is a valid synonym for the primary meta query.
		$query_args['meta_key'] = 'numeric_meta'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query_args['orderby']  = 'meta_value';
		$q                      = new OrdersTableQuery( $query_args );
		$this->assertEquals( $q->orders, array( $order1->get_id(), $order2->get_id() ) );

		// Forcing numeric sorting with 'meta_value_num' reverses the order above.
		$query_args['orderby'] = 'meta_value_num';
		$q                     = new OrdersTableQuery( $query_args );
		$this->assertEquals( $q->orders, array( $order2->get_id(), $order1->get_id() ) );

		// Sorting by 'animal' meta is ambiguous. Test that we can order by various meta fields (and use the names in 'orderby').
		unset( $query_args['meta_key'] );
		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'animal_meta' => array(
				'key' => 'animal',
			),
			'color_meta'  => array(
				'key' => 'color',
			),
		);
		$query_args['orderby']    = array(
			'animal_meta' => 'ASC',
			'color_meta'  => 'DESC',
		);
		$q                        = new OrdersTableQuery( $query_args );
		$this->assertEquals( $q->orders, array( $order1->get_id(), $order2->get_id() ) );

		// Order is reversed when changing the sort order for 'color_meta'.
		$query_args['orderby']['color_meta'] = 'ASC';
		$q                                   = new OrdersTableQuery( $query_args );
		$this->assertEquals( $q->orders, array( $order2->get_id(), $order1->get_id() ) );

	}

	/**
	 * @testDox Tests queries involving the 'customer' query var.
	 *
	 * @return void
	 */
	public function test_cot_query_customer() {
		$user_email_1 = 'email1@example.com';
		$user_email_2 = 'email2@example.com';
		$user_id_1    = wp_insert_user(
			array(
				'user_login' => 'user_1',
				'user_pass'  => 'testing',
				'user_email' => $user_email_1,
			)
		);
		$user_id_2    = wp_insert_user(
			array(
				'user_login' => 'user_2',
				'user_pass'  => 'testing',
				'user_email' => $user_email_2,
			)
		);

		$order1 = new WC_Order();
		$this->switch_data_store( $order1, $this->sut );
		$order1->set_customer_id( $user_id_1 );
		$order1->save();

		$order2 = new WC_Order();
		$this->switch_data_store( $order2, $this->sut );
		$order2->set_customer_id( $user_id_2 );
		$order2->save();

		$order3 = new WC_Order();
		$this->switch_data_store( $order3, $this->sut );
		$order3->set_customer_id( $user_id_2 );
		$order3->save();

		// Search for orders of either user (by ID). Should return all orders.
		$query = new OrdersTableQuery(
			array(
				'customer' => array( $user_id_1, $user_id_2 ),
			)
		);
		$this->assertEquals( 3, $query->found_orders );

		// Search for user 1 (by e-mail) and user 2 (by ID). Should return all orders.
		$query = new OrdersTableQuery(
			array(
				'customer' => array( $user_email_1, $user_id_2 ),
			)
		);
		$this->assertEquals( 3, $query->found_orders );

		// Search for orders that match user 1 (email and ID). Should return order 1.
		$query = new OrdersTableQuery(
			array(
				'customer' => array( array( $user_email_1, $user_id_1 ) ),
			)
		);
		$this->assertEquals( 1, $query->found_orders );
		$this->assertContains( $order1->get_id(), $query->orders );

		// Search for orders that match user 1 (email) and user 2 (ID). Should return no order.
		$query = new OrdersTableQuery(
			array(
				'customer' => array( array( $user_email_1, $user_id_2 ) ),
			)
		);
		$this->assertEquals( 0, $query->found_orders );

	}

	/**
	 * @testDox Tests queries involving 'date_query'.
	 *
	 * @return void
	 */
	public function test_cot_query_date_query() {
		// Hardcode a day so that we don't go over to a different month or year by adding/substracting hours and days.
		$now    = strtotime( '2022-06-04 10:00:00' );
		$deltas = array(
			- DAY_IN_SECONDS,
			- HOUR_IN_SECONDS,
			0,
			HOUR_IN_SECONDS,
			DAY_IN_SECONDS,
			YEAR_IN_SECONDS,
		);

		foreach ( $deltas as $delta ) {
			$time = $now + $delta;

			$order = new \WC_Order();
			$this->switch_data_store( $order, $this->sut );
			$order->set_date_created( $time );
			$order->set_date_paid( $time + HOUR_IN_SECONDS );
			$order->set_date_completed( $time + ( 2 * HOUR_IN_SECONDS ) );
			$order->save();
		}

		// Orders exactly created at $now.
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => $now,
			)
		);
		$this->assertCount( 1, $query->orders );

		// Orders created since $now (inclusive).
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => '>=' . $now,
			)
		);
		$this->assertCount( 4, $query->orders );

		// Orders created before $now (inclusive).
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => '<=' . $now,
			)
		);
		$this->assertCount( 3, $query->orders );

		// Orders created before $now (non-inclusive).
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => '<' . $now,
			)
		);
		$this->assertCount( 2, $query->orders );

		// Orders created exactly between the day before yesterday and yesterday.
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => ( $now - ( 2 * DAY_IN_SECONDS ) ) . '...' . ( $now - DAY_IN_SECONDS ),
			)
		);
		$this->assertCount( 1, $query->orders );

		// Orders created today. Tests 'day' precision strings.
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => gmdate( 'Y-m-d', $now ),
			)
		);
		$this->assertCount( 3, $query->orders );

		// Orders created after today. Tests 'day' precision strings.
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => '>' . gmdate( 'Y-m-d', $now ),
			)
		);
		$this->assertCount( 2, $query->orders );

		// Orders created next year. Tests top-level date_query args.
		$query = new OrdersTableQuery(
			array(
				'year' => gmdate( 'Y', $now + YEAR_IN_SECONDS ),
			)
		);
		$this->assertCount( 1, $query->orders );

		// Orders created today, paid between 11:00 and 13:00.
		$query = new OrdersTableQuery(
			array(
				'date_created_gmt' => gmdate( 'Y-m-d', $now ),
				'date_paid_gmt'    => strtotime( gmdate( 'Y-m-d 11:00:00', $now ) ) . '...' . strtotime( gmdate( 'Y-m-d 13:00:00', $now ) ),
			)
		);
		$this->assertCount( 2, $query->orders );

		// Orders completed after 11:00 AM on any date. Tests meta_query directly.
		$query = new OrdersTableQuery(
			array(
				'date_query' => array(
					array(
						'column'  => 'date_completed_gmt',
						'hour'    => 11,
						'compare' => '>',
					),
				),
			)
		);
		$this->assertCount( 5, $query->orders );

		// Orders completed last year. Should return none.
		$query = new OrdersTableQuery(
			array(
				'date_query' => array(
					array(
						'column'  => 'date_completed_gmt',
						'year'    => gmdate( 'Y', $now - YEAR_IN_SECONDS ),
						'compare' => '<',
					),
				),
			)
		);
		$this->assertCount( 0, $query->orders );

		// Orders created between a month ago and 2 years in the future. That is, all orders.
		$a_month_ago     = $now - MONTH_IN_SECONDS;
		$two_years_later = $now + ( 2 * YEAR_IN_SECONDS );

		$query = new OrdersTableQuery(
			array(
				'date_query' => array(
					array(
						'after'  => array(
							'year'   => gmdate( 'Y', $a_month_ago ),
							'month'  => gmdate( 'm', $a_month_ago ),
							'day'    => gmdate( 'd', $a_month_ago ),
							'hour'   => gmdate( 'H', $a_month_ago ),
							'minute' => gmdate( 'i', $a_month_ago ),
							'second' => gmdate( 's', $a_month_ago ),
						),
						'before' => array(
							'year'   => gmdate( 'Y', $two_years_later ),
							'month'  => gmdate( 'm', $two_years_later ),
							'day'    => gmdate( 'd', $two_years_later ),
							'hour'   => gmdate( 'H', $two_years_later ),
							'minute' => gmdate( 'i', $two_years_later ),
							'second' => gmdate( 's', $two_years_later ),
						),
					),
				),
			)
		);
		$this->assertCount( 6, $query->orders );
	}

	/**
	 * @testdox Test pagination works for COT queries.
	 *
	 * @return void
	 */
	public function test_cot_query_pagination(): void {
		$test_orders = array();
		$this->assertEquals( 0, ( new OrdersTableQuery() )->found_orders, 'We initially have zero orders within our custom order tables.' );

		for ( $i = 0; $i < 30; $i ++ ) {
			$order = new WC_Order();
			$this->switch_data_store( $order, $this->sut );
			$order->save();
			$test_orders[] = $order->get_id();
		}

		$query = new OrdersTableQuery();
		$this->assertCount( 30, $query->orders, 'If no limits are specified, we fetch all available orders.' );

		$query = new OrdersTableQuery( array( 'limit' => -1 ) );
		$this->assertCount( 30, $query->orders, 'A limit of -1 is equivalent to requesting all available orders.' );

		$query = new OrdersTableQuery( array( 'limit' => -10 ) );
		$this->assertCount( 30, $query->orders, 'An invalid limit is treated as a request for all available orders.' );

		$query = new OrdersTableQuery(
			array(
				'limit'  => - 1,
				'offset' => 18,
			)
		);
		$this->assertCount( 12, $query->orders, 'A limit of -1 can successfully be combined with an offset.' );
		$this->assertEquals( array_slice( $test_orders, 18 ), $query->orders, 'The expected dataset is supplied when an offset is combined with a limit of -1.' );

		$query = new OrdersTableQuery( array( 'limit' => 5 ) );
		$this->assertCount( 5, $query->orders, 'Limits are respected when applied.' );

		$query = new OrdersTableQuery(
			array(
				'limit'  => 5,
				'paged'  => 2,
				'return' => 'ids',
			)
		);
		$this->assertCount( 5, $query->orders, 'Pagination works with specified limit.' );
		$this->assertEquals( array_slice( $test_orders, 5, 5 ), $query->orders, 'The expected dataset is supplied when paginating through orders.' );
	}

	/**
	 * @testdox Test that the query counts works as expected.
	 *
	 * @return void
	 */
	public function test_cot_query_count() {
		$this->assertEquals( 0, ( new OrdersTableQuery() )->found_orders, 'We initially have zero orders within our custom order tables.' );

		for ( $i = 0; $i < 30; $i ++ ) {
			$order = new WC_Order();
			$this->switch_data_store( $order, $this->sut );
			if ( 0 === $i % 2 ) {
				$order->set_billing_address_2( 'Test' );
			}
			$order->save();
		}

		$query = new OrdersTableQuery( array( 'limit' => 5 ) );
		$this->assertEquals( 30, $query->found_orders, 'Specifying limits still calculate all found orders.' );

		// Count does not change based on the fields that we are fetching.
		$query = new OrdersTableQuery(
			array(
				'fields' => 'ids',
				'limit'  => 5,
			)
		);
		$this->assertEquals( 30, $query->found_orders, 'Fetching specific field does not change query count.' );

		$query = new OrdersTableQuery(
			array(
				'field_query' => array(
					array(
						'field' => 'billing_address_2',
						'value' => 'Test',
					),
				),
			)
		);
		$this->assertEquals( 15, $query->found_orders, 'Counting orders with a field query works.' );
	}

	/**
	 * @testDox Test the `get_order_count()` method.
	 */
	public function test_get_order_count(): void {
		$number_of_orders_by_status = array(
			'wc-completed'  => 4,
			'wc-processing' => 2,
			'wc-pending'    => 4,
		);

		foreach ( $number_of_orders_by_status as $order_status => $number_of_orders ) {
			foreach ( range( 1, $number_of_orders ) as $_ ) {
				$o = new \WC_Order();
				$this->switch_data_store( $o, $this->sut );
				$o->set_status( $order_status );
				$o->save();
			}
		}

		// Count all orders.
		$expected_count = array_sum( array_values( $number_of_orders_by_status ) );
		$actual_count   = ( new OrdersTableQuery( array( 'limit' => '-1' ) ) )->found_orders;
		$this->assertEquals( $expected_count, $actual_count );

		// Count orders by status.
		foreach ( $number_of_orders_by_status as $order_status => $number_of_orders ) {
			$this->assertEquals( $number_of_orders, $this->sut->get_order_count( $order_status ) );
		}
	}

	/**
	 * @testDox Test `get_unpaid_orders()`.
	 */
	public function test_get_unpaid_orders(): void {
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Intentional usage since timezone is changed for this file.
		$now_gmt = time();
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Testing a legacy code that does expect the offset timestamp.
		$now_ist = current_time( 'timestamp', 0 ); // IST (Indian standard time) is 5.5 hours ahead of GMT and is set as timezone for this class.

		// Create a few orders.
		$orders_by_status = array(
			'wc-completed' => 3,
			'wc-pending'   => 2,
		);
		$unpaid_ids       = array();
		foreach ( $orders_by_status as $order_status => $order_count ) {
			foreach ( range( 1, $order_count ) as $_ ) {
				$order = new \WC_Order();
				$this->switch_data_store( $order, $this->sut );
				$order->set_status( $order_status );
				$order->set_date_modified( $now_gmt - DAY_IN_SECONDS );
				$order->save();

				if ( ! $order->is_paid() ) {
					$unpaid_ids[] = $order->get_id();
				}
			}
		}

		// Confirm not all orders are unpaid.
		$this->assertEquals( $orders_by_status['wc-completed'], $this->sut->get_order_count( 'wc-completed' ) );

		// Find unpaid orders.
		$this->assertEqualsCanonicalizing( $unpaid_ids, $this->sut->get_unpaid_orders( $now_ist ) );
		$this->assertEqualsCanonicalizing( $unpaid_ids, $this->sut->get_unpaid_orders( $now_ist - HOUR_IN_SECONDS ) );

		// No unpaid orders from before yesterday.
		$this->assertCount( 0, $this->sut->get_unpaid_orders( $now_ist - DAY_IN_SECONDS ) );
	}

	/**
	 * @testDox Test `get_order_id_by_order_key()`.
	 *
	 * @return void
	 */
	public function test_get_order_id_by_order_key() {
		$order = new \WC_Order();
		$this->switch_data_store( $order, $this->sut );
		$order->set_order_key( 'an_order_key' );
		$order->save();

		$this->assertEquals( $order->get_id(), $this->sut->get_order_id_by_order_key( 'an_order_key' ) );
		$this->assertEquals( 0, $this->sut->get_order_id_by_order_key( 'other_order_key' ) );
	}

	/**
	 * @testDox Direct write to metadata should propagate to the orders table when reading.
	 */
	public function test_read_with_direct_meta_write() {
		$this->toggle_cot_feature_and_usage( true );
		$this->enable_cot_sync();
		$order = $this->create_complex_cot_order();

		$post_object = get_post( $order->get_id() );
		// Make sure that COT sync is enabled, by checking that this is not placeholder post.
		$this->assertTrue( get_post_type( $post_object->ID ) === 'shop_order' );

		// simulate direct write.
		update_post_meta( $post_object->ID, 'my_custom_meta', array( 'key' => 'value' ) );

		$refreshed_order = new WC_Order();
		$refreshed_order->set_id( $order->get_id() );
		$this->switch_data_store( $refreshed_order, $this->sut );
		$this->sut->read( $refreshed_order );

		$this->assertEquals( array( 'key' => 'value' ), $refreshed_order->get_meta( 'my_custom_meta' ) );
	}

	/**
	 * @testDox When there are direct writes to posts data, order should synced upon reading.
	 */
	public function test_read_multiple_with_direct_write() {
		$this->enable_cot_sync();
		$this->toggle_cot_feature_and_usage( true );
		$order       = $this->create_complex_cot_order();
		$order_total = $order->get_total();
		$order->add_meta_data( 'custom_meta_1', 'custom_value_1' );
		$order->add_meta_data( 'custom_meta_2', 'custom_value_2' );
		$order->add_meta_data( 'custom_meta_3', 'custom_value_3' );
		$order->save();
		$post_object = get_post( $order->get_id() );
		assert( get_post_type( $post_object->ID ) === 'shop_order' );

		// simulate direct write.
		update_post_meta( $post_object->ID, '_order_total', $order_total + 100 ); // core table.
		update_post_meta( $post_object->ID, '_billing_first_name', 'John Doe Updated' ); // address table.
		update_post_meta( $post_object->ID, '_created_via', 'Unit tests Updated' ); // op data table.

		add_post_meta( $post_object->ID, 'custom_meta_4', 'custom_value_4' ); // new meta add.
		update_post_meta( $post_object->ID, 'custom_meta_1', 'custom_value_1_updated' ); // existing meta update.
		delete_post_meta( $post_object->ID, 'custom_meta_2' ); // existing meta delete.

		// Read a refreshed order.
		$refreshed_order = new WC_Order();
		$refreshed_order->set_id( $order->get_id() );
		$this->switch_data_store( $refreshed_order, $this->sut );
		$this->sut->read( $refreshed_order );
		$this->assertEquals( $order_total + 100, $refreshed_order->get_total() );
		$this->assertEquals( 'John Doe Updated', $refreshed_order->get_billing_first_name() );
		$this->assertEquals( 'Unit tests Updated', $refreshed_order->get_created_via() );
		$this->assertEquals( 'custom_value_4', $refreshed_order->get_meta( 'custom_meta_4' ) );
		$this->assertEquals( 'custom_value_1_updated', $refreshed_order->get_meta( 'custom_meta_1' ) );
		$this->assertEquals( '', $refreshed_order->get_meta( 'custom_meta_2' ) );
	}

	/**
	 * @testDox Test that we are able to correctly detect when order and post are out of sync.
	 */
	public function test_is_post_different_from_order() {
		$this->toggle_cot_feature_and_usage( true );
		$this->enable_cot_sync();
		$order                         = $this->create_complex_cot_order();
		$post_order_comparison_closure = function ( $order ) {
			$post_order = $this->get_post_orders_for_ids( array( $order->get_id() => $order ) )[ $order->get_id() ];

			return $this->is_post_different_from_order( $order, $post_order );
		};
		// No changes, post and order should be same.
		$this->assertFalse( $post_order_comparison_closure->call( $this->sut, $order ) );

		// Simulate direct write.
		update_post_meta( $order->get_id(), 'my_custom_meta', array( 'key' => 'value' ) );

		// Order and post are different now.
		$this->assertTrue( $post_order_comparison_closure->call( $this->sut, $order ) );

		$r_order = new WC_Order();
		$r_order->set_id( $order->get_id() );
		$this->switch_data_store( $r_order, $this->sut );
		// Reading again will make a call to migrate_post_record.
		$this->sut->read( $r_order );
		$this->assertFalse( $post_order_comparison_closure->call( $this->sut, $r_order ) );
		$this->assertEquals( array( 'key' => 'value' ), $r_order->get_meta( 'my_custom_meta' ) );
	}

	/**
	 * @testDox Test that after backfilling, post order is same as cot order.
	 */
	public function test_post_is_same_as_order_after_backfill() {
		$order = $this->create_complex_cot_order();
		$order->save();
		$this->sut->backfill_post_record( $order );

		$r_order = new WC_Order();
		$r_order->set_id( $order->get_id() );
		$this->switch_data_store( $r_order, $this->sut );
		$clear_sync_on_read_closure = function () {
			self::$reading_order_ids = array();
		};
		$clear_sync_on_read_closure->call( $this->sut );
		$this->sut->read( $r_order );

		$post_order_comparison_closure = function () use ( $r_order ) {
			$post_order = $this->get_cpt_order( get_post( $r_order->get_id() ) );

			return $this->is_post_different_from_order( $r_order, $post_order );
		};

		$this->assertFalse( $post_order_comparison_closure->call( $this->sut ) );
	}

	/**
	 * @testDox Meta data should be migrated from post order to cot order.
	 *
	 * @return void
	 */
	public function test_migrate_meta_data_from_post_order() {
		$order1 = new WC_Order();
		$order1->add_meta_data( 'common_meta_key_1', 'common_meta_value_1' );
		$order1->add_meta_data( 'common_meta_key_2', 'common_meta_value_2' );
		$order1->add_meta_data( 'common_meta_key_3', 'common_meta_value_3' );
		$order1->add_meta_data( 'order1_meta_key_1', 'order1_meta_value_1' );
		$order1->save();

		$order2 = new WC_Order();
		$order2->add_meta_data( 'common_meta_key_1', 'common_meta_value_1' );
		$order2->add_meta_data( 'common_meta_key_2', 'common_meta_value_2_updated' );
		$order2->add_meta_data( 'order2_meta_key_1', 'order2_meta_key_1' );

		$diff_call_closure = function ( $order1, $order2 ) {
			return $this->migrate_meta_data_from_post_order( $order1, $order2 );
		};

		$diff = $diff_call_closure->call( $this->sut, $order1, $order2 );
		$this->assertFalse( empty( $diff ) );

		$this->assertEquals( 'common_meta_value_1', $order1->get_meta( 'common_meta_key_1' ) );
		$this->assertEquals( 'common_meta_value_2_updated', $order1->get_meta( 'common_meta_key_2' ) );
		$this->assertEquals( '', $order1->get_meta( 'common_meta_key_3' ) );
		$this->assertEquals( '', $order1->get_meta( 'order1_meta_key_1' ) );
		$this->assertEquals( 'order2_meta_key_1', $order1->get_meta( 'order2_meta_key_1' ) );
	}

	/**
	 * Helper function to delete all meta for post.
	 *
	 * @param int $post_id Post ID to delete data for.
	 */
	private function delete_all_meta_for_post( $post_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $post_id ) );
	}

	/**
	 * Helper method to allow switching data stores.
	 *
	 * @param WC_Order      $order Order object.
	 * @param WC_Data_Store $data_store Data store object to switch order to.
	 */
	private function switch_data_store( $order, $data_store ) {
		OrderHelper::switch_data_store( $order, $data_store );
	}

	/**
	 * Creates a complex COT order with address info, line items, etc.
	 * @return \WC_Order
	 */
	private function create_complex_cot_order() {
		return OrderHelper::create_complex_data_store_order( $this->sut );
	}

	/**
	 * @testDox Ensure search works as expected.
	 */
	public function test_cot_query_search(): void {
		$order_1 = new WC_Order();
		$order_1->set_billing_city( 'Fort Quality' );
		$this->switch_data_store( $order_1, $this->sut );
		$this->disable_cot_sync();
		$order_1->save();

		$product = new WC_Product_Simple();
		$product->set_name( 'Quality Chocolates' );
		$product->save();

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );
		$item->save();

		$order_2 = new WC_Order();
		$order_2->add_item( $item );
		$this->switch_data_store( $order_2, $this->sut );
		$order_2->save();

		$order_3 = new WC_Order();
		$order_3->set_billing_address_1( $order_1->get_id() . ' Functional Street' );
		$this->switch_data_store( $order_3, $this->sut );
		$order_3->save();

		// Order 1's ID happens to be the same number used in Order 3's billing street address.
		$query = new OrdersTableQuery( array( 's' => $order_1->get_id() ) );
		$this->assertEquals(
			array( $order_1->get_id(), $order_3->get_id() ),
			$query->orders,
			'Search terms match against IDs as well as address data.'
		);

		// Order 1's billing address references "Quality" and so does one of Order 2's order items.
		$query        = new OrdersTableQuery( array( 's' => 'Quality' ) );
		$orders_array = $query->orders;
		sort( $orders_array );
		$this->assertEquals(
			array( $order_1->get_id(), $order_2->get_id() ),
			$orders_array,
			'Search terms match against address data as well as order item names.'
		);
	}

	/**
	 * @testDox Ensure sorting by `includes` param works as expected.
	 */
	public function test_cot_query_sort_includes() {
		$this->disable_cot_sync();
		$order_1 = new WC_Order();
		$this->switch_data_store( $order_1, $this->sut );
		$order_1->save();

		$order_2 = new WC_Order();
		$this->switch_data_store( $order_2, $this->sut );
		$order_2->save();

		$query        = new OrdersTableQuery(
			array(
				'orderby'  => 'include',
				'includes' => array( $order_1->get_id(), $order_2->get_id() ),
			)
		);
		$orders_array = $query->orders;
		$this->assertEquals( array( $order_1->get_id(), $order_2->get_id() ), array( $orders_array[0], $orders_array[1] ) );

		$query        = new OrdersTableQuery(
			array(
				'orderby'  => 'include',
				'includes' => array( $order_2->get_id(), $order_1->get_id() ),
			)
		);
		$orders_array = $query->orders;
		$this->assertEquals( array( $order_2->get_id(), $order_1->get_id() ), array( $orders_array[0], $orders_array[1] ) );
	}

	/**
	 * @testDox Ensure search works as expected on updated orders.
	 */
	public function test_cot_query_search_update() {
		$order_1 = new WC_Order();
		$this->switch_data_store( $order_1, $this->sut );
		$this->disable_cot_sync();
		$order_1->save();

		$order_1->set_billing_city( 'New Cybertron' );
		$order_1->save();

		$order_2 = new WC_Order();
		$this->switch_data_store( $order_2, $this->sut );
		$order_2->save();

		$order_2->set_billing_city( 'Gigantian City' );
		$order_2->save();

		$query = new OrdersTableQuery( array( 's' => 'Cybertron' ) );
		$this->assertEquals(
			array( $order_1->get_id() ),
			$query->orders,
			'Search terms match against updated address data.'
		);
	}

	/**
	 * Test methods get_total_tax_refunded and get_total_shipping_refunded.
	 */
	public function test_get_total_tax_refunded_and_get_total_shipping_refunded() {
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate'          => '20',
			'tax_rate_name'     => 'tax',
			'tax_rate_order'    => '1',
			'tax_rate_shipping' => '1',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$rate = new WC_Shipping_Rate( 'flat_rate_shipping', 'Flat rate shipping', '10', array(), 'flat_rate' );
		$item = new WC_Order_Item_Shipping();
		$item->set_props(
			array(
				'method_title' => $rate->label,
				'method_id'    => $rate->id,
				'total'        => wc_format_decimal( $rate->cost ),
				'taxes'        => $rate->taxes,
			)
		);
		foreach ( $rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
		}

		$order = new WC_Order();
		$this->switch_data_store( $order, $this->sut );
		$order->save();
		$order->add_product( WC_Helper_Product::create_simple_product(), 10 );
		$order->add_item( $item );
		$order->calculate_totals();
		$order->save();
		$this->sut->backfill_post_record( $order );

		assert( $order->get_total_tax() > 0 );
		assert( $order->get_total() > 0 );
		$product_item_id  = current( $order->get_items() )->get_id();
		$shipping_item_id = current( $order->get_items( 'shipping' ) )->get_id();
		$refund           = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$product_item_id  => array(
						'id'           => $product_item_id,
						'qty'          => 1,
						'refund_total' => 10,
						'refund_tax'   => array( 1 => 2 ),
					),
					$shipping_item_id => array(
						'id'           => $shipping_item_id,
						'qty'          => 1,
						'refund_total' => 10,
						'refund_tax'   => array( 1 => 3 ),
					),
				),
			)
		);
		$refund->save();
		$this->migrator->migrate_order( $refund->get_id() );

		$this->assertEquals( 5, $order->get_data_store()->get_total_tax_refunded( $order ) );
		$this->assertEquals( 10, $order->get_data_store()->get_total_shipping_refunded( $order ) );
	}

	/**
	 * @testdox Ensure field_query works as expected.
	 */
	public function test_cot_query_field_query(): void {
		$orders_test_data = array(
			array( 'Werner', 'Heisenberg', 'Unknown', 'werner_heisenberg_1', '15.0', '1901-12-05', '1976-02-01' ),
			array( 'Max', 'Planck', 'Quanta', 'planck_1', '16.0', '1858-04-23', '1947-10-04' ),
			array( 'Édouard', 'Roche', 'Tidal', 'roche_3', '9.99', '1820-10-17', '1883-04-27' ),
		);
		$order_ids        = array();

		// Create some test orders.
		foreach ( $orders_test_data as $i => $order_data ) {
			$order = new \WC_Order();
			$this->switch_data_store( $order, $this->sut );
			$order->set_status( 'wc-completed' );
			$order->set_shipping_city( 'The Universe' );
			$order->set_billing_first_name( $order_data[0] );
			$order->set_billing_last_name( $order_data[1] );
			$order->set_billing_city( $order_data[2] );
			$order->set_order_key( $order_data[3] );
			$order->set_total( $order_data[4] );

			$order->add_meta_data( 'customer_birthdate', $order_data[5] );
			$order->add_meta_data( 'customer_last_seen', $order_data[6] );
			$order->add_meta_data( 'customer_age', absint( ( strtotime( $order_data[6] ) - strtotime( $order_data[5] ) ) / YEAR_IN_SECONDS ) );

			$order_ids[] = $order->save();
		}

		// Relatively simple field_query.
		$field_query = array(
			'relation' => 'OR',
			array(
				'field' => 'order_key',
				'value' => 'werner_heisenberg_1',
			),
			array(
				'field' => 'order_key',
				'value' => 'planck_1',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertEqualsCanonicalizing( array( $order_ids[0], $order_ids[1] ), $query->orders );

		// A more complex field_query.
		$field_query = array(
			array(
				'field'   => 'billing_first_name',
				'value'   => array( 'Werner', 'Max', 'Édouard' ),
				'compare' => 'IN',
			),
			array(
				'relation' => 'OR',
				array(
					'field'   => 'billing_last_name',
					'value'   => 'Heisen',
					'compare' => 'LIKE',
				),
				array(
					'field'   => 'billing_city',
					'value'   => 'Tid',
					'compare' => 'LIKE',
				),
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertEqualsCanonicalizing( array( $order_ids[0], $order_ids[2] ), $query->orders );

		// Complex field query with NOT IN.
		$field_query = array(
			array(
				'field'   => 'billing_first_name',
				'value'   => array( 'Werner', 'Édouard' ),
				'compare' => 'NOT IN',
			),
			array(
				'relation' => 'OR',
				array(
					'field'   => 'billing_last_name',
					'value'   => 'Planck',
					'compare' => 'LIKE',
				),
				array(
					'field'   => 'billing_city',
					'value'   => 'Tid',
					'compare' => 'LIKE',
				),
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertEqualsCanonicalizing( array( $order_ids[1] ), $query->orders );

		// Find orders with order_key ending in a number (i.e. all).
		$field_query = array(
			array(
				'field'   => 'order_key',
				'value'   => '[0-9]$',
				'compare' => 'RLIKE',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertEqualsCanonicalizing( $order_ids, $query->orders );

		// Find orders with order_key not ending in a number (i.e. none).
		$field_query = array(
			array(
				'field'   => 'order_key',
				'value'   => '[^0-9]$',
				'compare' => 'NOT RLIKE',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertCount( 0, $query->posts );

		// Use full column name in a query.
		$field_query = array(
			array(
				'field'   => $GLOBALS['wpdb']->prefix . 'wc_orders.total_amount',
				'value'   => '10.0',
				'compare' => '<=',
				'type'    => 'NUMERIC',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertEqualsCanonicalizing( array( $order_ids[2] ), $query->orders );

		// Pass an invalid column name.
		$field_query = array(
			array(
				'field' => 'non_existing_field',
				'value' => 'any-value',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertCount( 0, $query->posts );

		// Pass an apparently incorrect value to an 'IN' compare.
		$field_query = array(
			array(
				'field'   => 'wc_orders.total_amount',
				'value'   => 5.5,
				'compare' => 'IN',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertCount( 0, $query->posts );

		// Pass an invalid 'compare'.
		$field_query = array(
			array(
				'field'   => 'wc_orders.total_amount',
				'value'   => 10.0,
				'compare' => 'EXOSTS',
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertCount( 0, $query->posts );

		// Pass an incomplete array for BETWEEN (treated as =).
		$field_query = array(
			array(
				'field'   => 'total',
				'compare' => 'BETWEEN',
				'value'   => 10.0,
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertCount( 0, $query->posts );

		// Pass an incomplete array for NOT BETWEEN (treated as !=).
		$field_query = array(
			array(
				'field'   => 'total',
				'compare' => 'NOT BETWEEN',
				'value'   => array( 1.0 ),
			),
		);
		$query       = new OrdersTableQuery( array( 'field_query' => $field_query ) );
		$this->assertCount( 3, $query->posts );

		// Test combinations of field_query with regular query args.
		$args  = array(
			'id' => array( $order_ids[0], $order_ids[1] ),
		);
		$query = new OrdersTableQuery( $args );

		// At this point 2 orders would be returned...
		$this->assertEqualsCanonicalizing( array( $order_ids[0], $order_ids[1] ), $query->orders );

		// ... and now just one
		$args['field_query'] = array(
			'relation' => 'AND',
			array(
				'field' => 'id',
				'value' => $order_ids[1],
			),
		);
		$query               = new OrdersTableQuery( $args );
		$this->assertEqualsCanonicalizing( array( $order_ids[1] ), $query->orders );

		// ... and now none (no orders below < 5.0)
		$args['field_query'][] = array(
			'field'   => 'total',
			'value'   => '5.0',
			'compare' => '<',
		);
		$query                 = new OrdersTableQuery( $args );
		$this->assertCount( 0, $query->orders );

		// Now a more complex query with meta_query and date_query.
		$args = array(
			'shipping_address' => 'The Universe',
			'field_query'      => array(
				array(
					'field'   => 'total',
					'value'   => array( 1.0, 11.0 ),
					'compare' => 'NOT BETWEEN',
				),
			),
		);

		// this should fetch the orders from Heisenberg and Planck...
		$query = new OrdersTableQuery( $args );
		$this->assertEqualsCanonicalizing( array( $order_ids[0], $order_ids[1] ), $query->orders );

		// ... but only Planck is more than 80 years old.
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional usage for test.
		$args['meta_query'] = array(
			array(
				'key'     => 'customer_age',
				'value'   => 80,
				'compare' => '>=',
			),
		);
		$query              = new OrdersTableQuery( $args );
		$this->assertEqualsCanonicalizing( array( $order_ids[1] ), $query->orders );

		// Max and Werner are born in either 1858, or 1901.
		$args  = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional usage for test.
			'meta_query' => array(
				array(
					'key'     => 'customer_birthdate',
					'value'   => array( '1858-04-23', '1901-12-05' ),
					'compare' => 'IN',
				),
			),
		);
		$query = new OrdersTableQuery( $args );
		$this->assertEqualsCanonicalizing( array( $order_ids[0], $order_ids[1] ), $query->orders );

		// Let's do the same query, other way around, by excluding Édouard.
		$args  = array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional usage for test.
			'meta_query' => array(
				array(
					'key'     => 'customer_birthdate',
					'value'   => array( '1820-10-17' ),
					'compare' => 'NOT IN',
				),
			),
		);
		$query = new OrdersTableQuery( $args );
		$this->assertEqualsCanonicalizing( array( $order_ids[0], $order_ids[1] ), $query->orders );
	}

	/**
	 * @testDox Test that props set by datastores can be set and get by using any of metadata, object props or from data store setters.
	 * Ideally, this should be possible only from getters and setters for objects, but for backward compatibility, earlier ways are also supported.
	 */
	public function test_internal_ds_getters_and_setters() {
		$this->toggle_cot_feature_and_usage( true );
		$props_to_test = array(
			'_download_permissions_granted',
			'_recorded_sales',
			'_recorded_coupon_usage_counts',
			'_new_order_email_sent',
			'_order_stock_reduced',
		);

		$ds_getter_setter_names = array(
			'_order_stock_reduced'  => 'stock_reduced',
			'_new_order_email_sent' => 'email_sent',
		);

		$order = $this->create_complex_cot_order();

		// set everything to true via props.
		foreach ( $props_to_test as $prop ) {
			$order->{"set$prop"}( true );
			$order->save();
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, true, $ds_getter_setter_names );

		// set everything to false, via metadata.
		foreach ( $props_to_test as $prop ) {
			$order->update_meta_data( $prop, false );
			$order->save();
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, false, $ds_getter_setter_names );

		// set everything to true again, via datastore setter.
		foreach ( $props_to_test as $prop ) {
			if ( in_array( $prop, array_keys( $ds_getter_setter_names ), true ) ) {
				$setter = $ds_getter_setter_names[ $prop ];
				$order->get_data_store()->{"set_$setter"}( $order, true );
				continue;
			}
			$order->get_data_store()->{"set$prop"}( $order, true );
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, true, $ds_getter_setter_names );

		// set everything to false again, via props.
		foreach ( $props_to_test as $prop ) {
			$order->{"set$prop"}( false );
			$order->save();
		}
		$this->assert_get_prop_via_ds_object_and_metadata( $props_to_test, $order, false, $ds_getter_setter_names );
		$this->toggle_cot_feature_and_usage( false );
	}

	/**
	 * Helper method to assert props are set.
	 *
	 * @param array    $props List of props to test.
	 * @param WC_Order $order Order object.
	 * @param mixed    $value Value to assert.
	 * @param array    $ds_getter_setter_names List of props with custom getter/setter names.
	 */
	private function assert_get_prop_via_ds_object_and_metadata( array $props, WC_Order $order, $value, array $ds_getter_setter_names ) {
		wp_cache_flush();
		$refreshed_order = new WC_Order();
		$refreshed_order->set_id( $order->get_id() );
		$this->sut->read( $refreshed_order );
		$this->switch_data_store( $refreshed_order, $this->sut );
		$value = wc_bool_to_string( $value );
		// assert via metadata.
		foreach ( $props as $prop ) {
			$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->get_meta( $prop ) ), "Failed getting $prop from metadata" );
		}

		// assert via datastore object.
		foreach ( $props as $prop ) {
			if ( in_array( $prop, array_keys( $ds_getter_setter_names ), true ) ) {
				$getter = $ds_getter_setter_names[ $prop ];
				$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->get_data_store()->{"get_$getter"}( $refreshed_order ) ), "Failed getting $prop from datastore" );
				continue;
			}
			$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->get_data_store()->{"get$prop"}( $order ) ), "Failed getting $prop from datastore" );
		}

		// assert via order object.
		foreach ( $props as $prop ) {
			$this->assertEquals( $value, wc_bool_to_string( $refreshed_order->{"get$prop"}() ), "Failed getting $prop from object" );
		}
	}

	/**
	 * @testDox Legacy getters and setters for props migrated from data stores should be set/reset properly.
	 */
	public function test_legacy_getters_setters() {
		$this->toggle_cot_feature_and_usage( true );
		$this->disable_cot_sync();
		$order_id = OrderHelper::create_complex_data_store_order( $this->sut );
		$order    = wc_get_order( $order_id );
		$this->switch_data_store( $order, $this->sut );
		$bool_props = array(
			'_download_permissions_granted' => 'download_permissions_granted',
			'_recorded_sales'               => 'recorded_sales',
			'_recorded_coupon_usage_counts' => 'recorded_coupon_usage_counts',
			'_order_stock_reduced'          => 'order_stock_reduced',
			'_new_order_email_sent'         => 'new_order_email_sent',
		);

		$this->set_props_via_data_store( $order, $bool_props, true );

		$this->assert_props_value_via_data_store( $order, $bool_props, true );

		$this->assert_props_value_via_order_object( $order, $bool_props, true );

		// Let's repeat for false value.

		$this->set_props_via_data_store( $order, $bool_props, false );

		$this->assert_props_value_via_data_store( $order, $bool_props, false );

		$this->assert_props_value_via_order_object( $order, $bool_props, false );

		// Let's repeat for true value but setting via order object.

		$this->set_props_via_order_object( $order, $bool_props, true );

		$this->assert_props_value_via_data_store( $order, $bool_props, true );

		$this->assert_props_value_via_order_object( $order, $bool_props, true );
		$this->toggle_cot_feature_and_usage( false );
	}

	/**
	 * Helper function to set prop via data store.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their setter names.
	 * @param mixed    $value value to set.
	 */
	private function set_props_via_data_store( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$order->get_data_store()->{"set_$prop_name"}( $order, $value );
		}
	}

	/**
	 * Helper function to set prop value via object.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their setter names.
	 * @param mixed    $value value to set.
	 */
	private function set_props_via_order_object( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$order->{"set_$prop_name"}( $value );
		}
		$order->save();
	}

	/**
	 * Helper function to assert prop value via data store.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their getter names.
	 * @param mixed    $value value to assert.
	 */
	private function assert_props_value_via_data_store( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$this->assertEquals( $value, $order->get_data_store()->{"get_$prop_name"}( $order ), "Prop $prop_name was not set correctly." );
		}
	}

	/**
	 * Helper function to assert prop value via order object.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $props List of props and their getter names.
	 * @param mixed    $value value to assert.
	 */
	private function assert_props_value_via_order_object( $order, $props, $value ) {
		foreach ( $props as $meta_key_name => $prop_name ) {
			$this->assertEquals( $value, $order->{"get_$prop_name"}(), "Prop $prop_name was not set correctly." );
		}
	}

	/**
	 * @testDox Test that created and updated dates are backfilled correctly.
	 */
	public function test_create_update_date_gmt() {
		$order = new WC_Order();
		$this->switch_data_store( $order, $this->sut );
		$order->set_created_via( 'checkout' );
		$order->save();

		// 10s is to account for any flakiness.
		$this->assertTrue( 10 > absint( $order->get_date_created()->getTimestamp() - time() ), 'Order date created date is about the same as now.' );

		$this->sut->backfill_post_record( $order );

		$post = get_post( $order->get_id() );
		$this->assertEquals( $order->get_date_created()->format( 'Y-m-d H:i:s' ), $post->post_date );
		$created_date = $order->get_date_created();
		$created_date->setTimezone( new DateTimeZone( 'GMT' ) );
		$this->assertEquals( $created_date->format( 'Y-m-d H:i:s' ), $post->post_date_gmt );

		$order->set_status( 'completed' );
		$order->save();
		$this->sut->backfill_post_record( $order );

		// 10s is to account for any flakiness.
		$this->assertTrue( 10 > absint( $order->get_date_modified()->getTimestamp() - time() ), 'Order date modified date is about the same as now.' );

		$this->sut->backfill_post_record( $order );
		$post = get_post( $order->get_id() );

		$this->assertEquals( $order->get_date_modified()->format( 'Y-m-d H:i:s' ), $post->post_modified );
		$modified_date = $order->get_date_modified();
		$modified_date->setTimezone( new DateTimeZone( 'GMT' ) );
		$this->assertEquals( $modified_date->format( 'Y-m-d H:i:s' ), $post->post_modified_gmt );
	}

	/**
	 * @testDox Test that inserting with strict SQL mode is also supported.
	 */
	public function test_order_create_with_strict_mode_and_null_values() {
		global $wpdb;
		$this->toggle_cot_feature_and_usage( true );
		$sql_mode = $wpdb->get_var( 'SELECT @@sql_mode' );
		// Set SQL mode to strict to disallow 0 dates.
		$wpdb->query( "SET sql_mode = 'TRADITIONAL'" );

		$order = new WC_Order();
		$this->switch_data_store( $order, $this->sut );
		$order->save();

		$this->assertTrue( $order->get_id() > 0 );

		// Let's also repeat with sync off.
		$this->enable_cot_sync();
		$order = new WC_Order();
		$this->switch_data_store( $order, $this->sut );
		$order->save();

		$this->assertTrue( $order->get_id() > 0 );
		$order = wc_get_order( $order->get_id() );
		$post  = get_post( $order->get_id() );
		$this->assertEquals( $order->get_date_modified()->format( 'Y-m-d H:i:s' ), $post->post_date );

		// phpcs:ignore -- Hardcoded value.
		$wpdb->query( "SET sql_mode = '$sql_mode' " );
	}

	/**
	 * @testDox Test that multiple calls to read don't try to sync again.
	 */
	public function test_read_multiple_dont_sync_again_for_same_order() {
		$this->toggle_cot_feature_and_usage( true );
		$this->disable_cot_sync();
		$order = $this->create_complex_cot_order();
		$this->sut->backfill_post_record( $order );
		$this->enable_cot_sync();

		$order_id = $order->get_id();

		$should_sync_callable = function( $order ) {
			return $this->should_sync_order( $order );
		};

		$order = new WC_Order();
		$order->set_id( $order_id );
		$orders = array( $order_id => $order );
		$this->assertTrue( $should_sync_callable->call( $this->sut, $order ) );
		$this->sut->read_multiple( $orders );
		$this->assertFalse( $should_sync_callable->call( $this->sut, $order ) );
		$this->toggle_cot_feature_and_usage( false );
	}

	/**
	 * @testDox When parent order is deleted, and the post order type is hierarchical, child orders should be upshifted.
	 */
	public function test_child_orders_are_promoted_when_parent_is_deleted_if_order_type_is_hierarchical() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'is_post_type_hierarchical' => function( $post_type ) {
					return 'shop_order' === $post_type || is_post_type_hierarchical( $post_type );
				},
			)
		);

		$this->toggle_cot_feature_and_usage( true );
		$order = new WC_Order();
		$order->save();

		$child_order = new WC_Order();
		$child_order->set_parent_id( $order->get_id() );
		$child_order->save();

		$this->assertEquals( $order->get_id(), $child_order->get_parent_id() );
		$this->sut->delete( $order, array( 'force_delete' => true ) );
		$child_order = wc_get_order( $child_order->get_id() );

		$this->assertEquals( 0, $child_order->get_parent_id() );
	}

	/**
	 * @testDox When parent order is deleted, and the post order type is NOT hierarchical, child orders should be deleted.
	 */
	public function test_child_orders_are_promoted_when_parent_is_deleted_if_order_type_is_not_hierarchical() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'is_post_type_hierarchical' => function( $post_type ) {
					return 'shop_order' === $post_type ? false : is_post_type_hierarchical( $post_type );
				},
			)
		);

		$this->toggle_cot_feature_and_usage( true );
		$order = new WC_Order();
		$order->save();

		$child_order = new WC_Order();
		$child_order->set_parent_id( $order->get_id() );
		$child_order->save();

		$this->assertEquals( $order->get_id(), $child_order->get_parent_id() );
		$this->sut->delete( $order, array( 'force_delete' => true ) );
		$child_order = wc_get_order( $child_order->get_id() );

		$this->assertFalse( $child_order );
	}

	/**
	 * @testDox Make sure get_order return false when checking an order of different order types without warning.
	 */
	public function test_get_order_with_id_for_different_type() {
		$this->toggle_cot_feature_and_usage( true );
		$this->disable_cot_sync();
		$product = new \WC_Product();
		$product->save();
		$this->assertFalse( wc_get_order( $product->get_id() ) );
	}

	/**
	 * @testDox Make sure that getting order type for non order return without warning.
	 */
	public function test_get_order_type_for_non_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->save();
		$this->assertEquals( '', $this->sut->get_order_type( $product->get_id() ) );
	}

	/**
	 * @testDox Test get order type working as expected.
	 */
	public function test_get_order_type_for_order() {
		$order = $this->create_complex_cot_order();
		$this->assertEquals( 'shop_order', $this->sut->get_order_type( $order->get_id() ) );
	}

	/**
	 * @testDox Test that we are not duplicating address indexing when updating.
	 */
	public function test_address_index_saved_on_update() {
		global $wpdb;
		$this->toggle_cot_feature_and_usage( true );
		$this->disable_cot_sync();
		$order = new WC_Order();
		$order->set_billing_address_1( '123 Main St' );
		$order->save();

		$this->assertTrue( false !== strpos( $order->get_meta( '_billing_address_index', true ), '123 Main St' ) );
		$order = wc_get_order( $order->get_id() );
		$order->set_billing_address_2( 'Apt 1' );
		$order->save();

		$order_meta_table = $this->sut::get_meta_table_name();
		// Assert that we are not duplicating address indexes.
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$order_meta_table} WHERE order_id = %d AND meta_key = '_billing_address_index'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$order->get_id()
			)
		);

		$this->assertEquals( 1, $result );
	}

	/**
	 * @testdox When sync is enabled and an order is deleted, records in both the authoritative and the backup tables are deleted, and no deletion records are created.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 */
	public function test_order_deletion_with_sync_enabled( bool $cot_is_authoritative ) {
		$this->allow_current_user_to_delete_posts();
		$this->toggle_cot_feature_and_usage( true );
		$this->toggle_cot_authoritative( $cot_is_authoritative );
		$this->enable_cot_sync();

		list($order, $refund) = $this->create_order_with_refund();

		$order_id  = $order->get_id();
		$refund_id = $refund->get_id();

		$order->delete( true );

		$this->assert_no_order_records_or_deletion_records_exist( $order_id, $refund_id, $cot_is_authoritative );
	}

	/**
	 * @testdox Deletion records are created when an order is deleted with sync disabled, then when sync is enabled all order and deletion records are deleted.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 */
	public function test_order_deletion_with_sync_disabled( bool $cot_is_authoritative ) {
		$this->allow_current_user_to_delete_posts();
		$this->toggle_cot_feature_and_usage( true );
		$this->toggle_cot_authoritative( $cot_is_authoritative );
		$this->enable_cot_sync();

		list($order, $refund) = $this->create_order_with_refund();
		$order_id             = $order->get_id();
		$refund_id            = $refund->get_id();

		$this->disable_cot_sync();
		$order->delete( true );

		$this->assert_order_record_existence( $order_id, true, ! $cot_is_authoritative );
		$this->assert_order_record_existence( $order_id, false, $cot_is_authoritative );
		$this->assert_order_record_existence( $refund_id, true, ! $cot_is_authoritative );
		$this->assert_order_record_existence( $refund_id, false, $cot_is_authoritative );

		$this->assert_deletion_record_existence( $order_id, $cot_is_authoritative, true );
		$this->assert_deletion_record_existence( $refund_id, $cot_is_authoritative, true );

		$this->do_cot_sync();

		$this->assert_no_order_records_or_deletion_records_exist( $order_id, $refund_id, $cot_is_authoritative );
	}

	/**
	 * @testdox When the orders table is authoritative, sync is disabled and an order is deleted, existing placeholder records are deleted from the posts table.
	 */
	public function test_order_deletion_with_sync_disabled_when_placeholders_are_created() {
		$this->allow_current_user_to_delete_posts();
		$this->toggle_cot_feature_and_usage( true );
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();

		list($order, $refund) = $this->create_order_with_refund();
		$order_id             = $order->get_id();
		$refund_id            = $refund->get_id();

		$this->assert_order_record_existence( $order_id, true, true );
		$this->assert_order_record_existence( $order_id, false, true, 'shop_order_placehold' );
		$this->assert_order_record_existence( $refund_id, true, true );
		$this->assert_order_record_existence( $refund_id, false, true, 'shop_order_placehold' );

		$order->delete( true );

		$this->assert_no_order_records_or_deletion_records_exist( $order_id, $refund_id, false );
	}

	/**
	 * @testdox When deleting an order whose associated post type is hierarchical, child orders aren't deleted and get the parent id of their parent order.
	 */
	public function test_order_deletion_when_post_type_is_hierarchical_results_in_child_order_upshifting() {
		$this->reset_container_resolutions();
		$this->reset_legacy_proxy_mocks();
		$this->register_legacy_proxy_function_mocks(
			array(
				'is_post_type_hierarchical' => function( $post_type ) {
					return 'shop_order' === $post_type ? true : is_post_type_hierarchical( $post_type );
				},
			)
		);
		$this->sut = wc_get_container()->get( OrdersTableDataStore::class );

		$this->allow_current_user_to_delete_posts();
		$this->toggle_cot_feature_and_usage( true );
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();

		list($order, $refund) = $this->create_order_with_refund();
		$order_id             = $order->get_id();
		$refund_id            = $refund->get_id();

		$this->switch_data_store( $order, $this->sut );

		$order2    = OrderHelper::create_order();
		$order2_id = $order2->get_id();
		$order->set_parent_id( $order2_id );
		$order->save();

		$order->delete( true );

		$this->assert_order_record_existence( $order_id, true, false );
		$this->assert_order_record_existence( $refund_id, true, true );

		$refund = wc_get_order( $refund_id );
		$this->assertEquals( $order2_id, $refund->get_parent_id() );
	}

	/**
	 * Mock the current user capabilities so that it's allowed to delete posts.
	 *
	 * @return void
	 */
	private function allow_current_user_to_delete_posts() {
		$this->register_legacy_proxy_function_mocks(
			array(
				'current_user_can' => function( $capability ) {
					return 'delete_posts' === $capability ? true : current_user_can( $capability );
				},
			)
		);
	}

	/**
	 * Assert than no records exist whatsoever, and no deletion records either, for a given order and for its refund.
	 *
	 * @param int  $order_id The order id to test.
	 * @param int  $refund_id The refund id to test.
	 * @param bool $cot_is_authoritative True if the deletion record existence is to be checked for the orders table, false for the posts table.
	 * @return void
	 */
	private function assert_no_order_records_or_deletion_records_exist( $order_id, $refund_id, $cot_is_authoritative ) {
		$this->assert_order_record_existence( $order_id, true, false );
		$this->assert_order_record_existence( $order_id, false, false );
		$this->assert_order_record_existence( $refund_id, true, false );
		$this->assert_order_record_existence( $refund_id, false, false );

		$this->assert_deletion_record_existence( $order_id, $cot_is_authoritative, false );
		$this->assert_deletion_record_existence( $refund_id, $cot_is_authoritative, false );
	}

	/**
	 * Create an order and a refund.
	 *
	 * @return array An array containing the order as the first element and the refund as the second element.
	 */
	private function create_order_with_refund() {
		$order = OrderHelper::create_order();

		$item   = current( $order->get_items() )->get_data();
		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$item['id'] => array(
						'id'           => $item['id'],
						'qty'          => $item['quantity'],
						'refund_total' => $item['total'],
						'refund_tax'   => $item['total_tax'],
					),
				),
			)
		);
		$refund->save();

		return array( $order, $refund );
	}

	/**
	 * @testDox When saving an order, status is automatically prefixed even if it was not earlier.
	 */
	public function test_get_db_row_from_order_only_prefixed_status_is_written_to_db() {
		$order = wc_create_order();

		$order->set_status( 'completed' );
		$db_row_callback = function ( $order, $only_changes ) {
			return $this->get_db_row_from_order( $order, $this->order_column_mapping, $only_changes );
		};

		$db_row = $db_row_callback->call( $this->sut, $order, false );

		$this->assertEquals( 'wc-completed', $db_row['data']['status'] );
	}

	/**
	 * @testDox Test that orders token are stored in the correct meta table
	 */
	public function test_payment_token_stored_in_correct_table() {
		global $wpdb;
		$this->toggle_cot_feature_and_usage( true );
		$this->disable_cot_sync();
		$order = wc_create_order();
		$order->save();

		$token1 = WC_Helper_Payment_Token::create_eCheck_token();
		$token2 = WC_Helper_Payment_Token::create_eCheck_token();
		$order->add_payment_token( $token1 );
		$order->add_payment_token( $token2 );
		$order->save();

		$token_ids = $order->get_payment_tokens();
		$this->assertEquals( array( $token1->get_id(), $token2->get_id() ), $token_ids );

		$token_ids = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = '_payment_tokens'",
				$order->get_id()
			)
		);
		$token_ids = maybe_unserialize( $token_ids );
		$this->assertEquals( array( $token1->get_id(), $token2->get_id() ), $token_ids );
	}

	/**
	 * Before 7.9.0, payment tokens were stored in the post meta table. This test checks that we can read them anyway.
	 */
	public function test_payment_token_is_read_when_stored_in_post_meta() {
		global $wpdb;
		$this->toggle_cot_feature_and_usage( false );
		$order = wc_create_order();
		$order->save();

		$token1   = WC_Helper_Payment_Token::create_eCheck_token();
		$token2   = WC_Helper_Payment_Token::create_eCheck_token();
		$token_ar = array( $token1->get_id(), $token2->get_id() );
		update_post_meta( $order->get_id(), '_payment_tokens', $token_ar );
		$order->set_version( '7.9.0' );

		$token_ids = $order->get_payment_tokens();
		$this->assertEquals( $token_ar, $token_ids );
	}

	/**
	 * @testWith [true]
	 *           [false]
	 * @testDox An exception thrown while populating the properties of an order is captured and logged as a warning.
	 *
	 * @param bool $orders_authoritative Whether the orders table is authoritative or not.
	 */
	public function test_error_when_setting_order_property_is_captured_and_logged( bool $orders_authoritative ) {
		global $wpdb;

		$this->toggle_cot_feature_and_usage( $orders_authoritative );
		$this->disable_cot_sync();

		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $warnings = array();

			public function warning( $message, $data ) {
				$this->warnings[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}
		};
		// phpcs:enable Squiz.Commenting

		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function() use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		$order = new WC_Order();
		$order->save();
		$product_id = $order->add_product( WC_Helper_Product::create_simple_product(), 1 );
		$order->calculate_totals();
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'line_items' => array(
					$product_id => array(
						'id'           => $product_id,
						'qty'          => 1,
						'refund_total' => 1,
					),
				),
			)
		);
		$refund->save();

		$this->assertEquals( $order->get_id(), $refund->get_parent_id() );

		if ( $orders_authoritative ) {
			$wpdb->update(
				$this->sut::get_orders_table_name(),
				array( 'parent_order_id' => 999999 ),
				array( 'id' => $refund->get_id() ),
				array( 'parent_order_id' => '%d' ),
				array( 'id' => '%d' ),
			);
		} else {
			$wpdb->update(
				$wpdb->posts,
				array( 'post_parent' => 999999 ),
				array( 'ID' => $refund->get_id() ),
				array( 'post_parent' => '%d' ),
				array( 'ID' => '%d' ),
			);
		}

		$refund = wc_get_order( $refund->get_id() );

		$this->assertEquals( 0, $refund->get_parent_id() );
		$this->assertEquals( "Error when setting property 'parent_id' for order {$refund->get_id()}: Invalid parent ID", current( $fake_logger->warnings )['message'] );
	}

	/**
	 * @testDox A 'suppress_filters' argument can be passed to 'delete', if true no 'woocommerce_(before_)trash/delete_order' actions will be fired.
	 *
	 * @testWith [null, true]
	 *           [true, true]
	 *           [false, true]
	 *           [null, false]
	 *           [true, false]
	 *           [false, false]
	 *
	 * @param bool|null $suppress True or false to use a 'suppress_filters' argument with that value, null to not use it.
	 * @param bool      $force_delete True to delete the order, false to trash it.
	 * @return void
	 */
	public function test_filters_can_be_suppressed_when_trashing_or_deleting_an_order( ?bool $suppress, bool $force_delete ) {
		$order_id_from_before_delete = null;
		$order_id_from_after_delete  = null;
		$order_from_before_delete    = null;

		$this->toggle_cot_feature_and_usage( true );
		$this->disable_cot_sync();

		$trash_or_delete = $force_delete ? 'delete' : 'trash';

		add_action(
			"woocommerce_before_{$trash_or_delete}_order",
			function ( $order_id, $order ) use ( &$order_id_from_before_delete, &$order_from_before_delete ) {
				$order_id_from_before_delete = $order_id;
				$order_from_before_delete    = $order;
			},
			10,
			2
		);

		add_action(
			"woocommerce_{$trash_or_delete}_order",
			function ( $order_id ) use ( &$order_id_from_after_delete ) {
				$order_id_from_after_delete = $order_id;
			}
		);

		$args = array( 'force_delete' => $force_delete );
		if ( null !== $suppress ) {
			$args['suppress_filters'] = $suppress;
		}

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$this->sut->delete( $order, $args );

		if ( true === $suppress ) {
			$this->assertNull( $order_id_from_before_delete );
			$this->assertNull( $order_id_from_after_delete );
			$this->assertNull( $order_from_before_delete );
		} else {
			$this->assertEquals( $order_id, $order_id_from_before_delete );
			$this->assertEquals( $order_id, $order_id_from_after_delete );
			$this->assertSame( $order, $order_from_before_delete );
		}
	}

	/**
	 * @testDox Bacfilling posts doesn't read stale data.
	 */
	public function test_backfill_posts_dont_read_stale_data() {
		$this->toggle_cot_feature_and_usage( true );
		$this->enable_cot_sync();

		$product = new WC_Product();
		$product->set_price( 10 );
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product );
		$order->save();

		$this->assertEquals( 0, $product->get_total_sales() );

		$order->set_status( 'processing' );
		$order->save();
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 1, $product->get_total_sales() ); // Sale is increased when status is changed to processing.

		$order->set_status( 'completed' );
		$order->save();
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( 1, $product->get_total_sales() ); // Sale is not increased when status is changed to completed (from processing).
	}

	/**
	 * @testDox Test that adding meta, while in callback for adding another meta also works as expected.
	 */
	public function test_backfill_does_not_trigger_read_on_sync_with_filters() {
		$this->toggle_cot_feature_and_usage( true );
		$this->enable_cot_sync();

		add_filter( 'added_post_meta', array( $this, 'add_meta_when_meta_added' ), 10, 4 );

		$order = OrderHelper::create_order();
		$order->set_customer_id( 1 );
		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save();

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'test_value', $r_order->get_meta( 'test_key', true ) );
		$this->assertEquals( 'test_value_2', $r_order->get_meta( 'test_key_2', true ) );
		$this->assertEquals( 'test_value_3', $r_order->get_meta( 'test_key_3', true ) );
		remove_filter( 'added_post_meta', array( $this, 'add_meta_when_meta_added' ) );
	}

	/**
	 * Helper function to simulate adding meta withing a adding meta callback.
	 * @param int    $meta_id Meta ID.
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $meta_value Meta value.
	 */
	public function add_meta_when_meta_added( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( 'test_key' === $meta_key ) {
			$order = wc_get_order( $post_id );
			$order->add_meta_data( 'test_key_2', 'test_value_2' );
			$order->save_meta_data();
			$order->add_meta_data( 'test_key_3', 'test_value_3' );
			$order->save();
		}
	}

	/**
	 * @testDox When creating a new order, test that we are not backfilling stale data when there is a postmeta hooks that modifies data on the order.
	 */
	public function test_backfill_does_not_trigger_when_creating_orders_with_filter() {
		$this->toggle_cot_feature_and_usage( true );
		$this->enable_cot_sync();

		add_filter( 'added_post_meta', array( $this, 'add_meta_when_meta_added' ), 10, 4 );
		$order = new WC_Order();
		$order->set_customer_id( 1 );
		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save();

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'test_value', $r_order->get_meta( 'test_key', true ) );
		$this->assertEquals( 'test_value_2', $r_order->get_meta( 'test_key_2', true ) );
		$this->assertEquals( 'test_value_3', $r_order->get_meta( 'test_key_3', true ) );
		$this->assertEquals( 1, $r_order->get_customer_id() );
		remove_filter( 'added_post_meta', array( $this, 'add_meta_when_meta_added' ) );
	}

	/**
	 * @testDox When sync is enabled, order data can be saved and retrieved as expected.
	 */
	public function test_order_data_saved_correctly_with_sync() {
		$this->toggle_cot_feature_and_usage( true );
		$this->enable_cot_sync();

		$order = new WC_Order();
		$order->save();

		$order->set_customer_id( 1 ); // Change a custom table column.
		$order->set_billing_address_1( 'test' ); // Change an address column and a meta row.
		$order->set_download_permissions_granted( true ); // Change an operational data column.
		$order->add_meta_data( 'test_key', 'test_value' );

		$order->save();

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 1, $r_order->get_customer_id() );
		$this->assertEquals( 'test', $r_order->get_billing_address_1() );
		$this->assertTrue( $order->get_download_permissions_granted() );
		$this->assertEquals( 'test_value', $r_order->get_meta( 'test_key', true ) );
	}

	/**
	 * @testDox Checks that order new/updated hooks are fired at appropriate times in HPOS (vs CPT).
	 * @testWith [true]
	 *           [false]
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 */
	public function test_order_hooks_vs_cpt( $cot_is_authoritative = true ) {
		$this->toggle_cot_authoritative( $cot_is_authoritative );
		$this->disable_cot_sync();

		$new_count    = 0;
		$update_count = 0;

		$callback = function( $order_id ) use ( &$new_count, &$update_count ) {
			$new_count    += 'woocommerce_new_order' === current_action() ? 1 : 0;
			$update_count += 'woocommerce_update_order' === current_action() ? 1 : 0;
		};

		add_action( 'woocommerce_new_order', $callback );
		add_action( 'woocommerce_update_order', $callback );

		// Creating a new order should trigger 'woocommerce_new_order' but not 'woocommerce_update_order'.
		$order = new WC_Order();
		$order->save();
		$this->assertEquals( 1, $new_count );
		$this->assertEquals( 0, $update_count );

		// Saving an order again (with no changes) should still trigger 'woocommerce_update_order'.
		$order->save();
		$this->assertEquals( 1, $new_count );
		$this->assertEquals( 1, $update_count );

		// An update to the order should only trigger 'woocommerce_update_order'.
		$order->set_billing_city( 'Los Angeles' );
		$order->save();
		$this->assertEquals( 1, $new_count );
		$this->assertEquals( 2, $update_count );

		// Updating datastore-level props should not trigger anything.
		$order->get_data_store()->set_download_permissions_granted( $order->get_id(), true );
		$this->assertEquals( 1, $new_count );
		$this->assertEquals( 2, $update_count );

		// Trashing should not fire an update.
		$order->get_data_store()->delete( $order );
		$this->assertEquals( $order->get_status(), 'trash' );
		$this->assertEquals( 2, $update_count );

		// Untrashing should not fire an update.
		if ( $cot_is_authoritative ) {
			$order = wc_get_order( $order->get_id() ); // Refresh order.
			$order->get_data_store()->untrash_order( $order );
		} else {
			wp_untrash_post( $order->get_id() );
			$order = wc_get_order( $order->get_id() ); // Refresh order.
		}
		$this->assertNotEquals( $order->get_status(), 'trash' );
		$this->assertEquals( 2, $update_count );

		// An auto-draft order should not trigger 'woocommerce_new_order' until first saved with a valid status.
		if ( $cot_is_authoritative ) {
			$order = new WC_Order();
			$order->set_status( 'auto-draft' );
			$order->save();

			$this->assertEquals( 1, $new_count );
			$this->assertEquals( 2, $update_count );

			$order->set_status( 'on-hold' );
			$order->save();

			$this->assertEquals( 2, $new_count );
			$this->assertEquals( 2, $update_count );
		}

		remove_action( 'woocommerce_new_order', $callback );
		remove_action( 'woocommerce_update_order', $callback );
	}

	/**
	 * @testDox Stale data is not read when sync is off, but then switched on again after a while.
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $different_request Whether to simulate different requests (as much as we can in a unit test).
	 */
	public function test_stale_data_is_not_read_sync_off_on( $different_request ) {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		$cot_store = wc_get_container()->get( OrdersTableDataStore::class );

		$order = OrderHelper::create_order();
		$order->set_customer_id( 1 ); // Change a custom table column.
		$order->set_billing_address_1( 'test' ); // Change an address column and a meta row.
		$order->set_download_permissions_granted( true ); // Change an operational data column.
		$order->save();

		$different_request && $this->reset_order_data_store_state( $cot_store );

		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save_meta_data();

		$different_request && $this->reset_order_data_store_state( $cot_store );

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 1, $r_order->get_customer_id() );
		$this->assertEquals( 'test', $r_order->get_billing_address_1() );
		$this->assertTrue( $order->get_download_permissions_granted() );
		$this->assertEquals( 'test_value', $r_order->get_meta( 'test_key', true ) );

		$different_request && $this->reset_order_data_store_state( $cot_store );
		sleep( 2 );

		$this->disable_cot_sync();
		$r_order->update_meta_data( 'test_key', 'test_value_updated' );
		$r_order->add_meta_data( 'test_key2', 'test_value2' );
		$r_order->save_meta_data();

		$different_request && $this->reset_order_data_store_state( $cot_store );

		$this->enable_cot_sync();
		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 1, $r_order->get_customer_id() );
		$this->assertEquals( 'test', $r_order->get_billing_address_1() );
		$this->assertTrue( $order->get_download_permissions_granted() );
		$this->assertEquals( 'test_value_updated', $r_order->get_meta( 'test_key', true ) );
		$this->assertEquals( 'test_value2', $r_order->get_meta( 'test_key2', true ) );
	}

	/**
	 * Helper method to reset order data store state (to help simulate multiple requests).
	 *
	 * @param OrdersTableDataStore $sut System under test.
	 */
	private function reset_order_data_store_state( $sut ) {
		$reset_state = function () use ( $sut ) {
			self::$backfilling_order_ids = array();
			self::$reading_order_ids     = array();
		};
		$reset_state->call( $sut );
		wp_cache_flush();
	}

	/**
	 * @testDox Test that we can delete metadata just by sending the meta ID.
	 */
	public function test_allow_deleting_meta_with_id_only() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		$order = OrderHelper::create_order();
		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save();

		$meta_data = $this->sut->read_meta( $order );

		foreach ( $meta_data as $meta ) {
			$this->sut->delete_meta( $order, (object) array( 'id' => $meta->meta_id ) );
		}

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEmpty( $r_order->get_meta_data() );
		$this->assertEquals( '', get_post_meta( $order->get_id(), 'test_key', true ) );
	}

	/**
	 * @testDox Test that the protected method get_order_data_for_ids stays backward compatible.
	 */
	public function test_get_order_data_for_ids_is_back_compat() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();
		$order = OrderHelper::create_complex_data_store_order( $this->sut );
		$order->set_date_paid( new \WC_DateTime( '2023-01-01 00:00:00' ) );
		$order->set_date_completed( new \WC_DateTime( '2023-01-01 00:00:00' ) );
		$order->set_cart_tax( '1.23' );
		$order->set_customer_id( 1 );
		$order->set_shipping_address_1( 'Line1 Shipping' );
		$order->set_shipping_city( 'City Shipping' );
		$order->set_shipping_postcode( '12345' );
		$order->set_shipping_tax( '12.34' );
		$order->set_shipping_total( '123.45' );
		$order->set_total( '25' );
		$order->set_discount_tax( '2.111' );
		$order->set_discount_total( '1.23' );
		$order->save();

		$call_protected = function( $ids ) {
			return $this->get_order_data_for_ids( $ids );
		};

		$order_data = $call_protected->call( $this->sut, array( $order->get_id() ) );

		$expected_data_array = array(
			'id'                           => $order->get_id(),
			'status'                       => 'wc-' . $order->get_status(),
			'type'                         => $order->get_type(),
			'currency'                     => $order->get_currency(),
			'cart_tax'                     => '1.23000000',
			'total'                        => '25.00000000',
			'customer_id'                  => $order->get_customer_id(),
			'billing_email'                => $order->get_billing_email(),
			'date_created'                 => gmdate( 'Y-m-d H:i:s', $order->get_date_created()->format( 'U' ) ),
			'date_modified'                => gmdate( 'Y-m-d H:i:s', $order->get_date_modified()->format( 'U' ) ),
			'parent_id'                    => $order->get_parent_id(),
			'payment_method'               => $order->get_payment_method(),
			'payment_method_title'         => $order->get_payment_method_title(),
			'customer_ip_address'          => $order->get_customer_ip_address(),
			'transaction_id'               => $order->get_transaction_id(),
			'customer_user_agent'          => $order->get_customer_user_agent(),
			'customer_note'                => $order->get_customer_note(),
			'billing_first_name'           => $order->get_billing_first_name(),
			'billing_last_name'            => $order->get_billing_last_name(),
			'billing_company'              => $order->get_billing_company(),
			'billing_address_1'            => $order->get_billing_address_1(),
			'billing_address_2'            => $order->get_billing_address_2(),
			'billing_city'                 => $order->get_billing_city(),
			'billing_state'                => $order->get_billing_state(),
			'billing_postcode'             => $order->get_billing_postcode(),
			'billing_country'              => $order->get_billing_country(),
			'billing_phone'                => $order->get_billing_phone(),
			'shipping_first_name'          => $order->get_shipping_first_name(),
			'shipping_last_name'           => $order->get_shipping_last_name(),
			'shipping_company'             => $order->get_shipping_company(),
			'shipping_address_1'           => $order->get_shipping_address_1(),
			'shipping_address_2'           => $order->get_shipping_address_2(),
			'shipping_city'                => $order->get_shipping_city(),
			'shipping_state'               => $order->get_shipping_state(),
			'shipping_postcode'            => $order->get_shipping_postcode(),
			'shipping_country'             => $order->get_shipping_country(),
			'shipping_phone'               => $order->get_shipping_phone(),
			'created_via'                  => $order->get_created_via(),
			'version'                      => $order->get_version(),
			'prices_include_tax'           => $order->get_prices_include_tax(),
			'recorded_coupon_usage_counts' => $order->get_recorded_coupon_usage_counts(),
			'download_permissions_granted' => $order->get_download_permissions_granted(),
			'cart_hash'                    => $order->get_cart_hash(),
			'new_order_email_sent'         => $order->get_new_order_email_sent(),
			'order_key'                    => $order->get_order_key(),
			'order_stock_reduced'          => $order->get_order_stock_reduced(),
			'date_paid'                    => gmdate( 'Y-m-d H:i:s', $order->get_date_paid()->format( 'U' ) ),
			'date_completed'               => gmdate( 'Y-m-d H:i:s', $order->get_date_completed()->format( 'U' ) ),
			'shipping_tax'                 => '12.34000000',
			'shipping_total'               => '123.45000000',
			'discount_tax'                 => '2.11100000',
			'discount_total'               => '1.23000000',
			'recorded_sales'               => $order->get_recorded_sales(),
		);

		foreach ( $expected_data_array as $key => $value ) {
			$this->assertEquals( $value, $order_data[ $order->get_id() ]->{$key}, "Unable to match $key for {$order_data[ $order->get_id() ]->{$key}}. Expected $value" );
		}
	}

	/**
	 * @testDox Test that duplicate key, value pairs are also synced properly.
	 */
	public function test_duplicate_meta_is_synced_properly() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		$order = WC_Helper_Order::create_order();
		// Force an earlier modified date to trigger the `should_save_after_meta_change` to be true later in the test.
		$order->set_date_modified( gmdate( 'Y-m-d H:i:s', strtotime( '-2 day' ) ) );
		$current_date_time = new \WC_DateTime( current_time( 'mysql', 1 ), new \DateTimeZone( 'GMT' ) );
		assert( $order->get_date_modified() < $current_date_time ); // Meta check, to make sure our test stay effective if we run testsuite in a different timezone.
		$order->save();

		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save_meta_data();

		$post_meta = get_post_meta( $order->get_id(), 'test_key' );
		$this->assertCount( 1, $post_meta );
		$this->assertEquals( 'test_value', $post_meta[0] );

		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save_meta_data();

		$post_meta = get_post_meta( $order->get_id(), 'test_key' );
		$this->assertCount( 2, $post_meta );
		$this->assertEquals( 'test_value', $post_meta[0] );
		$this->assertEquals( 'test_value', $post_meta[1] );

		$order->add_meta_data( 'test_key', 'test_value2' );
		$order->save_meta_data();

		$post_meta = get_post_meta( $order->get_id(), 'test_key' );
		$this->assertCount( 3, $post_meta );
		$this->assertTrue( in_array( 'test_value2', $post_meta, true ) );

		$order->set_date_modified( gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) ) );
		$order->save();
		$order->update_meta_data( 'test_key', 'test_value3' );
		$order->save_meta_data();

		/**
		 * Note that WC_Data has a bug where if we update a duplicate key, it will delete rest of other keys. But since this bug has been there for 5 years as of writing this code, we will not assert whether postmeta is exactly the same as order_meta but only test that out value was saved.
		 */
		$post_meta = get_post_meta( $order->get_id(), 'test_key' );
		$this->assertTrue( in_array( 'test_value3', $post_meta, true ) );

		$order->set_date_modified( gmdate( 'Y-m-d H:i:s', strtotime( '-2 day' ) ) );
		$order->save();

		add_post_meta( $order->get_id(), 'test_key2', 'test_value5' );
		$order->add_meta_data( 'test_key2', 'test_value4' );
		$order->save();

		$post_meta = get_post_meta( $order->get_id(), 'test_key2' );
		$this->assertTrue( in_array( 'test_value4', $post_meta, true ) );
		$this->assertFalse( in_array( 'test_value5', $post_meta, true ) );
	}

	/**
	 * @testDox Test that date queries correctly handle timezones.
	 */
	public function test_timezone_date_query_support() {
		$order = new WC_Order();
		$order->set_date_created( '2023-09-01T00:30:00' ); // This would be 2023-08-31T18:00:00 UTC given the current timezone.
		$this->sut->create( $order );

		$query = new OrdersTableQuery( array( 'date_created_gmt' => '2023-09-01' ) );
		$this->assertEquals( 0, count( $query->orders ) ); // Should not return anything as the order was created on 2023-08-31 UTC.

		$query = new OrdersTableQuery( array( 'date_created_gmt' => '2023-08-31' ) );
		$this->assertEquals( 1, count( $query->orders ) );

		$query = new OrdersTableQuery( array( 'date_created_gmt' => '<=2023-09-01' ) );
		$this->assertEquals( 1, count( $query->orders ) );

		$query = new OrdersTableQuery( array( 'date_created' => '2023-08-31' ) );
		$this->assertEquals( 0, count( $query->orders ) );

		$query = new OrdersTableQuery( array( 'date_created' => '2023-09-01' ) );
		$this->assertEquals( 1, count( $query->orders ) );

		$query = new OrdersTableQuery( array( 'date_created' => '>2023-09-01' ) );
		$this->assertEquals( 0, count( $query->orders ) );

		$query = new OrdersTableQuery( array( 'date_created' => '<2023-09-01' ) );
		$this->assertEquals( 0, count( $query->orders ) );
	}

	/**
	 * @testDox Hooking into woocommerce_delete_shop_order_transients does not cause data loss.
	 */
	public function test_data_retained_when_hooked_in_cache_filter() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		add_action(
			'woocommerce_delete_shop_order_transients',
			function ( $order_id ) {
				wc_get_order( $order_id );
			}
		);
		$order = OrderHelper::create_order();

		$this->assertEquals( 1, $order->get_customer_id() );

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 1, $r_order->get_customer_id() );

		$this->reset_order_data_store_state( wc_get_container()->get( OrdersTableDataStore::class ) );
		$order->set_customer_id( 2 );
		$order->save();

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 2, $r_order->get_customer_id() );

		remove_all_actions( 'woocommerce_delete_shop_order_transients' );
	}

	/**
	 * @testDox Cache is cleared when order meta is saved.
	 */
	public function test_order_cache_is_cleared_on_meta_save() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		$order = OrderHelper::create_order();

		// set the cache.
		wc_get_order( $order->get_id() );

		$order->add_meta_data( 'test_key', 'test_value' );
		$order->save_meta_data();

		$r_order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'test_value', $r_order->get_meta( 'test_key' ) );
	}

	/**
	 * @testDox Test that order save is not called frequently on meta save.
	 */
	public function test_order_save_is_not_called_frequently_on_meta_save() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		$orders_table_data_store = fn() => wc_get_container()->get( OrdersTableDataStore::class );
		add_filter( 'woocommerce_order_data_store', $orders_table_data_store, 1000, 0 );

		$order = wc_create_order();

		assert( empty( $order->get_changes() ), 'Order was not saved properly, test cannot continue.' );
		$call_private = function ( $order ) {
			return $this->should_save_after_meta_change( $order );
		};

		$order->set_date_modified( time() - 1 );
		$order->save();

		$this->assertTrue( $call_private->call( $this->sut, $order ) );

		// count the calls to the filter to ensure it's called only once.
		$count = 0;
		add_filter(
			'woocommerce_before_order_object_save',
			function () use ( &$count ) {
				$count++;
			}
		);

		/**
		 * fix for previously flaky test:
		 * freeze time to ensure less than a second passes while the following saves happen.
		 */
		$current_time_called = false;
		$datetime            = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$now                 = $datetime->format( 'Y-m-d H:i:s' );

		$this->register_legacy_proxy_function_mocks(
			array(
				'current_time' => function( $type, $gmt ) use ( &$current_time_called, $now ) {
					$current_time_called = true;
					return $now;
				},
			)
		);

		$order->add_meta_data( 'key1', 'value' );
		$order->save_meta_data();
		$order->add_meta_data( 'key2', 'value' );
		$order->save_meta_data();
		$order->add_meta_data( 'key2', 'value2' );
		$order->save_meta_data();
		$order->update_meta_data( 'key1', 'value2' );
		$order->save_meta_data();
		$order->delete_meta_data( 'key1' );
		$order->save_meta_data();

		$this->assertEquals( 1, $count );
		$this->assertTrue( $current_time_called, 'current_time mock was not called' );

		remove_filter( 'woocommerce_order_data_store', $orders_table_data_store, 1000 );
		remove_all_actions( 'woocommerce_before_order_object_save' );
	}

	/**
	 * @testDox Test webhooks are not fired multiple times on order save.
	 */
	public function test_order_updated_webhook_delivered_once() {
		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();

		$webhook_tests = new WC_Tests_Webhook_Functions();
		$webhook       = $webhook_tests->create_webhook( 'order.updated' );

		$order = WC_Helper_Order::create_order();
		$order->set_date_modified( time() - 100 );
		$order->save();

		$this->assertTrue( wc_load_webhooks( 'active' ) );
		add_action( 'woocommerce_webhook_process_delivery', array( $webhook_tests, 'woocommerce_webhook_process_delivery' ), 1, 2 );
		add_filter( 'woocommerce_webhook_should_deliver', '__return_true' );
		$call_private = function ( $order ) {
			return $this->should_save_after_meta_change( $order );
		};
		$this->assertTrue( $call_private->call( $this->sut, $order ) );
		$order->add_meta_data( 'test', 'value' );
		$order->save_meta_data();
		$order->add_meta_data( 'key2', 'value' );
		$order->save_meta_data();
		$order->add_meta_data( 'key2', 'value2' );
		$order->save_meta_data();
		$order->update_meta_data( 'key1', 'value2' );
		$order->save_meta_data();
		$order->delete_meta_data( 'key1' );
		$order->save_meta_data();
		$this->assertEquals( 1, $webhook_tests->delivery_counter[ $webhook->get_id() . $order->get_id() ] );
		remove_all_actions( 'woocommerce_webhook_process_delivery' );
		remove_all_actions( 'woocommerce_webhook_should_deliver' );
	}

	/**
	 * @testDox Check that functions in the datastore correctly hold and release coupons from the order.
	 */
	public function test_datastore_coupon_methods() {
		$this->toggle_cot_authoritative( true );

		$coupon = new \WC_Coupon();
		$coupon->set_code( '10off' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10.0 );
		$coupon->set_usage_limit_per_user( 2 );
		$coupon->save();

		$product = WC_Helper_Product::create_simple_product( true );

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );

		$this->assertEquals( 0, $coupon->get_data_store()->get_usage_by_email( $coupon, 'user@woo.test' ) );

		$order_id = WC()->checkout->create_order(
			array(
				'billing_email'  => 'user@woo.test',
				'payment_method' => 'dummy',
			)
		);

		$this->assertEquals( 1, $coupon->get_data_store()->get_tentative_usages_for_user( $coupon->get_id(), array( 'user@woo.test' ) ) );
		$this->assertEquals( 1, $coupon->get_data_store()->get_usage_by_email( $coupon, 'user@woo.test' ) );

		wc_get_order( $order_id )->payment_complete();

		$this->assertEquals( 0, $coupon->get_data_store()->get_tentative_usages_for_user( $coupon->get_id(), array( 'user@woo.test' ) ) );
		$this->assertEquals( 1, $coupon->get_data_store()->get_usage_by_email( $coupon, 'user@woo.test' ) );

		// Load a fresh copy of the coupon and make sure things look ok.
		$coupon = new \WC_Coupon( $coupon->get_id() );
		$this->assertContains( 'user@woo.test', $coupon->get_used_by( 'edit' ) );
	}

	/**
	 * Tests that changes to certain keys don't trigger full order updates.
	 */
	public function test_ephemeral_meta_updates() {
		$this->toggle_cot_authoritative( true );

		// Set order in the past so that we can accurately compare dates.
		$order = WC_Helper_Order::create_order();
		$order->set_date_modified( time() - DAY_IN_SECONDS );
		$order->save();

		$date_modified = $order->get_date_modified();
		$order->update_meta_data( '_edit_lock', 'whatever' );
		$order->save_meta_data();
		$this->assertEquals( $date_modified, $order->get_date_modified() );

		$order->update_meta_data( 'other_meta', 'whatever' );
		$order->save_meta_data();
		$this->assertNotEquals( $date_modified, $order->get_date_modified() );
	}

	/**
	 * Tests that unserializing meta that contains a non-existent class doesn't cause a fatal error.
	 */
	public function test_unserialize_meta_with_nonexistent_class() {
		global $wpdb;
		$meta_key   = 'test_unserialize_meta_with_nonexistent_class';
		$meta_value = 'O:11:"geoiprecord":14:{s:12:"country_code";s:2:"BE";s:13:"country_code3";s:3:"BEL";s:12:"country_name";s:7:"Belgium";s:6:"region";s:3:"BRU";s:4:"city";s:8:"Brussels";s:11:"postal_code";s:4:"1000";s:8:"latitude";d:50.833300000000001;s:9:"longitude";d:4.3333000000000004;s:9:"area_code";N;s:8:"dma_code";N;s:10:"metro_code";N;s:14:"continent_code";s:2:"EU";s:11:"region_name";s:16:"Brussels Capital";s:8:"timezone";s:15:"Europe/Brussels";}}';

		// Create a fake logger to capture log entries.
		// phpcs:disable Squiz.Commenting
		$fake_logger = new class() {
			public $warnings = array();

			public function warning( $message, $data = array() ) {
				$this->warnings[] = array(
					'message' => $message,
					'data'    => $data,
				);
			}
		};
		// phpcs:enable Squiz.Commenting
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function() use ( $fake_logger ) {
					return $fake_logger;
				},
			)
		);

		$this->toggle_cot_authoritative( true );
		$this->enable_cot_sync();
		$order_meta_table = $this->sut::get_meta_table_name();

		$order = WC_Helper_Order::create_order();
		$order->save();
		$order_id = $order->get_id();

		$wpdb->query( "INSERT INTO {$order_meta_table} (order_id, meta_key, meta_value) VALUES ({$order_id}, '{$meta_key}', '{$meta_value}')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$order->set_date_modified( time() + 1 );
		$order->save();

		// Test fetching an order with meta data containing an object of a non-existent class.
		$fetched_order = wc_get_order( $order->get_id() );
		$meta          = $fetched_order->get_meta( $meta_key );

		$this->assertNotEmpty( $meta );
		$this->assertEquals( 'object', gettype( $meta ) );
		$this->assertEquals( '__PHP_Incomplete_Class', get_class( $meta ) );

		$meta_object_vars = get_object_vars( $meta );
		$this->assertEquals( 'geoiprecord', $meta_object_vars['__PHP_Incomplete_Class_Name'] );
		$this->assertEquals( 'Belgium', $meta_object_vars['country_name'] );
		$this->assertEquals( 'Brussels', $meta_object_vars['city'] );
		$this->assertEquals( 'Europe/Brussels', $meta_object_vars['timezone'] );

		// Check that the log entry was created.
		$this->assertEquals( 'encountered an order meta value of type __PHP_Incomplete_Class during `update_order_meta_from_object` in order with ID ' . $order->get_id() . ': "\'O:11:"geoiprecord":14:{s:12:"country_code";s:2:"BE";s:13:"country_code3";s:3:"BEL";s:12:"country_name";s:7:"Belgium";s:6:"region";s:3:"BRU";s:4:"city";s:8:"Brussels";s:11:"postal_code";s:4:"1000";s:8:"latitude";d:50.8333;s:9:"longitude";d:4.3333;s:9:"area_code";N;s:8:"dma_code";N;s:10:"metro_code";N;s:14:"continent_code";s:2:"EU";s:11:"region_name";s:16:"Brussels Capital";s:8:"timezone";s:15:"Europe/Brussels";}\'"', end( $fake_logger->warnings )['message'] );

		// Test deleting meta data containing an object of a non-existent class.
		$meta_data = $this->sut->read_meta( $order );
		foreach ( $meta_data as $meta ) {
			$this->sut->delete_meta( $order, (object) array( 'id' => $meta->meta_id ) );
		}
		$fetched_order = wc_get_order( $order->get_id() );

		$this->assertEmpty( $fetched_order->get_meta_data() );
		$this->assertEquals( '', get_post_meta( $order->get_id(), $meta_key, true ) );

		// Check that the log entry was created.
		$this->assertEquals( 'encountered an order meta value of type __PHP_Incomplete_Class during `delete_meta` in order with ID ' . $order->get_id() . ': "\'O:11:"geoiprecord":14:{s:12:"country_code";s:2:"BE";s:13:"country_code3";s:3:"BEL";s:12:"country_name";s:7:"Belgium";s:6:"region";s:3:"BRU";s:4:"city";s:8:"Brussels";s:11:"postal_code";s:4:"1000";s:8:"latitude";d:50.8333;s:9:"longitude";d:4.3333;s:9:"area_code";N;s:8:"dma_code";N;s:10:"metro_code";N;s:14:"continent_code";s:2:"EU";s:11:"region_name";s:16:"Brussels Capital";s:8:"timezone";s:15:"Europe/Brussels";}\'"', end( $fake_logger->warnings )['message'] );
	}

}
