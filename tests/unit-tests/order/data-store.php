<?php

/**
 * Orders Data Store.
 * @package WooCommerce\Tests\data-store
 */
class WC_Tests_Orders_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Test: New saved objects create shipping data.
	 */
	function test_shipping_meta_saving_new_data() {

		// Should be able to create data on a fresh object.
		$order = new WC_Order;
		$order->set_shipping_first_name( 'test' );
		$order->save();
		$this->assertEquals( 'test', get_post_meta( $order->get_id(), '_shipping_first_name', true ) );

		// Default data doesn't get saved to the DB.
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_shipping_last_name' ) );
	}

	/**
	 * Test: Existing saved objects update properly.
	 */
	function test_shipping_meta_saving() {

		// Should not create DB data if no title set.
		$order = new WC_Order;
		$order->save();
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_shipping_first_name' ) );

		// Should be able to update data.
		$order->set_shipping_first_name( 'test' );
		$order->save();
		$this->assertEquals( 'test', get_post_meta( $order->get_id(), '_shipping_first_name', true ) );
		$order->set_shipping_first_name( '' );
		$order->save();
		$this->assertEquals( '', get_post_meta( $order->get_id(), '_shipping_first_name', true ) );

		// Default data doesn't get saved to the DB.
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_shipping_last_name' ) );
	}
}
