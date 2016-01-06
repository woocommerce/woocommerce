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
		add_action( 'set_object_terms', array( __CLASS__, 'set_object_terms' ), 10, 6 );

		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_set_visibility', array( __CLASS__, 'delete_product_query_transients' ) );

		add_action( 'edit_term', array( __CLASS__, 'edit_term' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'edited_term' ), 10, 3 );
		add_filter( 'update_order_item_metadata', array( __CLASS__, 'update_order_item_metadata' ), 10, 5 );
		add_filter( 'update_post_metadata', array( __CLASS__, 'update_post_metadata' ), 10, 5 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'wp_insert_post_data' ) );
		add_action( 'pre_post_update', array( __CLASS__, 'pre_post_update' ) );
		add_action( 'update_post_meta', array( __CLASS__, 'sync_product_stock_status' ), 10, 4 );
	}

	/**
	 * Delete transients when terms are set.
	 */
	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		foreach ( array_merge( $tt_ids, $old_tt_ids ) as $id ) {
			delete_transient( 'wc_ln_count_' . md5( sanitize_key( $taxonomy ) . sanitize_key( $id ) ) );
		}
	}

	/**
	 * When a post status changes.
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
	 * @param  mixed $_meta_value
	 */
	public static function sync_product_stock_status( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_stock' === $meta_key && 'product' !== get_post_type( $object_id ) ) {
			$product = wc_get_product( $object_id );
			$product->check_stock_status();
		}
	}

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
				$order_title.= ' &ndash; ' . date_i18n( 'F j, Y @ h:i A', strtotime( $data['post_date'] ) );
			}
			$data['post_title'] = $order_title;
		}

		elseif ( 'product' === $data['post_type'] && isset( $_POST['product-type'] ) ) {
			$product_type = stripslashes( $_POST['product-type'] );
			switch ( $product_type ) {
				case 'grouped' :
				case 'variable' :
					$data['post_parent'] = 0;
				break;
			}
		}

		return $data;
	}

	/**
	 * Some functions, like the term recount, require the visibility to be set prior. Lets save that here.
	 *
	 * @param int $post_id
	 */
	public static function pre_post_update( $post_id ) {
		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );

		if ( isset( $_POST['_visibility'] ) ) {
			if ( update_post_meta( $post_id, '_visibility', wc_clean( $_POST['_visibility'] ) ) ) {
				do_action( 'woocommerce_product_set_visibility', $post_id, wc_clean( $_POST['_visibility'] ) );
			}
		}
		if ( isset( $_POST['_stock_status'] ) && 'variable' !== $product_type ) {
			wc_update_product_stock_status( $post_id, wc_clean( $_POST['_stock_status'] ) );
		}
	}
}

WC_Post_Data::init();
