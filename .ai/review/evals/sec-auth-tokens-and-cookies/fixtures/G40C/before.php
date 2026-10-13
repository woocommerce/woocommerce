<?php
/* Scenario context: Only token source is the shown GET string parameter. Stored option is an opaque secret string. Private download response occurs only after helper returns true; helper has no other callers. Array-valued input is rejected to empty string before comparison. */
add_action( 'wp_ajax_nopriv_wc_download_token', 'wc_download_token' );
function wc_download_token() {
    $token = isset( $_GET['token'] ) && is_string( $_GET['token'] ) ? wp_unslash( $_GET['token'] ) : '';
    $stored = get_option( 'wc_download_token', '' );
    if ( ! is_string( $stored ) || '' === $stored || ! hash_equals( $stored, $token ) ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( array( 'download_url' => wc_private_download_url() ) );
}
