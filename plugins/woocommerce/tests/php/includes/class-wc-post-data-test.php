<?php
/**
 * Post data tests
 *
 * @package WooCommerce\Tests\Post_Data.
 */

/**
 * Class WC_Post_Data_Test
 */
class WC_Post_Data_Test extends \WC_Unit_Test_Case {

	/**
	 * @testdox coupon code should be always sanitized.
	 */
	public function test_coupon_code_sanitization() {
		$this->login_as_role( 'shop_manager' );
		$coupon    = WC_Helper_Coupon::create_coupon( 'a&a' );
		$post_data = get_post( $coupon->get_id() );
		$this->assertEquals( 'a&amp;a', $post_data->post_title );
		$coupon->delete( true );

		$this->login_as_administrator();
		$coupon    = WC_Helper_Coupon::create_coupon( 'b&b' );
		$post_data = get_post( $coupon->get_id() );
		$this->assertEquals( 'b&amp;b', $post_data->post_title );
		$coupon->delete( true );

		wp_set_current_user( 0 );
		$coupon    = WC_Helper_Coupon::create_coupon( 'c&c' );
		$post_data = get_post( $coupon->get_id() );
		$this->assertEquals( 'c&amp;c', $post_data->post_title );
		$coupon->delete( true );
	}

	/**
	 * Order items should be deleted before deleting order.
	 */
	public function test_before_delete_order() {
		$order = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order();
		$items = $order->get_items();
		$this->assertNotEmpty( $items );

		WC_Post_Data::before_delete_order( $order->get_id() );
		$order = wc_get_order( $order->get_id() );
		$this->assertEmpty( $order->get_items() );
	}

	/**
	 * @testdox Should fire woocommerce_product_published when product transitions to publish status.
	 */
	public function test_transition_post_status_fires_product_published_action(): void {
		$product = \WC_Helper_Product::create_simple_product( false );
		$product->set_status( 'draft' );
		$product->save();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $product->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'draft', $post );

		$this->assertContains( $product->get_id(), $published_ids, 'woocommerce_product_published should fire when product transitions to publish' );

