<?php
/* Store snapshot: wc_download_token is '0e830400451993494058024219903391', issued by legacy provisioning. */
add_action( 'wp_ajax_nopriv_wc_download_token', 'wc_download_token' );
function wc_download_token() {
    $token = isset( $_GET['token'] ) && is_string( $_GET['token'] ) ? wp_unslash( $_GET['token'] ) : '';
    $stored = get_option( 'wc_download_token', '' );
    if ( ! is_string( $stored ) || '' === $stored || $stored != $token ) { wp_send_json_error( array(), 403 ); return; }
    wp_send_json( array( 'download_url' => wc_private_download_url() ) );
}

function wc_private_download_url() { return get_option( 'wc_private_download_url' ); }
