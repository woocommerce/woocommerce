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
	 * Helper to insert a stub attachment post for gallery tests.
	 *
	 * @param int $parent_id Optional parent post ID.
	 *
	 * @return int Attachment post ID.
	 */
	private function insert_gallery_attachment( int $parent_id = 0 ): int {
		return (int) wp_insert_attachment(
			array(
				'post_title'     => 'Gallery image ' . wp_generate_password( 6, false ),
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/png',
				'post_status'    => 'inherit',
			),
			'',
			$parent_id
		);
	}

	/**
	 * @testdox Should remove the deleted attachment ID from a product gallery meta when the attachment is deleted.
	 */
	public function test_remove_deleted_attachment_from_product_galleries_strips_middle_id(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$gallery_ids = array(
			$this->insert_gallery_attachment( $product->get_id() ),
			$this->insert_gallery_attachment( $product->get_id() ),
			$this->insert_gallery_attachment( $product->get_id() ),
		);

		update_post_meta( $product->get_id(), '_product_image_gallery', implode( ',', $gallery_ids ) );

		WC_Post_Data::remove_deleted_attachment_from_product_galleries( $gallery_ids[1] );

		$gallery_after = get_post_meta( $product->get_id(), '_product_image_gallery', true );

		$this->assertSame(
			$gallery_ids[0] . ',' . $gallery_ids[2],
			$gallery_after,
			'Deleted attachment ID should be removed from the gallery meta.'
		);
	}

	/**
	 * @testdox Should remove the attachment ID when it is the only item in the gallery.
	 */
	public function test_remove_deleted_attachment_from_product_galleries_handles_single_id(): void {
		$product       = \WC_Helper_Product::create_simple_product();
		$attachment_id = $this->insert_gallery_attachment( $product->get_id() );

		update_post_meta( $product->get_id(), '_product_image_gallery', (string) $attachment_id );

		WC_Post_Data::remove_deleted_attachment_from_product_galleries( $attachment_id );

		$gallery_after = get_post_meta( $product->get_id(), '_product_image_gallery', true );

		$this->assertSame( '', $gallery_after, 'Gallery meta should become empty when the sole image is deleted.' );
	}

	/**
	 * @testdox Should remove the attachment ID when it is the last item in the gallery.
	 */
	public function test_remove_deleted_attachment_from_product_galleries_handles_trailing_id(): void {
		$product = \WC_Helper_Product::create_simple_product();

		$gallery_ids = array(
			$this->insert_gallery_attachment( $product->get_id() ),
			$this->insert_gallery_attachment( $product->get_id() ),
		);

		update_post_meta( $product->get_id(), '_product_image_gallery', implode( ',', $gallery_ids ) );

		WC_Post_Data::remove_deleted_attachment_from_product_galleries( $gallery_ids[1] );

		$this->assertSame(
			(string) $gallery_ids[0],
			get_post_meta( $product->get_id(), '_product_image_gallery', true ),
			'Trailing attachment ID should be removed from the gallery meta.'
		);
	}

	/**
	 * @testdox Should clean gallery meta across every product that references the deleted attachment.
	 */
	public function test_remove_deleted_attachment_from_product_galleries_cleans_multiple_products(): void {
		$product_a = \WC_Helper_Product::create_simple_product();
		$product_b = \WC_Helper_Product::create_simple_product();

		$shared_id = $this->insert_gallery_attachment();
		$other_a   = $this->insert_gallery_attachment( $product_a->get_id() );
		$other_b   = $this->insert_gallery_attachment( $product_b->get_id() );

		update_post_meta( $product_a->get_id(), '_product_image_gallery', $other_a . ',' . $shared_id );
		update_post_meta( $product_b->get_id(), '_product_image_gallery', $shared_id . ',' . $other_b );

		WC_Post_Data::remove_deleted_attachment_from_product_galleries( $shared_id );

		$this->assertSame(
			(string) $other_a,
			get_post_meta( $product_a->get_id(), '_product_image_gallery', true ),
			'Shared attachment ID should be removed from product A gallery meta.'
		);
		$this->assertSame(
			(string) $other_b,
			get_post_meta( $product_b->get_id(), '_product_image_gallery', true ),
			'Shared attachment ID should be removed from product B gallery meta.'
		);
	}

	/**
	 * @testdox Should leave unrelated gallery meta untouched when an unrelated attachment is deleted.
	 */
	public function test_remove_deleted_attachment_from_product_galleries_leaves_unrelated_meta_intact(): void {
		$product     = \WC_Helper_Product::create_simple_product();
		$gallery_ids = array(
			$this->insert_gallery_attachment( $product->get_id() ),
			$this->insert_gallery_attachment( $product->get_id() ),
		);

		$gallery_meta = implode( ',', $gallery_ids );
		update_post_meta( $product->get_id(), '_product_image_gallery', $gallery_meta );

		$unrelated_id = $this->insert_gallery_attachment();

		WC_Post_Data::remove_deleted_attachment_from_product_galleries( $unrelated_id );

		$this->assertSame(
			$gallery_meta,
			get_post_meta( $product->get_id(), '_product_image_gallery', true ),
			'Unrelated attachment deletion should not modify gallery meta.'
		);
	}

	/**
	 * @testdox Should not match partial numeric overlaps in gallery meta when an attachment is deleted.
	 */
	public function test_remove_deleted_attachment_from_product_galleries_avoids_substring_matches(): void {
		$product = \WC_Helper_Product::create_simple_product();

		// Gallery meta containing IDs that share a numeric substring with the deleted ID (e.g. 123 vs 1234).
		$gallery_meta = '1234,12345,123';
		update_post_meta( $product->get_id(), '_product_image_gallery', $gallery_meta );

		WC_Post_Data::remove_deleted_attachment_from_product_galleries( 12 );

		$this->assertSame(
			$gallery_meta,
			get_post_meta( $product->get_id(), '_product_image_gallery', true ),
			'Gallery meta must not be modified when the deleted ID only appears as a substring.'
		);
	}

	/**
	 * @testdox Should be triggered by the delete_attachment WordPress action.
	 */
	public function test_delete_attachment_hook_removes_id_from_gallery(): void {
		$product       = \WC_Helper_Product::create_simple_product();
		$attachment_id = $this->insert_gallery_attachment( $product->get_id() );
		$keep_id       = $this->insert_gallery_attachment( $product->get_id() );

		update_post_meta( $product->get_id(), '_product_image_gallery', $attachment_id . ',' . $keep_id );

		wp_delete_attachment( $attachment_id, true );

		$this->assertSame(
			(string) $keep_id,
			get_post_meta( $product->get_id(), '_product_image_gallery', true ),
			'wp_delete_attachment should trigger gallery meta cleanup via the delete_attachment hook.'
		);
	}
}
