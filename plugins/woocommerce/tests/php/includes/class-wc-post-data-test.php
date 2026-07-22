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
	 * @testdox Reparenting a product category subtree recounts the affected hierarchy branches.
	 */
	public function test_reparenting_product_category_subtree_recounts_affected_hierarchy_branches(): void {
		$old_grandparent = wp_insert_term( 'Old grandparent', 'product_cat' );
		$new_grandparent = wp_insert_term( 'New grandparent', 'product_cat' );
		$old_parent      = wp_insert_term(
			'Old parent',
			'product_cat',
			array(
				'parent' => $old_grandparent['term_id'],
			)
		);
		$new_parent      = wp_insert_term(
			'New parent',
			'product_cat',
			array(
				'parent' => $new_grandparent['term_id'],
			)
		);
		$moved_category  = wp_insert_term(
			'Moved category',
			'product_cat',
			array(
				'parent' => $old_parent['term_id'],
			)
		);
		$leaf_category   = wp_insert_term(
			'Leaf category',
			'product_cat',
			array(
				'parent' => $moved_category['term_id'],
			)
		);
		$product         = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $leaf_category['term_id'] ),
			)
		);
		wc_recount_all_terms( false );

		$this->assertSame( 1, (int) get_term_meta( $old_grandparent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $old_parent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $moved_category['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $leaf_category['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 0, (int) get_term_meta( $new_grandparent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 0, (int) get_term_meta( $new_parent['term_id'], 'product_count_product_cat', true ) );

		$clear_term_cache = static function ( $term_id, $_tt_id, $taxonomy ) use ( $moved_category ) {
			if ( (int) $moved_category['term_id'] === (int) $term_id && 'product_cat' === $taxonomy ) {
				clean_term_cache( $term_id, $taxonomy );
			}
		};
		add_action( 'edit_term', $clear_term_cache, 9, 3 );

		wp_update_term(
			$moved_category['term_id'],
			'product_cat',
			array(
				'parent' => $new_parent['term_id'],
			)
		);
		remove_action( 'edit_term', $clear_term_cache, 9 );

		$this->assertSame( 0, (int) get_term_meta( $old_grandparent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 0, (int) get_term_meta( $old_parent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $moved_category['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $leaf_category['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $new_grandparent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $new_parent['term_id'], 'product_count_product_cat', true ) );

		$product->delete( true );
		wp_delete_term( $leaf_category['term_id'], 'product_cat' );
		wp_delete_term( $moved_category['term_id'], 'product_cat' );
		wp_delete_term( $old_parent['term_id'], 'product_cat' );
		wp_delete_term( $new_parent['term_id'], 'product_cat' );
		wp_delete_term( $old_grandparent['term_id'], 'product_cat' );
		wp_delete_term( $new_grandparent['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Editing a product category without changing its parent does not recount products.
	 */
	public function test_editing_product_category_without_reparenting_does_not_recount_products(): void {
		$category         = wp_insert_term( 'Category', 'product_cat' );
		$recount_attempts = 0;
		$track_recounts   = function ( $should_recount ) use ( &$recount_attempts ) {
			++$recount_attempts;
			return $should_recount;
		};
		add_filter( 'woocommerce_product_recount_terms', $track_recounts );

		wp_update_term(
			$category['term_id'],
			'product_cat',
			array(
				'name' => 'Renamed category',
			)
		);

		remove_filter( 'woocommerce_product_recount_terms', $track_recounts );

		$this->assertSame( 0, $recount_attempts );
		wp_delete_term( $category['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Nested edits of the same product category recount every parent visited.
	 */
	public function test_nested_product_category_edits_recount_every_parent_visited(): void {
		$old_parent          = wp_insert_term( 'Old parent', 'product_cat' );
		$intermediate_parent = wp_insert_term( 'Intermediate parent', 'product_cat' );
		$final_parent        = wp_insert_term( 'Final parent', 'product_cat' );
		$moved_category      = wp_insert_term(
			'Moved category',
			'product_cat',
			array(
				'parent' => $old_parent['term_id'],
			)
		);
		$product             = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $moved_category['term_id'] ),
			)
		);
		$run_nested_edit     = true;
		$nested_edit         = static function ( $tt_id, $taxonomy ) use ( &$run_nested_edit, $moved_category, $intermediate_parent ) {
			if ( ! $run_nested_edit || (int) $moved_category['term_taxonomy_id'] !== (int) $tt_id || 'product_cat' !== $taxonomy ) {
				return;
			}

			$run_nested_edit = false;
			wp_update_term(
				$moved_category['term_id'],
				'product_cat',
				array(
					'parent' => $intermediate_parent['term_id'],
				)
			);
		};
		wc_recount_all_terms( false );
		add_action( 'edit_term_taxonomy', $nested_edit, 20, 2 );

		wp_update_term(
			$moved_category['term_id'],
			'product_cat',
			array(
				'parent' => $final_parent['term_id'],
			)
		);
		remove_action( 'edit_term_taxonomy', $nested_edit, 20 );

		$this->assertSame( (int) $final_parent['term_id'], (int) get_term( $moved_category['term_id'], 'product_cat' )->parent );
		$this->assertSame( 0, (int) get_term_meta( $old_parent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 0, (int) get_term_meta( $intermediate_parent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $final_parent['term_id'], 'product_count_product_cat', true ) );

		$product->delete( true );
		wp_delete_term( $moved_category['term_id'], 'product_cat' );
		wp_delete_term( $old_parent['term_id'], 'product_cat' );
		wp_delete_term( $intermediate_parent['term_id'], 'product_cat' );
		wp_delete_term( $final_parent['term_id'], 'product_cat' );
	}

	/**
	 * @testdox Filtering a product category term ID does not prevent its parents from being recounted.
	 */
	public function test_filtering_product_category_term_id_does_not_prevent_parent_recount(): void {
		$old_parent     = wp_insert_term( 'Old parent', 'product_cat' );
		$new_parent     = wp_insert_term( 'New parent', 'product_cat' );
		$filtered_term  = wp_insert_term( 'Filtered term', 'product_cat' );
		$moved_category = wp_insert_term(
			'Moved category',
			'product_cat',
			array(
				'parent' => $old_parent['term_id'],
			)
		);
		$product        = WC_Helper_Product::create_simple_product(
			true,
			array(
				'category_ids' => array( $moved_category['term_id'] ),
			)
		);
		$filter_term_id = static function ( $term_id, $tt_id ) use ( $moved_category, $filtered_term ) {
			if ( (int) $moved_category['term_taxonomy_id'] === (int) $tt_id ) {
				return $filtered_term['term_id'];
			}

			return $term_id;
		};
		wc_recount_all_terms( false );
		add_filter( 'term_id_filter', $filter_term_id, 10, 2 );

		wp_update_term(
			$moved_category['term_id'],
			'product_cat',
			array(
				'parent' => $new_parent['term_id'],
			)
		);
		remove_filter( 'term_id_filter', $filter_term_id, 10 );

		$this->assertSame( 0, (int) get_term_meta( $old_parent['term_id'], 'product_count_product_cat', true ) );
		$this->assertSame( 1, (int) get_term_meta( $new_parent['term_id'], 'product_count_product_cat', true ) );

		$product->delete( true );
		wp_delete_term( $moved_category['term_id'], 'product_cat' );
		wp_delete_term( $old_parent['term_id'], 'product_cat' );
		wp_delete_term( $new_parent['term_id'], 'product_cat' );
		wp_delete_term( $filtered_term['term_id'], 'product_cat' );
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

	/**
	 * @testdox Should delete variation attribute meta when the parent variation attribute is removed.
	 */
	public function test_product_attributes_updated_deletes_stale_variation_attribute_meta(): void {
		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		foreach ( $variation_ids as $variation_id ) {
			update_post_meta( $variation_id, 'attribute_pa_colour', 'red' );
			$this->assertSame( 'red', get_post_meta( $variation_id, 'attribute_pa_colour', true ), 'Variation should start with colour attribute meta' );
		}

		$attributes = $product->get_attributes();
		unset( $attributes['pa_colour'] );
		$product->set_attributes( $attributes );
		$product->save();

		foreach ( $variation_ids as $variation_id ) {
			$this->assertFalse( metadata_exists( 'post', $variation_id, 'attribute_pa_colour' ), 'Removed parent variation attribute meta should be deleted from each child variation' );
		}
		$this->assertSame( 'huge', get_post_meta( $variation_ids[2], 'attribute_pa_size', true ), 'Remaining parent variation attribute meta should be preserved' );

		$product       = WC_Helper_Product::create_variation_product();
		$variation_ids = $product->get_children();

		foreach ( $variation_ids as $variation_id ) {
			update_post_meta( $variation_id, 'attribute_pa_size', 'huge' );
			update_post_meta( $variation_id, 'attribute_pa_colour', 'red' );
			update_post_meta( $variation_id, 'attribute_pa_number', '2' );
		}

		$product->set_attributes( array() );
		$product->save();

		foreach ( $variation_ids as $variation_id ) {
			$this->assertFalse( metadata_exists( 'post', $variation_id, 'attribute_pa_size' ), 'Removed size attribute meta should be deleted from each child variation' );
			$this->assertFalse( metadata_exists( 'post', $variation_id, 'attribute_pa_colour' ), 'Removed colour attribute meta should be deleted from each child variation' );
			$this->assertFalse( metadata_exists( 'post', $variation_id, 'attribute_pa_number' ), 'Removed number attribute meta should be deleted from each child variation' );
		}
	}
}
