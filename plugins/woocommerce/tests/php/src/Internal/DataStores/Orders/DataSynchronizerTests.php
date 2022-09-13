<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests for DataSynchronizer class.
 */
class DataSynchronizerTests extends WC_Unit_Test_Case {

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
}
