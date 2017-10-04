<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Post Data.
 *
 * Standardises certain post data on save.
 *
 * @class 		WC_Post_Data
 * @version		2.2.0
 * @package		WooCommerce/Classes/Data
 * @category	Class
 * @author 		WooThemes
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
		add_action( 'set_object_terms', array( __CLASS__, 'set_object_terms' ), 10, 6 );

		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_set_visibility', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_type_changed', array( __CLASS__, 'product_type_changed' ), 10, 3 );

		add_action( 'edit_term', array( __CLASS__, 'edit_term' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'edited_term' ), 10, 3 );
		add_filter( 'update_order_item_metadata', array( __CLASS__, 'update_order_item_metadata' ), 10, 5 );
		add_filter( 'update_post_metadata', array( __CLASS__, 'update_post_metadata' ), 10, 5 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'wp_insert_post_data' ) );
		add_filter( 'oembed_response_data', array( __CLASS__, 'filter_oembed_response_data' ), 10, 2 );

		// Status transitions
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'trash_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'untrash_post' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'before_delete_order' ) );

		// Download permissions
		add_action( 'woocommerce_process_product_file_download_paths', array( __CLASS__, 'process_product_file_download_paths' ), 10, 3 );

		// Meta cache flushing.
		add_action( 'updated_post_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
		add_action( 'updated_order_item_meta', array( __CLASS__, 'flush_object_meta_cache' ), 10, 4 );
	}

	/**
	 * Link to parent products when getting permalink for variation.
	 *
	 * @param string $permalink
	 * @param object $post
	 *
	 * @return string
	 */
	public static function variation_post_link( $permalink, $post ) {
		if ( isset( $post->ID, $post->post_type ) && 'product_variation' === $post->post_type && ( $variation = wc_get_product( $post->ID ) ) && $variation->get_parent_id() ) {
			return $variation->get_permalink();
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
	 * @param  int $product_id
	 */
	public static function deferred_product_sync( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( is_callable( array( $product, 'sync' ) ) ) {
			$product->sync( $product );
		}
	}

	/**
	 * Delete transients when terms are set.
	 *
	 * @param int $object_id
	 * @param mixed $terms
	 * @param array $tt_ids
	 * @param string $taxonomy
	 * @param mixed $append
	 * @param array $old_tt_ids
	 */
	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		foreach ( array_merge( $tt_ids, $old_tt_ids ) as $id ) {
			delete_transient( 'wc_ln_count_' . md5( sanitize_key( $taxonomy ) . sanitize_key( $id ) ) );
		}
	}

	/**
	 * When a post status changes.
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param object $post
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( ( 'publish' === $new_status || 'publish' === $old_status ) && in_array( $post->post_type, array( 'product', 'product_variation' ) ) ) {
			self::delete_product_query_transients();
		}
	}

	/**
	 * Delete product view transients when needed e.g. when post status changes, or visibility/stock status is modified.
	 */
	public static function delete_product_query_transients() {
		// Increments the transient version to invalidate cache
		WC_Cache_Helper::get_transient_version( 'product_query', true );

		// If not using an external caching system, we can clear the transients out manually and avoid filling our DB
		if ( ! wp_using_ext_object_cache() ) {
			global $wpdb;

			$wpdb->query( "
				DELETE FROM `$wpdb->options`
				WHERE `option_name` LIKE ('\_transient\_wc\_uf\_pid\_%')
				OR `option_name` LIKE ('\_transient\_timeout\_wc\_uf\_pid\_%')
				OR `option_name` LIKE ('\_transient\_wc\_products\_will\_display\_%')
				OR `option_name` LIKE ('\_transient\_timeout\_wc\_products\_will\_display\_%')
			" );
		}
	}

	/**
	 * Handle type changes.
	 *
	 * @since 3.0.0
	 * @param WC_Product $product
	 * @param string $from
	 * @param string $to
	 */
	public static function product_type_changed( $product, $from, $to ) {
		if ( 'variable' === $from && 'variable' !== $to ) {
			// If the product is no longer variable, we should ensure all variations are removed.
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->delete_variations( $product->get_id() );
		}
	}

	/**
	 * When editing a term, check for product attributes.
	 * @param  id $term_id
	 * @param  id $tt_id
	 * @param  string $taxonomy
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
	 * @param  id $term_id
	 * @param  id $tt_id
	 * @param  string $taxonomy
	 */
	public static function edited_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! is_null( self::$editing_term ) && strpos( $taxonomy, 'pa_' ) === 0 ) {
			$edited_term = get_term_by( 'id', $term_id, $taxonomy );

			if ( $edited_term->slug !== self::$editing_term->slug ) {
				global $wpdb;

				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s;", $edited_term->slug, 'attribute_' . sanitize_title( $taxonomy ), self::$editing_term->slug ) );
			}
		} else {
			self::$editing_term = null;
		}
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param  null $check
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @param  mixed $prev_value
	 * @return null|bool
	 */
	public static function update_order_item_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! empty( $meta_value ) && is_float( $meta_value ) ) {

			// Convert float to string
			$meta_value = wc_float_to_string( $meta_value );

			// Update meta value with new string
			update_metadata( 'order_item', $object_id, $meta_key, $meta_value, $prev_value );

			// Return
			return true;
		}
		return $check;
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param  null $check
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @param  mixed $prev_value
	 * @return null|bool
	 */
	public static function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		// Delete product cache if someone uses meta directly.
		if ( in_array( get_post_type( $object_id ), array( 'product', 'product_variation' ) ) ) {
			wp_cache_delete( 'product-' . $object_id, 'products' );
		}

		if ( ! empty( $meta_value ) && is_float( $meta_value ) && in_array( get_post_type( $object_id ), array_merge( wc_get_order_types(), array( 'shop_coupon', 'product', 'product_variation' ) ) ) ) {

			// Convert float to string
			$meta_value = wc_float_to_string( $meta_value );

			// Update meta value with new string
			update_metadata( 'post', $object_id, $meta_key, $meta_value, $prev_value );

			// Return
			return true;
		}
		return $check;
	}

	/**
	 * When setting stock level, ensure the stock status is kept in sync.
	 * @param  int $meta_id
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @deprecated
	 */
	public static function sync_product_stock_status( $meta_id, $object_id, $meta_key, $meta_value ) {}

	/**
	 * Forces the order posts to have a title in a certain format (containing the date).
	 * Forces certain product data based on the product's type, e.g. grouped products cannot have a parent.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function wp_insert_post_data( $data ) {
		if ( 'shop_order' === $data['post_type'] && isset( $data['post_date'] ) ) {
			$order_title = 'Order';
			if ( $data['post_date'] ) {
				$order_title .= ' &ndash; ' . date_i18n( 'F j, Y @ h:i A', strtotime( $data['post_date'] ) );
			}
			$data['post_title'] = $order_title;
		} elseif ( 'product' === $data['post_type'] && isset( $_POST['product-type'] ) ) {
			$product_type = stripslashes( $_POST['product-type'] );
			switch ( $product_type ) {
				case 'grouped' :
				case 'variable' :
					$data['post_parent'] = 0;
				break;
			}
		} elseif ( 'product' === $data['post_type'] && 'auto-draft' === $data['post_status'] ) {
			$data['post_title'] = 'AUTO-DRAFT';
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
		if ( in_array( $post->post_type, array( 'shop_order', 'shop_coupon' ) ) ) {
			return array();
		}
		return $data;
	}

	/**
	 * Removes variations etc belonging to a deleted post, and clears transients.
	 *
	 * @param mixed $id ID of post being deleted
	 */
	public static function delete_post( $id ) {
		if ( ! current_user_can( 'delete_posts' ) || ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		switch ( $post_type ) {
			case 'product' :
				$data_store = WC_Data_Store::load( 'product-variable' );
				$data_store->delete_variations( $id, true );

				if ( $parent_id = wp_get_post_parent_id( $id ) ) {
					wc_delete_product_transients( $parent_id );
				}
				break;
			case 'product_variation' :
				wc_delete_product_transients( wp_get_post_parent_id( $id ) );
				break;
			case 'shop_order' :
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
	 * woocommerce_trash_post function.
	 *
	 * @param mixed $id
	 */
	public static function trash_post( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		// If this is an order, trash any refunds too.
		if ( in_array( $post_type, wc_get_order_types( 'order-count' ) ) ) {
			global $wpdb;

			$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

			foreach ( $refunds as $refund ) {
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'trash' ), array( 'ID' => $refund->ID ) );
			}

			wc_delete_shop_order_transients( $id );

		// If this is a product, trash children variations.
		} elseif ( 'product' === $post_type ) {
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->delete_variations( $id, false );
		}
	}

	/**
	 * woocommerce_untrash_post function.
	 *
	 * @param mixed $id
	 */
	public static function untrash_post( $id ) {
		if ( ! $id ) {
			return;
		}

		$post_type = get_post_type( $id );

		if ( in_array( $post_type, wc_get_order_types( 'order-count' ) ) ) {
			global $wpdb;

			$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

			foreach ( $refunds as $refund ) {
				$wpdb->update( $wpdb->posts, array( 'post_status' => 'wc-completed' ), array( 'ID' => $refund->ID ) );
			}

			wc_delete_shop_order_transients( $id );

		} elseif ( 'product' === $post_type ) {
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->untrash_variations( $id );

			wc_product_force_unique_sku( $id );
		}
	}

	/**
	 * Before deleting an order, do some cleanup.
	 *
	 * @since 3.2.0
	 * @param int $order_id
	 */
	public static function before_delete_order( $order_id ) {
		if ( in_array( get_post_type( $order_id ), wc_get_order_types() ) ) {
			// Clean up user.
			$order       = wc_get_order( $order_id );

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

				// Delete order count meta.
				delete_user_meta( $customer_id, '_order_count' );
			}

			// Clean up items.
			self::delete_order_items( $order_id );
			self::delete_order_downloadable_permissions( $order_id );
		}
	}

	/**
	 * Remove item meta on permanent deletion.
	 *
	 * @param int $postid
	 */
	public static function delete_order_items( $postid ) {
		global $wpdb;

		if ( in_array( get_post_type( $postid ), wc_get_order_types() ) ) {
			do_action( 'woocommerce_delete_order_items', $postid );

			$wpdb->query( "
				DELETE {$wpdb->prefix}woocommerce_order_items, {$wpdb->prefix}woocommerce_order_itemmeta
				FROM {$wpdb->prefix}woocommerce_order_items
				JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_items.order_item_id = {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id
				WHERE {$wpdb->prefix}woocommerce_order_items.order_id = '{$postid}';
				" );

			do_action( 'woocommerce_deleted_order_items', $postid );
		}
	}

	/**
	 * Remove downloadable permissions on permanent order deletion.
	 *
	 * @param int $postid
	 */
	public static function delete_order_downloadable_permissions( $postid ) {
		if ( in_array( get_post_type( $postid ), wc_get_order_types() ) ) {
			do_action( 'woocommerce_delete_order_downloadable_permissions', $postid );

			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->delete_by_order_id( $postid );

			do_action( 'woocommerce_deleted_order_downloadable_permissions', $postid );
		}
	}

	/**
	 * Update changed downloads.
	 *
	 * @param int $product_id product identifier
	 * @param int $variation_id optional product variation identifier
	 * @param array $downloads newly set files
	 */
	public static function process_product_file_download_paths( $product_id, $variation_id, $downloads ) {
		if ( $variation_id ) {
			$product_id = $variation_id;
		}
		$data_store = WC_Data_Store::load( 'customer-download' );

		if ( $downloads ) {
			foreach ( $downloads as $download ) {
				$new_hash = md5( $download->get_file() );

				if ( $download->get_previous_hash() && $download->get_previous_hash() !== $new_hash ) {
					// Update permissions.
					$data_store->update_download_id( $product_id, $download->get_previous_hash(), $new_hash );
				}
			}
		}
	}

	/**
	 * Flush meta cache for CRUD objects on direct update.
	 * @param  int $meta_id
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  string $meta_value
	 */
	public static function flush_object_meta_cache( $meta_id, $object_id, $meta_key, $meta_value ) {
		WC_Cache_Helper::incr_cache_prefix( 'object_' . $object_id );
	}
}

WC_Post_Data::init();
