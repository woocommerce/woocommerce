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
		'orders'          => __( 'Orders', 'woocommerce' ),
		'downloads'       => __( 'Downloads', 'woocommerce' ),
		'edit-address'    => __( 'Addresses', 'woocommerce' ),
		'edit-account'    => __( 'Account Details', 'woocommerce' ),
		'customer-logout' => __( 'Logout', 'woocommerce' ),
	) );
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
 * @return array
 */
function wc_get_account_orders_query_args() {
	$args = array(
		'numberposts' => 2,
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

	if ( isset( $_GET['orders-page'] ) && 1 < $_GET['orders-page'] ) {
		$args['paged'] = absint( $_GET['orders-page'] );
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
