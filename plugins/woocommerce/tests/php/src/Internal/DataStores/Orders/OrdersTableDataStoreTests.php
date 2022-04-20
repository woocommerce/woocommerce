<?php

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\WPPostToCOTMigrator;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

class OrdersTableDataStoreTests extends WC_Unit_Test_Case {

	/**
	 * @var WPPostToCOTMigrator
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

	public function setUp(): void {
		parent::setUp();
		OrderHelper::create_order_custom_table_if_not_exist();
		$this->sut = wc_get_container()->get( OrdersTableDataStore::class );
		$this->migrator = wc_get_container()->get( WPPostToCOTMigrator::class );
		$this->cpt_data_store = new WC_Order_Data_Store_CPT();
	}

	public function test_read_from_migrated_order() {
		$post_order_id = OrderHelper::create_complex_wp_post_order();
		$this->migrator->process_migration_for_ids( array( $post_order_id ) );

		$cot_order = new WC_Order();
		$cot_order->set_id( $post_order_id );
		$this->sut->read( $cot_order );

		$post_order = new WC_Order();
		$post_order->set_id( $post_order_id );
		$this->cpt_data_store->read( $post_order );

		$this->assertEquals( $cot_order->get_status(), $post_order->get_status() );
	}

}