		remove_action( 'woocommerce_product_published', $callback );
		$product->delete( true );
	}

	/**
	 * @testdox Should not fire woocommerce_product_published when product is already published and updated.
	 */
	public function test_transition_post_status_does_not_fire_product_published_on_update(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $product->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'publish', $post );

		$this->assertEmpty( $published_ids, 'woocommerce_product_published should not fire when product is already published' );

		remove_action( 'woocommerce_product_published', $callback );
		$product->delete( true );
	}

	/**
	 * @testdox Should not fire woocommerce_product_published for non-product post types.
	 */
	public function test_transition_post_status_does_not_fire_product_published_for_non_products(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test Post',
				'post_type'   => 'post',
				'post_status' => 'draft',
			)
		);

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $post_id );
		WC_Post_Data::transition_post_status( 'publish', 'draft', $post );

		$this->assertEmpty( $published_ids, 'woocommerce_product_published should not fire for non-product post types' );

		remove_action( 'woocommerce_product_published', $callback );
		wp_delete_post( $post_id, true );
	}

	/**
	 * @testdox Should fire woocommerce_product_published when a product variation transitions to publish status.
	 */
	public function test_transition_post_status_fires_product_published_action_for_variation(): void {
		$variation = new WC_Product_Variation();
		$variation->set_status( 'draft' );
		$variation->save();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $variation->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'draft', $post );

		$this->assertContains( $variation->get_id(), $published_ids, 'woocommerce_product_published should fire when a variation transitions to publish' );

		remove_action( 'woocommerce_product_published', $callback );
		$variation->delete( true );
	}

	/**
	 * @testdox Should fire woocommerce_product_published when a scheduled product transitions from future to publish.
	 */
	public function test_transition_post_status_fires_product_published_action_on_scheduled_publish(): void {
		$product = \WC_Helper_Product::create_simple_product( false );
		$product->set_status( 'future' );
		$product->save();

		$published_ids = array();
		$callback      = function ( $product_id ) use ( &$published_ids ) {
			$published_ids[] = $product_id;
		};
		add_action( 'woocommerce_product_published', $callback );

		$post = get_post( $product->get_id() );
		WC_Post_Data::transition_post_status( 'publish', 'future', $post );

		$this->assertContains( $product->get_id(), $published_ids, 'woocommerce_product_published should fire when a scheduled product transitions from future to publish' );

		remove_action( 'woocommerce_product_published', $callback );
		$product->delete( true );
	}

	/**
	 * @testdox do_deferred_product_sync should sync each queued product once (even if queued multiple times) and empty the queue.
	 */
	public function test_do_deferred_product_sync_syncs_queued_products(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();
		$product_1                = WC_Helper_Product::create_grouped_product();
		$product_2                = WC_Helper_Product::create_grouped_product();

		$synced_ids = array();
		$callback   = function ( $product_id ) use ( &$synced_ids ) {
			$synced_ids[] = $product_id;
		};
		add_action( 'woocommerce_update_product', $callback );

		wc_deferred_product_sync( $product_1->get_id() );
		wc_deferred_product_sync( $product_2->get_id() );
		wc_deferred_product_sync( $product_1->get_id() );

		WC_Post_Data::do_deferred_product_sync();

		remove_action( 'woocommerce_update_product', $callback );

		$this->assertSame( array( $product_1->get_id(), $product_2->get_id() ), $synced_ids, 'Each queued product should be synced exactly once' );
		$this->assertEmpty( $wc_deferred_product_sync, 'The queue should be empty after the sync' );
	}

	/**
	 * @testdox do_deferred_product_sync should also sync products that get deferred while another product is being synced.
	 */
	public function test_do_deferred_product_sync_processes_products_deferred_during_sync(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();
		$product_1                = WC_Helper_Product::create_grouped_product();
		$product_2                = WC_Helper_Product::create_grouped_product();

		$synced_ids = array();
		$callback   = function ( $product_id ) use ( &$synced_ids, $product_1, $product_2 ) {
			$synced_ids[] = $product_id;
			if ( $product_1->get_id() === $product_id ) {
				wc_deferred_product_sync( $product_2->get_id() );
			}
		};
		add_action( 'woocommerce_update_product', $callback );

		wc_deferred_product_sync( $product_1->get_id() );

		WC_Post_Data::do_deferred_product_sync();

		remove_action( 'woocommerce_update_product', $callback );

		$this->assertSame( array( $product_1->get_id(), $product_2->get_id() ), $synced_ids, 'Products deferred while syncing another product should be synced too' );
		$this->assertEmpty( $wc_deferred_product_sync, 'The queue should be empty after the sync' );
	}

	/**
	 * @testdox do_deferred_product_sync should terminate, syncing each product at most once, when synced products keep re-deferring each other.
	 */
	public function test_do_deferred_product_sync_terminates_on_mutual_re_deferral(): void {
		global $wc_deferred_product_sync;

		$wc_deferred_product_sync = array();
		$product_1                = WC_Helper_Product::create_grouped_product();
		$product_2                = WC_Helper_Product::create_grouped_product();

		// Each product defers the other one when synced, as e.g. translation plugins do.
		// With the old array_walk-based implementation this caused an infinite loop,
		// hence the cap on the number of syncs: it makes the test fail instead of hanging.
		$synced_ids = array();
		$callback   = function ( $product_id ) use ( &$synced_ids, $product_1, $product_2 ) {
			$synced_ids[] = $product_id;
			if ( count( $synced_ids ) > 100 ) {
				$this->fail( 'do_deferred_product_sync does not terminate when synced products keep re-deferring each other' );
			}
			wc_deferred_product_sync( $product_1->get_id() === $product_id ? $product_2->get_id() : $product_1->get_id() );
		};
		add_action( 'woocommerce_update_product', $callback );

		wc_deferred_product_sync( $product_1->get_id() );

		WC_Post_Data::do_deferred_product_sync();

		remove_action( 'woocommerce_update_product', $callback );

		$this->assertSame( array( $product_1->get_id(), $product_2->get_id() ), $synced_ids, 'Each product should be synced at most once per request' );
		$this->assertEmpty( $wc_deferred_product_sync, 'The queue should be empty after the sync' );
	}
}
