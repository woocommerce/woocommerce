<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

require_once __DIR__ . '/../../../../helpers/HPOSToggleTrait.php';

/**
 * Tests for DataSynchronizer class.
 */
class DataSynchronizerTests extends HposTestCase {
	use ArraySubsetAsserts;
	use HPOSToggleTrait;

	/**
	 * @var DataSynchronizer
	 */
	private $sut;

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
		$this->sut           = wc_get_container()->get( DataSynchronizer::class );
		$features_controller = wc_get_container()->get( Featurescontroller::class );
		$features_controller->change_feature_enable( 'custom_order_tables', true );
	}

	/**
	 * Simulates a situation where there are orders to be synced in both directions. Ideally, we won't find ourselves in this situation ever, but this is helpful for testing.
	 *
	 * @param string $authoritative_source Authoritative source, could be cot or posts.
	 *
	 * @return array[]|void
	 * @throws Exception When orders are not created as expected.
	 */
	private function init_dirty_orders( $authoritative_source ) {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, false );
		$post_data_store = WC_Data_Store::load( 'order' );

		$cot_enabled = 'cot' === $authoritative_source ? 'yes' : 'no';
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, $cot_enabled );
		update_option( DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION, 'no' );

		$post_order1 = OrderHelper::create_complex_data_store_order( $post_data_store );
		$post_order2 = OrderHelper::create_complex_data_store_order( $post_data_store );

		assert( get_post_type( $post_order1->get_id() ) === 'shop_order' );
		assert( get_post_type( $post_order2->get_id() ) === 'shop_order' );

		$cot_order1 = OrderHelper::create_complex_data_store_order();
		$cot_order2 = OrderHelper::create_complex_data_store_order();
		$cot_order3 = OrderHelper::create_complex_data_store_order();

		assert( get_post_type( $cot_order1->get_id() ) === DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE );
		assert( get_post_type( $cot_order2->get_id() ) === DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE );
		assert( get_post_type( $cot_order3->get_id() ) === DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE );

		return array(
			'cot_orders'  => array( $cot_order1->get_id(), $cot_order2->get_id(), $cot_order3->get_id() ),
			'post_orders' => array( $post_order1->get_id(), $post_order2->get_id() ),
		);
	}

	/**
	 * Destroys system under test.
	 */
	public function tearDown(): void {
		// Add back removed filter.
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		parent::tearDown();
	}

	/**
	 * Test that orders are synced properly where there are orders to migrate from posts table.
	 */
	public function test_get_ids_orders_pending_sync_migration() {
		$order_collection = $this->init_dirty_orders( 'posts' );
		$this->assertEquals( 2, $this->sut->get_current_orders_pending_sync_count() );
		$this->assertArraySubset( $order_collection['post_orders'], $this->sut->get_next_batch_to_process( 10 ) );

		$this->sut->process_batch( $order_collection['post_orders'] );
		$this->assertEquals( 0, $this->sut->get_current_orders_pending_sync_count() );
	}

	/**
	 * Test that orders are synced properly when there are orders to backfill into posts table.
	 */
	public function test_get_ids_orders_pending_sync_backfill() {
		$order_collection = $this->init_dirty_orders( 'cot' );
		$this->assertEquals( 3, $this->sut->get_current_orders_pending_sync_count() );
		$this->assertArraySubset( $order_collection['cot_orders'], $this->sut->get_next_batch_to_process( 10 ) );

		$this->sut->process_batch( $order_collection['cot_orders'] );
		$this->assertEquals( 0, $this->sut->get_current_orders_pending_sync_count() );
	}

	/**
	 * Test that orders that are backfilled later on don't need to be synced again.
	 */
	public function test_get_ids_orders_pending_async_backfill() {
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'no' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$order = OrderHelper::create_complex_data_store_order();
		$this->assertEquals( 1, $this->sut->get_current_orders_pending_sync_count() );
		// Simulate that order was updated some time ago, and we are backfilling just now.
		$order->set_date_modified( time() - 1000 );
		$order->save();

		$this->sut->process_batch( array( $order->get_id() ) );
		$this->assertEquals( 0, $this->sut->get_current_orders_pending_sync_count() );

		// So far so good, now if we change the authoritative source to posts, we should still have 0 order pending sync.
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		$this->assertEquals( 0, $this->sut->get_current_orders_pending_sync_count() );
	}

	/**
	 * When the CPT data store is authoritative, and an order is updated, we should propagate those changes to
	 * the COT store immediately (rather than wait for sync to catch up). This guards against a situation where
	 * the corresponding COT record is temporarily inaccurate.
	 */
	public function test_updates_to_cpt_orders_should_propagate_while_sync_is_in_progress() {
		// Enable sync, make the posts table authoritative.
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$post_order = OrderHelper::create_order();
		$this->assertInstanceOf(
			WP_Post::class,
			get_post( $post_order->get_id() ),
			'The order was initially created as a post.'
		);

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$cot_order = wc_get_order( $post_order->get_id() );
		$this->assertEquals(
			OrdersTableDataStore::class,
			$cot_order->get_data_store()->get_current_class_name(),
			'The order was successfully copied to the COT table, outside of a dedicated synchronization batch.'
		);
	}

	/**
	 * When sync is enabled and the posts store is authoritative, creating an order and updating the status from
	 * draft to some non-draft status (as happens when an order is manually created in the admin environment) should
	 * result in the same status change being made in the duplicate COT record.
	 */
	public function test_status_syncs_correctly_after_order_creation() {
		global $wpdb;
		$orders_table = OrdersTableDataStore::get_orders_table_name();

		// Enable sync, make the posts table authoritative.
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		// When a new order is manually created in the admin environment, WordPress automatically creates an empty
		// draft post for us.
		$order_id = (int) wp_insert_post(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'draft',
			)
		);

		// Once the admin user decides to go ahead and save the order (ie, they click 'Create'), we start performing
		// various updates to the order record.
		wc_get_order( $order_id )->save();

		// As soon as the order is saved via our own internal API, the DataSynchronizer should create a copy of the
		// record in the COT table.
		$this->assertEquals(
			'draft',
			$wpdb->get_var( "SELECT status FROM $orders_table WHERE id = $order_id" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'When HPOS is enabled but the posts data store is authoritative, saving an order will result in a duplicate with the same status being saved in the COT table.'
		);

		// In a separate operation, the status will be updated to an actual non-draft order status. This should also be
		// observed by the DataSynchronizer and a further update made to the COT table.
		$order = wc_get_order( $order_id );
		$order->set_status( 'pending' );
		$order->save();
		$this->assertEquals(
			'wc-pending',
			$wpdb->get_var( "SELECT status FROM $orders_table WHERE id = $order_id" ), //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'When the order status is updated, the change should be observed by the DataSynhronizer and a matching update will take place in the COT table.'
		);
	}

	/**
	 * When sync is enabled, and an order is deleted either from the post table or the COT table, the
	 * change should propagate across to the other table.
	 */
	public function test_order_deletions_propagate_with_sync_enabled(): void {
		// Sync enabled and COT authoritative.
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$order = OrderHelper::create_order();
		wp_delete_post( $order->get_id(), true );

		$this->assertFalse(
			wc_get_order( $order->get_id() ),
			'After the order post record was deleted, the order was also deleted from COT.'
		);

		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();
		$order->delete( true );

		$this->assertNull(
			get_post( $order_id ),
			'After the COT order record was deleted, the order was also deleted from the posts table.'
		);
	}

	/**
	 * @testdox When sync is disabled and the posts table is authoritative, deleting an order generates a deletion record in the meta table if the order exists in the backup table.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $manual_sync True to trigger synchronization manually, false if automatic synchronization is enabled.
	 */
	public function test_synced_order_deletion_with_sync_disabled_and_posts_authoritative_generates_proper_deletion_record_if_cot_record_exists( bool $manual_sync ) {
		$this->toggle_cot_authoritative( false );

		if ( $manual_sync ) {
			$this->disable_cot_sync();
			$order = OrderHelper::create_order();
			$this->do_cot_sync();
		} else {
			$this->enable_cot_sync();
			$order = OrderHelper::create_order();
		}

		$this->disable_cot_sync();
		$order_id = $order->get_id();
		$order->delete( true );

		$this->assert_deletion_record_existence( $order_id, false );
		$this->assert_order_record_existence( $order_id, true, true );
	}

	/**
	 * @testdox When sync is disabled and the posts table is authoritative, deleting an order generates NO deletion record in the meta table if the order does NOT exist in the backup table.
	 *
	 * @return void
	 */
	public function test_synced_order_deletion_with_sync_disabled_and_posts_authoritative_not_generating_deletion_record_if_cot_record_not_exists() {
		$this->toggle_cot_authoritative( false );
		$this->disable_cot_sync();
		$order = OrderHelper::create_order();

		$order_id = $order->get_id();
		$order->delete( true );

		$this->assert_deletion_record_existence( $order_id, null, false );
	}

	/**
	 * @testdox 'get_next_batch_to_process' returns ids of deleted orders, unless there are orders pending to be created or updated.
	 *
	 * @testWith [false, false]
	 *           [false, true]
	 *           [true, false]
	 *           [true, true]
	 *
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 * @param bool $new_records_exist True to test with a new order pending sync.
	 */
	public function test_get_next_batch_to_process_returns_orders_deleted_from_current_authoritative_table( bool $cot_is_authoritative, bool $new_records_exist ) {
		global $wpdb;

		$meta_table_name = OrdersTableDataStore::get_meta_table_name();

		$this->toggle_cot_authoritative( $cot_is_authoritative );

		$this->enable_cot_sync();
		$order_1 = OrderHelper::create_order();
		$order_2 = OrderHelper::create_order();
		$order_3 = OrderHelper::create_order();
		$order_4 = OrderHelper::create_order();

		$this->disable_cot_sync();
		if ( $new_records_exist ) {
			$order_5 = OrderHelper::create_order();
		}
		$order_3_id = $order_3->get_id();
		$order_3->delete( true );
		$order_4_id = $order_4->get_id();
		$order_4->delete( true );

		$this->assert_deletion_record_existence( $order_3_id, $cot_is_authoritative );
		$this->assert_deletion_record_existence( $order_4_id, $cot_is_authoritative );

		$batch = $this->sut->get_next_batch_to_process( 100 );
		if ( $new_records_exist ) {
			$this->assertEquals( array( $order_5->get_id() ), $batch );
		} else {
			$this->assertEquals( array( $order_3_id, $order_4_id ), $batch );
		}
	}

	/**
	 * @testdox 'process_batch' processes both orders pending creation/modification and orders pending deletion.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 */
	public function test_process_batch_processes_modified_and_deleted_orders( bool $cot_is_authoritative ) {
		$this->toggle_cot_authoritative( $cot_is_authoritative );
		$this->enable_cot_sync();

		$order_1 = OrderHelper::create_order();
		$order_2 = OrderHelper::create_order();
		$order_3 = OrderHelper::create_order();
		$order_4 = OrderHelper::create_order();

		$this->disable_cot_sync();

		$order_1->set_date_modified( '2100-01-01 00:00:00' );
		$order_1->save();

		$order_3_id = $order_3->get_id();
		$order_3->delete( true );
		$order_4_id = $order_4->get_id();
		$order_4->delete( true );

		$this->sut->process_batch( array( $order_1->get_id(), $order_3_id, $order_4_id ) );

		$this->assertEmpty( $this->sut->get_next_batch_to_process( 100 ) );

		$this->assert_order_record_existence( $order_3_id, true, false );
		$this->assert_order_record_existence( $order_3_id, false, false );
		$this->assert_order_record_existence( $order_4_id, true, false );
		$this->assert_order_record_existence( $order_4_id, false, false );

		$this->assert_deletion_record_existence( $order_3_id, null, false );
		$this->assert_deletion_record_existence( $order_4_id, null, false );
	}

	/**
	 * @testdox When an order deletion is recorded for an order that no longer exists in the backup table, a warning is logged.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 */
	public function test_deletion_record_for_non_existing_order_logs_warning_on_sync( bool $cot_is_authoritative ) {
		global $wpdb;

		//phpcs:disable Squiz.Commenting
		$logger = new class() {
			public $warnings = array();

			public function debug( $text ) {}

			public function warning( $text ) {
				$this->warnings[] = $text; }

			public function error( $text ) {}
		};
		//phpcs:enable Squiz.Commenting

		$this->reset_container_resolutions();
		$this->reset_legacy_proxy_mocks();
		$this->register_legacy_proxy_function_mocks(
			array(
				'wc_get_logger' => function() use ( $logger ) {
					return $logger;
				},
			)
		);
		$this->sut = wc_get_container()->get( DataSynchronizer::class );

		$this->toggle_cot_authoritative( $cot_is_authoritative );
		$this->enable_cot_sync();

		$order_1 = OrderHelper::create_order();
		$order_2 = OrderHelper::create_order();

		$this->disable_cot_sync();

		$order_1_id = $order_1->get_id();
		$order_1->delete( true );
		$order_2_id = $order_2->get_id();
		$order_2->delete( true );

		if ( $cot_is_authoritative ) {
			$wpdb->delete( $wpdb->posts, array( 'ID' => $order_1_id ) );
		} else {
			$wpdb->delete( OrdersTableDataStore::get_orders_table_name(), array( 'ID' => $order_1_id ) );
		}

		$this->sut->process_batch( array( $order_1_id, $order_2_id ) );

		$this->assertEquals( array( "Order {$order_1_id} doesn't exist in the backup table, thus it can't be deleted" ), $logger->warnings );
	}

	/*
	 * When sync is enabled, changes to meta data should propagate from the Custom Orders Table to
	 * the post meta table whenever the order object's save_meta_data() method is called.
	 *
	 * @return void
	 */
	public function test_meta_data_changes_propagate_from_cot_to_cpt(): void {
		// Sync enabled and COT authoritative.
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$order = OrderHelper::create_order();
		$order->add_meta_data( 'foo', 'bar' );
		$order->add_meta_data( 'bar', 'baz' );
		$order->save_meta_data();

		$order->delete_meta_data( 'bar' );
		$order->save_meta_data();

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		$refreshed_order = wc_get_order( $order->get_id() );

		$this->assertEquals(
			$refreshed_order->get_meta( 'foo' ),
			'bar',
			'Meta data persisted via the HPOS datastore is accessible via the CPT datastore.'
		);

		$this->assertEquals(
			$refreshed_order->get_meta( 'bar' ),
			'',
			'Meta data deleted from the HPOS datastore should also be deleted from the CPT datastore.'
		);
	}

	/**
	 * When sync is enabled, changes to meta data should propagate from the post meta table to
	 * the Custom Orders Table whenever the order object's save_meta_data() method is called.
	 *
	 * @return void
	 */
	public function test_meta_data_changes_propagate_from_cpt_to_cot(): void {
		// Sync enabled and CPT authoritative.
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$order = OrderHelper::create_order();
		$order->add_meta_data( 'foo', 'bar' );
		$order->add_meta_data( 'bar', 'baz' );
		$order->save_meta_data();

		$order->delete_meta_data( 'bar' );
		$order->save_meta_data();

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$refreshed_order = wc_get_order( $order->get_id() );

		$this->assertEquals(
			$refreshed_order->get_meta( 'foo' ),
			'bar',
			'Meta data persisted via the CPT datastore is accessible via the HPOS datastore.'
		);

		$this->assertEquals(
			$refreshed_order->get_meta( 'bar' ),
			'',
			'Meta data deleted from the CPT datastore should also be deleted from the HPOS datastore.'
		);
	}
}
