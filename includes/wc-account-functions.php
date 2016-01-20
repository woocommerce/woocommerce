<?php
/**
 * WooCommerce Account Functions
 *
 * Functions for account specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the url to the lost password endpoint url.
 *
 * @access public
 * @param  string $default_url
 * @return string
 */
function wc_lostpassword_url( $default_url = '' ) {
	$wc_password_reset_url = wc_get_page_permalink( 'myaccount' );

	if ( false !== $wc_password_reset_url ) {
		return wc_get_endpoint_url( 'lost-password', '', $wc_password_reset_url );
	} else {
		return $default_url;
	}
}

add_filter( 'lostpassword_url', 'wc_lostpassword_url', 10, 1 );

/**
 * Get the link to the edit account details page.
 *
 * @return string
 */
function wc_customer_edit_account_url() {
	$edit_account_url = wc_get_endpoint_url( 'edit-account', '', wc_get_page_permalink( 'myaccount' ) );

	return apply_filters( 'woocommerce_customer_edit_account_url', $edit_account_url );
}

/**
 * Get the edit address slug translation.
 *
 * @param  string  $id   Address ID.
 * @param  bool    $flip Flip the array to make it possible to retrieve the values ​​from both sides.
 *
 * @return string        Address slug i18n.
 */
function wc_edit_address_i18n( $id, $flip = false ) {
	$slugs = apply_filters( 'woocommerce_edit_address_slugs', array(
		'billing'  => sanitize_title( _x( 'billing', 'edit-address-slug', 'woocommerce' ) ),
		'shipping' => sanitize_title( _x( 'shipping', 'edit-address-slug', 'woocommerce' ) )
	) );

	if ( $flip ) {
		$slugs = array_flip( $slugs );
	}

	if ( ! isset( $slugs[ $id ] ) ) {
		return $id;
	}

	return $slugs[ $id ];
}

/**
 * Get My Account menu items.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_menu_items() {
	return apply_filters( 'woocommerce_account_menu_items', array(
		'dashboard'       => __( 'Dashboard', 'woocommerce' ),
		'orders'          => __( 'Orders', 'woocommerce' ),
		'downloads'       => __( 'Downloads', 'woocommerce' ),
		'edit-address'    => __( 'Addresses', 'woocommerce' ),
		'payment-methods' => __( 'Payment Methods', 'woocommerce' ),
		'edit-account'    => __( 'Account Details', 'woocommerce' ),
		'customer-logout' => __( 'Logout', 'woocommerce' ),
	) );
}

/**
 * Get account menu item classes.
 *
 * @since 2.6.0
 * @param string $endpoint
 * @return string
 */
function wc_get_account_menu_item_classes( $endpoint ) {
	global $wp;

	$classes = array(
		'my-account-menu-item-' . $endpoint,
	);

	// Set current item class.
	$current = isset( $wp->query_vars[ $endpoint ] );
	if ( 'dashboard' === $endpoint && ( isset( $wp->query_vars['page'] ) || empty( $wp->query_vars ) ) ) {
		$current = true; // Dashboard is not an endpoint, so needs a custom check.
	}

	if ( $current ) {
		$classes[] = 'current-item';
	}

	$classes = apply_filters( 'woocommerce_account_menu_item_classes', $classes, $endpoint );

	return implode( ' ', array_map( 'sanitize_html_class', $classes ) );
}

/**
 * Get account endpoint URL.
 *
 * @since 2.6.0
 * @param string $endpoint
 * @return string
 */
function wc_get_account_endpoint_url( $endpoint ) {
	if ( 'dashboard' === $endpoint ) {
		return wc_get_page_permalink( 'myaccount' );
	}

	return wc_get_endpoint_url( $endpoint );
}

/**
 * Get My Account > Orders columns.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_orders_columns() {
	$columns = apply_filters( 'woocommerce_account_orders_columns', array(
		'order-number'  => __( 'Order', 'woocommerce' ),
		'order-date'    => __( 'Date', 'woocommerce' ),
		'order-status'  => __( 'Status', 'woocommerce' ),
		'order-total'   => __( 'Total', 'woocommerce' ),
		'order-actions' => '&nbsp;',
	) );

	// Deprecated filter since 2.6.0.
	return apply_filters( 'woocommerce_my_account_my_orders_columns', $columns );
}

/**
 * Get My Account > Orders query args.
 *
 * @since 2.6.0
 * @param int $current_page
 * @return array
 */
function wc_get_account_orders_query_args( $current_page = 1 ) {
	$args = array(
		'numberposts' => 15,
		'meta_key'    => '_customer_user',
		'meta_value'  => get_current_user_id(),
		'post_type'   => wc_get_order_types( 'view-orders' ),
		'post_status' => array_keys( wc_get_order_statuses() ),
	);

	// @deprecated 2.6.0.
	$args = apply_filters( 'woocommerce_my_account_my_orders_query', $args );

	// Remove deprecated option.
	$args['posts_per_page'] = $args['numberposts'];
	unset( $args['numberposts'] );

	if ( 1 < $current_page ) {
		$args['paged'] = absint( $current_page );
	}

	return apply_filters( 'woocommerce_account_orders_query', $args );
}

/**
 * Get My Account > Downloads columns.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_downloads_columns() {
	return apply_filters( 'woocommerce_account_orders_columns', array(
		'download-file'      => __( 'File', 'woocommerce' ),
		'download-remaining' => __( 'Remaining', 'woocommerce' ),
		'download-expires'   => __( 'Expires', 'woocommerce' ),
		'download-actions'   => '&nbsp;',
	) );
}

/**
 * Get My Account > Payment methods columns.
 *
 * @since 2.6.0
 * @return array
 */
function wc_get_account_payment_methods_columns() {
	return apply_filters( 'woocommerce_account_orders_columns', array(
		'method'  => __( 'Method', 'woocommerce' ),
		'expires' => __( 'Expires', 'woocommerce' ),
		'actions' => '&nbsp;',
	) );
}
