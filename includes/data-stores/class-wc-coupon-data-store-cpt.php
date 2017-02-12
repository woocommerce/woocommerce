<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Coupon Data Store: Custom Post Type.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Coupon_Data_Store_CPT extends WC_Data_Store_WP implements WC_Coupon_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store coupon data.
	 * @since 2.7.0
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for a coupon.
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'discount_type',
		'coupon_amount',
		'expiry_date',
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
	 * @since 2.7.0
	 * @param WC_Coupon
	 */
	public function create( &$coupon ) {
		$coupon->set_date_created( current_time( 'timestamp' ) );

		$coupon_id = wp_insert_post( apply_filters( 'woocommerce_new_coupon_data', array(
			'post_type'     => 'shop_coupon',
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_title'    => $coupon->get_code(),
			'post_content'  => '',
			'post_excerpt'  => $coupon->get_description(),
			'post_date'     => date( 'Y-m-d H:i:s', $coupon->get_date_created() ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $coupon->get_date_created() ) ),
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
	 * @since 2.7.0
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
			'date_created'                => $post_object->post_date,
			'date_modified'               => $post_object->post_modified,
			'date_expires'                => get_post_meta( $coupon_id, 'expiry_date', true ),
			'discount_type'               => get_post_meta( $coupon_id, 'discount_type', true ),
			'amount'                      => get_post_meta( $coupon_id, 'coupon_amount', true ),
			'usage_count'                 => get_post_meta( $coupon_id, 'usage_count', true ),
			'individual_use'              => 'yes' === get_post_meta( $coupon_id, 'individual_use', true ),
			'product_ids'                 => array_filter( (array) explode( ',', get_post_meta( $coupon_id, 'product_ids', true ) ) ),
			'excluded_product_ids'        => array_filter( (array) explode( ',', get_post_meta( $coupon_id, 'exclude_product_ids', true ) ) ),
			'usage_limit'                 => get_post_meta( $coupon_id, 'usage_limit', true ),
			'usage_limit_per_user'        => get_post_meta( $coupon_id, 'usage_limit_per_user', true ),
			'limit_usage_to_x_items'      => get_post_meta( $coupon_id, 'limit_usage_to_x_items', true ),
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
	 * @since 2.7.0
	 * @param WC_Coupon
	 */
	public function update( &$coupon ) {
		$post_data = array(
			'ID'           => $coupon->get_id(),
			'post_title'   => $coupon->get_code(),
			'post_excerpt' => $coupon->get_description(),
		);
		wp_update_post( $post_data );
		$this->update_post_meta( $coupon );
		$coupon->save_meta_data();
		$coupon->apply_changes();
		do_action( 'woocommerce_update_coupon', $coupon->get_id() );
	}

	/**
	 * Deletes a coupon from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Coupon
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$coupon, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'force_delete' => false,
		) );

		$id = $coupon->get_id();

		if ( $args['force_delete'] ) {
			wp_delete_post( $coupon->get_id() );
			$coupon->set_id( 0 );
			do_action( 'woocommerce_delete_coupon', $id );
		} else {
			wp_trash_post( $coupon->get_id() );
			do_action( 'woocommerce_trash_coupon', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for a coupon based on it's settings in the WC_Coupon class.
	 *
	 * @param WC_Coupon
	 * @since 2.7.0
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
			'expiry_date'                => 'date_expires',
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
	 * @since 2.7.0
	 * @param WC_Coupon
	 * @param string $used_by Either user ID or billing email
	 */
	public function increase_usage_count( &$coupon, $used_by = '' ) {
		update_post_meta( $coupon->get_id(), 'usage_count', $coupon->get_usage_count( 'edit' ) );
		if ( $used_by ) {
			add_post_meta( $coupon->get_id(), '_used_by', strtolower( $used_by ) );
			$coupon->set_used_by( (array) get_post_meta( $coupon->get_id(), '_used_by' ) );
		}
	}

	/**
	 * Decrease usage count for current coupon.
	 *
	 * @since 2.7.0
	 * @param WC_Coupon
	 * @param string $used_by Either user ID or billing email
	 */
	public function decrease_usage_count( &$coupon, $used_by = '' ) {
		global $wpdb;
		update_post_meta( $coupon->get_id(), 'usage_count', $coupon->get_usage_count() );
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
	}

	/**
	 * Get the number of uses for a coupon by user ID.
	 *
	 * @since 2.7.0
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
	 * @since 2.7.0
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
	 * @since 2.7.0
	 * @param string $code
	 * @return array Array of IDs.
	 */
	public function get_ids_by_code( $code ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' ORDER BY post_date DESC;", $code ) );
	}
}
