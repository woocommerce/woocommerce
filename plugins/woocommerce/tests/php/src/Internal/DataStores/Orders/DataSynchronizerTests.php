<?php

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

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

		$this->reset_legacy_proxy_mocks();
		$container = wc_get_container();
		$container->reset_all_resolved();

		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		OrderHelper::delete_order_custom_tables(); // We need this since non-temporary tables won't drop automatically.
		OrderHelper::create_order_custom_table_if_not_exist();
		OrderHelper::toggle_cot_feature_and_usage( false );
		$this->sut = $container->get( DataSynchronizer::class );
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
	 * @testdox 'get_next_batch_to_process' returns ids of deleted orders, together with orders pending to be created or updated.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $cot_is_authoritative True to test with the orders table as authoritative, false to test with the posts table as authoritative.
	 */
	public function test_get_next_batch_to_process_returns_orders_deleted_from_current_authoritative_table( bool $cot_is_authoritative ) {
		global $wpdb;

		$this->toggle_cot_authoritative( $cot_is_authoritative );

		$this->enable_cot_sync();
		$order_1 = OrderHelper::create_order();
		$order_2 = OrderHelper::create_order();
		$order_3 = OrderHelper::create_order();
		$order_4 = OrderHelper::create_order();

		$this->disable_cot_sync();

		$order_5 = OrderHelper::create_order();

		$this->set_order_as_updated( $order_1, '2999-12-31 23:59:59', $cot_is_authoritative );

		$order_3_id = $order_3->get_id();
		$order_3->delete( true );
		$order_4_id = $order_4->get_id();
		$order_4->delete( true );

		$this->assert_deletion_record_existence( $order_3_id, $cot_is_authoritative );
		$this->assert_deletion_record_existence( $order_4_id, $cot_is_authoritative );

		$batch = $this->sut->get_next_batch_to_process( 100 );

		$this->assertEquals( array( $order_5->get_id(), $order_1->get_id(), $order_3_id, $order_4_id ), $batch );
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

	/**
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

	/**
	 * @testDox Orders for migration are picked by ID sorted.
	 */
	public function test_migration_sort() {
		global $wpdb;
		$order1 = wc_get_order( OrderHelper::create_order() );
		$order2 = wc_get_order( OrderHelper::create_order() );

		// Let's update order1 id to be greater than order2 id.
		// phpcs:ignore
		$max_id = $wpdb->get_var( "SELECT MAX(id) FROM $wpdb->posts" );
		$wpdb->update( $wpdb->posts, array( 'ID' => $max_id + 1 ), array( 'ID' => $order1->get_id() ) );

		$orders_to_migrate = $this->sut->get_next_batch_to_process( 2 );
		$this->assertEquals( $order2->get_id(), $orders_to_migrate[0] );
		$this->assertEquals( $max_id + 1, $orders_to_migrate[1] );
	}

	/**
	 * Tests that auto-draft orders older than 1 week are automatically deleted when WP does the same for posts.
	 *
	 * @return void
	 */
	public function test_auto_draft_deletion(): void {
		OrderHelper::toggle_cot_feature_and_usage( true );

		$order1 = new \WC_Order();
		$order1->set_status( 'auto-draft' );
		$order1->set_date_created( strtotime( '-10 days' ) );
		$order1->save();

		$order2 = new \WC_Order();
		$order2->set_status( 'auto-draft' );
		$order2->save();

		$order3 = new \WC_Order();
		$order3->set_status( 'processing' );
		$order3->save();

		// Run WP's auto-draft delete.
		do_action( 'wp_scheduled_auto_draft_delete' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle

		$orders = wc_get_orders(
			array(
				'status' => 'all',
				'limit'  => -1,
				'return' => 'ids',
			)
		);

		// Confirm that only $order1 is deleted when the action runs but the other orders remain intact.
		$this->assertContains( $order2->get_id(), $orders );
		$this->assertContains( $order3->get_id(), $orders );
		$this->assertNotContains( $order1->get_id(), $orders );
	}

	/**
	 * Test that trashed orders are deleted after the time set in `EMPTY_TRASH_DAYS`.
	 */
	public function test_trashed_order_deletion(): void {
		$this->toggle_cot_authoritative( true );
		$this->disable_cot_sync();

		$order = new WC_Order();
		$order->save();

		// Ensure the placeholder post is there.
		$placeholder = get_post( $order->get_id() );
		$this->assertEquals( $order->get_id(), $placeholder->ID );

		// Trashed orders should be deleted by the collection mechanism.
		$order->get_data_store()->delete( $order );
		$this->assertEquals( $order->get_status(), 'trash' );
		$order->save();

		// Run scheduled deletion.
		do_action( 'wp_scheduled_delete' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle

		// Refresh order and ensure it's *not* gone.
		$order = wc_get_order( $order->get_id() );
		$this->assertNotNull( $order );

		// Time-travel into the future so that the time required to delete a trashed order has passed.
		$this->register_legacy_proxy_function_mocks(
			array(
				'time' => function() {
					return time() + DAY_IN_SECONDS * EMPTY_TRASH_DAYS + 1;
				},
			)
		);

		// Run scheduled deletion.
		do_action( 'wp_scheduled_delete' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle

		// Ensure the placeholder post is gone.
		$placeholder = get_post( $order->get_id() );
		$this->assertNull( $placeholder );

		// Refresh order and ensure it's gone.
		$order = wc_get_order( $order->get_id() );
		$this->assertFalse( $order );
	}

	/**
	 * @testDox When HPOS is enabled, the custom orders table is created.
	 */
	public function test_tables_are_created_when_hpos_enabled() {
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'no' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		$this->sut->delete_database_tables();
		$this->assertFalse( $this->sut->check_orders_table_exists() );

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );
		$this->assertTrue( $this->sut->check_orders_table_exists() );
		$this->assertEquals( get_option( DataSynchronizer::ORDERS_TABLE_CREATED ), 'yes' );
	}

	/**
	 * @testDox When sync is enabled, the custom orders table is created.
	 */
	public function test_tables_are_created_when_sync_is_enabled() {
		$this->markTestSkipped( 'This is interfering with OrdersTableDataStoreTests and requires further review.' );

		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'no' );
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );
		$this->sut->delete_database_tables();
		$this->assertFalse( $this->sut->check_orders_table_exists() );

		$cot_sync_value = get_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION );
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes' );
		$this->assertTrue( $this->sut->check_orders_table_exists() );
		$this->assertEquals( get_option( DataSynchronizer::ORDERS_TABLE_CREATED ), 'yes' );
		$this->assertTrue( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
		update_option( $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, $cot_sync_value );
	}

	/**
	 * @testDox HPOS cannot be turned on when there are pending orders.
	 *
	 * @testWith [true, []]
	 *           [false, ["yes", "no"]]
	 *
	 * @param bool  $auth_table_change_allowed_with_sync_pending True if changing the authoritative data source for orders while synchronization is pending is allowed, false otherwise.
	 * @param array $expected_setting_disabled_status Expected value for the 'disabled' key in the setting configuration array.
	 */
	public function test_hpos_option_is_disabled_but_sync_enabled_with_pending_orders( $auth_table_change_allowed_with_sync_pending, $expected_setting_disabled_status ) {
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', fn() => $auth_table_change_allowed_with_sync_pending );

		$this->sut->delete_database_tables();
		$this->toggle_cot_authoritative( false );
		$this->disable_cot_sync();
		OrderHelper::create_order();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- test code.
		$features = apply_filters( 'woocommerce_get_settings_advanced', array(), 'features' );

		$cot_setting = array_filter(
			$features,
			function( $feature ) {
				return CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION === $feature['id'];
			}
		);
		$cot_setting = array_values( $cot_setting )[0];
		$this->assertEquals( $cot_setting['value'], 'no' );
		$this->assertEquals( $cot_setting['disabled'], $expected_setting_disabled_status );

		$sync_setting = array_filter(
			$features,
			function( $feature ) {
				return DataSynchronizer::ORDERS_DATA_SYNC_ENABLED_OPTION === $feature['id'];
			}
		);
		$sync_setting = array_values( $sync_setting )[0];
		$this->assertEquals( $sync_setting['value'], 'no' );
		$this->assertTrue( str_contains( $sync_setting['desc_tip'], "There's 1 order pending sync" ) );
		$this->assertTrue(
			str_contains(
				$sync_setting['desc_tip'],
				$auth_table_change_allowed_with_sync_pending ?
				'Switching data storage while sync is incomplete is dangerous' :
				'You can switch order data storage <strong>only when the posts and orders tables are in sync</strong>'
			)
		);
		$this->assertEquals( $auth_table_change_allowed_with_sync_pending, $sync_setting['description_is_error'] );
	}

	/**
	 * Delete an order directly from the wc_orders table so that the usual hooks and filters don't run.
	 *
	 * @param int $order_id The ID of the order to delete.
	 *
	 * @return void
	 */
	private function direct_delete_cot_order( $order_id ) {
		global $wpdb;

		$wpdb->delete(
			OrdersTableDataStore::get_orders_table_name(),
			array( 'id' => $order_id ),
			array( '%d' )
		);
	}

	/**
	 * @testDox When data sync is enabled, there should be a background sync process scheduled, and running it should
	 *          enqueue the DataSynchronizer batch processor when there are pending orders.
	 */
	public function test_bg_sync_while_sync_enabled() {
		$reflection     = new ReflectionClass( get_class( $this->sut ) );
		$process_method = $reflection->getMethod( 'process_updated_option' );
		$process_method->setAccessible( true );

		$this->toggle_cot_authoritative( false );

		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );

		$this->enable_cot_sync();
		$process_method->invoke( $this->sut, $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, false, 'yes' );
		$this->assertTrue( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );
		$this->assertTrue( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );

		wc_get_container()->get( BatchProcessingController::class )->remove_processor( DataSynchronizer::class );
		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- This is a test.
		do_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK );
		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );

		$cot_order = OrderHelper::create_complex_data_store_order();
		$this->direct_delete_cot_order( $cot_order->get_id() );
		$this->assertEquals( 1, $this->sut->get_total_pending_count() );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- This is a test.
		do_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK );
		$this->assertTrue( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );

		$this->disable_cot_sync();
		$process_method->invoke( $this->sut, $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, 'yes', 'no' );
		$this->assertFalse( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );
	}

	/**
	 * @testDox When data sync is disabled, but background sync is enabled, there should be a background sync process
	 *          scheduled, and running it should enqueue the DataSynchronizer batch processor when there are pending orders.
	 */
	public function test_bg_sync_while_sync_disabled_interval_mode() {
		$reflection     = new ReflectionClass( get_class( $this->sut ) );
		$process_method = $reflection->getMethod( 'process_updated_option' );
		$process_method->setAccessible( true );

		$this->toggle_cot_authoritative( false );
		$this->disable_cot_sync();
		$process_method->invoke( $this->sut, $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, false, 'no' );

		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );

		update_option( $this->sut::BACKGROUND_SYNC_MODE_OPTION, $this->sut::BACKGROUND_SYNC_MODE_INTERVAL );
		$this->assertTrue( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- This is a test.
		do_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK );
		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );

		$this->enable_cot_sync();
		$cot_order = OrderHelper::create_complex_data_store_order();
		$this->disable_cot_sync();
		$this->direct_delete_cot_order( $cot_order->get_id() );
		$this->assertEquals( 1, $this->sut->get_total_pending_count() );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- This is a test.
		do_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK );
		$this->assertTrue( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );

		update_option( $this->sut::BACKGROUND_SYNC_MODE_OPTION, $this->sut::BACKGROUND_SYNC_MODE_OFF );
		$this->assertFalse( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );
	}

	/**
	 * @testDox When data sync is disabled, but background sync is enabled and set to continuous mode, there should not
	 *          be a background sync process scheduled, but the DataSynchronizer batch processor should be enqueued anyway.
	 */
	public function test_bg_sync_while_sync_disabled_continuous_mode() {
		$reflection     = new ReflectionClass( get_class( $this->sut ) );
		$process_method = $reflection->getMethod( 'process_updated_option' );
		$process_method->setAccessible( true );
		$handler_method = $reflection->getMethod( 'handle_continuous_background_sync' );
		$handler_method->setAccessible( true );

		$this->toggle_cot_authoritative( false );
		$this->disable_cot_sync();
		$process_method->invoke( $this->sut, $this->sut::ORDERS_DATA_SYNC_ENABLED_OPTION, false, 'no' );

		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );

		update_option( $this->sut::BACKGROUND_SYNC_MODE_OPTION, $this->sut::BACKGROUND_SYNC_MODE_CONTINUOUS );
		$handler_method->invoke( $this->sut );
		$this->assertTrue( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
		$this->assertFalse( as_has_scheduled_action( $this->sut::BACKGROUND_SYNC_EVENT_HOOK ) );

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- This is a test.
		do_action( wc_get_container()->get( BatchProcessingController::class )::PROCESS_SINGLE_BATCH_ACTION_NAME, get_class( $this->sut ) );
		$this->assertFalse( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
		$handler_method->invoke( $this->sut );
		$this->assertTrue( wc_get_container()->get( BatchProcessingController::class )->is_enqueued( DataSynchronizer::class ) );
	}
}
