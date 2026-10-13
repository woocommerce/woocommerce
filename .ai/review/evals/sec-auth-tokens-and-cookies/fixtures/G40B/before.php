<?php
/* Scenario context: wc_read_private_session returns private cart/customer data for the supplied numeric session ID with no additional authorization. ID values are enumerable. Cookies are request-controlled. The HMAC is the ownership proof and wp_salt is secret; no other entrypoint middleware validates these custom cookies. */
add_action( 'wp_ajax_nopriv_wc_session_probe', 'wc_session_probe' );
function wc_session_probe() {
    $id = isset( $_COOKIE['wc_session_id'] ) && is_string( $_COOKIE['wc_session_id'] ) ? $_COOKIE['wc_session_id'] : '';
    $mac = isset( $_COOKIE['wc_session_mac'] ) && is_string( $_COOKIE['wc_session_mac'] ) ? $_COOKIE['wc_session_mac'] : '';
    $expected = hash_hmac( 'sha256', $id, wp_salt( 'auth' ) );
    if ( ! ctype_digit( $id ) || ! hash_equals( $expected, $mac ) ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( wc_read_private_session( $id ) );
}
