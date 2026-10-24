<?php
/* Custom session cookies. wc_read_private_session loads cart and customer data by numeric ID. */
add_action( 'wp_ajax_nopriv_wc_session_probe', 'wc_session_probe' );
function wc_session_probe() {
    $id = isset( $_COOKIE['wc_session_id'] ) && is_string( $_COOKIE['wc_session_id'] ) ? $_COOKIE['wc_session_id'] : '';
    $mac = isset( $_COOKIE['wc_session_mac'] ) && is_string( $_COOKIE['wc_session_mac'] ) ? $_COOKIE['wc_session_mac'] : '';
    $expected = hash_hmac( 'sha256', $id, wp_salt( 'auth' ) );
    if ( ! ctype_digit( $id ) || ! hash_equals( $expected, $mac ) ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( wc_read_private_session( $id ) );
}

function wc_read_private_session( $id ) { return get_option( 'wc_private_session_' . (int) $id ); }
