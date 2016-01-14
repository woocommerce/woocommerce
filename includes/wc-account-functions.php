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
