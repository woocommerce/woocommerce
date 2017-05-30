<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Coupon Data Store: Custom Post Type.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Coupon_Data_Store_CPT extends WC_Data_Store_WP implements WC_Coupon_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store coupon data.
	 * @since 3.0.0
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for a coupon.
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'discount_type',
		'coupon_amount',
		'expiry_date',
		'date_expires',
		'usage_count',
		'individual_use',
		'product_ids',
		'exclude_product_ids',
		'usage_limit',
		'usage_limit_per_user',
		'limit_usage_to_x_items',
		'free_shipping',
		'product_categories',
		'exclude_product_categories',
		'exclude_sale_items',
		'minimum_amount',
		'maximum_amount',
		'customer_email',
		'_used_by',
		'_edit_lock',
		'_edit_last',
	);

	/**
	 * Method to create a new coupon in the database.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 */
	public function create( &$coupon ) {
		$coupon->set_date_created( current_time( 'timestamp', true ) );

		$coupon_id = wp_insert_post( apply_filters( 'woocommerce_new_coupon_data', array(
			'post_type'     => 'shop_coupon',
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_title'    => $coupon->get_code( 'edit' ),
			'post_content'  => '',
			'post_excerpt'  => $coupon->get_description( 'edit' ),
			'post_date'     => gmdate( 'Y-m-d H:i:s', $coupon->get_date_created()->getOffsetTimestamp() ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $coupon->get_date_created()->getTimestamp() ),
		) ), true );

		if ( $coupon_id ) {
			$coupon->set_id( $coupon_id );
			$this->update_post_meta( $coupon );
			$coupon->save_meta_data();
			$coupon->apply_changes();
			do_action( 'woocommerce_new_coupon', $coupon_id );
		}
	}

	/**
	 * Method to read a coupon.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 */
	public function read( &$coupon ) {
		$coupon->set_defaults();

		if ( ! $coupon->get_id() || ! ( $post_object = get_post( $coupon->get_id() ) ) || 'shop_coupon' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid coupon.', 'woocommerce' ) );
		}

		$coupon_id = $coupon->get_id();
		$coupon->set_props( array(
			'code'                        => $post_object->post_title,
			'description'                 => $post_object->post_excerpt,
			'date_created'                => 0 < $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
			'date_modified'               => 0 < $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
			'date_expires'                => metadata_exists( 'post', $coupon_id, 'date_expires' ) ? get_post_meta( $coupon_id, 'date_expires', true ) : get_post_meta( $coupon_id, 'expiry_date', true ),
			'discount_type'               => get_post_meta( $coupon_id, 'discount_type', true ),
			'amount'                      => get_post_meta( $coupon_id, 'coupon_amount', true ),
			'usage_count'                 => get_post_meta( $coupon_id, 'usage_count', true ),
			'individual_use'              => 'yes' === get_post_meta( $coupon_id, 'individual_use', true ),
			'product_ids'                 => array_filter( (array) explode( ',', get_post_meta( $coupon_id, 'product_ids', true ) ) ),
			'excluded_product_ids'        => array_filter( (array) explode( ',', get_post_meta( $coupon_id, 'exclude_product_ids', true ) ) ),
			'usage_limit'                 => get_post_meta( $coupon_id, 'usage_limit', true ),
			'usage_limit_per_user'        => get_post_meta( $coupon_id, 'usage_limit_per_user', true ),
			'limit_usage_to_x_items'      => 0 < get_post_meta( $coupon_id, 'limit_usage_to_x_items', true ) ? get_post_meta( $coupon_id, 'limit_usage_to_x_items', true ) : null,
			'free_shipping'               => 'yes' === get_post_meta( $coupon_id, 'free_shipping', true ),
			'product_categories'          => array_filter( (array) get_post_meta( $coupon_id, 'product_categories', true ) ),
			'excluded_product_categories' => array_filter( (array) get_post_meta( $coupon_id, 'exclude_product_categories', true ) ),
			'exclude_sale_items'          => 'yes' === get_post_meta( $coupon_id, 'exclude_sale_items', true ),
			'minimum_amount'              => get_post_meta( $coupon_id, 'minimum_amount', true ),
			'maximum_amount'              => get_post_meta( $coupon_id, 'maximum_amount', true ),
			'email_restrictions'          => array_filter( (array) get_post_meta( $coupon_id, 'customer_email', true ) ),
			'used_by'                     => array_filter( (array) get_post_meta( $coupon_id, '_used_by' ) ),
		) );
		$coupon->read_meta_data();
		$coupon->set_object_read( true );
		do_action( 'woocommerce_coupon_loaded', $coupon );
	}

	/**
	 * Updates a coupon in the database.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 */
	public function update( &$coupon ) {
		$coupon->save_meta_data();
		$changes = $coupon->get_changes();

		if ( array_intersect( array( 'code', 'description', 'date_created', 'date_modified' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_title'        => $coupon->get_code( 'edit' ),
				'post_excerpt'      => $coupon->get_description( 'edit' ),
				'post_date'         => gmdate( 'Y-m-d H:i:s', $coupon->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $coupon->get_date_created( 'edit' )->getTimestamp() ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $coupon->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $coupon->get_date_modified( 'edit' )->getTimestamp() )       : current_time( 'mysql', 1 ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $coupon->get_id() ) );
				clean_post_cache( $coupon->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $coupon->get_id() ), $post_data ) );
			}
			$coupon->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}
		$this->update_post_meta( $coupon );
		$coupon->apply_changes();
		do_action( 'woocommerce_update_coupon', $coupon->get_id() );
	}

	/**
	 * Deletes a coupon from the database.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$coupon, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'force_delete' => false,
		) );

		$id = $coupon->get_id();

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$coupon->set_id( 0 );
			do_action( 'woocommerce_delete_coupon', $id );
		} else {
			wp_trash_post( $id );
			do_action( 'woocommerce_trash_coupon', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for a coupon based on it's settings in the WC_Coupon class.
	 *
	 * @param WC_Coupon
	 * @since 3.0.0
	 */
	private function update_post_meta( &$coupon ) {
		$updated_props     = array();
		$meta_key_to_props = array(
			'discount_type'              => 'discount_type',
			'coupon_amount'              => 'amount',
			'individual_use'             => 'individual_use',
			'product_ids'                => 'product_ids',
			'exclude_product_ids'        => 'excluded_product_ids',
			'usage_limit'                => 'usage_limit',
			'usage_limit_per_user'       => 'usage_limit_per_user',
			'limit_usage_to_x_items'     => 'limit_usage_to_x_items',
			'usage_count'                => 'usage_count',
			'date_expires'            	 => 'date_expires',
			'free_shipping'              => 'free_shipping',
			'product_categories'         => 'product_categories',
			'exclude_product_categories' => 'excluded_product_categories',
			'exclude_sale_items'         => 'exclude_sale_items',
			'minimum_amount'             => 'minimum_amount',
			'maximum_amount'             => 'maximum_amount',
			'customer_email'             => 'email_restrictions',
		);

		$props_to_update = $this->get_props_to_update( $coupon, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $coupon->{"get_$prop"}( 'edit' );
			switch ( $prop ) {
				case 'individual_use' :
				case 'free_shipping' :
				case 'exclude_sale_items' :
					$updated = update_post_meta( $coupon->get_id(), $meta_key, wc_bool_to_string( $value ) );
					break;
				case 'product_ids' :
				case 'excluded_product_ids' :
					$updated = update_post_meta( $coupon->get_id(), $meta_key, implode( ',', array_filter( array_map( 'intval', $value ) ) ) );
					break;
				case 'product_categories' :
				case 'excluded_product_categories' :
					$updated = update_post_meta( $coupon->get_id(), $meta_key, array_filter( array_map( 'intval', $value ) ) );
					break;
				case 'email_restrictions' :
					$updated = update_post_meta( $coupon->get_id(), $meta_key, array_filter( array_map( 'sanitize_email', $value ) ) );
					break;
				case 'date_expires' :
					$updated = update_post_meta( $coupon->get_id(), $meta_key, ( $value ? $value->getTimestamp() : null ) );
					update_post_meta( $coupon->get_id(), 'expiry_date', ( $value ? $value->date( 'Y-m-d' ) : '' ) ); // Update the old meta key for backwards compatibility.
					break;
				default :
					$updated = update_post_meta( $coupon->get_id(), $meta_key, $value );
					break;
			}
			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'woocommerce_coupon_object_updated_props', $coupon, $updated_props );
	}

	/**
	 * Increase usage count for current coupon.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 * @param string $used_by Either user ID or billing email
	 * @return int New usage count
	 */
	public function increase_usage_count( &$coupon, $used_by = '' ) {
		$new_count = $this->update_usage_count_meta( $coupon, 'increase' );
		if ( $used_by ) {
			add_post_meta( $coupon->get_id(), '_used_by', strtolower( $used_by ) );
			$coupon->set_used_by( (array) get_post_meta( $coupon->get_id(), '_used_by' ) );
		}
		return $new_count;
	}

	/**
	 * Decrease usage count for current coupon.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 * @param string $used_by Either user ID or billing email
	 * @return int New usage count
	 */
	public function decrease_usage_count( &$coupon, $used_by = '' ) {
		global $wpdb;
		$new_count = $this->update_usage_count_meta( $coupon, 'decrease' );
		if ( $used_by ) {
			/**
			 * We're doing this the long way because `delete_post_meta( $id, $key, $value )` deletes.
			 * all instances where the key and value match, and we only want to delete one.
			 */
			$meta_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = '_used_by' AND meta_value = %s AND post_id = %d LIMIT 1;", $used_by, $coupon->get_id() ) );
			if ( $meta_id ) {
				delete_metadata_by_mid( 'post', $meta_id );
				$coupon->set_used_by( (array) get_post_meta( $coupon->get_id(), '_used_by' ) );
			}
		}
		return $new_count;
	}

	/**
	 * Increase or decrease the usage count for a coupon by 1.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 * @param string $operation 'increase' or 'decrease'
	 * @return int New usage count
	 */
	private function update_usage_count_meta( &$coupon, $operation = 'increase' ) {
		global $wpdb;
		$id = $coupon->get_id();
		$operator = ( 'increase' === $operation ) ? '+' : '-';

		add_post_meta( $id, 'usage_count', $coupon->get_usage_count( 'edit' ), true );
		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = meta_value {$operator} 1 WHERE meta_key = 'usage_count' AND post_id = %d;", $id ) );

		// Get the latest value direct from the DB, instead of possibly the WP meta cache.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = 'usage_count' AND post_id = %d;", $id ) );
	}

	/**
	 * Get the number of uses for a coupon by user ID.
	 *
	 * @since 3.0.0
	 * @param WC_Coupon
	 * @param id $user_id
	 * @return int
	 */
	public function get_usage_by_user_id( &$coupon, $user_id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( meta_id ) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_used_by' AND meta_value = %d;", $coupon->get_id(), $user_id ) );
	}

	/**
	 * Return a coupon code for a specific ID.
	 *
	 * @since 3.0.0
	 * @param int $id
	 * @return string Coupon Code
	 */
	public function get_code_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "
			SELECT post_title
			FROM $wpdb->posts
			WHERE ID = %d
			AND post_type = 'shop_coupon'
			AND post_status = 'publish';
		", $id ) );
	}

	/**
	 * Return an array of IDs for for a specific coupon code.
	 * Can return multiple to check for existence.
	 *
	 * @since 3.0.0
	 * @param string $code
	 * @return array Array of IDs.
	 */
	public function get_ids_by_code( $code ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC;", $code ) );
	}
}
