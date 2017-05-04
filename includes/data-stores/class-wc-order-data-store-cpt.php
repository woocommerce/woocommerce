<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Data Store: Stored in CPT.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Data_Store_CPT extends Abstract_WC_Order_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Order_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_customer_user',
		'_order_key',
		'_order_currency',
		'_billing_first_name',
		'_billing_last_name',
		'_billing_company',
		'_billing_address_1',
		'_billing_address_2',
		'_billing_city',
		'_billing_state',
		'_billing_postcode',
		'_billing_country',
		'_billing_email',
		'_billing_phone',
		'_shipping_first_name',
		'_shipping_last_name',
		'_shipping_company',
		'_shipping_address_1',
		'_shipping_address_2',
		'_shipping_city',
		'_shipping_state',
		'_shipping_postcode',
		'_shipping_country',
		'_completed_date',
		'_paid_date',
		'_edit_lock',
		'_edit_last',
		'_cart_discount',
		'_cart_discount_tax',
		'_order_shipping',
		'_order_shipping_tax',
		'_order_tax',
		'_order_total',
		'_payment_method',
		'_payment_method_title',
		'_transaction_id',
		'_customer_ip_address',
		'_customer_user_agent',
		'_created_via',
		'_order_version',
		'_prices_include_tax',
		'_date_completed',
		'_date_paid',
		'_payment_tokens',
		'_billing_address_index',
		'_shipping_address_index',
		'_recorded_sales',
		'_recorded_coupon_usage_counts',
		'_shipping_method',
	);

	/**
	 * Method to create a new order in the database.
	 * @param WC_Order $order
	 */
	public function create( &$order ) {
		$order->set_order_key( 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
		parent::create( $order );
		do_action( 'woocommerce_new_order', $order->get_id() );
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order
	 * @param object $post_object
	 * @since 3.0.0
	 */
	protected function read_order_data( &$order, $post_object ) {
		parent::read_order_data( $order, $post_object );
		$id             = $order->get_id();
		$date_completed = get_post_meta( $id, '_date_completed', true );
		$date_paid      = get_post_meta( $id, '_date_paid', true );

		if ( ! $date_completed ) {
			$date_completed = get_post_meta( $id, '_completed_date', true );
		}

		if ( ! $date_paid ) {
			$date_paid = get_post_meta( $id, '_paid_date', true );
		}

		$order->set_props( array(
			'order_key'            => get_post_meta( $id, '_order_key', true ),
			'customer_id'          => get_post_meta( $id, '_customer_user', true ),
			'billing_first_name'   => get_post_meta( $id, '_billing_first_name', true ),
			'billing_last_name'    => get_post_meta( $id, '_billing_last_name', true ),
			'billing_company'      => get_post_meta( $id, '_billing_company', true ),
			'billing_address_1'    => get_post_meta( $id, '_billing_address_1', true ),
			'billing_address_2'    => get_post_meta( $id, '_billing_address_2', true ),
			'billing_city'         => get_post_meta( $id, '_billing_city', true ),
			'billing_state'        => get_post_meta( $id, '_billing_state', true ),
			'billing_postcode'     => get_post_meta( $id, '_billing_postcode', true ),
			'billing_country'      => get_post_meta( $id, '_billing_country', true ),
			'billing_email'        => get_post_meta( $id, '_billing_email', true ),
			'billing_phone'        => get_post_meta( $id, '_billing_phone', true ),
			'shipping_first_name'  => get_post_meta( $id, '_shipping_first_name', true ),
			'shipping_last_name'   => get_post_meta( $id, '_shipping_last_name', true ),
			'shipping_company'     => get_post_meta( $id, '_shipping_company', true ),
			'shipping_address_1'   => get_post_meta( $id, '_shipping_address_1', true ),
			'shipping_address_2'   => get_post_meta( $id, '_shipping_address_2', true ),
			'shipping_city'        => get_post_meta( $id, '_shipping_city', true ),
			'shipping_state'       => get_post_meta( $id, '_shipping_state', true ),
			'shipping_postcode'    => get_post_meta( $id, '_shipping_postcode', true ),
			'shipping_country'     => get_post_meta( $id, '_shipping_country', true ),
			'payment_method'       => get_post_meta( $id, '_payment_method', true ),
			'payment_method_title' => get_post_meta( $id, '_payment_method_title', true ),
			'transaction_id'       => get_post_meta( $id, '_transaction_id', true ),
			'customer_ip_address'  => get_post_meta( $id, '_customer_ip_address', true ),
			'customer_user_agent'  => get_post_meta( $id, '_customer_user_agent', true ),
			'created_via'          => get_post_meta( $id, '_created_via', true ),
			'date_completed'       => $date_completed,
			'date_paid'            => $date_paid,
			'cart_hash'            => get_post_meta( $id, '_cart_hash', true ),
			'customer_note'        => $post_object->post_excerpt,
		) );
	}

	/**
	 * Method to update an order in the database.
	 * @param WC_Order $order
	 */
	public function update( &$order ) {
		// Before updating, ensure date paid is set if missing.
		if ( ! $order->get_date_paid( 'edit' ) && version_compare( $order->get_version( 'edit' ), '3.0', '<' ) && $order->has_status( apply_filters( 'woocommerce_payment_complete_order_status', $order->needs_processing() ? 'processing' : 'completed', $order->get_id(), $order ) ) ) {
			$order->set_date_paid( $order->get_date_created( 'edit' ) );
		}

		// Update the order.
		parent::update( $order );

		do_action( 'woocommerce_update_order', $order->get_id() );
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$order ) {
		$updated_props     = array();
		$id                = $order->get_id();
		$meta_key_to_props = array(
			'_order_key'            => 'order_key',
			'_customer_user'        => 'customer_id',
			'_payment_method'       => 'payment_method',
			'_payment_method_title' => 'payment_method_title',
			'_transaction_id'       => 'transaction_id',
			'_customer_ip_address'  => 'customer_ip_address',
			'_customer_user_agent'  => 'customer_user_agent',
			'_created_via'          => 'created_via',
			'_date_completed'       => 'date_completed',
			'_date_paid'            => 'date_paid',
			'_cart_hash'            => 'cart_hash',
		);

		$props_to_update = $this->get_props_to_update( $order, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $order->{"get_$prop"}( 'edit' );

			if ( 'date_paid' === $prop ) {
				// In 3.0.x we store this as a UTC timestamp.
				update_post_meta( $id, $meta_key, ! is_null( $value ) ? $value->getTimestamp() : '' );

				// In 2.6.x date_paid was stored as _paid_date in local mysql format.
				update_post_meta( $id, '_paid_date', ! is_null( $value ) ? $value->date( 'Y-m-d H:i:s' ) : '' );

			} elseif ( 'date_completed' === $prop ) {
				// In 3.0.x we store this as a UTC timestamp.
				update_post_meta( $id, $meta_key, ! is_null( $value ) ? $value->getTimestamp() : '' );

				// In 2.6.x date_paid was stored as _paid_date in local mysql format.
				update_post_meta( $id, '_completed_date', ! is_null( $value ) ? $value->date( 'Y-m-d H:i:s' ) : '' );

			} else {
				update_post_meta( $id, $meta_key, $value );
			}

			$updated_props[] = $prop;
		}

		$address_props = array(
			'billing' => array(
				'_billing_first_name' => 'billing_first_name',
				'_billing_last_name'  => 'billing_last_name',
				'_billing_company'    => 'billing_company',
				'_billing_address_1'  => 'billing_address_1',
				'_billing_address_2'  => 'billing_address_2',
				'_billing_city'       => 'billing_city',
				'_billing_state'      => 'billing_state',
				'_billing_postcode'   => 'billing_postcode',
				'_billing_country'    => 'billing_country',
				'_billing_email'      => 'billing_email',
				'_billing_phone'      => 'billing_phone',
			),
			'shipping' => array(
				'_shipping_first_name' => 'shipping_first_name',
				'_shipping_last_name'  => 'shipping_last_name',
				'_shipping_company'    => 'shipping_company',
				'_shipping_address_1'  => 'shipping_address_1',
				'_shipping_address_2'  => 'shipping_address_2',
				'_shipping_city'       => 'shipping_city',
				'_shipping_state'      => 'shipping_state',
				'_shipping_postcode'   => 'shipping_postcode',
				'_shipping_country'    => 'shipping_country',
			),
		);

		foreach ( $address_props as $props_key => $props ) {
			$props_to_update = $this->get_props_to_update( $order, $props );
			foreach ( $props_to_update as $meta_key => $prop ) {
				$value = $order->{"get_$prop"}( 'edit' );
				update_post_meta( $id, $meta_key, $value );
				$updated_props[] = $prop;
				$updated_props[] = $props_key;
			}
		}

		parent::update_post_meta( $order );

		// If address changed, store concatenated version to make searches faster.
		if ( in_array( 'billing', $updated_props ) || ! metadata_exists( 'post', $id, '_billing_address_index' ) ) {
			update_post_meta( $id, '_billing_address_index', implode( ' ', $order->get_address( 'billing' ) ) );
		}
		if ( in_array( 'shipping', $updated_props ) || ! metadata_exists( 'post', $id, '_shipping_address_index' ) ) {
			update_post_meta( $id, '_shipping_address_index', implode( ' ', $order->get_address( 'shipping' ) ) );
		}

		// If customer changed, update any downloadable permissions.
		if ( in_array( 'customer_user', $updated_props ) || in_array( 'billing_email', $updated_props ) ) {
			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->update_user_by_order_id( $id, $order->get_customer_id(), $order->get_billing_email() );
		}

		do_action( 'woocommerce_order_object_updated_props', $order, $updated_props );
	}

	/**
	 * Excerpt for post.
	 *
	 * @param  WC_Order $order
	 * @return string
	 */
	protected function get_post_excerpt( $order ) {
		return $order->get_customer_note();
	}

	/**
	 * Get amount already refunded.
	 *
	 * @param  WC_Order
	 * @return string
	 */
	public function get_total_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( postmeta.meta_value )
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			WHERE postmeta.meta_key = '_refund_amount'
			AND postmeta.post_id = posts.ID
		", $order->get_id() ) );

		return $total;
	}

	/**
	 * Get the total tax refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_tax_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( order_itemmeta.meta_value )
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ( order_items.order_id = posts.ID AND order_items.order_item_type = 'tax' )
			WHERE order_itemmeta.order_item_id = order_items.order_item_id
			AND order_itemmeta.meta_key IN ('tax_amount', 'shipping_tax_amount')
		", $order->get_id() ) );

		return abs( $total );
	}

	/**
	 * Get the total shipping refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_shipping_refunded( $order ) {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( order_itemmeta.meta_value )
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ( order_items.order_id = posts.ID AND order_items.order_item_type = 'shipping' )
			WHERE order_itemmeta.order_item_id = order_items.order_item_id
			AND order_itemmeta.meta_key IN ('cost')
		", $order->get_id() ) );

		return abs( $total );
	}

	/**
	 * Finds an Order ID based on an order key.
	 *
	 * @param string $order_key An order key has generated by
	 * @return int The ID of an order, or 0 if the order could not be found
	 */
	public function get_order_id_by_order_key( $order_key ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_key' AND meta_value = %s", $order_key ) );
	}

	/**
	 * Return count of orders with a specific status.
	 *
	 * @param  string $status
	 * @return int
	 */
	public function get_order_count( $status ) {
		global $wpdb;
		return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'shop_order' AND post_status = %s", $status ) ) );
	}

	/**
	 * Get all orders matching the passed in args.
	 *
	 * @see    wc_get_orders()
	 * @param  array $args
	 * @return array of orders
	 */
	public function get_orders( $args = array() ) {
		/**
		 * Generate WP_Query args. This logic will change if orders are moved to
		 * custom tables in the future.
		 */
		$wp_query_args = array(
			'post_type'      => $args['type'] ? $args['type'] : 'shop_order',
			'post_status'    => $args['status'],
			'posts_per_page' => $args['limit'],
			'meta_query'     => array(),
			'fields'         => 'ids',
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
		);

		if ( ! is_null( $args['parent'] ) ) {
			$wp_query_args['post_parent'] = absint( $args['parent'] );
		}

		if ( ! is_null( $args['offset'] ) ) {
			$wp_query_args['offset'] = absint( $args['offset'] );
		} else {
			$wp_query_args['paged'] = absint( $args['page'] );
		}

		if ( isset( $args['customer'] ) && '' !== $args['customer'] ) {
			$values = is_array( $args['customer'] ) ? $args['customer'] : array( $args['customer'] );
			$wp_query_args['meta_query'][] = $this->get_orders_generate_customer_meta_query( $values );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
		}

		if ( ! $args['paginate'] ) {
			$wp_query_args['no_found_rows'] = true;
		}

		if ( ! empty( $args['date_before'] ) ) {
			$wp_query_args['date_query']['before'] = $args['date_before'];
		}

		if ( ! empty( $args['date_after'] ) ) {
			$wp_query_args['date_query']['after'] = $args['date_after'];
		}

		// Get results.
		$orders = new WP_Query( apply_filters( 'woocommerce_order_data_store_cpt_get_orders_query', $wp_query_args, $args, $this ) );

		if ( 'objects' === $args['return'] ) {
			$return = array_map( 'wc_get_order', $orders->posts );
		} else {
			$return = $orders->posts;
		}

		if ( $args['paginate'] ) {
			return (object) array(
				'orders'        => $return,
				'total'         => $orders->found_posts,
				'max_num_pages' => $orders->max_num_pages,
			);
		} else {
			return $return;
		}
	}

	/**
	 * Generate meta query for wc_get_orders.
	 *
	 * @param  array $values
	 * @param  string $relation
	 * @return array
	 */
	private function get_orders_generate_customer_meta_query( $values, $relation = 'or' ) {
		$meta_query = array(
			'relation' => strtoupper( $relation ),
			'customer_emails' => array(
				'key'     => '_billing_email',
				'value'   => array(),
				'compare' => 'IN',
			),
			'customer_ids' => array(
				'key'     => '_customer_user',
				'value'   => array(),
				'compare' => 'IN',
			),
		);
		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				$meta_query[] = $this->get_orders_generate_customer_meta_query( $value, 'and' );
			} elseif ( is_email( $value ) ) {
				$meta_query['customer_emails']['value'][] = sanitize_email( $value );
			} else {
				$meta_query['customer_ids']['value'][] = strval( absint( $value ) );
			}
		}

		if ( empty( $meta_query['customer_emails']['value'] ) ) {
			unset( $meta_query['customer_emails'] );
			unset( $meta_query['relation'] );
		}

		if ( empty( $meta_query['customer_ids']['value'] ) ) {
			unset( $meta_query['customer_ids'] );
			unset( $meta_query['relation'] );
		}

		return $meta_query;
	}

	/**
	 * Get unpaid orders after a certain date,
	 *
	 * @param  int timestamp $date
	 * @return array
	 */
	public function get_unpaid_orders( $date ) {
		global $wpdb;

		$unpaid_orders = $wpdb->get_col( $wpdb->prepare( "
			SELECT posts.ID
			FROM {$wpdb->posts} AS posts
			WHERE   posts.post_type   IN ('" . implode( "','", wc_get_order_types() ) . "')
			AND     posts.post_status = 'wc-pending'
			AND     posts.post_modified < %s
		", date( 'Y-m-d H:i:s', absint( $date ) ) ) );

		return $unpaid_orders;
	}

	/**
	 * Search order data for a term and return ids.
	 *
	 * @param  string $term
	 * @return array of ids
	 */
	public function search_orders( $term ) {
		global $wpdb;

		/**
		 * Searches on meta data can be slow - this lets you choose what fields to search.
		 * 3.0.0 added _billing_address and _shipping_address meta which contains all address data to make this faster.
		 * This however won't work on older orders unless updated, so search a few others (expand this using the filter if needed).
		 * @var array
		 */
		$search_fields = array_map( 'wc_clean', apply_filters( 'woocommerce_shop_order_search_fields', array(
			'_billing_address_index',
			'_shipping_address_index',
			'_billing_last_name',
			'_billing_email',
		) ) );
		$order_ids = array();

		if ( is_numeric( $term ) ) {
			$order_ids[] = absint( $term );
		}

		if ( ! empty( $search_fields ) ) {
			$order_ids = array_unique( array_merge(
				$order_ids,
				$wpdb->get_col(
					$wpdb->prepare( "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE '%%%s%%'", $wpdb->esc_like( wc_clean( $term ) ) ) . " AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')"
				),
				$wpdb->get_col(
					$wpdb->prepare( "
						SELECT order_id
						FROM {$wpdb->prefix}woocommerce_order_items as order_items
						WHERE order_item_name LIKE '%%%s%%'
						",
						$wpdb->esc_like( wc_clean( $term ) )
					)
				)
			) );
		}

		return apply_filters( 'woocommerce_shop_order_search_results', $order_ids, $term, $search_fields );
	}

	/**
	 * Gets information about whether permissions were generated yet.
	 *
	 * @param WC_Order|int $order
	 * @return bool
	 */
	public function get_download_permissions_granted( $order ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		return wc_string_to_bool( get_post_meta( $order_id, '_download_permissions_granted', true ) );
	}

	/**
	 * Stores information about whether permissions were generated yet.
	 *
	 * @param WC_Order|int $order
	 * @param bool $set
	 */
	public function set_download_permissions_granted( $order, $set ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		update_post_meta( $order_id, '_download_permissions_granted', wc_bool_to_string( $set ) );
	}

	/**
	 * Gets information about whether sales were recorded.
	 *
	 * @param WC_Order|int $order
	 * @return bool
	 */
	public function get_recorded_sales( $order ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		return wc_string_to_bool( get_post_meta( $order_id, '_recorded_sales', true ) );
	}

	/**
	 * Stores information about whether sales were recorded.
	 *
	 * @param WC_Order|int $order
	 * @param bool $set
	 */
	public function set_recorded_sales( $order, $set ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		update_post_meta( $order_id, '_recorded_sales', wc_bool_to_string( $set ) );
	}

	/**
	 * Gets information about whether coupon counts were updated.
	 *
	 * @param WC_Order|int $order
	 * @return bool
	 */
	public function get_recorded_coupon_usage_counts( $order ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		return wc_string_to_bool( get_post_meta( $order_id, '_recorded_coupon_usage_counts', true ) );
	}

	/**
	 * Stores information about whether coupon counts were updated.
	 *
	 * @param WC_Order|int $order
	 * @param bool $set
	 */
	public function set_recorded_coupon_usage_counts( $order, $set ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		update_post_meta( $order_id, '_recorded_coupon_usage_counts', wc_bool_to_string( $set ) );
	}

	/**
	 * Gets information about whether stock was reduced.
	 *
	 * @param WC_Order|int $order
	 * @return bool
	 */
	public function get_stock_reduced( $order ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		return wc_string_to_bool( get_post_meta( $order_id, '_order_stock_reduced', true ) );
	}

	/**
	 * Stores information about whether stock was reduced.
	 *
	 * @param WC_Order|int $order
	 * @param bool $set
	 */
	public function set_stock_reduced( $order, $set ) {
		$order_id = WC_Order_Factory::get_order_id( $order );
		update_post_meta( $order_id, '_order_stock_reduced', wc_bool_to_string( $set ) );
	}

	/**
	 * Get the order type based on Order ID.
	 *
	 * @since 3.0.0
	 * @param int $order_id
	 * @return string
	 */
	public function get_order_type( $order_id ) {
		return get_post_type( $order_id );
	}
}
