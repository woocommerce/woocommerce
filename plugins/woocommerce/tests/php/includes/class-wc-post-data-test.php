<?php
/**
 * Post data tests
 *
 * @package WooCommerce\Tests\Post_Data.
 */

use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class WC_Post_Data_Test
 */
class WC_Post_Data_Test extends \WC_Unit_Test_Case {

	use HPOSToggleTrait;

	/**
	 * Ensure the HPOS tables exist before per-test transactions start.
	 */
	public static function wpSetUpBeforeClass(): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		try {
			self::setup_cot_tables();
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				OrderHelper::toggle_cot_feature_and_usage( $previous_hpos_state );
			}
		} finally {
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
	}

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
	 * @testdox A coupon saved from the edit screen without a code is saved as a draft.
	 * @testWith ["publish", "", "draft"]
	 *           ["future", "", "draft"]
	 *           ["private", "", "draft"]
	 *           ["publish", "   ", "draft"]
	 *           ["pending", "", "pending"]
	 *           ["draft", "", "draft"]
	 *           ["publish", "has-code", "publish"]
	 *
	 * @param string $requested_status Status the edit screen asks for.
	 * @param string $coupon_code      Coupon code entered on the edit screen.
	 * @param string $expected_status  Status that should be saved.
	 */
	public function test_coupon_edit_screen_saves_coupon_without_code_as_draft( string $requested_status, string $coupon_code, string $expected_status ): void {
		$post_id = $this->create_auto_draft_coupon();
		$postarr = array(
			'ID'          => $post_id,
			'post_title'  => $coupon_code,
			'post_status' => $requested_status,
		);
		if ( 'future' === $requested_status ) {
			$postarr['post_date'] = gmdate( 'Y-m-d H:i:s', strtotime( '+1 week' ) );
		}

		$this->submit_coupon_edit_screen( $post_id, $postarr );

		$this->assertSame( $expected_status, get_post_status( $post_id ) );
	}

	/**
	 * @testdox Clearing the code of a published coupon on the edit screen unpublishes it through the normal status transition.
	 */
	public function test_coupon_edit_screen_unpublishes_coupon_when_code_is_cleared(): void {
		$coupon      = WC_Helper_Coupon::create_coupon( 'clear-me' );
		$transitions = array();
		$listener    = function ( $new_status, $old_status, $post ) use ( &$transitions, $coupon ) {
			if ( $post->ID === $coupon->get_id() ) {
				$transitions[] = $old_status . '>' . $new_status;
			}
		};

		add_action( 'transition_post_status', $listener, 10, 3 );

		try {
			$this->submit_coupon_edit_screen(
				$coupon->get_id(),
				array(
					'ID'          => $coupon->get_id(),
					'post_title'  => '',
					'post_status' => 'publish',
				)
			);
		} finally {
			remove_action( 'transition_post_status', $listener, 10 );
		}

		$this->assertSame( 'draft', get_post_status( $coupon->get_id() ) );
		$this->assertSame( '', get_post_field( 'post_title', $coupon->get_id() ) );
		$this->assertSame( array( 'publish>draft' ), $transitions );
	}

	/**
	 * @testdox Other coupons without a code saved during a coupon edit screen request keep their status.
	 */
	public function test_coupon_edit_screen_does_not_change_other_coupons_without_code(): void {
		$edited_post_id = $this->create_auto_draft_coupon();
		$other_post_id  = $this->create_auto_draft_coupon();

		$this->submit_coupon_edit_screen(
			$edited_post_id,
			array(
				'ID'          => $other_post_id,
				'post_title'  => '',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'publish', get_post_status( $other_post_id ) );
	}

	/**
	 * @testdox A coupon without a code saved outside the edit screen keeps its status.
	 */
	public function test_coupon_without_code_saved_outside_edit_screen_keeps_status(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => '',
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
			)
		);

		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}

	/**
	 * Create an auto-draft coupon, like the one WordPress creates when the new coupon screen opens.
	 *
	 * @return int
	 */
	private function create_auto_draft_coupon(): int {
		return wp_insert_post(
			array(
				'post_title'  => 'Auto Draft',
				'post_type'   => 'shop_coupon',
				'post_status' => 'auto-draft',
			)
		);
	}

	/**
	 * Save a post while simulating a coupon edit screen submission for a coupon.
	 *
	 * @param int   $edited_post_id ID of the coupon open on the edit screen.
	 * @param array $postarr        Post data to save.
	 */
	private function submit_coupon_edit_screen( int $edited_post_id, array $postarr ): void {
		$previous_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Restored after the save.
		$this->login_as_administrator();
		$_POST['post_ID']                = (string) $edited_post_id;
		$_POST['woocommerce_meta_nonce'] = wp_create_nonce( 'woocommerce_save_data' );

		try {
			wp_update_post( $postarr );
		} finally {
			$_POST = $previous_post;
		}
	}

	/**
	 * @testdox Should remove order items when permanently deleting an order.
	 * @testWith [false]
	 *           [true]
	 *
	 * @param bool $hpos_enabled Whether HPOS is enabled.
	 */
	public function test_deleting_order_removes_items( bool $hpos_enabled ): void {
		$previous_hpos_state = OrderUtil::custom_orders_table_usage_is_enabled();
		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		try {
			$this->toggle_cot_authoritative( $hpos_enabled );

			$order    = OrderHelper::create_order();
			$item_ids = array_keys( $order->get_items() );
			$this->assertNotEmpty( $item_ids, 'The order should contain an item' );

			$order->delete( true );

			$this->assertFalse( WC_Order_Factory::get_order_item( reset( $item_ids ) ), 'The deleted order item should no longer be available' );
		} finally {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() !== $previous_hpos_state ) {
				$this->toggle_cot_authoritative( $previous_hpos_state );
			}
			add_filter( 'query', array( $this, '_create_temporary_tables' ) );
			add_filter( 'query', array( $this, '_drop_temporary_tables' ) );
			remove_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );
		}
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
