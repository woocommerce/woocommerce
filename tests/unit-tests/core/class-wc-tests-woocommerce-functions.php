<?php
/**
 * Tests for the core functions.
 *
 * @package WooCommerce\Tests\Core
 * @since 3.2.0
 */

/**
 * Function tests.
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
			'created_via' => 'unit tests',
			'cart_hash'   => '',
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
		$current_theme = get_template();
		$this->assertTrue( wc_is_active_theme( $current_theme ) );
		$this->assertFalse( wc_is_active_theme( 'somegiberish' ) );
		$this->assertTrue( wc_is_active_theme( array( $current_theme, 'somegiberish' ) ) );
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
	 * Test wc_enqueue_js and wc_print_js functions.
	 *
	 * @return void
	 */
	public function test_wc_enqueue_js_wc_print_js() {
		$js = 'alert( "test" );';

		ob_start();
		wc_print_js();
		$printed_js = ob_get_clean();
		$this->assertNotContains( $js, $printed_js );

		wc_enqueue_js( $js );

		ob_start();
		wc_print_js();
		$printed_js = ob_get_clean();
		$this->assertContains( $js, $printed_js );
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
			'post_title'  => 'Parent Page',
			'post_type'   => 'page',
			'post_name'   => 'parent-page',
			'post_status' => 'publish',
			'post_author' => 1,
			'menu_order'  => 0,
		) );

		$child_page_id = wp_insert_post( array(
			'post_parent' => $page_id,
			'post_title'  => 'Parent Page',
			'post_type'   => 'page',
			'post_name'   => 'parent-page',
			'post_status' => 'publish',
			'post_author' => 1,
			'menu_order'  => 0,
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
		$this->assertTrue( hash_equals( 'abc', 'abc' ) ); // @codingStandardsIgnoreLine.
		$this->assertFalse( hash_equals( 'abcd', 'abc' ) ); // @codingStandardsIgnoreLine.
	}

	/**
	 * Test wc_rand_hash function.
	 *
	 * @return void
	 */
	public function test_wc_rand_hash() {
		$this->assertNotEquals( wc_rand_hash(), wc_rand_hash() );
	}

	/**
	 * Test wc_transaction_query function.
	 */
	public function test_wc_transaction_query() {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'options',
			array(
				'option_name'  => 'transaction_test',
				'option_value' => '1',
			),
			array(
				'%s',
				'%s',
			)
		);
		wc_transaction_query( 'start' );
		$this->assertTrue( WC_USE_TRANSACTIONS );
		$wpdb->update(
			$wpdb->prefix . 'options',
			array(
				'option_value' => '0',
			),
			array(
				'option_name' => 'transaction_test',
			)
		);
		$col = $wpdb->get_col( "SElECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'transaction_test'" );
		$this->assertEquals( '0', $col[0] );

		wc_transaction_query( 'rollback' );
		$col = $wpdb->get_col( "SElECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'transaction_test'" );
		$this->assertEquals( '1', $col[0] );

		wc_transaction_query( 'start' );
		$wpdb->update(
			$wpdb->prefix . 'options',
			array(
				'option_value' => '0',
			),
			array(
				'option_name' => 'transaction_test',
			)
		);
		wc_transaction_query( 'commit' );
		$col = $wpdb->get_col( "SElECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'transaction_test'" );
		$this->assertEquals( '0', $col[0] );

		$wpdb->delete(
			$wpdb->prefix . 'options',
			array(
				'option_name' => 'transaction_test',
			)
		);
	}
}
