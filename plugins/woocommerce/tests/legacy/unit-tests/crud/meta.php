<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Test meta for https://github.com/woocommerce/woocommerce/issues/13533.
 *
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Meta_Data extends WC_Unit_Test_Case {

	private $item_id  = 0;
	private $order_id = 0;

	/**
	 * A callback for the hooks triggered by eg_disappearing_order_meta() and
	 * eg_disappearing_item_meta() that saves additional meta data on the
	 * object via by pre-CRUD and CRUD methods.
	 */
	function add_different_object_meta( $object_id ) {

		// Get a new instance of the item or order object
		$object = ( $this->item_id == $object_id ) ? WC_Order_Factory::get_order_item( $object_id ) : wc_get_order( $object_id );
		$object->add_meta_data( 'random_other', 'This might disappear :cry:.' );
		$object->save(); // 'random_other' will be saved correctly, 'random' will also be saved

		// Now set some meta data for it using the pre-CRUD approach
		if ( $this->item_id == $object_id ) {
			wc_update_order_item_meta( $object_id, 'random_other_pre_crud', 'This might disappear too :sob:.' );
		} else {
			update_post_meta( $object_id, 'random_other_pre_crud', 'This might disappear too :sob:.' );
		}
	}

	/**
	 * Instantiate an instance of an order line item, save meta on that item, then trigger a hook which only passes the
	 * item's ID to callbacks. Afterwards, save the existing item without that other meta here and log the result to
	 * show that data has been removed.
	 */
	function test_disappearing_item_meta() {
		// Setup for testing by making an item.
		$item          = new WC_Order_Item_Product();
		$this->item_id = $item->save();

		$item = WC_Order_Factory::get_order_item( $this->item_id );

		// First make sure we're starting from a clean slate
		$item->delete_meta_data( 'random' );
		$item->delete_meta_data( 'random_other' );
		$item->delete_meta_data( 'random_other_pre_crud' );
		$item->save();

		// Now add one piece of meta
		$item->add_meta_data( 'random', (string) rand( 0, 0xffffff ) );
		$item->save(); // 'random' will be saved correctly

		// Run callback that passes just the item's ID to mimic this kind of normal behaviour in WP/WC
		$this->add_different_object_meta( $this->item_id );

		// Resave our current item that has our meta data, but not the other piece of meta data
		$item->save();

		// Get a new instance that should have both 'random' and 'random_other' set on it
		$new_item = WC_Order_Factory::get_order_item( $this->item_id );

		// The original $item should have 1 item of meta - random.
		$this->assertCount( 1, $item->get_meta_data() );
		$this->assertTrue( in_array( 'random', wp_list_pluck( $item->get_meta_data(), 'key' ) ) );

		// The new $item should have 3 items of meta since it's freshly loaded.
		$this->assertCount( 3, $new_item->get_meta_data() );
		$this->assertTrue( in_array( 'random', wp_list_pluck( $new_item->get_meta_data(), 'key' ) ) );
		$this->assertTrue( in_array( 'random_other', wp_list_pluck( $new_item->get_meta_data(), 'key' ) ) );
		$this->assertTrue( in_array( 'random_other_pre_crud', wp_list_pluck( $new_item->get_meta_data(), 'key' ) ) );
	}

	/**
	 * Instantiate an instance of an order, save meta on that order, then trigger a hook which only passes the order's ID to
	 * callbacks. Afterwards, save the existing order without that other meta here and log the result to show that data
	 * has been removed.
	 */
	function test_disappearing_order_meta() {
		// Setup for testing by making an item.
		$order          = new WC_Order();
		$this->order_id = $order->save();

		$order = wc_get_order( $this->order_id );

		// First make sure we're starting from a clean slate
		$order->delete_meta_data( 'random' );
		$order->delete_meta_data( 'random_other' );
		$order->delete_meta_data( 'random_other_pre_crud' );
		$order->save();
		// Track the number of meta items we originally have for future assertions.
		$original_meta_count = count( $order->get_meta_data() );

		// Now add one piece of meta
		$order->add_meta_data( 'random', (string) rand( 0, 0xffffff ) );
		$order->save(); // 'random' will be saved correctly

		// Run callback that passes just the item's ID to mimic this kind of normal behaviour in WP/WC
		$this->add_different_object_meta( $this->order_id );

		// Resave our current item that has our meta data, but not the other piece of meta data
		$order->save();

		// Get a new instance of the same order. It should have both 'random' and 'random_other' set on it
		$new_order = wc_get_order( $this->order_id );

		// The original $order should have $original_meta_count + 1 item of meta - random.
		$this->assertCount( $original_meta_count + 1, $order->get_meta_data() );
		$this->assertTrue( in_array( 'random', wp_list_pluck( $order->get_meta_data(), 'key' ) ) );

		$expected_new_meta = OrderUtil::custom_orders_table_usage_is_enabled() ? 2 : 3;
		// The new $order should have 3 items (or 2 in case of HPOS since direct post updates are not read) of meta since it's freshly loaded.
		$this->assertCount( $expected_new_meta + $original_meta_count, $new_order->get_meta_data() );
		$this->assertTrue( in_array( 'random', wp_list_pluck( $new_order->get_meta_data(), 'key' ) ) );
		$this->assertTrue( in_array( 'random_other', wp_list_pluck( $new_order->get_meta_data(), 'key' ) ) );
		if ( ! OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->assertTrue( in_array( 'random_other_pre_crud', wp_list_pluck( $new_order->get_meta_data(), 'key' ), true ) );
		}
	}

	/**
	 * Tests that the meta data cache gets flushed when update_post_meta updates the object's meta.
	 * @see https://github.com/woocommerce/woocommerce/issues/15274
	 */
	function test_get_meta_data_after_update_post_meta() {
		// Create an object.
		$object = new WC_Product();
		$object->save();

		// Update a meta value.
		update_post_meta( $object->get_id(), '_some_meta_key', 'val1' );
		$product = wc_get_product( $object->get_id() );
		$this->assertEquals( 'val1', $product->get_meta( '_some_meta_key', true ) );

		// Update meta to diff value.
		update_post_meta( $object->get_id(), '_some_meta_key', 'val2' );
		$product = wc_get_product( $object->get_id() );
		$this->assertEquals( 'val2', $product->get_meta( '_some_meta_key', true ) );
	}

	/**
	 * Tests setting objects and strings in meta to ensure slashing/unslashing works.
	 */
	function test_strings_in_meta() {
		// Create objects.
		$object                 = new WC_Product();
		$object_to_store        = new stdClass();
		$object_to_store->prop1 = 'prop_value';
		$object_to_store->prop2 = 'prop_value_with_\\\"quotes"';

		$object->add_meta_data( 'Test Object', $object_to_store );
		$object->add_meta_data( 'Test meta with slash', 'Test\slashes' );
		$object_id = $object->save();

		// Get object and check it.
		$object = wc_get_product( $object_id );
		$value  = $object->get_meta( 'Test Object', true );

		$this->assertEquals( $object_to_store, $object->get_meta( 'Test Object', true ) );
		$this->assertEquals( 'Test\slashes', $object->get_meta( 'Test meta with slash', true ) );

		// clean
		$object->delete();
	}
}
