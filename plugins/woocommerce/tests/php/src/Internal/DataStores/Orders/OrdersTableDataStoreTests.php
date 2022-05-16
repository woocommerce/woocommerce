<?php

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

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
		OrderHelper::create_order_custom_table_if_not_exist();
		$this->sut            = wc_get_container()->get( OrdersTableDataStore::class );
		$this->migrator       = wc_get_container()->get( PostsToOrdersMigrationController::class );
		$this->cpt_data_store = new WC_Order_Data_Store_CPT();
		// Add back removed filter.
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
	}

	/**
	 * Test reading from migrated post order.
	 */
	public function test_read_from_migrated_order() {
		$post_order_id = OrderHelper::create_complex_wp_post_order();
		$this->migrator->migrate_orders( array( $post_order_id ) );

		$cot_order = new WC_Order();
		$cot_order->set_id( $post_order_id );
		$this->sut->read( $cot_order );

		$post_order = new WC_Order();
		$post_order->set_id( $post_order_id );
		$this->cpt_data_store->read( $post_order );

		$post_order_data    = $post_order->get_data();
		$string_to_num_keys = array( 'discount_total', 'discount_tax', 'shipping_total', 'shipping_tax', 'cart_tax' );
		array_walk(
			$post_order_data,
			function ( &$data, $key ) use ( $string_to_num_keys ) {
				if ( in_array( $key, $string_to_num_keys, true ) ) {
					$data = (float) $data;
				}
			}
		);

		$this->assertEquals( $post_order_data, $cot_order->get_data() );
	}

}
