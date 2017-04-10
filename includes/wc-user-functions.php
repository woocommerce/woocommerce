<?php
/**
 * WooCommerce Customer Functions
 *
 * Functions for customers.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version 	2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from seeing the admin bar.
 *
 * Note: get_option( 'woocommerce_lock_down_admin', true ) is a deprecated option here for backwards compat. Defaults to true.
 *
 * @access public
 * @param bool $show_admin_bar
 * @return bool
 */
function wc_disable_admin_bar( $show_admin_bar ) {
	if ( apply_filters( 'woocommerce_disable_admin_bar', get_option( 'woocommerce_lock_down_admin', 'yes' ) === 'yes' ) && ! ( current_user_can( 'edit_posts' ) || current_user_can( 'manage_woocommerce' ) ) ) {
		$show_admin_bar = false;
	}

	return $show_admin_bar;
}
add_filter( 'show_admin_bar', 'wc_disable_admin_bar', 10, 1 );

if ( ! function_exists( 'wc_create_new_customer' ) ) {

	/**
	 * Create a new customer.
	 *
	 * @param  string $email Customer email.
	 * @param  string $username Customer username.
	 * @param  string $password Customer password.
	 * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
	 */
	function wc_create_new_customer( $email, $username = '', $password = '' ) {

		// Check the email address.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'woocommerce' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'registration-error-email-exists', __( 'An account is already registered with your email address. Please login.', 'woocommerce' ) );
		}

		// Handle username creation.
		if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) || ! empty( $username ) ) {
			$username = sanitize_user( $username );

			if ( empty( $username ) || ! validate_username( $username ) ) {
				return new WP_Error( 'registration-error-invalid-username', __( 'Please enter a valid account username.', 'woocommerce' ) );
			}

			if ( username_exists( $username ) ) {
				return new WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'woocommerce' ) );
			}
		} else {
			$username = sanitize_user( current( explode( '@', $email ) ), true );

			// Ensure username is unique.
			$append     = 1;
			$o_username = $username;

			while ( username_exists( $username ) ) {
				$username = $o_username . $append;
				$append++;
			}
		}

		// Handle password creation.
		if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && empty( $password ) ) {
			$password           = wp_generate_password();
			$password_generated = true;
		} elseif ( empty( $password ) ) {
			return new WP_Error( 'registration-error-missing-password', __( 'Please enter an account password.', 'woocommerce' ) );
		} else {
			$password_generated = false;
		}

		// Use WP_Error to handle registration errors.
		$errors = new WP_Error();

		do_action( 'woocommerce_register_post', $username, $email, $errors );

		$errors = apply_filters( 'woocommerce_registration_errors', $errors, $username, $email );

		if ( $errors->get_error_code() ) {
			return $errors;
		}

		$new_customer_data = apply_filters( 'woocommerce_new_customer_data', array(
			'user_login' => $username,
			'user_pass'  => $password,
			'user_email' => $email,
			'role'       => 'customer',
		) );

		$customer_id = wp_insert_user( $new_customer_data );

		if ( is_wp_error( $customer_id ) ) {
			return new WP_Error( 'registration-error', '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
		}

		do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );

		return $customer_id;
	}
}

/**
 * Login a customer (set auth cookie and set global user object).
 *
 * @param int $customer_id
 */
function wc_set_customer_auth_cookie( $customer_id ) {
	global $current_user;

	$current_user = get_user_by( 'id', $customer_id );

	wp_set_auth_cookie( $customer_id, true );
}

/**
 * Get past orders (by email) and update them.
 *
 * @param  int $customer_id
 * @return int
 */
function wc_update_new_customer_past_orders( $customer_id ) {
	$linked          = 0;
	$complete        = 0;
	$customer        = get_user_by( 'id', absint( $customer_id ) );
	$customer_orders = wc_get_orders( array(
		'limit'    => -1,
		'customer' => array( array( 0, $customer->user_email ) ),
		'return'   => 'ids',
	) );

	if ( ! empty( $customer_orders ) ) {
		foreach ( $customer_orders as $order_id ) {
			update_post_meta( $order_id, '_customer_user', $customer->ID );

			do_action( 'woocommerce_update_new_customer_past_order', $order_id, $customer );

			if ( get_post_status( $order_id ) === 'wc-completed' ) {
				$complete++;
			}

			$linked++;
		}
	}

	if ( $complete ) {
		update_user_meta( $customer_id, 'paying_customer', 1 );
		update_user_meta( $customer_id, '_order_count', '' );
		update_user_meta( $customer_id, '_money_spent', '' );
	}

	return $linked;
}

