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

	/**
	 * @testdox Renaming an attribute term should rename the defaults of several products together and keep a default saved while the rename runs.
	 */
	public function test_renaming_attribute_term_keeps_defaults_saved_during_the_rename(): void {
		global $wpdb;
		$attribute = WC_Helper_Product::create_attribute( 'color', array( 'blue' ) );
		$taxonomy  = $attribute['attribute_taxonomy'];
		$products  = array();
		foreach ( array( 'renamed', 'saved' ) as $label ) {
			$products[ $label ] = new WC_Product_Variable();
			$products[ $label ]->set_default_attributes(
				array(
					$taxonomy => 'blue',
					'note'    => $label,
				)
			);
			$products[ $label ]->save();
		}
		$saved_meanwhile = array(
			$taxonomy => 'blue',
			'note'    => 'saved meanwhile',
		);
		// Another request saves the second product between the rename reading the rows and writing them.
		$save = function ( $query ) use ( $wpdb, $products, $saved_meanwhile, &$save ) {
			if ( 0 === strpos( $query, "UPDATE {$wpdb->postmeta} SET meta_value = CASE" ) ) {
				remove_filter( 'query', $save );
				$wpdb->update(
					$wpdb->postmeta,
					array( 'meta_value' => serialize( $saved_meanwhile ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					array(
						'post_id'  => $products['saved']->get_id(),
						'meta_key' => '_default_attributes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					)
				);
			}
			return $query;
		};
		add_filter( 'query', $save );

		wp_update_term( $attribute['term_ids'][0], $taxonomy, array( 'slug' => 'navy-blue' ) );
		remove_filter( 'query', $save );

		$this->assertSame(
			array(
				$taxonomy => 'navy-blue',
				'note'    => 'renamed',
			),
			get_post_meta( $products['renamed']->get_id(), '_default_attributes', true ),
			'The default for the renamed term should change'
		);
		$this->assertSame( $saved_meanwhile, get_post_meta( $products['saved']->get_id(), '_default_attributes', true ), 'A default saved during the rename should be kept' );
	}

	/**
	 * @testdox Renaming an attribute term should update the default attributes that use it and leave other default values unchanged.
	 */
	public function test_renaming_attribute_term_updates_only_matching_default_attributes(): void {
		$attribute = WC_Helper_Product::create_attribute( 'color', array( 'blue' ) );
		$taxonomy  = $attribute['attribute_taxonomy'];
		// A custom attribute value can hold the same text as the stored taxonomy/slug pair.
		$custom_value = serialize( $taxonomy ) . serialize( 'blue' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		$product      = new WC_Product_Variable();
		$product->set_default_attributes(
			array(
				$taxonomy => 'blue',
				'note'    => $custom_value,
			)
		);
		$product->save();

		wp_update_term( $attribute['term_ids'][0], $taxonomy, array( 'slug' => 'navy-blue' ) );

		$this->assertSame(
			array(
				$taxonomy => 'navy-blue',
				'note'    => $custom_value,
			),
			get_post_meta( $product->get_id(), '_default_attributes', true ),
			'Only the default for the renamed term should change'
		);
	}

	/**
	 * @testdox Renaming an attribute term should leave default attributes that hold objects unchanged, without creating the objects.
	 */
	public function test_renaming_attribute_term_leaves_default_attributes_with_objects_alone(): void {
		global $wpdb;

		ObjectInstantiationProbe::$was_unserialized = false;
		$attribute                                  = WC_Helper_Product::create_attribute( 'color', array( 'blue' ) );
		$taxonomy                                   = $attribute['attribute_taxonomy'];
		$product                                    = new WC_Product_Variable();
		$product->set_default_attributes( array( $taxonomy => 'blue' ) );
		$product->save();
		// Store the row directly, as only a corrupted row can hold an object.
		$stored = serialize( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			array(
				$taxonomy => 'blue',
				'probe'   => new ObjectInstantiationProbe(),
			)
		);
		$wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => $stored ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			array(
				'post_id'  => $product->get_id(),
				'meta_key' => '_default_attributes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);

		wp_update_term( $attribute['term_ids'][0], $taxonomy, array( 'slug' => 'navy-blue' ) );

		$this->assertFalse( ObjectInstantiationProbe::$was_unserialized, 'The rename must not create objects stored in default attributes' );
		$this->assertSame(
			$stored,
			$wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_default_attributes'", $product->get_id() ) ),
			'A row holding an object should not be rewritten'
		);
	}
}
