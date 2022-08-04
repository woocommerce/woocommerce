<?php

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableQuery;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrdersTableDataStoreTests.
 *
 * Test or OrdersTableDataStore class.
 */
class OrdersTableDataStoreTests extends WC_Unit_Test_Case {

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
	 * Initializes system under test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		OrderHelper::delete_order_custom_tables(); // We need this since non-temporary tables won't drop automatically.
		OrderHelper::create_order_custom_table_if_not_exist();
		$this->sut            = wc_get_container()->get( OrdersTableDataStore::class );
		$this->migrator       = wc_get_container()->get( PostsToOrdersMigrationController::class );
		$this->cpt_data_store = new WC_Order_Data_Store_CPT();
	}

	/**
	 * Destroys system under test.
	 */
	public function tearDown(): void {
		// Add back removed filter.
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		$this->toggle_cot( false );

		parent::tearDown();
	}

	/**
	 * Test reading from migrated post order.
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
	 * Test whether backfill_post_record works as expected.
	 */
	public function test_backfill_post_record() {
		$post_order_id = OrderHelper::create_complex_wp_post_order();
		$this->migrator->migrate_orders( array( $post_order_id ) );

		$post_data      = get_post( $post_order_id, ARRAY_A );
		$post_meta_data = get_post_meta( $post_order_id );
		// TODO: Remove `_recorded_sales` from exempted keys after https://github.com/woocommerce/woocommerce/issues/32843.
		$exempted_keys         = array( 'post_modified', 'post_modified_gmt', '_recorded_sales' );
		$convert_to_float_keys = array( '_cart_discount_tax', '_order_shipping', '_order_shipping_tax', '_order_tax', '_cart_discount', 'cart_tax' );
		$exempted_keys         = array_flip( array_merge( $exempted_keys, $convert_to_float_keys ) );

		$post_data_float      = array_intersect_key( $post_data, array_flip( $convert_to_float_keys ) );
		$post_meta_data_float = array_intersect_key( $post_meta_data, array_flip( $convert_to_float_keys ) );
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
	}

	/**
	 * Tests update() on the COT datastore.
	 */
	public function test_cot_datastore_update() {
		static $props_to_update   = array(
			'billing_first_name' => 'John',
			'billing_last_name'  => 'Doe',
			'shipping_phone'     => '555-55-55',
			'status'             => 'on-hold',
			'cart_hash'          => 'YET-ANOTHER-CART-HASH',
		);
		static $datastore_updates = array(
			'email_sent' => true,
		);
		static $meta_to_update    = array(
			'my_meta_key' => array( 'my', 'custom', 'meta' ),
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
			$this->assertEquals( $this->sut->{"get_$prop"}( $order ), $value );
		}
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
	 * Tests create() on the COT datastore.
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
		);

		foreach ( $props_to_compare as $prop ) {
			$this->assertEquals( $order->{"get_$prop"}( 'edit' ), $r_order->{"get_$prop"}( 'edit' ) );
		}

		$this->assertEquals( $order->get_meta( 'my_meta', true, 'edit' ), $r_order->get_meta( 'my_meta', true, 'edit' ) );
		$this->assertEquals( $this->sut->get_stock_reduced( $order ), $this->sut->get_stock_reduced( $r_order ) );
	}

	/**
	 * Tests creation of full vs placeholder records in the posts table when creating orders in the COT datastore.
	 *
	 * @return void
	 */
	public function test_cot_datastore_create_sync() {
		global $wpdb;

		// Sync enabled implies a full post should be created.
		add_filter(
			'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
			function() {
				return 'yes';
			}
		);
		$order = $this->create_complex_cot_order();
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s", $order->get_id(), 'shop_order' ) ) );

		// Sync disabled implies a placeholder post should be created.
		add_filter(
			'pre_option_' . DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION,
			function() {
				return 'no';
			}
		);
		$order = $this->create_complex_cot_order();
		$this->assertEquals( 1, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE ID = %d AND post_type = %s", $order->get_id(), DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE ) ) );
	}

	/**
	 * Tests the `delete()` method on the COT datastore -- trashing.
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
	 * Tests the `delete()` method on the COT datastore -- full deletes.
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
	 * Tests the `OrdersTableQuery` class on the COT datastore.
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
	 * Tests meta queries in the `OrdersTableQuery` class.
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
	 * Tests queries involving the 'customer' query var.
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
	 * Tests queries involving 'date_query'.
	 *
	 * @return void
	 */
	public function test_cot_query_date_query() {
		// Hardcode a day so that we don't go over to a different month or year by adding/substracting hours and days.
		$now    = strtotime( '2022-06-04 10:00:00' );
		$deltas = array(
			-DAY_IN_SECONDS,
			-HOUR_IN_SECONDS,
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
	 * Tests REST API responses backed by the COT datastore.
	 */
	public function test_rest_api_orders_get() {
		// Authenticate as admin.
		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator'
				)
			)
		);

		// Enable COT.
		$this->toggle_cot( true );

		$time_before_orders = time();

		// Create a few orders for testing.
		$order_ids = array();
		foreach ( range( 1, 5 ) as $i ) {
			$order = new \WC_Order();
			$order->save();

			$order_ids[] = $order->get_id();
		}

		$time_after_orders = time() + HOUR_IN_SECONDS;

		// Make sure GET /wc/v3/orders includes the orders above.
		$request = new \WP_REST_Request( 'GET', '/wc/v3/orders' );
		$request->set_param( 'dates_are_gmt', 1 );

		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $response->get_data() );

		// Test with date fields.
		$request->set_param( 'before', gmdate( DateTime::ATOM, $time_before_orders ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response->get_data() );

		$request->set_param( 'before', gmdate( DateTime::ATOM, $time_after_orders ) );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 5, $response->get_data() );

		// GET one of the orders.
		$order_id = array_rand( array_flip( $order_ids ) );
		$request  = new \WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id );
		$response = rest_do_request( $request );
		$this->assertEquals( 200, $response->get_status() );

		// Make sure the order data "looks" correct by testing some of the keys.
		$this->assertArrayHasKey( 'id', $response->get_data() );
		$this->assertArrayHasKey( 'status', $response->get_data() );
		$this->assertArrayHasKey( 'currency', $response->get_data() );
		$this->assertArrayHasKey( 'billing', $response->get_data() );
	}

	/**
	 * Tests that REST API responses backed by the COT datastore match the responses from the CPT implementation.
	 */
	public function test_rest_api_orders_cpt() {
		// Authenticate as admin.
		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator'
				)
			)
		);

		$order_id = OrderHelper::create_complex_wp_post_order();
		$this->migrator->migrate_orders( array( $order_id ) );

		$request = new \WP_REST_Request( 'GET', '/wc/v3/orders/' . $order_id );

		$response_cpt = rest_do_request( $request );
		$this->assertEquals( 200, $response_cpt->get_status() );
		$response_cpt_data = $response_cpt->get_data();

		// Enable COT.
		$this->toggle_cot( true );

		$response_cot = rest_do_request( $request );
		$this->assertEquals( 200, $response_cot->get_status() );
		$response_cot_data = $response_cot->get_data();

		$this->assertEmpty( array_diff_key( $response_cpt_data, $response_cot_data ) );
		$this->assertEmpty( array_diff_key( $response_cot_data, $response_cpt_data ) );
		$this->assertEquals( count( $response_cpt_data['meta_data'] ), count( $response_cot_data['meta_data'] ) );
		$this->assertTrue( $response_cpt_data == $response_cot_data ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
	}

	/**
	 * Tests REST API responses backed by the COT datastore.
	 */
	public function test_rest_api_orders_create() {
		// Authenticate as admin.
		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator'
				)
			)
		);

		$product                  = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$order_params             = array(
			'payment_method'       => 'bacs',
			'payment_method_title' => 'Direct Bank Transfer',
			'set_paid'             => true,
			'billing'              => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
				'email'      => 'john.doe@example.com',
				'phone'      => '(555) 555-5555',
			),
			'line_items'           => array(
				array(
					'product_id' => $product->get_id(),
					'quantity'   => 3,
				),
			),
		);
		$order_params['shipping'] = $order_params['billing'];

		// Enable COT.
		$this->toggle_cot( true );

		$request = new \WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params( $order_params );

		$response = rest_do_request( $request );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'processing', $data['status'] );

		// Fetch the order and compare some data.
		$order = wc_get_order( $data['id'] );
		$this->assertNotEmpty( $order );

		$this->assertEquals( (float) ( $product->get_price() * 3 ), (float) $order->get_total() );
		$this->assertEquals( $order_params['payment_method'], $order->get_payment_method( 'edit' ) );

		foreach ( array_keys( $order_params['billing'] ) as $address_key ) {
			$this->assertEquals( $order_params['billing'][ $address_key ], $order->{"get_billing_{$address_key}"}( 'edit' ) );
		}
	}

	/**
	 * Tests REST API responses backed by the COT datastore.
	 */
	public function test_rest_api_orders_delete() {
		// Authenticate as admin.
		wp_set_current_user(
			$this->factory->user->create(
				array(
					'role' => 'administrator'
				)
			)
		);

		// Enable COT.
		$this->toggle_cot( true );

		$order = new \WC_Order();
		$order->set_status( 'completed' );
		$order->save();

		$request  = new \WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order->get_id() );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( $data['id'], $order->get_id() );
		$this->assertEquals( 'completed', $data['status'] ); // Check that the previous status was returned.

		// Check the order was actually deleted.
		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'trash', $order->get_status( 'edit' ) );
	}

	/**
	 * Enables or disables the custom orders table across WP temporarily.
	 *
	 * @param boolean $enabled TRUE to enable COT or FALSE to disable.
	 * @return void
	 */
	private function toggle_cot( bool $enabled ): void {
		$controller = wc_get_container()->get( CustomOrdersTableController::class )->show_feature();
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, wc_bool_to_string( $enabled ) );

		// Confirm things are really correct.
		$wc_data_store = WC_Data_Store::load( 'order' );
		if ( $enabled !== is_a( $wc_data_store->get_current_class_name(), OrdersTableDataStore::class, true ) ) {
			$this->fail( __( 'Order data store could not be changed to the correct type.', 'woocommerce' ) );
		}
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
		$update_data_store_func = function ( $data_store ) {
			$this->data_store = $data_store;
		};
		$update_data_store_func->call( $order, $data_store );
	}

	/**
	 * Creates a complex COT order with address info, line items, etc.
	 * @return \WC_Order
	 */
	private function create_complex_cot_order() {
		$order = new WC_Order();
		$this->switch_data_store( $order, $this->sut );

		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		$order->set_status( 'pending' );
		$order->set_created_via( 'unit-tests' );
		$order->set_currency( 'COP' );
		$order->set_customer_ip_address( '127.0.0.1' );

		$item = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $product,
				'quantity' => 2,
				'subtotal' => wc_get_price_excluding_tax( $product, array( 'qty' => 2 ) ),
				'total'    => wc_get_price_excluding_tax( $product, array( 'qty' => 2 ) ),
			)
		);

		$order->add_item( $item );

		$order->set_billing_first_name( 'Jeroen' );
		$order->set_billing_last_name( 'Sormani' );
		$order->set_billing_company( 'WooCompany' );
		$order->set_billing_address_1( 'WooAddress' );
		$order->set_billing_address_2( '' );
		$order->set_billing_city( 'WooCity' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '123456' );
		$order->set_billing_country( 'US' );
		$order->set_billing_email( 'admin@example.org' );
		$order->set_billing_phone( '555-32123' );

		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$order->set_payment_method( $payment_gateways['bacs'] );

		$order->set_shipping_total( 5.0 );
		$order->set_discount_total( 0.0 );
		$order->set_discount_tax( 0.0 );
		$order->set_cart_tax( 0.0 );
		$order->set_shipping_tax( 0.0 );
		$order->set_total( 25.0 );
		$order->save();

		$order->get_data_store()->set_stock_reduced( $order, true, false );

		$order->update_meta_data( 'my_meta', rand( 0, 255 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand

		$order->save();

		return $order;
	}

}
