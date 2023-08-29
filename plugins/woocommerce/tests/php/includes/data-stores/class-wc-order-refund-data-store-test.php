<?php

use Automattic\WooCommerce\Caches\OrderDataCache;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

//phpcs:disable Squiz.Classes.ClassFileName.NoMatch, Squiz.Classes.ValidClassName.NotCamelCaps -- Legacy class name.
/**
 * Tests for the WC_Order_Refund_Data_Store_CPT class.
 */
class WC_Order_Refund_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var WC_Order_Refund_Data_Store_CPT
	 */
	private WC_Order_Refund_Data_Store_CPT $sut;

	/**
	 * Store the COT state before the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_Order_Refund_Data_Store_CPT();
	}

	/**
	 * @testdox Creating a refund deletes cached data related to the parent order.
	 */
	public function test_foo_creating_refund_deletes_cached_order_data() {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$order_data_cache = wc_get_container()->get( OrderDataCache::class );
		$order_data_cache->set( array( 'order_id' => $order_id ) );

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order_id );
		$this->sut->create( $refund );

		$this->assertFalse( $order_data_cache->is_cached( $order_id ) );
	}

	/**
	 * @testdox Updating or deleting a refund deletes cached data related to the parent order.
	 *
	 * @testWith ["update", "delete"]
	 *
	 * @param string $operation The operation to perform (the name of a public method in the tested class).
	 */
	public function test_updating_or_deleting_refund_deletes_cached_order_data( string $operation ) {
		$order    = OrderHelper::create_order();
		$order_id = $order->get_id();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order_id );
		$this->sut->create( $refund );

		$order_data_cache = wc_get_container()->get( OrderDataCache::class );
		$order_data_cache->set( array( 'order_id' => $order_id ) );

		$this->sut->$operation( $refund );

		$this->assertFalse( $order_data_cache->is_cached( $order_id ) );
	}
}
