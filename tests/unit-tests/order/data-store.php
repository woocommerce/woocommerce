<?php

/**
 * Orders Data Store.
 * @package WooCommerce\Tests\data-store
 */
class WC_Tests_Orders_Data_Store extends WC_Unit_Test_Case {

	/**
	 * Test: Saving creates shipping data if shipping enabled.
	 */
	function test_shipping_saving_shipping_enabled() {
		add_filter( 'wc_shipping_enabled', '__return_true' );

		$order = new WC_Order;
		$order->save();

		// Should create empty data if shipping is enabled.
		$this->assertTrue( metadata_exists( 'post', $order->get_id(), '_shipping_first_name' ) );

		$order->set_shipping_first_name( 'test' );
		$order->save();

		// Should be able to update data if shipping is enabled.
		$this->assertEquals( 'test', get_post_meta( $order->get_id(), '_shipping_first_name', true ) );
	}

	/**
	 * Test: Saving doesn't always create shipping data if shipping disabled.
	 */
	function test_shipping_saving_shipping_disabled() {
		add_filter( 'wc_shipping_enabled', '__return_false' );

		$order = new WC_Order;
		$order->save();

		// Should not create empty data if shipping disabled.
		$this->assertFalse( metadata_exists( 'post', $order->get_id(), '_shipping_first_name' ) );

		$order->set_shipping_first_name( 'test' );
		$order->save();

		// Should save shipping data when manually set even if shipping disabled.
		$this->assertEquals( 'test', get_post_meta( $order->get_id(), '_shipping_first_name', true ) );

		$order->set_shipping_first_name( '' );
		$order->save();

		// Should save empty shipping data if shipping metadata already exists.
		$this->assertEquals( '', get_post_meta( $order->get_id(), '_shipping_first_name', true ) );
	}

}
