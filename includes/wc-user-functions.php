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
 * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from seeing the admin bar
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


/**
 * Create a new customer
 *
 * @param  string $email
 * @param  string $username
 * @param  string $password
 * @return int|WP_Error on failure, Int (user ID) on success
 */
function wc_create_new_customer( $email, $username = '', $password = '' ) {

	// Check the e-mail address
	if ( empty( $email ) || ! is_email( $email ) ) {
		return new WP_Error( 'registration-error', __( 'Please provide a valid email address.', 'woocommerce' ) );
	}

	if ( email_exists( $email ) ) {
		return new WP_Error( 'registration-error', __( 'An account is already registered with your email address. Please login.', 'woocommerce' ) );
	}

	// Handle username creation
	if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) || ! empty( $username ) ) {

		$username = sanitize_user( $username );

		if ( empty( $username ) || ! validate_username( $username ) ) {
			return new WP_Error( 'registration-error', __( 'Please enter a valid account username.', 'woocommerce' ) );
		}

		if ( username_exists( $username ) )
			return new WP_Error( 'registration-error', __( 'An account is already registered with that username. Please choose another.', 'woocommerce' ) );
	} else {

		$username = sanitize_user( current( explode( '@', $email ) ) );

		// Ensure username is unique
		$append     = 1;
		$o_username = $username;

		while ( username_exists( $username ) ) {
			$username = $o_username . $append;
			$append ++;
		}
	}

	// Handle password creation
	if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && empty( $password ) ) {
		$password = wp_generate_password();
		$password_generated = true;

	} elseif ( empty( $password ) ) {
		return new WP_Error( 'registration-error', __( 'Please enter an account password.', 'woocommerce' ) );

	} else {
		$password_generated = false;
	}

	// WP Validation
	$validation_errors = new WP_Error();

	do_action( 'woocommerce_register_post', $username, $email, $validation_errors );

	$validation_errors = apply_filters( 'woocommerce_registration_errors', $validation_errors, $username, $email );

	if ( $validation_errors->get_error_code() )
		return $validation_errors;

	$new_customer_data = apply_filters( 'woocommerce_new_customer_data', array(
		'user_login' => $username,
		'user_pass'  => $password,
		'user_email' => $email,
		'role'       => 'customer'
	) );

	$customer_id = wp_insert_user( $new_customer_data );

	if ( is_wp_error( $customer_id ) ) {
		return new WP_Error( 'registration-error', '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
	}

	do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );

	return $customer_id;
}

/**
 * Login a customer (set auth cookie and set global user object)
 *
 * @param  int $customer_id
 * @return void
 */
function wc_set_customer_auth_cookie( $customer_id ) {
	global $current_user;

	$current_user = get_user_by( 'id', $customer_id );

	wp_set_auth_cookie( $customer_id, true );
}

/**
 * Get past orders (by email) and update them
 *
 * @param  int $customer_id
 * @return int
 */
function wc_update_new_customer_past_orders( $customer_id ) {

	$customer = get_user_by( 'id', absint( $customer_id ) );

	$customer_orders = get_posts( array(
		'numberposts' => -1,
		'post_type'   => wc_get_order_types(),
		'post_status' => array_keys( wc_get_order_statuses() ),
		'fields'      => 'ids',
		'meta_query' => array(
			array(
				'key'     => '_customer_user',
				'value'   => array( 0, '' ),
				'compare' => 'IN'
			),
			array(
				'key'     => '_billing_email',
				'value'   => $customer->user_email,
			)
		),
	) );

	$linked   = 0;
	$complete = 0;

	if ( $customer_orders )
		foreach ( $customer_orders as $order_id ) {
			update_post_meta( $order_id, '_customer_user', $customer->ID );

			$order_status = get_post_status( $order_id );

			if ( $order_status ) {
				$order_status = current( $order_status );
				$order_status = sanitize_title( $order_status->slug );
			}

			if ( $order_status === 'completed' ) {
				$complete ++;
			}

			$linked ++;
		}

	if ( $complete ) {
		update_user_meta( $customer_id, 'paying_customer', 1 );
		update_user_meta( $customer_id, '_order_count', '' );
		update_user_meta( $customer_id, '_money_spent', '' );
	}

	return $linked;
}

/**
 * Order Status completed - This is a paying customer
 *
 * @access public
 * @param int $order_id
 * @return void
 */
function wc_paying_customer( $order_id ) {

	$order = wc_get_order( $order_id );

	if ( $order->user_id > 0 ) {
		update_user_meta( $order->user_id, 'paying_customer', 1 );

		$old_spent = absint( get_user_meta( $order->user_id, '_money_spent', true ) );
		update_user_meta( $order->user_id, '_money_spent', $old_spent + $order->order_total );

		$old_count = absint( get_user_meta( $order->user_id, '_order_count', true ) );
		update_user_meta( $order->user_id, '_order_count', $old_count + 1 );
	}
}
add_action( 'woocommerce_order_status_completed', 'wc_paying_customer' );


/**
 * Checks if a user (by email) has bought an item
 *
 * @access public
 * @param string $customer_email
 * @param int $user_id
 * @param int $product_id
 * @return bool
 */
