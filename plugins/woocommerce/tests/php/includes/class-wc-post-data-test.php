<?php
/**
 * Post data tests
 *
 * @package WooCommerce\Tests\Post_Data.
 */

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Class WC_Post_Data_Test
 */
class WC_Post_Data_Test extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

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
	 * Helper: create a CPT-based shop_order post bypassing the order data store.
	 *
	 * Avoids triggering the data store's own delete hooks or HPOS sync, so we can
	 * exercise the WP-level `wp_trash_post` / `before_delete_post` / `trashed_post`
	 * / `deleted_post` hook chain on a real shop_order post regardless of whether
	 * HPOS is authoritative in the test environment.
	 *
	 * @return int Post ID of the created order.
	 */
	private function create_cpt_shop_order_post(): int {
		return wp_insert_post(
			array(
				'post_type'   => 'shop_order',
				'post_status' => 'wc-pending',
				'post_title'  => 'CPT order for hook test',
			)
		);
	}

	/**
	 * Switch the active order data store to the CPT one so `OrderUtil::is_order()`
	 * resolves against `wp_posts.post_type` for these hook tests.
	 *
	 * Disables HPOS sync first to avoid the "orders out of sync" guard when toggling.
	 *
	 * @return void
	 */
	private function switch_to_cpt_order_store(): void {
		// Bypass the "orders out of sync" guard in CustomOrdersTableController so we
		// can switch the authoritative store regardless of any HPOS leftovers from
		// other tests in this class (HPOS custom tables are not rolled back by
		// WP_UnitTestCase's transactions).
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		$this->disable_cot_sync();
		$this->toggle_cot_authoritative( false );
	}

	/**
	 * Restore HPOS as the authoritative store after a CPT-mode test.
	 *
	 * @return void
	 */
	private function restore_hpos_order_store(): void {
		$this->toggle_cot_authoritative( true );
		remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
	}

	/**
	 * @testdox woocommerce_trash_order fires when a CPT-based order is trashed via wp_trash_post.
	 */
	public function test_wp_trash_post_fires_woocommerce_trash_order_for_cpt_order(): void {
		$this->switch_to_cpt_order_store();

		try {
			$order_id = $this->create_cpt_shop_order_post();
			$this->assertGreaterThan( 0, $order_id );

			$before_trash_ids = array();
			$trash_ids        = array();

			$before_callback = static function ( $id ) use ( &$before_trash_ids ) {
				$before_trash_ids[] = $id;
			};
			$after_callback  = static function ( $id ) use ( &$trash_ids ) {
				$trash_ids[] = $id;
			};

			add_action( 'woocommerce_before_trash_order', $before_callback );
			add_action( 'woocommerce_trash_order', $after_callback );

			wp_trash_post( $order_id );

			remove_action( 'woocommerce_before_trash_order', $before_callback );
			remove_action( 'woocommerce_trash_order', $after_callback );

			$this->assertSame( array( $order_id ), $before_trash_ids, 'woocommerce_before_trash_order should fire once for the trashed order.' );
			$this->assertSame( array( $order_id ), $trash_ids, 'woocommerce_trash_order should fire once for the trashed order.' );
		} finally {
			$this->restore_hpos_order_store();
		}
	}

	/**
	 * @testdox woocommerce_delete_order fires when a CPT-based order is force-deleted via wp_delete_post.
	 */
	public function test_wp_delete_post_fires_woocommerce_delete_order_for_cpt_order(): void {
		$this->switch_to_cpt_order_store();

		try {
			$order_id = $this->create_cpt_shop_order_post();
			$this->assertGreaterThan( 0, $order_id );

			$before_delete_ids = array();
			$delete_ids        = array();

			$before_callback = static function ( $id ) use ( &$before_delete_ids ) {
				$before_delete_ids[] = $id;
			};
			$after_callback  = static function ( $id ) use ( &$delete_ids ) {
				$delete_ids[] = $id;
			};

			add_action( 'woocommerce_before_delete_order', $before_callback );
			add_action( 'woocommerce_delete_order', $after_callback );

			wp_delete_post( $order_id, true );

			remove_action( 'woocommerce_before_delete_order', $before_callback );
			remove_action( 'woocommerce_delete_order', $after_callback );

			$this->assertSame( array( $order_id ), $before_delete_ids, 'woocommerce_before_delete_order should fire once for the deleted order.' );
			$this->assertSame( array( $order_id ), $delete_ids, 'woocommerce_delete_order should fire once for the deleted order.' );
		} finally {
			$this->restore_hpos_order_store();
		}
	}

	/**
	 * @testdox WP post hook chain does not fire CPT-order hooks when the data store has marked the order.
	 */
	public function test_wp_post_hooks_skip_firing_when_order_handled_by_data_store(): void {
		$this->switch_to_cpt_order_store();
		$order_id = $this->create_cpt_shop_order_post();
		$this->assertGreaterThan( 0, $order_id );

		$before_trash_ids = array();
		$trash_ids        = array();
		$before_delete    = array();
		$delete_ids       = array();

		$cb_bt = static function ( $id ) use ( &$before_trash_ids ) {
			$before_trash_ids[] = $id;
		};
		$cb_t  = static function ( $id ) use ( &$trash_ids ) {
			$trash_ids[] = $id;
		};
		$cb_bd = static function ( $id ) use ( &$before_delete ) {
			$before_delete[] = $id;
		};
		$cb_d  = static function ( $id ) use ( &$delete_ids ) {
			$delete_ids[] = $id;
		};

		add_action( 'woocommerce_before_trash_order', $cb_bt );
		add_action( 'woocommerce_trash_order', $cb_t );
		add_action( 'woocommerce_before_delete_order', $cb_bd );
		add_action( 'woocommerce_delete_order', $cb_d );

		WC_Post_Data::set_order_handled_by_data_store( $order_id, true );

		try {
			wp_trash_post( $order_id );
			wp_delete_post( $order_id, true );
		} finally {
			WC_Post_Data::set_order_handled_by_data_store( $order_id, false );
			$this->restore_hpos_order_store();
		}

		remove_action( 'woocommerce_before_trash_order', $cb_bt );
		remove_action( 'woocommerce_trash_order', $cb_t );
		remove_action( 'woocommerce_before_delete_order', $cb_bd );
		remove_action( 'woocommerce_delete_order', $cb_d );

		$this->assertSame( array(), $before_trash_ids, 'woocommerce_before_trash_order must not fire when the data store has marked the order.' );
		$this->assertSame( array(), $trash_ids, 'woocommerce_trash_order must not fire when the data store has marked the order.' );
		$this->assertSame( array(), $before_delete, 'woocommerce_before_delete_order must not fire when the data store has marked the order.' );
		$this->assertSame( array(), $delete_ids, 'woocommerce_delete_order must not fire when the data store has marked the order.' );
	}

	/**
	 * @testdox woocommerce_delete_order does not fire for non-order post types.
	 */
	public function test_wp_delete_post_does_not_fire_woocommerce_delete_order_for_non_orders(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Generic post',
				'post_type'   => 'post',
				'post_status' => 'publish',
			)
		);

		$delete_ids = array();
		$callback   = static function ( $order_id ) use ( &$delete_ids ) {
			$delete_ids[] = $order_id;
		};

		add_action( 'woocommerce_delete_order', $callback );

		wp_delete_post( $post_id, true );

		remove_action( 'woocommerce_delete_order', $callback );

		$this->assertSame( array(), $delete_ids, 'woocommerce_delete_order should not fire for non-order post types.' );
	}
}
