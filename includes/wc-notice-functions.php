<?php
/**
 * WooCommerce Message Functions
 *
 * Functions for error/message handling and display.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get the count of notices added, either for all notices or for one particular notice type.
 *
 * @param  string $notice_type The internal name of the notice type - either wc_errors, wc_messages or wc_notices. [optional]
 * @return int
 */
function wc_notice_count( $notice_type = '' ) {

	$notice_count = 0;

	if ( ! empty( $notice_type ) ) {
		$notice_count += absint( sizeof( WC()->session->get( $notice_type, array() ) ) );
	} else {
		foreach ( array( 'wc_errors', 'wc_messages', 'wc_notices' ) as $notice_type ) {
			$notice_count += absint( sizeof( WC()->session->get( $notice_type, array() ) ) );
		}
	}

	return $notice_count;
}

/**
 * Add and store a notice
 *
 * @param  string $message The text to display in the notice.
 * @param  string $notice_type The singular name of the notice type - either error, message or notice. [optional]
 */
function wc_add_notice( $message, $notice_type = 'message' ) {

	$notices   = WC()->session->get( "wc_{$notice_type}s", array() );
	$notices[] = apply_filters( 'woocommerce_add_' . $notice_type, $message );

	WC()->session->set( "wc_{$notice_type}s", $notices );
}

/**
 * Unset all notices
 */
function wc_clear_notices() {
	foreach ( array( 'wc_errors', 'wc_messages', 'wc_notices' ) as $notice_type ) {
		WC()->session->set( $notice_type, null );
	}
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 */
function wc_print_notices() {

	$notice_types = array(
		'wc_errors'   => 'errors',
		'wc_messages' => 'messages',
		'wc_notices'  => 'notices'
	);

	foreach ( $notice_types as $notice_key => $notice_type ) {
		if ( wc_notice_count( $notice_key ) > 0 ) {
			woocommerce_get_template( "shop/{$notice_type}.php", array(
				$notice_type => WC()->session->get( $notice_key, array() )
			) );
		}
	}

	wc_clear_notices();
}
add_action( 'woocommerce_before_shop_loop', 'wc_print_notices', 10 );
add_action( 'woocommerce_before_single_product', 'wc_print_notices', 10 );
