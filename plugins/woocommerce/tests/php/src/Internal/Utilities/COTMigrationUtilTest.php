<?php
/**
 * Tests for COTMigration utility.
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\Utilities\COTMigrationUtil;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Tests for COTMigration utility.
 */
class COTMigrationUtilTest extends WC_Unit_Test_Case {

	/**
	 * @var COTMigrationUtil
	 */
	private $sut;

	/**
	 * @var bool
	 */
	private $prev_cot_state;

	/**
	 * Set-up subject under test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( COTMigrationUtil::class );

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$cot_controller       = wc_get_container()->get( CustomOrdersTableController::class );
		$this->prev_cot_state = $cot_controller->custom_orders_table_usage_is_enabled();
	}

	/**
	 * Restore the COT state after the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		OrderHelper::toggle_cot_feature_and_usage( $this->prev_cot_state );
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		parent::tearDown();
	}

	/**
	 * Test test_get_post_or_object_meta function with both posts and order objects.
	 */
	public function test_get_post_or_object_meta() {
		$order = OrderHelper::create_order();
		$post  = get_post( $order->get_id() );
		update_post_meta( $order->get_id(), 'dummy_meta', 'dummy_value' );

		$this->assertEquals( 'dummy_value', $this->sut->get_post_or_object_meta( $post, $order, 'dummy_meta', true ) );
		$this->assertEquals( 'dummy_value', $this->sut->get_post_or_object_meta( $post, null, 'dummy_meta', true ) );
	}

	/**
	 * Tests init_theorder_object with both posts and order objects.
	 */
	public function test_init_theorder_object() {
		global $theorder;
		$order1           = OrderHelper::create_order();
		$order2           = OrderHelper::create_order();
		$post_from_order2 = get_post( $order2->get_id() );

		$this->assertEquals( $order1, $this->sut->init_theorder_object( $order1 ) );
		$this->assertEquals( $theorder->get_id(), $order1->get_id() );

		$theorder = null;
		$this->sut->init_theorder_object( $post_from_order2 );
		$this->assertEquals( $theorder->get_id(), $order2->get_id() );
	}

	/**
	 * Tests get_post_or_order_id with both posts and order objects.
	 */
	public function test_get_post_or_order_id() {
		$order1           = OrderHelper::create_order();
		$order2           = OrderHelper::create_order();
		$post_from_order2 = get_post( $order2->get_id() );

		$this->assertEquals( $order1->get_id(), $this->sut->get_post_or_order_id( $order1 ) );
		$this->assertEquals( $order2->get_id(), $this->sut->get_post_or_order_id( $post_from_order2 ) );
	}

	/**
	 * @testDox `is_custom_order_tables_in_sync` should return true when Custom Order Tables are in sync.
	 */
	public function test_is_custom_order_tables_in_sync_is_true() {
		$data_sync_mock = $this->getMockBuilder( DataSynchronizer::class )
			->setMethods( array( 'get_current_orders_pending_sync_count', 'data_sync_is_enabled' ) )
			->getMock();

		$data_sync_mock->method( 'get_current_orders_pending_sync_count' )->willReturn( 0 );
		$data_sync_mock->method( 'data_sync_is_enabled' )->willReturn( true );

		// This is needed to prevent "Call to private method Mock_DataSynchronizer_xxxx::process_added_option" errors.
		remove_filter( 'updated_option', array( $data_sync_mock, 'process_updated_option' ), 999, 3 );
		remove_filter( 'added_option', array( $data_sync_mock, 'process_added_option' ), 999, 2 );

		$cot_controller = wc_get_container()->get( CustomOrdersTableController::class );
		$this->sut      = new COTMigrationUtil();
		$this->sut->init( $cot_controller, $data_sync_mock );
		$this->assertTrue( $this->sut->is_custom_order_tables_in_sync() );
	}

	/**
	 * @testDox `is_custom_order_tables_in_sync` should return false when Custom Order Tables are not in sync.
	 */
	public function test_is_custom_order_tables_in_sync_is_false() {
		$data_sync_mock = $this->getMockBuilder( DataSynchronizer::class )
							->setMethods( array( 'get_current_orders_pending_sync_count', 'data_sync_is_enabled' ) )
							->getMock();

		$data_sync_mock->method( 'get_current_orders_pending_sync_count' )->willReturn( 0 );
		$data_sync_mock->method( 'data_sync_is_enabled' )->willReturn( false );

		// This is needed to prevent "Call to private method Mock_DataSynchronizer_xxxx::process_added_option" errors.
		remove_filter( 'updated_option', array( $data_sync_mock, 'process_updated_option' ), 999, 3 );
		remove_filter( 'added_option', array( $data_sync_mock, 'process_added_option' ), 999, 2 );

		$cot_controller = wc_get_container()->get( CustomOrdersTableController::class );
		$this->sut      = new COTMigrationUtil();
		$this->sut->init( $cot_controller, $data_sync_mock );
		$this->assertFalse( $this->sut->is_custom_order_tables_in_sync() );
	}

	/**
	 * @testdox `get_table_for_orders` should return the name of the posts table when HPOS is not in use.
	 */
	public function test_get_table_for_orders_posts() {
		global $wpdb;

		OrderHelper::toggle_cot_feature_and_usage( false );

		$table_name = $this->sut->get_table_for_orders();
		$this->assertEquals( $wpdb->posts, $table_name );
	}

	/**
	 * @testdox `get_table_for_orders` should return the name of the orders table when HPOS is in use.
	 */
	public function test_get_table_for_orders_hpos() {
		global $wpdb;

		OrderHelper::toggle_cot_feature_and_usage( true );

		$table_name = $this->sut->get_table_for_orders();
		$this->assertEquals( "{$wpdb->prefix}wc_orders", $table_name );
	}

	/**
	 * @testdox `get_table_for_order_meta` should return the name of the postmeta table when HPOS is not in use.
	 */
	public function test_get_table_for_order_meta_posts() {
		global $wpdb;

		OrderHelper::toggle_cot_feature_and_usage( false );

		$table_name = $this->sut->get_table_for_order_meta();
		$this->assertEquals( $wpdb->postmeta, $table_name );
	}

	/**
	 * @testdox `get_table_for_order_meta` should return the name of the orders meta table when HPOS is in use.
	 */
	public function test_get_table_for_order_meta_hpos() {
		global $wpdb;

		OrderHelper::toggle_cot_feature_and_usage( true );

		$table_name = $this->sut->get_table_for_order_meta();
		$this->assertEquals( "{$wpdb->prefix}wc_orders_meta", $table_name );
	}
}
