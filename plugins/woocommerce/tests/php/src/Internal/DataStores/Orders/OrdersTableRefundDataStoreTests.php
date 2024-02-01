<?php
/**
 * File containing the class WP_Test_WC_Order_Refund.
 */

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableRefundDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class OrdersTableRefundDataStoreTests.
 */
class OrdersTableRefundDataStoreTests extends WC_Unit_Test_Case {
	use \Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

	/**
	 * @var PostsToOrdersMigrationController
	 */
	private $migrator;

	/**
	 * @var OrdersTableRefundDataStore
	 */
	private $sut;

	/**
	 * @var OrdersTableDataStore;
	 */
	private $order_data_store;

	/**
	 * @var WC_Order_Refund_Data_Store_CPT
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
		$this->sut              = wc_get_container()->get( OrdersTableRefundDataStore::class );
		$this->order_data_store = wc_get_container()->get( OrdersTableDataStore::class );
		$this->migrator         = wc_get_container()->get( PostsToOrdersMigrationController::class );
		$this->cpt_data_store   = new WC_Order_Refund_Data_Store_CPT();
	}

	/**
	 * Test that we are able to create refund.
	 */
	public function test_create_refund() {
		$order  = OrderHelper::create_complex_data_store_order( $this->order_data_store );
		$refund = new WC_Order_Refund();
		OrderHelper::switch_data_store( $refund, $this->sut );
		$refund->set_amount( 10 );
		$refund->set_refunded_by( get_current_user_id() );
		$refund->set_reason( 'Test' );
		$refund->set_parent_id( $order->get_id() );
		$this->sut->create( $refund );

		$this->assertNotEquals( 0, $refund->get_id() );
		// Read from DB.
		$refreshed_refund = new WC_Order_Refund();
		OrderHelper::switch_data_store( $refreshed_refund, $this->sut );
		$refreshed_refund->set_id( $refund->get_id() );
		$this->sut->read( $refreshed_refund );
		$this->assertEquals( $refund->get_id(), $refreshed_refund->get_id() );
		$this->assertEquals( 10, $refreshed_refund->get_amount() );
		$this->assertEquals( 'Test', $refreshed_refund->get_reason() );
	}