function wc_customer_bought_product( $customer_email, $user_id, $product_id ) {
	global $wpdb;

	$emails = array();

	if ( $user_id ) {
		$user     = get_user_by( 'id', $user_id );
		$emails[] = $user->user_email;
	}

	if ( is_email( $customer_email ) ) {
		$emails[] = $customer_email;
	}

	if ( sizeof( $emails ) == 0 ) {
		return false;
	}

	return $wpdb->get_var(
		$wpdb->prepare( "
			SELECT COUNT( DISTINCT order_items.order_item_id )
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON order_items.order_item_id = itemmeta.order_item_id
			LEFT JOIN {$wpdb->postmeta} AS postmeta ON order_items.order_id = postmeta.post_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			WHERE
				posts.post_status IN ( 'wc-completed', 'wc-processing' ) AND
				itemmeta.meta_value  = %s AND
				itemmeta.meta_key    IN ( '_variation_id', '_product_id' ) AND
				postmeta.meta_key    IN ( '_billing_email', '_customer_user' ) AND
				(
					postmeta.meta_value  IN ( '" . implode( "','", array_unique( $emails ) ) . "' ) OR
					(
						postmeta.meta_value = %s AND
						postmeta.meta_value > 0
					)
				)
			", $product_id, $user_id
		)
	);
}

/**
 * Checks if a user has a certain capability
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

				if ( $user_id == $order->user_id ) {
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
				if ( $user_id == $order->user_id || empty( $order->user_id ) ) {
					$allcaps['pay_for_order'] = true;
				}
			break;
			case 'order_again' :
				$user_id = $args[1];
				$order   = wc_get_order( $args[2] );

				if ( $user_id == $order->user_id ) {
					$allcaps['order_again'] = true;
				}
			break;
			case 'cancel_order' :
				$user_id = $args[1];
				$order   = wc_get_order( $args[2] );

				if ( $user_id == $order->user_id ) {
					$allcaps['cancel_order'] = true;
				}
			break;
			case 'download_file' :
				$user_id  = $args[1];
				$download = $args[2];

				if ( $user_id == $download->user_id ) {
					$allcaps['download_file'] = true;
				}
			break;
		}
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'wc_customer_has_capability', 10, 3 );

/**
 * Modify the list of editable roles to prevent non-admin adding admin users
 * @param  array $roles
 * @return array
 */
function wc_modify_editable_roles( $roles ){
	if ( ! current_user_can( 'administrator' ) ) {
		unset( $roles[ 'administrator' ] );
	}
    return $roles;
}
add_filter( 'editable_roles', 'wc_modify_editable_roles' );

/**
 * Modify capabiltiies to prevent non-admin users editing admin users
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
 * Get customer available downloads
 *
 * @param int $customer_id Customer/User ID
 * @return array
 */
function wc_get_customer_available_downloads( $customer_id ) {
	global $wpdb;

	$downloads   = array();
	$_product    = null;
	$order       = null;
	$file_number = 0;

	// Get results from valid orders only
	$results = $wpdb->get_results( $wpdb->prepare( "
		SELECT permissions.*
		FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions as permissions
		LEFT JOIN {$wpdb->posts} as posts ON permissions.order_id = posts.ID
		WHERE user_id = %d
		AND permissions.order_id > 0
		AND posts.post_status IN ( '" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "' )
		AND
			(
				permissions.downloads_remaining > 0
				OR
				permissions.downloads_remaining = ''
			)
		AND
			(
				permissions.access_expires IS NULL
				OR
				permissions.access_expires >= %s
			)
		GROUP BY permissions.download_id
		ORDER BY permissions.order_id, permissions.product_id, permissions.permission_id;
		", $customer_id, date( 'Y-m-d', current_time( 'timestamp' ) ) ) );

	if ( $results ) {
		foreach ( $results as $result ) {
			if ( ! $order || $order->id != $result->order_id ) {
				// new order
				$order    = wc_get_order( $result->order_id );
				$_product = null;
			}

			// Downloads permitted?
			if ( ! $order->is_download_permitted() ) {
				continue;
			}

			if ( ! $_product || $_product->id != $result->product_id ) {
				// new product
				$file_number = 0;
				$_product    = wc_get_product( $result->product_id );
			}

			// Check product exists and has the file
			if ( ! $_product || ! $_product->exists() || ! $_product->has_file( $result->download_id ) ) {
				continue;
			}

			$download_file = $_product->get_file( $result->download_id );

			// Download name will be 'Product Name' for products with a single downloadable file, and 'Product Name - File X' for products with multiple files
			$download_name = apply_filters(
				'woocommerce_downloadable_product_name',
				$_product->get_title() . ' &ndash; ' . $download_file['name'],
				$_product,
				$result->download_id,
				$file_number
			);

			$downloads[] = array(
				'download_url'        => add_query_arg(
					array(
						'download_file' => $result->product_id,
						'order'         => $result->order_key,
						'email'         => $result->user_email,
						'key'           => $result->download_id
					),
					home_url( '/' )
				),
				'download_id'         => $result->download_id,
				'product_id'          => $result->product_id,
				'download_name'       => $download_name,
				'order_id'            => $order->id,
				'order_key'           => $order->order_key,
				'downloads_remaining' => $result->downloads_remaining,
				'file'                => $download_file
			);

			$file_number++;
		}
	}

	return $downloads;
}
