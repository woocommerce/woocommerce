<?php

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Orders extends WC_Unit_Test_Case {

	/**
	 * Test: get_type
	 */
	function test_get_type() {
		$object = new WC_Order();
		$this->assertEquals( 'shop_order', $object->get_type() );
	}

	/**
	 * Test: CRUD
	 */
	function test_CRUD() {
		$object = new WC_Order();

		// Save + create
		$save_id = $object->save();
		$post    = get_post( $save_id );
		$this->assertEquals( 'shop_order', $post->post_type );
		$this->assertEquals( 'shop_order', $post->post_type );

		// Update
		$update_id = $object->save();
		$this->assertEquals( $update_id, $save_id );

		// Delete
		$object->delete();
		$post = get_post( $save_id );
		$this->assertNull( $post );
	}

}
