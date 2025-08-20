<?php
/**
 * Post Data
 *
 * Standardises certain post data on save.
 *
 * @package WooCommerce\Classes\Data
 * @version 2.2.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\OrderInternalStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore as ProductAttributesLookupDataStore;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\OrderUtil;

defined( 'ABSPATH' ) || exit;

/**
 * Post data class.
 */
class WC_Post_Data {

	/**
	 * Editing term.
	 *
	 * @var object
	 */
	private static $editing_term = null;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'post_type_link', array( __CLASS__, 'variation_post_link' ), 10, 2 );
		add_action( 'shutdown', array( __CLASS__, 'do_deferred_product_sync' ), 10 );
		add_action( 'set_object_terms', array( __CLASS__, 'force_default_term' ), 10, 5 );
		add_action( 'set_object_terms', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'deleted_term_relationships', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_set_visibility', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_type_changed', array( __CLASS__, 'product_type_changed' ), 10, 3 );

		add_action( 'edit_term', array( __CLASS__, 'edit_term' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'edited_term' ), 10, 3 );
		add_filter( 'update_order_item_metadata', array( __CLASS__, 'update_order_item_metadata' ), 10, 5 );
		add_filter( 'update_post_metadata', array( __CLASS__, 'update_post_metadata' ), 10, 5 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'wp_insert_post_data' ) );
		add_filter( 'oembed_response_data', array( __CLASS__, 'filter_oembed_response_data' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( __CLASS__, 'wp_untrash_post_status' ), 10, 3 );

		// Status transitions.
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'delete_post', array( __CLASS__, 'delete_post_data' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'trash_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'untrash_post' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_order' ) );
		add_action( 'woocommerce_before_delete_order', array( __CLASS__, 'before_delete_order' ) );

		// Meta cache flushing.
		add_action( 'updated_post_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
		add_action( 'added_post_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
		add_action( 'updated_order_item_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );

		// Product Variations - Attributes.
		// Priority 50 to make sure this runs after WooCommerce attribute migrations.
		add_action( 'woocommerce_attribute_updated', array( __CLASS__, 'handle_global_attribute_updated' ), 50, 3 );
		add_action( 'woocommerce_attribute_deleted', array( __CLASS__, 'handle_global_attribute_updated' ), 10, 3 );
		// Product Variations - Terms.
		add_action( 'edited_term', array( __CLASS__, 'handle_attribute_term_updated' ), 10, 3 );
		add_action( 'delete_term', array( __CLASS__, 'handle_attribute_term_deleted' ), 10, 4 );
		// Product Variations - Parent Product Updates Attributes.
		add_action( 'woocommerce_product_attributes_updated', array( __CLASS__, 'on_product_attributes_updated' ), 10, 1 );
		// Product Variations - Action Scheduler.
		add_action( 'wc_regenerate_product_variation_summaries', array( __CLASS__, 'regenerate_product_variation_summaries' ), 10, 1 );
		add_action( 'wc_regenerate_attribute_variation_summaries', array( __CLASS__, 'regenerate_attribute_variation_summaries' ), 10, 1 );
		add_action( 'wc_regenerate_term_variation_summaries', array( __CLASS__, 'regenerate_term_variation_summaries' ), 10, 2 );
	}

	/**
	 * Link to parent products when getting permalink for variation.
	 *
	 * @param string  $permalink Permalink.
	 * @param WP_Post $post      Post data.
	 *
	 * @return string
	 */
	public static function variation_post_link( $permalink, $post ) {
		if ( isset( $post->ID, $post->post_type ) && 'product_variation' === $post->post_type ) {
			$variation = wc_get_product( $post->ID );

			if ( $variation && $variation->get_parent_id() ) {
				return $variation->get_permalink();
			}
		}
		return $permalink;
	}

	/**
	 * Sync products queued to sync.
	 */
	public static function do_deferred_product_sync() {
		global $wc_deferred_product_sync;

		if ( ! empty( $wc_deferred_product_sync ) ) {
			$wc_deferred_product_sync = wp_parse_id_list( $wc_deferred_product_sync );
			array_walk( $wc_deferred_product_sync, array( __CLASS__, 'deferred_product_sync' ) );
		}
	}

	/**
	 * Sync a product.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function deferred_product_sync( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( is_callable( array( $product, 'sync' ) ) ) {
			$product->sync( $product );
		}
	}

	/**
	 * When a post status changes.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post data.
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( ( ProductStatus::PUBLISH === $new_status || ProductStatus::PUBLISH === $old_status ) && in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
			self::delete_product_query_transients();
		}
	}

	/**
	 * Delete product view transients when needed e.g. when post status changes, or visibility/stock status is modified.
	 */
	public static function delete_product_query_transients() {
		WC_Cache_Helper::get_transient_version( 'product_query', true );
	}

	/**
	 * Handle type changes.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Product $product Product data.
	 * @param string     $from    Origin type.
	 * @param string     $to      New type.
	 */
	public static function product_type_changed( $product, $from, $to ) {
		/**
		 * Filter to prevent variations from being deleted while switching from a variable product type to a variable product type.
		 *
		 * @since 5.0.0
		 *
		 * @param bool       A boolean value of true will delete the variations.
		 * @param WC_Product $product Product data.
		 * @return string    $from    Origin type.
		 * @param string     $to      New type.
		 */
		if ( apply_filters( 'woocommerce_delete_variations_on_product_type_change', ProductType::VARIABLE === $from && ProductType::VARIABLE !== $to, $product, $from, $to ) ) {
			// If the product is no longer variable, we should ensure all variations are removed.
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->delete_variations( $product->get_id(), true );
		}
	}

	/**
	 * When editing a term, check for product attributes.
	 *
	 * @param  int    $term_id  Term ID.
	 * @param  int    $tt_id    Term taxonomy ID.
	 * @param  string $taxonomy Taxonomy slug.
	 */
	public static function edit_term( $term_id, $tt_id, $taxonomy ) {
		if ( strpos( $taxonomy, 'pa_' ) === 0 ) {
			self::$editing_term = get_term_by( 'id', $term_id, $taxonomy );
		} else {
			self::$editing_term = null;
		}
	}

	/**
	 * When a term is edited, check for product attributes and update variations.
	 *
	 * @param  int    $term_id  Term ID.
	 * @param  int    $tt_id    Term taxonomy ID.
	 * @param  string $taxonomy Taxonomy slug.
	 */
	public static function edited_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! is_null( self::$editing_term ) && strpos( $taxonomy, 'pa_' ) === 0 ) {
			$edited_term = get_term_by( 'id', $term_id, $taxonomy );

			if ( $edited_term->slug !== self::$editing_term->slug ) {
				global $wpdb;

				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s;", $edited_term->slug, 'attribute_' . sanitize_title( $taxonomy ), self::$editing_term->slug ) );

				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE( meta_value, %s, %s ) WHERE meta_key = '_default_attributes'",
						serialize( self::$editing_term->taxonomy ) . serialize( self::$editing_term->slug ),
						serialize( $edited_term->taxonomy ) . serialize( $edited_term->slug )
					)
				);
			}
		} else {
			self::$editing_term = null;
		}
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param  null   $check      Whether to allow updating metadata for the given type.
	 * @param  int    $object_id  Object ID.
	 * @param  string $meta_key   Meta key.
	 * @param  mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 * @param  mixed  $prev_value If specified, only update existing metadata entries with the specified value. Otherwise, update all entries.
	 * @return null|bool
	 */
	public static function update_order_item_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! empty( $meta_value ) && is_float( $meta_value ) ) {

			// Convert float to string.
			$meta_value = wc_float_to_string( $meta_value );

			// Update meta value with new string.
			update_metadata( 'order_item', $object_id, $meta_key, $meta_value, $prev_value );

			return true;
		}
		return $check;
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param  null   $check      Whether to allow updating metadata for the given type.
	 * @param  int    $object_id  Object ID.
	 * @param  string $meta_key   Meta key.
	 * @param  mixed  $meta_value Meta value. Must be serializable if non-scalar.
	 * @param  mixed  $prev_value If specified, only update existing metadata entries with the specified value. Otherwise, update all entries.
	 * @return null|bool
	 */
	public static function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		// Delete product cache if someone uses meta directly.
		if ( in_array( get_post_type( $object_id ), array( 'product', 'product_variation' ), true ) ) {
			wp_cache_delete( 'product-' . $object_id, 'products' );
		}

		if ( ! empty( $meta_value ) && is_float( $meta_value ) && ! registered_meta_key_exists( 'post', $meta_key ) && in_array( get_post_type( $object_id ), array_merge( wc_get_order_types(), array( 'shop_coupon', 'product', 'product_variation' ) ), true ) ) {

			// Convert float to string.
			$meta_value = wc_float_to_string( $meta_value );

			// Update meta value with new string.
			update_metadata( 'post', $object_id, $meta_key, $meta_value, $prev_value );

			return true;
		}
		return $check;
	}

	/**
	 * Forces the order posts to have a title in a certain format (containing the date).
	 * Forces certain product data based on the product's type, e.g. grouped products cannot have a parent.
	 *
	 * @param array $data An array of slashed post data.
	 * @return array
	 */
	public static function wp_insert_post_data( $data ) {
		if ( 'shop_order' === $data['post_type'] && isset( $data['post_date'] ) ) {
			$order_title = 'Order';
			if ( $data['post_date'] ) {
				$order_title .= ' &ndash; ' . date_i18n( 'F j, Y @ h:i A', strtotime( $data['post_date'] ) );
			}
			$data['post_title'] = $order_title;
		} elseif ( 'product' === $data['post_type'] && isset( $_POST['product-type'] ) ) { // WPCS: input var ok, CSRF ok.
			$product_type = wc_clean( wp_unslash( $_POST['product-type'] ) ); // WPCS: input var ok, CSRF ok.
			switch ( $product_type ) {
				case ProductType::GROUPED:
				case ProductType::VARIABLE:
					$data['post_parent'] = 0;
					break;
			}
		} elseif ( 'product' === $data['post_type'] && ProductStatus::AUTO_DRAFT === $data['post_status'] ) {
			$data['post_title'] = 'AUTO-DRAFT';
		} elseif ( 'shop_coupon' === $data['post_type'] ) {
			// Coupons should never allow unfiltered HTML.
			$data['post_title'] = wp_filter_kses( $data['post_title'] );
		}

		return $data;
	}

	/**
	 * Change embed data for certain post types.
	 *
	 * @since 3.2.0
	 * @param array   $data The response data.
	 * @param WP_Post $post The post object.
	 * @return array
	 */
	public static function filter_oembed_response_data( $data, $post ) {
		if ( in_array( $post->post_type, array( 'shop_order', 'shop_coupon' ), true ) ) {
			return array();
		}
		return $data;
	}

	/**
	 * Removes variations etc. belonging to a deleted post, and clears transients.
	 *
	 * @internal Use the delete_post function instead.
	 * @since 9.8.0
	 *
	 * @param mixed $id ID of post being deleted.
	 */
	public static function delete_post_data( $id ) {
		$container = wc_get_container();

		$post_type = self::get_post_type( $id );
		switch ( $post_type ) {
			case 'product':
				$data_store = WC_Data_Store::load( 'product-variable' );
				$data_store->delete_variations( $id, true );
				$data_store->delete_from_lookup_table( $id, 'wc_product_meta_lookup' );
				$container->get( ProductAttributesLookupDataStore::class )->on_product_deleted( $id );

				$parent_id = wp_get_post_parent_id( $id );
				if ( $parent_id ) {
					wc_delete_product_transients( $parent_id );
				}

				break;
			case 'product_variation':
				$data_store = WC_Data_Store::load( 'product' );
				$data_store->delete_from_lookup_table( $id, 'wc_product_meta_lookup' );
				wc_delete_product_transients( wp_get_post_parent_id( $id ) );
				$container->get( ProductAttributesLookupDataStore::class )->on_product_deleted( $id );

				break;
			case 'shop_order':
			case DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE:
				global $wpdb;

				$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

				if ( ! is_null( $refunds ) ) {
					foreach ( $refunds as $refund ) {
						wp_delete_post( $refund->ID, true );
					}
				}
				break;
		}
	}

	/**
	 * Removes variations etc. belonging to a deleted post, and clears transients, if the user has permission.
	 *
	 * @param mixed $id ID of post being deleted.
	 */
	public static function delete_post( $id ) {
		$container = wc_get_container();
		if ( ! $container->get( LegacyProxy::class )->call_function( 'current_user_can', 'delete_posts' ) || ! $id ) {
			return;
		}

		self::delete_post_data( $id );
	}

	/**
	 * Trash post.
	 *
	 * @param mixed $id Post ID.
	 */
	public static function trash_post( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = self::get_post_type( $id );

		// If this is an order, trash any refunds too.
		if ( in_array( $post_type, wc_get_order_types( 'order-count' ), true ) ) {
			global $wpdb;

			$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

			foreach ( $refunds as $refund ) {
				$wpdb->update( $wpdb->posts, array( 'post_status' => OrderStatus::TRASH ), array( 'ID' => $refund->ID ) );
			}

			wc_delete_shop_order_transients( $id );

			// If this is a product, trash children variations.
		} elseif ( 'product' === $post_type ) {
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->delete_variations( $id, false );
			wc_get_container()->get( ProductAttributesLookupDataStore::class )->on_product_deleted( $id );
		} elseif ( 'product_variation' === $post_type ) {
			wc_get_container()->get( ProductAttributesLookupDataStore::class )->on_product_deleted( $id );
		}
	}

	/**
	 * Untrash post.
	 *
	 * @param mixed $id Post ID.
	 */
	public static function untrash_post( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = self::get_post_type( $id );

		if ( in_array( $post_type, wc_get_order_types( 'order-count' ), true ) ) {
			global $wpdb;

			$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

			foreach ( $refunds as $refund ) {
				$wpdb->update( $wpdb->posts, array( 'post_status' => OrderInternalStatus::COMPLETED ), array( 'ID' => $refund->ID ) );
			}

			wc_delete_shop_order_transients( $id );

		} elseif ( 'product' === $post_type ) {
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->untrash_variations( $id );

			wc_product_force_unique_sku( $id );
			self::clear_global_unique_id_if_necessary( $id );

			wc_get_container()->get( ProductAttributesLookupDataStore::class )->on_product_changed( $id );
		} elseif ( 'product_variation' === $post_type ) {
			wc_get_container()->get( ProductAttributesLookupDataStore::class )->on_product_changed( $id );
		}
	}

	/**
	 * Clear global unique id if it's not unique.
	 *
	 * @param mixed $id Post ID.
	 */
	private static function clear_global_unique_id_if_necessary( $id ) {
		$product = wc_get_product( $id );
		if ( $product && ! wc_product_has_global_unique_id( $id, $product->get_global_unique_id() ) ) {
			$product->set_global_unique_id( '' );
			$product->save();
		}
	}

	/**
	 * Get the post type for a given post.
	 *
	 * @param int $id The post id.
	 * @return string The post type.
	 */
	private static function get_post_type( $id ) {
		return wc_get_container()->get( LegacyProxy::class )->call_function( 'get_post_type', $id );
	}

	/**
	 * Before deleting an order, do some cleanup.
	 *
	 * @since 3.2.0
	 * @param int $order_id Order ID.
	 */
	public static function before_delete_order( $order_id ) {
		if ( OrderUtil::is_order( $order_id, wc_get_order_types() ) ) {
			// Clean up user.
			$order = wc_get_order( $order_id );

			// Check for `get_customer_id`, since this may be e.g. a refund order (which doesn't implement it).
			$customer_id = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id() : 0;

			if ( $customer_id > 0 && 'shop_order' === $order->get_type() ) {
				$customer    = new WC_Customer( $customer_id );
				$order_count = $customer->get_order_count();
				$order_count --;

				if ( 0 === $order_count ) {
					$customer->set_is_paying_customer( false );
					$customer->save();
				}

				// Delete order count and last order meta.
				delete_user_meta( $customer_id, '_order_count' );
				delete_user_meta( $customer_id, '_last_order' );
			}

			// Clean up items.
			self::delete_order_items( $order_id );
			self::delete_order_downloadable_permissions( $order_id );
		}
	}

	/**
	 * Remove item meta on permanent deletion.
	 *
	 * @param int $postid Post ID.
	 */
	public static function delete_order_items( $postid ) {
		global $wpdb;

		if ( OrderUtil::is_order( $postid, wc_get_order_types() ) ) {
			do_action( 'woocommerce_delete_order_items', $postid );

			$wpdb->query(
				"
				DELETE {$wpdb->prefix}woocommerce_order_items, {$wpdb->prefix}woocommerce_order_itemmeta
				FROM {$wpdb->prefix}woocommerce_order_items
				JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_items.order_item_id = {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id
				WHERE {$wpdb->prefix}woocommerce_order_items.order_id = '{$postid}';
				"
			); // WPCS: unprepared SQL ok.

			do_action( 'woocommerce_deleted_order_items', $postid );
		}
	}

	/**
	 * Remove downloadable permissions on permanent order deletion.
	 *
	 * @param int $postid Post ID.
	 */
	public static function delete_order_downloadable_permissions( $postid ) {
		if ( OrderUtil::is_order( $postid, wc_get_order_types() ) ) {
			do_action( 'woocommerce_delete_order_downloadable_permissions', $postid );

			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->delete_by_order_id( $postid );

			do_action( 'woocommerce_deleted_order_downloadable_permissions', $postid );
		}
	}

	/**
	 * Flush meta cache for CRUD objects on direct update.
	 *
	 * @param  int    $meta_id    Meta ID.
	 * @param  int    $object_id  Object ID.
	 * @param  string $meta_key   Meta key.
	 * @param  mixed  $meta_value Meta value.
	 */
	public static function flush_object_meta_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		WC_Cache_Helper::invalidate_cache_group( 'object_' . $object_id );
	}

	/**
	 * Ensure default category gets set.
	 *
	 * @since 3.3.0
	 * @param int    $object_id Product ID.
	 * @param array  $terms     Terms array.
	 * @param array  $tt_ids    Term ids array.
	 * @param string $taxonomy  Taxonomy name.
	 * @param bool   $append    Are we appending or setting terms.
	 */
	public static function force_default_term( $object_id, $terms, $tt_ids, $taxonomy, $append ) {
		if ( ! $append && 'product_cat' === $taxonomy && empty( $tt_ids ) && 'product' === get_post_type( $object_id ) ) {
			$default_term = absint( get_option( 'default_product_cat', 0 ) );
			$tt_ids       = array_map( 'absint', $tt_ids );

			if ( $default_term && ! in_array( $default_term, $tt_ids, true ) ) {
				wp_set_post_terms( $object_id, array( $default_term ), 'product_cat', true );
			}
		}
	}

	/**
	 * Ensure statuses are correctly reassigned when restoring orders and products.
	 *
	 * @param string $new_status      The new status of the post being restored.
	 * @param int    $post_id         The ID of the post being restored.
	 * @param string $previous_status The status of the post at the point where it was trashed.
	 * @return string
	 */
	public static function wp_untrash_post_status( $new_status, $post_id, $previous_status ) {
		$post_types = array( 'shop_order', 'shop_coupon', 'product', 'product_variation' );

		if ( in_array( get_post_type( $post_id ), $post_types, true ) ) {
			$new_status = $previous_status;
		}

		return $new_status;
	}

	/**
	 * When setting stock level, ensure the stock status is kept in sync.
	 *
	 * @param  int    $meta_id    Meta ID.
	 * @param  int    $object_id  Object ID.
	 * @param  string $meta_key   Meta key.
	 * @param  mixed  $meta_value Meta value.
	 * @deprecated    3.3
	 */
	public static function sync_product_stock_status( $meta_id, $object_id, $meta_key, $meta_value ) {}

	/**
	 * Update changed downloads.
	 *
	 * @deprecated  3.3.0 No action is necessary on changes to download paths since download_id is no longer based on file hash.
	 * @param int   $product_id   Product ID.
	 * @param int   $variation_id Variation ID. Optional product variation identifier.
	 * @param array $downloads    Newly set files.
	 */
	public static function process_product_file_download_paths( $product_id, $variation_id, $downloads ) {
		wc_deprecated_function( __FUNCTION__, '3.3' );
	}

	/**
	 * Delete transients when terms are set.
	 *
	 * @deprecated   3.6
	 * @param int    $object_id  Object ID.
	 * @param mixed  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param mixed  $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( in_array( get_post_type( $object_id ), array( 'product', 'product_variation' ), true ) ) {
			self::delete_product_query_transients();
		}
	}

	/**
	 * Regenerates attribute summaries for a list of variations.
	 *
	 * @since 10.2.0
	 * @param array $variation_ids Array of variation IDs.
	 */
	private static function regenerate_variation_summaries( $variation_ids ) {
		if ( empty( $variation_ids ) ) {
			return;
		}

		$variation_ids = array_unique( array_filter( array_map( 'intval', $variation_ids ) ) );

		foreach ( $variation_ids as $variation_id ) {
			self::regenerate_variation_attribute_summary( $variation_id );
		}
	}

	/**
	 * Regenerates the attribute summary for a single variation.
	 *
	 * @since 10.2.0
	 * @param int $variation_id Variation ID.
	 */
	public static function regenerate_variation_attribute_summary( $variation_id ) {
		global $wpdb;

		$product = wc_get_product( $variation_id );
		if ( ! $product || ! $product->is_type( 'variation' ) ) {
			return;
		}

		$data_store = WC_Data_Store::load( 'product-variation' );
		if ( $data_store->has_callable( 'get_attribute_summary' ) ) {
			$new_summary     = $data_store->get_attribute_summary( $product );
			$current_excerpt = get_post_field( 'post_excerpt', $variation_id );
			if ( $new_summary === $current_excerpt ) {
				return;
			}

			/**
			* Update directly via $wpdb for performance: Avoid firing save_post hooks, loading full post objects,
			* and creating revisions. This is safe here as we're only updating post_excerpt.
			*/
			$wpdb->update(
				$wpdb->posts,
				array( 'post_excerpt' => $new_summary ),
				array( 'ID' => $variation_id )
			);
			clean_post_cache( $variation_id );
			/**
			* Fires after the attribute summary of a product variation has been updated.
			*
			* @since 10.2.0
			* @param int $variation_id The ID of the product variation.
			*/
			do_action( 'woocommerce_updated_product_attribute_summary', $variation_id );
		}
	}

	/**
	 * Gets the threshold for synchronous regeneration of variation summaries.
	 *
	 * @since 10.2.0
	 * @return int
	 */
	public static function get_variation_summaries_sync_threshold() {
		/**
		 * Filters the threshold for synchronous regeneration of variation attribute summaries.
		 * If the number of variations affected by an update is below this threshold, the summaries
		 * are regenerated synchronously. Otherwise, the regeneration is scheduled asynchronously.
		 *
		 * @since 10.2.0
		 * @param int $threshold The default threshold value (50).
		 * @return int The filtered threshold value.
		 */
		return absint( apply_filters( 'woocommerce_regenerate_variation_summaries_sync_threshold', 50 ) );
	}

	/**
	 * Handles updates to a global attribute by triggering variation summary regeneration.
	 *
	 * @since 10.2.0
	 * @param int    $attribute_id Attribute ID.
	 * @param string $attribute    Attribute name.
	 * @param string $old_slug     Old attribute slug.
	 */
	public static function handle_global_attribute_updated( $attribute_id, $attribute, $old_slug ) {
		// We use this trigger for both updates and deletions of global attributes.
		// They pass different parameters to $old_slug - deleted attributes include the "pa_" prefix, while updated attributes do not.
		// Remove it if existing for consistency.
		if ( strpos( $old_slug, 'pa_' ) === 0 ) {
			$old_slug = substr( $old_slug, 3 );
		}
		$taxonomy  = 'pa_' . $old_slug;
		$threshold = self::get_variation_summaries_sync_threshold();
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args = array(
			'post_type'      => 'product_variation',
			'post_status'    => 'any',
			'posts_per_page' => $threshold + 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'attribute_' . $taxonomy,
					'compare' => 'EXISTS',
				),
			),
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		$variation_ids = get_posts( $args );

		if ( empty( $variation_ids ) ) {
			return;
		}

		if ( count( $variation_ids ) <= $threshold ) {
			// Update variation summaries that used this product attribute, but
			// wait until shutdown. This will allow WooC to carry out post_meta migrations
			// if the slug of the attribute changed.
			add_action(
				'shutdown',
				function () use ( $variation_ids ) {
					self::regenerate_variation_summaries( $variation_ids );
				}
			);
		} else {
			$new_slug     = ! empty( $attribute['attribute_name'] ) ? $attribute['attribute_name'] : $old_slug;
			$new_taxonomy = 'pa_' . $new_slug;

			self::schedule_variation_summary_regeneration(
				'wc_regenerate_attribute_variation_summaries',
				array( $new_taxonomy ),
				'Taxonomy: ' . $taxonomy . ', Attribute ID: ' . $attribute_id
			);
		}
	}

	/**
	 * Regenerates variation summaries for all variations using a specific attribute taxonomy.
	 *
	 * @since 10.2.0
	 * @param string $taxonomy Attribute taxonomy.
	 */
	public static function regenerate_attribute_variation_summaries( $taxonomy ) {
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$variation_ids = get_posts(
			array(
				'post_type'   => 'product_variation',
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => 'attribute_' . $taxonomy,
						'compare' => 'EXISTS',
					),
				),
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		self::regenerate_variation_summaries( $variation_ids );
	}

	/**
	 * Handles regeneration of variation summaries when a variable product's attributes are updated.
	 *
	 * @since 10.2.0
	 * @param WC_Product $product The variable product whose attributes were updated.
	 */
	public static function on_product_attributes_updated( $product ) {
		if ( $product->is_type( 'variable' ) ) {
			global $wpdb;
			$threshold     = self::get_variation_summaries_sync_threshold();
			$variation_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT ID
					FROM {$wpdb->posts}
					WHERE post_parent = %d
					AND post_type = %s
					LIMIT %d
					",
					$product->get_id(),
					'product_variation',
					$threshold + 1
				)
			);

			if ( empty( $variation_ids ) ) {
				return;
			}

			if ( count( $variation_ids ) <= $threshold ) {
				// If the number of variations is below the threshold, regenerate summaries synchronously.
				$variation_ids = $product->get_children();
				self::regenerate_variation_summaries( $variation_ids );
			} else {
				self::schedule_variation_summary_regeneration(
					'wc_regenerate_product_variation_summaries',
					array( $product->get_id() ),
					'Product ID: ' . $product->get_id()
				);
			}
		}
	}

	/**
	 * Regenerates variation summaries for all variations of a variable product.
	 *
	 * @since 10.2.0
	 * @param int $product_id Variable product ID.
	 */
	public static function regenerate_product_variation_summaries( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$variation_ids = $product->get_children();
		self::regenerate_variation_summaries( $variation_ids );
	}

	/**
	 * Hook called after a term is updated to handle updates for product variations.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public static function handle_attribute_term_updated( $term_id, $tt_id, $taxonomy ) {
		if ( strpos( $taxonomy, 'pa_' ) !== 0 ) {
			return;
		}

		$new_term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $new_term ) || ! $new_term ) {
			return;
		}

		$meta_key = 'attribute_' . $taxonomy;
		global $wpdb;

		$threshold     = self::get_variation_summaries_sync_threshold();
		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				FROM $wpdb->postmeta pm
				INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				AND p.post_type = 'product_variation'
				LIMIT %d
				",
				$meta_key,
				$new_term->slug,
				$threshold + 1
			)
		);
		if ( empty( $variation_ids ) ) {
			return;
		}

		if ( count( $variation_ids ) <= $threshold ) {
			// If the number of variations is below the threshold, regenerate summaries synchronously.
			self::regenerate_variation_summaries( $variation_ids );
		} else {
			self::schedule_variation_summary_regeneration(
				'wc_regenerate_term_variation_summaries',
				array( $taxonomy, $new_term->slug ),
				'Taxonomy: ' . $taxonomy . ', Term ID: ' . $term_id
			);
		}
	}

	/**
	 * Hook called after a term is deleted to handle updates for product variations.
	 *
	 * @param int     $term_id  Term ID.
	 * @param int     $tt_id    Term taxonomy ID.
	 * @param string  $taxonomy Taxonomy slug.
	 * @param WP_Term $deleted_term Copy of the already-deleted term.
	 */
	public static function handle_attribute_term_deleted( $term_id, $tt_id, $taxonomy, $deleted_term ) {
		if ( strpos( $taxonomy, 'pa_' ) !== 0 ) {
			return;
		}

		$meta_key = 'attribute_' . $taxonomy;
		global $wpdb;
		$threshold     = self::get_variation_summaries_sync_threshold();
		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.post_id
				FROM $wpdb->postmeta pm
				INNER JOIN $wpdb->posts p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				AND p.post_type = 'product_variation'
				LIMIT %d
				",
				$meta_key,
				$deleted_term->slug,
				$threshold + 1
			)
		);

		if ( empty( $variation_ids ) ) {
			return;
		}

		if ( count( $variation_ids ) <= $threshold ) {
			// If the number of variations is below the threshold, regenerate summaries synchronously.
			self::regenerate_variation_summaries( $variation_ids );
		} else {
			self::schedule_variation_summary_regeneration(
				'wc_regenerate_term_variation_summaries',
				array( $taxonomy, $deleted_term->slug ),
				'Taxonomy: ' . $taxonomy . ', Term ID: ' . $term_id
			);
		}
	}

	/**
	 * Schedule an asynchronous action to regenerate product variation summaries.
	 *
	 * This method uses the WooCommerce Action Scheduler to queue a single regeneration action
	 * for product variation summaries. It first checks whether an identical action with the
	 * given arguments is already scheduled to avoid duplicate jobs. If the Action Scheduler
	 * is not available, a warning is logged instead.
	 *
	 * @param string $action_name     The name/identifier of the scheduled action (hook name).
	 * @param array  $args            Arguments to pass to the scheduled action callback.
	 * @param string $warning_message Message to log when the Action Scheduler is unavailable.
	 * @param string $group           Optional. The Action Scheduler group to associate with
	 *                                the scheduled action. Default 'woocommerce'.
	 *
	 * @return void
	 */
	private static function schedule_variation_summary_regeneration( $action_name, $args, $warning_message, $group = 'woocommerce' ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			// Prevent duplicate scheduling of the action.
			$when = as_next_scheduled_action( $action_name, $args, $group );
			if ( ! $when ) {
				as_schedule_single_action( time() + 1, $action_name, $args, $group );
			}
		} else {
			wc_get_logger()->warning(
				'Action Scheduler unavailable for product variation summary regeneration. ' . $warning_message,
				array( 'source' => 'woocommerce-variations' )
			);
		}
	}

	/**
	 * Regenerates variation summaries for all variations using a specific term.
	 *
	 * @since 10.2.0
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $term_slug Term slug.
	 */
	public static function regenerate_term_variation_summaries( $taxonomy, $term_slug ) {
		global $wpdb;

		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				AND p.post_type = %s",
				'attribute_' . $taxonomy,
				$term_slug,
				'product_variation'
			)
		);

		self::regenerate_variation_summaries( $variation_ids );
	}
}

WC_Post_Data::init();