	/**
	 * @testDox Test that refunds can be backfilled correctly.
	 */
	public function test_refunds_backfill() {
		$this->enable_cot_sync();
		$this->toggle_cot_feature_and_usage( true );
		$order  = OrderHelper::create_complex_data_store_order( $this->order_data_store );
		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test',
			)
		);
		$refund->save();
		$this->assertTrue( $refund->get_id() > 0 );

		// Check that data was saved.
		$refreshed_refund = new WC_Order_Refund();
		$cpt_store        = $this->sut->get_cpt_data_store_instance();
		$refreshed_refund->set_id( $refund->get_id() );
		$cpt_store->read( $refreshed_refund );
		$this->assertEquals( $refund->get_id(), $refreshed_refund->get_id() );
		$this->assertEquals( 10, $refreshed_refund->get_amount() );
		$this->assertEquals( 'Test', $refreshed_refund->get_reason() );
	}

	/**
	 * @testDox Test that refund props are set as expected with HPOS enabled.
	 */
	public function test_refund_data_is_set() {
		$this->toggle_cot_feature_and_usage( true );

		$order = OrderHelper::create_order();
		$user  = $this->factory()->user->create_and_get( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user->ID );

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test',
			)
		);
		$refund->save();

		$refreshed_refund = wc_get_order( $order->get_id() )->get_refunds()[0];
		$this->assertEquals( $refund->get_id(), $refreshed_refund->get_id() );
		$this->assertEquals( 10, $refreshed_refund->get_data()['amount'] );
		$this->assertEquals( 'Test', $refreshed_refund->get_data()['reason'] );
		$this->assertEquals( $user->ID, $refreshed_refund->get_data()['refunded_by'] );
	}

	/**
	 * Helper function to create a complex order and its refund.
	 *
	 * @return array Order and refund.
	 */
	private function create_order_and_refund() {
		$order  = OrderHelper::create_complex_data_store_order( $this->order_data_store );
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 10,
				'reason'         => 'Test',
				'refund_payment' => false,
				'refunded_by'    => 1,
			)
		);
		$refund->save();
		return array( $order, $refund );
	}

	/**
	 * @testDox Refund props are set as expected with HPOS with sync.
	 */
	public function test_refund_update() {
		$this->enable_cot_sync();
		$this->toggle_cot_feature_and_usage( true );
		list( $order, $refund ) = $this->create_order_and_refund();
		$refund->add_meta_data( 'test', 'test' );
		$refund->save();

		$this->assertTrue( $refund->get_id() > 0 );
		$this->assertEquals( 'Test', $refund->get_reason() );
		$this->assertEquals( 10, $refund->get_amount() );
		$this->assertFalse( $refund->get_refunded_payment() );

		$refund->set_reason( 'Test 2' );
		$refund->set_amount( 5 );
		$refund->set_refunded_payment( true );
		$refund->set_refunded_by( 2 );
		$refund->update_meta_data( 'test', 'test 2' );
		$refund->save();

		$refreshed_refund = wc_get_order( $refund->get_id() );
		$this->assertEquals( 'Test 2', $refreshed_refund->get_reason() );
		$this->assertEquals( 5, $refreshed_refund->get_amount() );
		$this->assertTrue( $refreshed_refund->get_refunded_payment() );
		$this->assertEquals( 2, $refreshed_refund->get_refunded_by() );
		$this->assertEquals( 'test 2', $refreshed_refund->get_meta( 'test' ) );

		$this->assertEquals( 'Test 2', get_post_meta( $refund->get_id(), '_refund_reason', true ) );
		$this->assertEquals( 5, get_post_meta( $refund->get_id(), '_refund_amount', true ) );
		$this->assertEquals( 1, get_post_meta( $refund->get_id(), '_refunded_payment', true ) );
		$this->assertEquals( 2, get_post_meta( $refund->get_id(), '_refunded_by', true ) );
		$this->assertEquals( 'test 2', get_post_meta( $refund->get_id(), 'test', true ) );
	}

	/**
	 * @testDox Refund props are set as expected with HPOS without sync.
	 */
	public function test_refund_update_without_sync() {
		$this->disable_cot_sync();
		$this->toggle_cot_feature_and_usage( true );
		list( $order, $refund ) = $this->create_order_and_refund();
		$refund->add_meta_data( 'test', 'test' );
		$refund->save();

		$this->assertTrue( $refund->get_id() > 0 );

		$refund->set_reason( 'Test 2' );
		$refund->set_amount( 5 );
		$refund->set_refunded_payment( true );
		$refund->set_refunded_by( 2 );
		$refund->update_meta_data( 'test', 'test 2' );
		$refund->save();

		$refreshed_refund = wc_get_order( $refund->get_id() );
		$this->assertEquals( 'Test 2', $refreshed_refund->get_reason() );
		$this->assertEquals( 5, $refreshed_refund->get_amount() );
		$this->assertTrue( $refreshed_refund->get_refunded_payment() );
		$this->assertEquals( 2, $refreshed_refund->get_refunded_by() );
		$this->assertEquals( 'test 2', $refreshed_refund->get_meta( 'test' ) );
	}

	/**
	 * @testDox Refund meta data is updated as expted using the save metadata call.
	 *
	 * @param bool $sync_status Whether to enable sync.
	 *
	 * @testWith [true, false]
	 */
	public function test_refund_save_meta_data( $sync_status ) {
		$sync_status && $this->enable_cot_sync();
		$this->toggle_cot_authoritative( true );

		list( $order, $refund ) = $this->create_order_and_refund();

		$refund->add_meta_data( 'test_key1', 'test_value1' );
		$refund->add_meta_data( 'test_key2', 'test_value2' );
		$refund->add_meta_data( 'test_key3', 'test_value3_1' );
		$refund->add_meta_data( 'test_key3', 'test_value3_2' );
		$refund->save_meta_data();

		$refreshed_refund = wc_get_order( $refund->get_id() );
		$this->assertEquals( 'test_value1', $refreshed_refund->get_meta( 'test_key1' ) );
		$this->assertEquals( 'test_value2', $refreshed_refund->get_meta( 'test_key2' ) );
		$test_key3_data   = $refreshed_refund->get_meta( 'test_key3', false );
		$test_key3_values = array();
		foreach ( $test_key3_data as $test_key3_datum ) {
			$test_key3_values[] = $test_key3_datum->value;
		}
		$this->assertEquals( array( 'test_value3_1', 'test_value3_2' ), $test_key3_values );

		if ( $sync_status ) {
			$this->assertEquals( 'test_value1', get_post_meta( $refund->get_id(), 'test_key1', true ) );
			$this->assertEquals( 'test_value2', get_post_meta( $refund->get_id(), 'test_key2', true ) );
			$this->assertEquals( array( 'test_value3_1', 'test_value3_2' ), get_post_meta( $refund->get_id(), 'test_key3' ) );
		}

		$refund->update_meta_data( 'test_key1', 'test_value1_updated' );
		$refund->delete_meta_data( 'test_key2' );
		$refund->delete_meta_data_value( 'test_key3', 'test_value3_1' );

		$refund->save_meta_data();

		$refreshed_refund = wc_get_order( $refund->get_id() );
		$this->assertEquals( 'test_value1_updated', $refreshed_refund->get_meta( 'test_key1' ) );
		$this->assertEquals( null, $refreshed_refund->get_meta( 'test_key2' ) );
		$this->assertEquals( 'test_value3_2', $refreshed_refund->get_meta( 'test_key3' ) );

		if ( $sync_status ) {
			$this->assertEquals( 'test_value1_updated', get_post_meta( $refund->get_id(), 'test_key1', true ) );
			$this->assertEquals( null, get_post_meta( $refund->get_id(), 'test_key2', true ) );
			$this->assertEquals( 'test_value3_2', get_post_meta( $refund->get_id(), 'test_key3', true ) );
		}
	}

}