/**
 * Order Status completed - This is a paying customer.
 *
 * @access public
 * @param int $order_id
 */
function wc_paying_customer( $order_id ) {
	$order       = wc_get_order( $order_id );
	$customer_id = $order->get_customer_id();

	if ( $customer_id > 0 && 'shop_order_refund' !== $order->get_type() ) {
		$customer = new WC_Customer( $customer_id );
		$customer->set_is_paying_customer( true );
		$customer->save();
	}
}
add_action( 'woocommerce_order_status_completed', 'wc_paying_customer' );

/**
 * Checks if a user (by email or ID or both) has bought an item.
 * @param string $customer_email
 * @param int $user_id
 * @param int $product_id
 * @return bool
 */
function wc_customer_bought_product( $customer_email, $user_id, $product_id ) {
	global $wpdb;

	$transient_name = 'wc_cbp_' . md5( $customer_email . $user_id . WC_Cache_Helper::get_transient_version( 'orders' ) );

	if ( false === ( $result = get_transient( $transient_name ) ) ) {
		$customer_data = array( $user_id );

		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );

			if ( isset( $user->user_email ) ) {
				$customer_data[] = $user->user_email;
			}
		}

		if ( is_email( $customer_email ) ) {
			$customer_data[] = $customer_email;
		}

		$customer_data = array_map( 'esc_sql', array_filter( array_unique( $customer_data ) ) );
		$statuses      = array_map( 'esc_sql', wc_get_is_paid_statuses() );

		if ( sizeof( $customer_data ) == 0 ) {
			return false;
		}

		$result = $wpdb->get_col( "
			SELECT im.meta_value FROM {$wpdb->posts} AS p
			INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS i ON p.ID = i.order_id
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS im ON i.order_item_id = im.order_item_id
			WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $statuses ) . "' )
			AND pm.meta_key IN ( '_billing_email', '_customer_user' )
			AND im.meta_key IN ( '_product_id', '_variation_id' )
			AND im.meta_value != 0
			AND pm.meta_value IN ( '" . implode( "','", $customer_data ) . "' )
		" );
		$result = array_map( 'absint', $result );

		set_transient( $transient_name, $result, DAY_IN_SECONDS * 30 );
	}
	return in_array( absint( $product_id ), $result );
}

/**
 * Checks if a user has a certain capability.
 *
 * @access public
 * @param array $allcaps
 * @param array $caps
 * @param array $args
 * @return bool
 */
