<?php

/**
 * Class Functions.
 *
 * @package WooCommerce\Tests\Core
 * @since 3.2.0
 */
class WC_Tests_WooCommerce_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests wc_maybe_define_constant().
	 *
	 * @since 3.2.0
	 */
	public function test_wc_maybe_define_constant() {
		$this->assertFalse( defined( 'WC_TESTING_DEFINE_FUNCTION' ) );

		// Check if defined.
		wc_maybe_define_constant( 'WC_TESTING_DEFINE_FUNCTION', true );
		$this->assertTrue( defined( 'WC_TESTING_DEFINE_FUNCTION' ) );

		// Check value.
		wc_maybe_define_constant( 'WC_TESTING_DEFINE_FUNCTION', false );
		$this->assertTrue( WC_TESTING_DEFINE_FUNCTION );
	}

	/**
	 * Tests wc_create_order() and wc_update_order() currency handling.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_create_update_order_currency() {
		$old_currency = get_woocommerce_currency();
		$new_currency = 'BGN';

		update_option( 'woocommerce_currency', $new_currency );

		// New order should be created using shop currency.
		$order = wc_create_order( array(
			'status'      => 'pending',
			'customer_id' => 1,
			'created_via'	=> 'unit tests',
			'cart_hash'		=> '',
		) );
		$this->assertEquals( $new_currency, $order->get_currency() );

		update_option( 'woocommerce_currency', $old_currency );

		// Currency should not change when order is updated.
		$order = wc_update_order( array(
			'customer_id' => 2,
			'order_id'    => $order->get_id(),
		) );
		$this->assertEquals( $new_currency, $order->get_currency() );

		$order = wc_update_order( array(
			'customer_id' => 2,
		) );
		$this->assertInstanceOf( 'WP_Error', $order );
	}

	/**
	 * Test the wc_is_active_theme function.
	 *
	 * @return void
	 */
	public function test_wc_is_active_theme() {
		$this->assertTrue( wc_is_active_theme( 'default' ) );
		$this->assertFalse( wc_is_active_theme( 'twentyfifteen' ) );
		$this->assertTrue( wc_is_active_theme( array( 'default', 'twentyseventeen' ) ) );
	}

	/**
	 * Test the wc_get_template function.
	 *
	 * @return void
	 */
	public function test_wc_get_template_part() {
		$this->assertEmpty( wc_get_template_part( 'nothinghere' ) );
	}

	/**
	 * Test wc_get_image_size function.
	 *
	 * @return void
	 */
	public function test_wc_get_image_size() {
		$this->assertArrayHasKey( 'width', wc_get_image_size( array( 100, 100, 1 ) ) );
		$this->assertArrayHasKey( 'height', wc_get_image_size( 'shop_single' ) );
		update_option( 'woocommerce_thumbnail_cropping', 'uncropped' );
		$this->assertArrayHasKey( 'crop', wc_get_image_size( 'shop_thumbnail' ) );
		update_option( 'woocommerce_thumbnail_cropping', 'custom' );
		$this->assertArrayHasKey( 'crop', wc_get_image_size( 'shop_thumbnail' ) );
	}

	/**
	 * Test wc_enqueue_js function.
	 *
	 * @return void
	 */
	public function test_wc_enqueue_js() {
		global $wc_queued_js;
		$old_js = $wc_queued_js;
		wc_enqueue_js( 'alert( "test" );' );
		$this->assertNotEquals( $old_js, $wc_queued_js );
	}

	/**
	 * Test wc_print_js function.
	 *
	 * @return void
	 */
	public function test_wc_print_js() {
		global $wc_queued_js;
		wc_enqueue_js( 'alert( "test" );' );
		wc_print_js();
		$this->assertNotEmpty( $wc_queued_js );
	}

	/**
	 * Test wc_get_log_file_name function.
	 *
	 * @return void
	 */
	public function test_wc_get_log_file_name() {
		$this->assertNotEmpty( wc_get_log_file_name( 'test' ) );
	}

	/**
	 * Test wc_get_page_children function.
	 *
	 * @return void
	 */
	public function test_wc_get_page_children() {
		$page_id = wp_insert_post( array(
			'post_title'	=> 'Parent Page',
			'post_type' 	=> 'page',
			'post_name'		=> 'parent-page',
			'post_status'	=> 'publish',
			'post_author'	=> 1,
			'menu_order'	=> 0
		) );

		$child_page_id = wp_insert_post( array(
			'post_parent'	=> $page_id,
			'post_title'	=> 'Parent Page',
			'post_type' 	=> 'page',
			'post_name'		=> 'parent-page',
			'post_status'	=> 'publish',
			'post_author'	=> 1,
			'menu_order'	=> 0
		) );
		$children = wc_get_page_children( $page_id );
		$this->assertEquals( $child_page_id, $children[0] );

		wp_delete_post( $page_id, true );
		wp_delete_post( $child_page_id, true );
	}

	/**
	 * Test hash_equals function.
	 *
	 * @return void
	 */
	public function test_hash_equals() {
		$this->assertTrue( hash_equals( 'abc', 'abc' ) );
		$this->assertFalse( hash_equals( 'abcd', 'abc' ) );
	}

	/**
	 * Test wc_rand_hash function.
	 *
	 * @return void
	 */
	public function test_wc_rand_hash() {
		$this->assertNotEquals( wc_rand_hash(), wc_rand_hash() );
	}
}
