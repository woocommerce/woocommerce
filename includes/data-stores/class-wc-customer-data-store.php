<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Data Store.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Customer_Data_Store extends WC_Data_Store_WP implements WC_Customer_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'locale',
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address_2',
		'billing_state',
		'billing_country',
		'shipping_postcode',
		'shipping_city',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_state',
		'shipping_country',
		'paying_customer',
		'last_update',
		'first_name',
		'last_name',
		'show_admin_bar_front',
		'use_ssl',
		'admin_color',
		'rich_editing',
		'comment_shortcuts',
		'dismissed_wp_pointers',
		'show_welcome_panel',
		'_woocommerce_persistent_cart',
		'session_tokens',
		'nickname',
		'description',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'wptests_capabilities',
		'wptests_user_level',
		'_order_count',
		'_money_spent',
	);

	/**
	 * Internal meta type used to store user data.
	 *
	 * @var string
	 */
	protected $meta_type = 'user';

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		global $wpdb;

		$table_prefix = $wpdb->prefix ? $wpdb->prefix : 'wp_';

		return ! in_array( $meta->meta_key, $this->internal_meta_keys )
			&& 0 !== strpos( $meta->meta_key, 'closedpostboxes_' )
			&& 0 !== strpos( $meta->meta_key, 'metaboxhidden_' )
			&& 0 !== strpos( $meta->meta_key, 'manageedit-' )
			&& ! strstr( $meta->meta_key, $table_prefix )
			&& 0 !== stripos( $meta->meta_key, 'wp_' );
	 }

	/**
	 * Method to create a new customer in the database.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 */
	public function create( &$customer ) {
		$id = wc_create_new_customer( $customer->get_email(), $customer->get_username(), $customer->get_password() );

		if ( is_wp_error( $id ) ) {
			throw new WC_Data_Exception( $id->get_error_code(), $id->get_error_message() );
		}

		$customer->set_id( $id );
		$this->update_user_meta( $customer );

		// Prevent wp_update_user calls in the same request and customer trigger the 'Notice of Password Changed' email
		$customer->set_password( '' );

		wp_update_user( apply_filters( 'woocommerce_update_customer_args', array(
			'ID'           => $customer->get_id(),
			'role'         => $customer->get_role(),
			'display_name' => $customer->get_first_name() . ' ' . $customer->get_last_name(),
		), $customer ) );
		$wp_user = new WP_User( $customer->get_id() );
		$customer->set_date_created( $wp_user->user_registered );
		$customer->set_date_modified( get_user_meta( $customer->get_id(), 'last_update', true ) );
		$customer->save_meta_data();
		$customer->apply_changes();
		do_action( 'woocommerce_new_customer', $customer->get_id() );
	}

	/**
	 * Method to read a customer object.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @throws Exception
	 */
	public function read( &$customer ) {
		global $wpdb;

		// User object is required.
		if ( ! $customer->get_id() || ! ( $user_object = get_user_by( 'id', $customer->get_id() ) ) || empty( $user_object->ID ) ) {
			throw new Exception( __( 'Invalid customer.', 'woocommerce' ) );
		}

		// Only users on this site should be read.
		if ( is_multisite() && ! is_user_member_of_blog( $customer->get_id() ) ) {
			throw new Exception( __( 'Invalid customer.', 'woocommerce' ) );
		}

		$customer_id = $customer->get_id();
		// Load meta but exclude deprecated props.
		$user_meta = array_diff_key( array_map( 'wc_flatten_meta_callback', get_user_meta( $customer_id ) ), array_flip( array( 'country', 'state', 'postcode', 'city', 'address', 'address_2', 'default', 'location' ) ) );
		$customer->set_props( $user_meta );
		$customer->set_props( array(
			'is_paying_customer' => get_user_meta( $customer_id, 'paying_customer', true ),
			'email'              => $user_object->user_email,
			'username'           => $user_object->user_login,
			'date_created'       => $user_object->user_registered, // Mysql string in local format.
			'date_modified'      => get_user_meta( $customer_id, 'last_update', true ),
			'role'               => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'customer',
		) );
		$customer->read_meta_data();
		$customer->set_object_read( true );
		do_action( 'woocommerce_customer_loaded', $customer );
	}

	/**
	 * Updates a customer in the database.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 */
	public function update( &$customer ) {
		wp_update_user( apply_filters( 'woocommerce_update_customer_args', array(
			'ID'           => $customer->get_id(),
			'user_email'   => $customer->get_email(),
			'display_name' => $customer->get_first_name() . ' ' . $customer->get_last_name(),
		), $customer ) );
		// Only update password if a new one was set with set_password.
		if ( $customer->get_password() ) {
			wp_update_user( array( 'ID' => $customer->get_id(), 'user_pass' => $customer->get_password() ) );
			$customer->set_password( '' );
		}
		$this->update_user_meta( $customer );
		$customer->set_date_modified( get_user_meta( $customer->get_id(), 'last_update', true ) );
		$customer->save_meta_data();
		$customer->apply_changes();
		do_action( 'woocommerce_update_customer', $customer->get_id() );
	}

	/**
	 * Deletes a customer from the database.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$customer, $args = array() ) {
		if ( ! $customer->get_id() ) {
			return;
		}
		$args = wp_parse_args( $args, array(
			'reassign' => 0,
		) );

		$id = $customer->get_id();
		wp_delete_user( $id, $args['reassign'] );

		do_action( 'woocommerce_delete_customer', $id );
	}

	/**
	 * Helper method that updates all the meta for a customer. Used for update & create.
	 * @since 3.0.0
	 * @param WC_Customer
	 */
	private function update_user_meta( $customer ) {
		$updated_props = array();
		$changed_props = $customer->get_changes();

		$meta_key_to_props = array(
			'paying_customer' => 'is_paying_customer',
			'first_name'      => 'first_name',
			'last_name'       => 'last_name',
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! array_key_exists( $prop, $changed_props ) ) {
				continue;
			}

			if ( update_user_meta( $customer->get_id(), $meta_key, $customer->{"get_$prop"}( 'edit' ) ) ) {
				$updated_props[] = $prop;
			}
		}

		$billing_address_props = array(
			'billing_first_name' => 'billing_first_name',
			'billing_last_name'  => 'billing_last_name',
			'billing_company'    => 'billing_company',
			'billing_address_1'  => 'billing_address_1',
			'billing_address_2'  => 'billing_address_2',
			'billing_city'       => 'billing_city',
			'billing_state'      => 'billing_state',
			'billing_postcode'   => 'billing_postcode',
			'billing_country'    => 'billing_country',
			'billing_email'      => 'billing_email',
			'billing_phone'      => 'billing_phone',
		);

		foreach ( $billing_address_props as $meta_key => $prop ) {
			$prop_key = substr( $prop, 8 );
			if ( ! isset( $changed_props['billing'] ) || ! array_key_exists( $prop_key, $changed_props['billing'] ) ) {
				continue;
			}
			if ( update_user_meta( $customer->get_id(), $meta_key, $customer->{"get_$prop"}( 'edit' ) ) ) {
				$updated_props[] = $prop;
			}
		}

		$shipping_address_props = array(
			'shipping_first_name' => 'shipping_first_name',
			'shipping_last_name'  => 'shipping_last_name',
			'shipping_company'    => 'shipping_company',
			'shipping_address_1'  => 'shipping_address_1',
			'shipping_address_2'  => 'shipping_address_2',
			'shipping_city'       => 'shipping_city',
			'shipping_state'      => 'shipping_state',
			'shipping_postcode'   => 'shipping_postcode',
			'shipping_country'    => 'shipping_country',
		);

		foreach ( $shipping_address_props as $meta_key => $prop ) {
			$prop_key = substr( $prop, 9 );
			if ( ! isset( $changed_props['shipping'] ) || ! array_key_exists( $prop_key, $changed_props['shipping'] ) ) {
				continue;
			}
			if ( update_user_meta( $customer->get_id(), $meta_key, $customer->{"get_$prop"}( 'edit' ) ) ) {
				$updated_props[] = $prop;
			}
		}

		do_action( 'woocommerce_customer_object_updated_props', $customer, $updated_props );
	}

	/**
	 * Gets the customers last order.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @return WC_Order|false
	 */
	public function get_last_order( &$customer ) {
		global $wpdb;

		$last_order = $wpdb->get_var( "SELECT posts.ID
			FROM $wpdb->posts AS posts
			LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
			WHERE meta.meta_key = '_customer_user'
			AND   meta.meta_value = '" . esc_sql( $customer->get_id() ) . "'
			AND   posts.post_type = 'shop_order'
			AND   posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( wc_get_order_statuses() ) ) ) . "' )
			ORDER BY posts.ID DESC
		" );

		if ( $last_order ) {
			return wc_get_order( absint( $last_order ) );
		} else {
			return false;
		}
	}

	/**
	 * Return the number of orders this customer has.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @return integer
	 */
	public function get_order_count( &$customer ) {
		$count = get_user_meta( $customer->get_id(), '_order_count', true );

		if ( '' === $count ) {
			global $wpdb;

			$count = $wpdb->get_var( "SELECT COUNT(*)
				FROM $wpdb->posts as posts
				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				WHERE   meta.meta_key = '_customer_user'
				AND     posts.post_type = 'shop_order'
				AND     posts.post_status IN ( '" . implode( "','", array_map( 'esc_sql', array_keys( wc_get_order_statuses() ) ) ) . "' )
				AND     meta_value = '" . esc_sql( $customer->get_id() ) . "'
			" );
			update_user_meta( $customer->get_id(), '_order_count', $count );
		}

		return absint( $count );
	}

	/**
	 * Return how much money this customer has spent.
	 *
	 * @since 3.0.0
	 * @param WC_Customer
	 * @return float
	 */
	public function get_total_spent( &$customer ) {
		$spent = get_user_meta( $customer->get_id(), '_money_spent', true );

		if ( '' === $spent ) {
			global $wpdb;

			$statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );
			$spent    = $wpdb->get_var( "SELECT SUM(meta2.meta_value)
				FROM $wpdb->posts as posts
				LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
				LEFT JOIN {$wpdb->postmeta} AS meta2 ON posts.ID = meta2.post_id
				WHERE   meta.meta_key       = '_customer_user'
				AND     meta.meta_value     = '" . esc_sql( $customer->get_id() ) . "'
				AND     posts.post_type     = 'shop_order'
				AND     posts.post_status   IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
				AND     meta2.meta_key      = '_order_total'
			" );

			if ( ! $spent ) {
				$spent = 0;
			}
			update_user_meta( $customer->get_id(), '_money_spent', $spent );
		}

		return wc_format_decimal( $spent, 2 );
	}

	/**
	 * Search customers and return customer IDs.
	 *
	 * @param  string $term
	 * @oaram  int|string $limit @since 3.0.7
	 * @return array
	 */
	public function search_customers( $term, $limit = '' ) {
		$query = new WP_User_Query( array(
			'search'         => '*' . esc_attr( $term ) . '*',
			'search_columns' => array( 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' ),
			'fields'         => 'ID',
			'number'         => $limit,
		) );

		$query2 = new WP_User_Query( array(
			'fields'         => 'ID',
			'number'         => $limit,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'first_name',
					'value'   => $term,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'last_name',
					'value'   => $term,
					'compare' => 'LIKE',
				),
			),
		) );

		$results = wp_parse_id_list( array_merge( $query->get_results(), $query2->get_results() ) );

		if ( $limit && count( $results ) > $limit ) {
			$results = array_slice( $results, 0, $limit );
		}

		return $results;
	}
}