function wc_customer_has_capability( $allcaps, $caps, $args ) {
	if ( isset( $caps[0] ) ) {
		switch ( $caps[0] ) {
			case 'view_order' :
				$user_id = $args[1];
				$order   = wc_get_order( $args[2] );

				if ( $order && $user_id == $order->get_user_id() ) {
					$allcaps['view_order'] = true;
				}
			break;
			case 'pay_for_order' :
				$user_id  = $args[1];
				$order_id = isset( $args[2] ) ? $args[2] : null;

				// When no order ID, we assume it's a new order
				// and thus, customer can pay for it
				if ( ! $order_id ) {
					$allcaps['pay_for_order'] = true;
					break;
				}

				$order = wc_get_order( $order_id );
				if ( $user_id == $order->get_user_id() || ! $order->get_user_id() ) {
					$allcaps['pay_for_order'] = true;
				}
			break;
			case 'order_again' :
				$user_id = $args[1];
				$order   = wc_get_order( $args[2] );

				if ( $user_id == $order->get_user_id() ) {
					$allcaps['order_again'] = true;
				}
			break;
			case 'cancel_order' :
				$user_id = $args[1];
				$order   = wc_get_order( $args[2] );

				if ( $user_id == $order->get_user_id() ) {
					$allcaps['cancel_order'] = true;
				}
			break;
			case 'download_file' :
				$user_id  = $args[1];
				$download = $args[2];

				if ( $user_id == $download->get_user_id() ) {
					$allcaps['download_file'] = true;
				}
			break;
		}
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'wc_customer_has_capability', 10, 3 );

/**
 * Modify the list of editable roles to prevent non-admin adding admin users.
 * @param  array $roles
 * @return array
 */
function wc_modify_editable_roles( $roles ) {
	if ( ! current_user_can( 'administrator' ) ) {
		unset( $roles['administrator'] );
	}
	return $roles;
}
add_filter( 'editable_roles', 'wc_modify_editable_roles' );

/**
 * Modify capabiltiies to prevent non-admin users editing admin users.
 *
 * $args[0] will be the user being edited in this case.
 *
 * @param  array $caps Array of caps
 * @param  string $cap Name of the cap we are checking
 * @param  int $user_id ID of the user being checked against
 * @param  array $args
 * @return array
 */
function wc_modify_map_meta_cap( $caps, $cap, $user_id, $args ) {
	switch ( $cap ) {
		case 'edit_user' :
		case 'remove_user' :
		case 'promote_user' :
		case 'delete_user' :
			if ( ! isset( $args[0] ) || $args[0] === $user_id ) {
				break;
			} else {
				if ( user_can( $args[0], 'administrator' ) && ! current_user_can( 'administrator' ) ) {
					$caps[] = 'do_not_allow';
				}
			}
		break;
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'wc_modify_map_meta_cap', 10, 4 );

/**
 * Get customer download permissions from the database.
 *
 * @param int $customer_id Customer/User ID
 * @return array
 */
function wc_get_customer_download_permissions( $customer_id ) {
	$data_store = WC_Data_Store::load( 'customer-download' );
	return apply_filters( 'woocommerce_permission_list', $data_store->get_downloads_for_customer( $customer_id ), $customer_id );
}

/**
 * Get customer available downloads.
 *
 * @param int $customer_id Customer/User ID
 * @return array
 */
function wc_get_customer_available_downloads( $customer_id ) {
	$downloads   = array();
	$_product    = null;
	$order       = null;
	$file_number = 0;

	// Get results from valid orders only
	$results = wc_get_customer_download_permissions( $customer_id );

	if ( $results ) {
		foreach ( $results as $result ) {
			if ( ! $order || $order->get_id() != $result->order_id ) {
				// new order
				$order    = wc_get_order( $result->order_id );
				$_product = null;
			}

			// Make sure the order exists for this download
			if ( ! $order ) {
				continue;
			}

			// Downloads permitted?
			if ( ! $order->is_download_permitted() ) {
				continue;
			}

			$product_id = intval( $result->product_id );

			if ( ! $_product || $_product->get_id() != $product_id ) {
				// new product
				$file_number = 0;
				$_product    = wc_get_product( $product_id );
			}

			// Check product exists and has the file
			if ( ! $_product || ! $_product->exists() || ! $_product->has_file( $result->download_id ) ) {
				continue;
			}

			$download_file = $_product->get_file( $result->download_id );

			// Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files.
			$download_name = apply_filters(
				'woocommerce_downloadable_product_name',
				$_product->get_name() . ' &ndash; ' . $download_file['name'],
				$_product,
				$result->download_id,
				$file_number
			);

			$downloads[] = array(
				'download_url'          => add_query_arg(
					array(
						'download_file' => $product_id,
						'order'         => $result->order_key,
						'email'         => urlencode( $result->user_email ),
						'key'           => $result->download_id,
					),
					home_url( '/' )
				),
				'download_id'           => $result->download_id,
				'product_id'            => $_product->get_id(),
				'product_name'          => $_product->get_name(),
				'download_name'         => $download_name,
				'order_id'              => $order->get_id(),
				'order_key'             => $order->get_order_key(),
				'downloads_remaining'   => $result->downloads_remaining,
				'access_expires'        => $result->access_expires,
				'file'                  => array(
					'name' => $download_file->get_name(),
					'file' => $download_file->get_file(),
				),
			);

			$file_number++;
		}
	}

	return apply_filters( 'woocommerce_customer_available_downloads', $downloads, $customer_id );
}

/**
 * Get total spent by customer.
 * @param  int $user_id
 * @return string
 */
function wc_get_customer_total_spent( $user_id ) {
	$customer = new WC_Customer( $user_id );
	return $customer->get_total_spent();
}

/**
 * Get total orders by customer.
 * @param  int $user_id
 * @return int
 */
function wc_get_customer_order_count( $user_id ) {
	$customer = new WC_Customer( $user_id );
	return $customer->get_order_count();
}

/**
 * Reset _customer_user on orders when a user is deleted.
 * @param int $user_id
 */
function wc_reset_order_customer_id_on_deleted_user( $user_id ) {
	global $wpdb;

	$wpdb->update( $wpdb->postmeta, array( 'meta_value' => 0 ), array( 'meta_key' => '_customer_user', 'meta_value' => $user_id ) );
}

add_action( 'deleted_user', 'wc_reset_order_customer_id_on_deleted_user' );

/**
 * Get review verification status.
 * @param  int $comment_id
 * @return bool
 */
function wc_review_is_from_verified_owner( $comment_id ) {
	$verified = get_comment_meta( $comment_id, 'verified', true );

	// If no "verified" meta is present, generate it (if this is a product review).
	if ( '' === $verified ) {
		$verified = WC_Comments::add_comment_purchase_verification( $comment_id );
	}

	return (bool) $verified;
}

/**
 * Disable author archives for customers.
 *
 * @since 2.5.0
 */
function wc_disable_author_archives_for_customers() {
	global $wp_query, $author;

	if ( is_author() ) {
		$user = get_user_by( 'id', $author );

		if ( isset( $user->roles[0] ) && 'customer' === $user->roles[0] ) {
			wp_redirect( wc_get_page_permalink( 'shop' ) );
		}
	}
}

add_action( 'template_redirect', 'wc_disable_author_archives_for_customers' );

/**
 * Hooks into the `profile_update` hook to set the user last updated timestamp.
 *
 * @since 2.6.0
 * @param int   $user_id The user that was updated.
 * @param array $old     The profile fields pre-change.
 */
function wc_update_profile_last_update_time( $user_id, $old ) {
	wc_set_user_last_update_time( $user_id );
}

add_action( 'profile_update', 'wc_update_profile_last_update_time', 10, 2 );

/**
 * Hooks into the update user meta function to set the user last updated timestamp.
 *
 * @since 2.6.0
 * @param int    $meta_id     ID of the meta object that was changed.
 * @param int    $user_id     The user that was updated.
 * @param string $meta_key    Name of the meta key that was changed.
 * @param string $_meta_value Value of the meta that was changed.
 */
function wc_meta_update_last_update_time( $meta_id, $user_id, $meta_key, $_meta_value ) {
	$keys_to_track = apply_filters( 'woocommerce_user_last_update_fields', array( 'first_name', 'last_name' ) );
	$update_time   = false;
	if ( in_array( $meta_key, $keys_to_track ) ) {
		$update_time = true;
	}
	if ( 'billing_' === substr( $meta_key, 0, 8 ) ) {
		$update_time = true;
	}
	if ( 'shipping_' === substr( $meta_key, 0, 9 ) ) {
		$update_time = true;
	}

	if ( $update_time ) {
		wc_set_user_last_update_time( $user_id );
	}
}

add_action( 'update_user_meta', 'wc_meta_update_last_update_time', 10, 4 );

/**
 * Sets a user's "last update" time to the current timestamp.
 *
 * @since 2.6.0
 * @param int $user_id The user to set a timestamp for.
 */
function wc_set_user_last_update_time( $user_id ) {
	update_user_meta( $user_id, 'last_update', gmdate( 'U' ) );
}

/**
 * Get customer saved payment methods list.
 *
 * @since 2.6.0
 * @param int $customer_id
 * @return array
 */
function wc_get_customer_saved_methods_list( $customer_id ) {
	return apply_filters( 'woocommerce_saved_payment_methods_list', array(), $customer_id );
}

/**
 * Get info about customer's last order.
 *
 * @since 2.6.0
 * @param int $customer_id Customer ID.
 * @return WC_Order Order object if successful or false.
 */
function wc_get_customer_last_order( $customer_id ) {
	global $wpdb;

	$customer_id = absint( $customer_id );

	$id = $wpdb->get_var( "SELECT id
		FROM $wpdb->posts AS posts
		LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
		WHERE meta.meta_key = '_customer_user'
		AND   meta.meta_value = {$customer_id}
		AND   posts.post_type = 'shop_order'
		AND   posts.post_status IN ( '" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "' )
		ORDER BY posts.ID DESC
	" );

	return wc_get_order( $id );
}

/**
 * Wrapper for @see get_avatar() which doesn't simply return
 * the URL so we need to pluck it from the HTML img tag.
 *
 * Kudos to https://github.com/WP-API/WP-API for offering a better solution.
 *
 * @since 2.6.0
 * @param string $email the customer's email.
 * @return string the URL to the customer's avatar.
 */
function wc_get_customer_avatar_url( $email ) {
	$avatar_html = get_avatar( $email );

	// Get the URL of the avatar from the provided HTML.
	preg_match( '/src=["|\'](.+)[\&|"|\']/U', $avatar_html, $matches );

	if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
		return esc_url_raw( $matches[1] );
	}

	return null;
}
