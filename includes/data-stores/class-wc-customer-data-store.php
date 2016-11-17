<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Customer Data Store.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Customer_Data_Store implements WC_Customer_Data_Store_Interface, WC_Object_Data_Store {

	/**
	 * Method to create a new customer in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Customer
	 */
	public function create( &$customer ) {
		$customer_id = wc_create_new_customer( $customer->get_email(), $customer->get_username(), $customer->get_password() );

		if ( ! is_wp_error( $customer_id ) ) {
			$customer->set_id( $customer_id );
			$this->update_user_meta( $customer );
			wp_update_user( array( 'ID' => $customer->get_id(), 'role' => $customer->get_role() ) );
			$wp_user = new WP_User( $customer->get_id() );
			$customer->set_date_created( strtotime( $wp_user->user_registered ) );
			$customer->set_date_modified( get_user_meta( $customer->get_id(), 'last_update', true ) );
			$customer->save_meta_data();
			$customer->apply_changes();
		}
	}

	/**
	 * Method to read a customer object.
	 *
	 * @since 2.7.0
	 * @param WC_Customer
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
		$customer->set_props( array_map( 'wc_flatten_meta_callback', get_user_meta( $customer_id ) ) );
		$customer->set_props( array(
			'is_paying_customer' => get_user_meta( $customer_id, 'paying_customer', true ),
			'email'              => $user_object->user_email,
			'username'           => $user_object->user_login,
			'date_created'       => strtotime( $user_object->user_registered ),
			'date_modified'      => get_user_meta( $customer_id, 'last_update', true ),
			'role'               => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'customer',
		) );
		$customer->read_meta_data();
		$customer->set_object_read( true );
	}

	/**
	 * Updates a customer in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Customer
	 */
	public function update( &$customer ) {
		wp_update_user( array( 'ID' => $customer->get_id(), 'user_email' => $customer->get_email() ) );
		// Only update password if a new one was set with set_password
		if ( ! empty( $customer->get_password() ) ) {
			wp_update_user( array( 'ID' => $customer->get_id(), 'user_pass' => $customer->get_password() ) );
			$customer->set_password( '' );
		}
		$this->update_user_meta( $customer );
		$customer->set_date_modified( get_user_meta( $customer->get_id(), 'last_update', true ) );
		$customer->save_meta_data();
		$customer->apply_changes();
	}

	/**
	 * Deletes a customer from the database.
	 *
	 * @since 2.7.0
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
		return wp_delete_user( $customer->get_id(), $args['reassign'] );
	}

	/**
	 * Helper method that updates all the meta for a customer. Used for update & create.
	 * @since 2.7.0
	 * @param WC_Customer
	 */
	private function update_user_meta( $customer ) {
		$updated_props = array();
		$changed_props = array_keys( $customer->get_changes() );

		$meta_key_to_props = array(
			'paying_customer' => 'is_paying_customer',
			'first_name'      => 'first_name',
			'last_name'       => 'last_name',
		);

		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! in_array( $prop, $changed_props ) ) {
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
			if ( ! isset( $changed_props['billing'] ) || ! in_array( $prop_key, $changed_props['billing'] ) ) {
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
			if ( ! isset( $changed_props['shipping'] ) || ! in_array( $prop_key, $changed_props['shipping'] ) ) {
				continue;
			}
			if ( update_user_meta( $customer->get_id(), $meta_key, $customer->{"get_$prop"}( 'edit' ) ) ) {
				$updated_props[] = $prop;
			}
		}
	}

	/**
	 * Gets the customers last order.
	 *
	 * @since 2.7.0
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
	 * @since 2.7.0
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
	 * @since 2.7.0
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
}
